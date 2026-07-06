<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\CourierPayoutAdminNotificationService;
use App\Service\CourierPayoutService;
use App\Service\CourierPayoutSettingsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'hodina:courier-payouts:generate',
    description: 'J5Q-C — génère les brouillons de rémunération livreur par quinzaine et peut envoyer un récap aux admins.'
)]
final class GenerateCourierPayoutsCommand extends Command
{
    public function __construct(
        private readonly CourierPayoutService $courierPayoutService,
        private readonly CourierPayoutSettingsService $settingsService,
        private readonly CourierPayoutAdminNotificationService $notificationService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('period', null, InputOption::VALUE_REQUIRED, 'Période à générer : current ou previous.', 'current')
            ->addOption('date', null, InputOption::VALUE_REQUIRED, 'Date de référence YYYY-MM-DD. Par défaut : aujourd’hui dans le timezone choisi.')
            ->addOption('timezone', null, InputOption::VALUE_REQUIRED, 'Fuseau métier utilisé pour déterminer la période.', 'Indian/Mayotte')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simule la génération sans écrire en base et sans envoyer d’e-mail.')
            ->addOption('auto-due', null, InputOption::VALUE_NONE, 'Mode cron : génère seulement le 15 ou le dernier jour du mois dans le timezone choisi.')
            ->addOption('notify-admins', null, InputOption::VALUE_NONE, 'Envoie un récapitulatif e-mail aux administrateurs après une génération réelle.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $timezoneName = trim((string) $input->getOption('timezone')) ?: 'Indian/Mayotte';

        try {
            $timezone = new \DateTimeZone($timezoneName);
        } catch (\Throwable) {
            $output->writeln(sprintf('<error>Timezone invalide : %s</error>', $timezoneName));

            return Command::FAILURE;
        }

        $referenceDate = $this->resolveReferenceDate($input, $timezone);
        if (!$referenceDate instanceof \DateTimeImmutable) {
            $output->writeln('<error>Date invalide. Format attendu : YYYY-MM-DD.</error>');

            return Command::FAILURE;
        }

        $dryRun = (bool) $input->getOption('dry-run');
        $autoDue = (bool) $input->getOption('auto-due');
        $notifyAdmins = (bool) $input->getOption('notify-admins');
        $periodOption = mb_strtolower(trim((string) $input->getOption('period')) ?: 'current');

        if (!$this->settingsService->isCourierPayoutEnabled()) {
            $output->writeln('<comment>Paiements livreurs désactivés dans Réglages > Paiements. Aucune génération lancée.</comment>');

            return Command::SUCCESS;
        }

        if (!$this->settingsService->isSemiMonthlyFrequency()) {
            $output->writeln(sprintf(
                '<error>Fréquence paiements livreurs non prise en charge : %s.</error>',
                $this->settingsService->getFrequency()
            ));
            $output->writeln('Fréquence actuellement supportée : semi_monthly.');

            return Command::FAILURE;
        }

        if ($autoDue && !$this->settingsService->isCronGenerationEnabled()) {
            $output->writeln('<comment>Génération cron des paiements livreurs désactivée dans Réglages > Paiements.</comment>');

            return Command::SUCCESS;
        }

        if ($notifyAdmins && !$this->settingsService->isAdminRecapEnabled()) {
            $notifyAdmins = false;
            $output->writeln('<comment>Récap admin paiements livreurs désactivé dans Réglages > Paiements. Aucun e-mail ne sera envoyé.</comment>');
            $output->writeln('');
        }

        if (!in_array($periodOption, ['current', 'previous'], true)) {
            $output->writeln('<error>Option --period invalide. Valeurs acceptées : current, previous.</error>');

            return Command::FAILURE;
        }

        if ($autoDue && !$this->isAutoDueDay($referenceDate)) {
            $output->writeln(sprintf(
                '<info>Aucune génération à lancer le %s.</info>',
                $referenceDate->format('d/m/Y')
            ));
            $output->writeln('Mode --auto-due : seuls le 15 et le dernier jour du mois déclenchent une génération.');

            return Command::SUCCESS;
        }

        $period = $periodOption === 'previous'
            ? $this->courierPayoutService->getPreviousPeriod($referenceDate)
            : $this->courierPayoutService->getCurrentPeriod($referenceDate);

        $output->writeln(sprintf(
            '<info>J5Q-C génération rémunérations livreurs — %s</info>',
            $dryRun ? 'SIMULATION' : 'APPLICATION'
        ));
        $output->writeln(sprintf('Date de référence : %s (%s)', $referenceDate->format('d/m/Y'), $timezoneName));
        $output->writeln(sprintf('Période : %s', $period['label']));
        $output->writeln(sprintf('Paiement prévu : %s', $period['due']->format('d/m/Y')));
        $output->writeln('');

        $result = $dryRun
            ? $this->courierPayoutService->previewGenerationForPeriod($period)
            : $this->courierPayoutService->generateForPeriod($period);

        $this->displayGenerationResult($output, $result, $dryRun);

        if ($dryRun && $notifyAdmins) {
            $output->writeln('');
            $output->writeln('<comment>Notification non envoyée : --dry-run ne modifie pas la base et n’envoie aucun e-mail.</comment>');
        }

        if (!$dryRun && $notifyAdmins) {
            $output->writeln('');
            $notificationResult = $this->notificationService->notifyAdmins($period, $result, $referenceDate, $autoDue);
            $output->writeln(sprintf(
                'Récap admin : %d envoyé(s), %d échec(s), %d destinataire(s) ignoré(s).',
                $notificationResult['sent'],
                $notificationResult['failed'],
                $notificationResult['skipped']
            ));

            foreach ($notificationResult['warnings'] as $warning) {
                $output->writeln('<comment>! '.$warning.'</comment>');
            }
        }

        return Command::SUCCESS;
    }

    private function resolveReferenceDate(InputInterface $input, \DateTimeZone $timezone): ?\DateTimeImmutable
    {
        $dateOption = trim((string) $input->getOption('date'));

        try {
            if ($dateOption !== '') {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOption)) {
                    return null;
                }

                return (new \DateTimeImmutable($dateOption.' 12:00:00', $timezone))->setTime(12, 0, 0);
            }

            return new \DateTimeImmutable('now', $timezone);
        } catch (\Throwable) {
            return null;
        }
    }

    private function isAutoDueDay(\DateTimeImmutable $referenceDate): bool
    {
        $day = (int) $referenceDate->format('d');
        $lastDay = (int) $referenceDate->modify('last day of this month')->format('d');

        return $day === 15 || $day === $lastDay;
    }

    /**
     * @param array{created: int, updated: int, skippedOrders: int, lines: int, payouts: array} $result
     */
    private function displayGenerationResult(OutputInterface $output, array $result, bool $dryRun): void
    {
        $output->writeln(sprintf('Paiements %s : %d', $dryRun ? 'à créer' : 'créés', $result['created']));
        $output->writeln(sprintf('Paiements %s : %d', $dryRun ? 'à compléter' : 'complétés', $result['updated']));
        $output->writeln(sprintf('Lignes %s : %d', $dryRun ? 'à rattacher' : 'rattachées', $result['lines']));
        $output->writeln(sprintf('Commandes ignorées : %d', $result['skippedOrders']));

        if ($result['payouts'] === []) {
            $output->writeln('');
            $output->writeln('<comment>Aucun nouveau brouillon à générer pour cette période.</comment>');

            return;
        }

        $output->writeln('');
        $output->writeln('Détail :');

        foreach ($result['payouts'] as $payout) {
            if (is_array($payout)) {
                $output->writeln(sprintf(
                    '- %s : %s € — %d commande(s)',
                    $payout['courierLabel'] ?? 'Livreur',
                    number_format((float) ($payout['totalAmount'] ?? 0), 2, ',', ' '),
                    (int) ($payout['ordersCount'] ?? 0)
                ));
                continue;
            }

            if (method_exists($payout, 'getCourierLabel')) {
                $output->writeln(sprintf(
                    '- %s : %s € — %d commande(s)',
                    $payout->getCourierLabel(),
                    number_format((float) $payout->getTotalAmount(), 2, ',', ' '),
                    $payout->getOrdersCount()
                ));
            }
        }
    }
}

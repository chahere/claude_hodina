<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Customer;
use App\Entity\CustomerSignup;
use App\Service\PhoneNumberNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'hodina:customers:normalize-phones',
    description: 'Normalise les anciens numéros clients locaux connus au format international simplifié.'
)]
final class NormalizeCustomerPhonesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PhoneNumberNormalizer $normalizer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Applique les changements. Sans cette option, la commande reste en simulation.')
            ->addOption('strict', null, InputOption::VALUE_NONE, 'Retourne une erreur si un numéro non vide ne peut pas être normalisé.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apply = (bool) $input->getOption('apply');
        $strict = (bool) $input->getOption('strict');

        $output->writeln(sprintf('<info>Normalisation téléphones clients — mode %s</info>', $apply ? 'APPLICATION' : 'SIMULATION'));
        $output->writeln('Périmètre rattrapage : Mayotte 0639/0269 et métropole 01..07/09 connus en base. Les nouveaux formulaires utilisent un indicatif explicite.');
        $output->writeln('');

        $customerResult = $this->normalizeCustomers($output, $apply);
        $signupResult = $this->normalizeCustomerSignups($output, $apply);

        if ($apply) {
            $this->entityManager->flush();
            $output->writeln('');
            $output->writeln('<info>Changements appliqués.</info>');
        } else {
            $this->entityManager->clear();
            $output->writeln('');
            $output->writeln('<comment>Simulation uniquement. Relance avec --apply pour enregistrer.</comment>');
        }

        $changed = $customerResult['changed'] + $signupResult['changed'];
        $invalid = $customerResult['invalid'] + $signupResult['invalid'];

        $output->writeln(sprintf('Résumé : %d numéro(s) modifié(s), %d numéro(s) non normalisable(s).', $changed, $invalid));

        return $strict && $invalid > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /** @return array{changed:int, invalid:int} */
    private function normalizeCustomers(OutputInterface $output, bool $apply): array
    {
        /** @var list<Customer> $customers */
        $customers = $this->entityManager->getRepository(Customer::class)->findBy([], ['id' => 'ASC']);

        $changed = 0;
        $invalid = 0;

        foreach ($customers as $customer) {
            $before = trim((string) $customer->getPhone());
            $after = $this->normalizer->normalizeLegacy($before);

            if ($before !== '' && $after === '') {
                ++$invalid;
                $output->writeln(sprintf('<comment>Customer #%s : numéro ignoré "%s"</comment>', $customer->getId() ?? '?', $before));
                continue;
            }

            if ($after !== '' && $after !== $before) {
                ++$changed;
                $output->writeln(sprintf('Customer #%s : %s -> %s', $customer->getId() ?? '?', $before, $after));

                if ($apply) {
                    $customer->setPhone($after);
                }
            }
        }

        return ['changed' => $changed, 'invalid' => $invalid];
    }

    /** @return array{changed:int, invalid:int} */
    private function normalizeCustomerSignups(OutputInterface $output, bool $apply): array
    {
        /** @var list<CustomerSignup> $signups */
        $signups = $this->entityManager->getRepository(CustomerSignup::class)->findBy([], ['id' => 'ASC']);

        $changed = 0;
        $invalid = 0;

        foreach ($signups as $signup) {
            $before = trim((string) $signup->getPhone());
            $after = $this->normalizer->normalizeLegacy($before);

            if ($before !== '' && $after === '') {
                ++$invalid;
                $output->writeln(sprintf('<comment>CustomerSignup #%s : numéro ignoré "%s"</comment>', $signup->getId() ?? '?', $before));
                continue;
            }

            if ($after !== '' && $after !== $before) {
                ++$changed;
                $output->writeln(sprintf('CustomerSignup #%s : %s -> %s', $signup->getId() ?? '?', $before, $after));

                if ($apply) {
                    $signup->setPhone($after);
                }
            }
        }

        return ['changed' => $changed, 'invalid' => $invalid];
    }
}

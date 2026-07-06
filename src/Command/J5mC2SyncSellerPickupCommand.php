<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Address;
use App\Entity\Seller;
use App\Service\SellerPickupLogisticsSynchronizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'hodina:j5m:c2:sync-seller-pickup',
    description: 'J5M-C2-bis — synchronise les adresses de retrait vendeur et les communes logistiques.'
)]
final class J5mC2SyncSellerPickupCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SellerPickupLogisticsSynchronizer $synchronizer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Applique les changements. Sans cette option, la commande reste en simulation.')
            ->addOption('seller-id', null, InputOption::VALUE_REQUIRED, 'Limiter la synchronisation à un vendeur précis.')
            ->addOption('create-missing-pickup-address', null, InputOption::VALUE_NONE, 'Crée une adresse de retrait minimale depuis Seller.deliveryCommune si le vendeur a déjà un compte client mais aucune adresse.')
            ->addOption('strict', null, InputOption::VALUE_NONE, 'Retourne une erreur si une incohérence est détectée.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apply = (bool) $input->getOption('apply');
        $strict = (bool) $input->getOption('strict');
        $createMissingPickupAddress = (bool) $input->getOption('create-missing-pickup-address');
        $sellerId = $input->getOption('seller-id');

        $criteria = [];
        if ($sellerId !== null && trim((string) $sellerId) !== '') {
            $criteria['id'] = (int) $sellerId;
        }

        /** @var list<Seller> $sellers */
        $sellers = $this->entityManager->getRepository(Seller::class)->findBy($criteria, ['id' => 'ASC']);

        $output->writeln(sprintf(
            '<info>J5M-C2-bis synchronisation vendeurs — mode %s</info>',
            $apply ? 'APPLICATION' : 'SIMULATION'
        ));
        $output->writeln(sprintf('Vendeurs analysés : %d', count($sellers)));
        $output->writeln('');

        $changedCount = 0;
        $errorCount = 0;
        $warningCount = 0;
        $createdPickupAddressCount = 0;

        foreach ($sellers as $seller) {
            $createdAddress = null;

            if ($createMissingPickupAddress && !$seller->getPickupAddress() instanceof Address && $seller->getCustomerAccount() !== null) {
                $createdAddress = $this->synchronizer->createPickupAddressFromExistingLogisticsCommune($seller);

                if ($createdAddress instanceof Address) {
                    $createdPickupAddressCount++;
                    $this->entityManager->persist($createdAddress);
                }
            }

            $result = $this->synchronizer->synchronize($seller);
            $hasChanges = $result['changed'] || $createdAddress instanceof Address;

            if ($hasChanges) {
                $changedCount++;
            }

            $errorCount += count($result['errors']);
            $warningCount += count($result['warnings']);

            $status = $result['errors'] !== [] ? '<error>ERREUR</error>' : ($hasChanges ? '<comment>MAJ</comment>' : '<info>OK</info>');
            $output->writeln(sprintf(
                '%s vendeur #%s — %s',
                $status,
                $seller->getId() ?? '?',
                $seller->getName()
            ));

            if ($createdAddress instanceof Address) {
                $output->writeln('  + adresse de retrait minimale créée depuis la commune logistique existante');
            }

            foreach ($result['infos'] as $info) {
                $output->writeln('  + ' . $info);
            }

            foreach ($result['warnings'] as $warning) {
                $output->writeln('  ! ' . $warning);
            }

            foreach ($result['errors'] as $error) {
                $output->writeln('  x ' . $error);
            }
        }

        if ($apply) {
            $this->entityManager->flush();
            $output->writeln('');
            $output->writeln('<info>Changements appliqués.</info>');
        } else {
            $this->entityManager->clear();
            $output->writeln('');
            $output->writeln('<comment>Simulation uniquement. Relance avec --apply pour enregistrer.</comment>');
        }

        $output->writeln(sprintf(
            'Résumé : %d vendeur(s) modifié(s), %d adresse(s) créée(s), %d avertissement(s), %d erreur(s).',
            $changedCount,
            $createdPickupAddressCount,
            $warningCount,
            $errorCount,
        ));

        if ($strict && $errorCount > 0) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

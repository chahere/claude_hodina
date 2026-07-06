<?php

namespace App\Command;

use App\Entity\AddressLocality;
use App\Entity\DeliveryCommune;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'hodina:address-localities:seed',
    description: 'J5AA-A — initialise les localités d’adresse connues par Hodina.'
)]
final class SeedAddressLocalitiesCommand extends Command
{
    /** @var list<array{name: string, commune: string, postalCode: string, countryCode: string, sortOrder: int}> */
    /**
     * Seed de départ des villages/localités de Mayotte.
     *
     * Cette donnée sert à aider la saisie d’adresse. Elle ne calcule jamais les frais :
     * la commune de livraison reste Address.commune, validée par le référentiel DeliveryCommune.
     *
     * @var list<array{name: string, commune: string, postalCode: string, countryCode: string, sortOrder: int}>
     */
    private const INITIAL_LOCALITIES = [
        ['name' => 'Acoua', 'commune' => 'Acoua', 'postalCode' => '97630', 'countryCode' => 'YT', 'sortOrder' => 10],
        ['name' => 'Mtsangadoua', 'commune' => 'Acoua', 'postalCode' => '97630', 'countryCode' => 'YT', 'sortOrder' => 20],
        ['name' => 'Bandraboua', 'commune' => 'Bandraboua', 'postalCode' => '97650', 'countryCode' => 'YT', 'sortOrder' => 30],
        ['name' => 'Handréma', 'commune' => 'Bandraboua', 'postalCode' => '97650', 'countryCode' => 'YT', 'sortOrder' => 40],
        ['name' => 'Mtsangamboua', 'commune' => 'Bandraboua', 'postalCode' => '97650', 'countryCode' => 'YT', 'sortOrder' => 50],
        ['name' => 'Dzoumogné', 'commune' => 'Bandraboua', 'postalCode' => '97650', 'countryCode' => 'YT', 'sortOrder' => 60],
        ['name' => 'Bouyouni', 'commune' => 'Bandraboua', 'postalCode' => '97650', 'countryCode' => 'YT', 'sortOrder' => 70],
        ['name' => 'Bandrélé', 'commune' => 'Bandrélé', 'postalCode' => '97660', 'countryCode' => 'YT', 'sortOrder' => 80],
        ['name' => 'Hamouro', 'commune' => 'Bandrélé', 'postalCode' => '97660', 'countryCode' => 'YT', 'sortOrder' => 90],
        ['name' => 'Nyambadao', 'commune' => 'Bandrélé', 'postalCode' => '97660', 'countryCode' => 'YT', 'sortOrder' => 100],
        ['name' => 'Bambo-Est', 'commune' => 'Bandrélé', 'postalCode' => '97660', 'countryCode' => 'YT', 'sortOrder' => 110],
        ['name' => 'Mtsamoudou', 'commune' => 'Bandrélé', 'postalCode' => '97660', 'countryCode' => 'YT', 'sortOrder' => 120],
        ['name' => 'Dapani', 'commune' => 'Bandrélé', 'postalCode' => '97660', 'countryCode' => 'YT', 'sortOrder' => 130],
        ['name' => 'Bouéni', 'commune' => 'Bouéni', 'postalCode' => '97620', 'countryCode' => 'YT', 'sortOrder' => 140],
        ['name' => 'Moinatrindri', 'commune' => 'Bouéni', 'postalCode' => '97620', 'countryCode' => 'YT', 'sortOrder' => 150],
        ['name' => 'Hagnoundrou', 'commune' => 'Bouéni', 'postalCode' => '97620', 'countryCode' => 'YT', 'sortOrder' => 160],
        ['name' => 'Bambo-Ouest', 'commune' => 'Bouéni', 'postalCode' => '97620', 'countryCode' => 'YT', 'sortOrder' => 170],
        ['name' => 'Mzouazia', 'commune' => 'Bouéni', 'postalCode' => '97620', 'countryCode' => 'YT', 'sortOrder' => 180],
        ['name' => 'Mbouanatsa', 'commune' => 'Bouéni', 'postalCode' => '97620', 'countryCode' => 'YT', 'sortOrder' => 190],
        ['name' => 'Majiméouni', 'commune' => 'Bouéni', 'postalCode' => '97620', 'countryCode' => 'YT', 'sortOrder' => 200],
        ['name' => 'Chiconi', 'commune' => 'Chiconi', 'postalCode' => '97670', 'countryCode' => 'YT', 'sortOrder' => 210],
        ['name' => 'Sohoa', 'commune' => 'Chiconi', 'postalCode' => '97670', 'countryCode' => 'YT', 'sortOrder' => 220],
        ['name' => 'Chirongui', 'commune' => 'Chirongui', 'postalCode' => '97620', 'countryCode' => 'YT', 'sortOrder' => 230],
        ['name' => 'Tsimkoura', 'commune' => 'Chirongui', 'postalCode' => '97620', 'countryCode' => 'YT', 'sortOrder' => 240],
        ['name' => 'Mramadoudou', 'commune' => 'Chirongui', 'postalCode' => '97620', 'countryCode' => 'YT', 'sortOrder' => 250],
        ['name' => 'Malamani', 'commune' => 'Chirongui', 'postalCode' => '97620', 'countryCode' => 'YT', 'sortOrder' => 260],
        ['name' => 'Poroani', 'commune' => 'Chirongui', 'postalCode' => '97620', 'countryCode' => 'YT', 'sortOrder' => 270],
        ['name' => 'Mréréni', 'commune' => 'Chirongui', 'postalCode' => '97620', 'countryCode' => 'YT', 'sortOrder' => 280],
        ['name' => 'Dembéni', 'commune' => 'Dembéni', 'postalCode' => '97660', 'countryCode' => 'YT', 'sortOrder' => 290],
        ['name' => 'Ongojou', 'commune' => 'Dembéni', 'postalCode' => '97660', 'countryCode' => 'YT', 'sortOrder' => 300],
        ['name' => 'Iloni', 'commune' => 'Dembéni', 'postalCode' => '97660', 'countryCode' => 'YT', 'sortOrder' => 310],
        ['name' => 'Hajangoua', 'commune' => 'Dembéni', 'postalCode' => '97660', 'countryCode' => 'YT', 'sortOrder' => 320],
        ['name' => 'Tsararano', 'commune' => 'Dembéni', 'postalCode' => '97660', 'countryCode' => 'YT', 'sortOrder' => 330],
        ['name' => 'Dzaoudzi', 'commune' => 'Dzaoudzi', 'postalCode' => '97615', 'countryCode' => 'YT', 'sortOrder' => 340],
        ['name' => 'Labattoir', 'commune' => 'Labattoir', 'postalCode' => '97615', 'countryCode' => 'YT', 'sortOrder' => 350],
        ['name' => 'Kani-Kéli', 'commune' => 'Kani-Kéli', 'postalCode' => '97625', 'countryCode' => 'YT', 'sortOrder' => 360],
        ['name' => 'Kani-Bé', 'commune' => 'Kani-Kéli', 'postalCode' => '97625', 'countryCode' => 'YT', 'sortOrder' => 370],
        ['name' => 'Choungui', 'commune' => 'Kani-Kéli', 'postalCode' => '97625', 'countryCode' => 'YT', 'sortOrder' => 380],
        ['name' => 'Mronabéja', 'commune' => 'Kani-Kéli', 'postalCode' => '97625', 'countryCode' => 'YT', 'sortOrder' => 390],
        ['name' => 'Passi-Kéli', 'commune' => 'Kani-Kéli', 'postalCode' => '97625', 'countryCode' => 'YT', 'sortOrder' => 400],
        ['name' => 'Mbouini', 'commune' => 'Kani-Kéli', 'postalCode' => '97625', 'countryCode' => 'YT', 'sortOrder' => 410],
        ['name' => 'Koungou', 'commune' => 'Koungou', 'postalCode' => '97600', 'countryCode' => 'YT', 'sortOrder' => 420],
        ['name' => 'Longoni', 'commune' => 'Koungou', 'postalCode' => '97600', 'countryCode' => 'YT', 'sortOrder' => 430],
        ['name' => 'Kangani', 'commune' => 'Koungou', 'postalCode' => '97600', 'countryCode' => 'YT', 'sortOrder' => 440],
        ['name' => 'Trévani', 'commune' => 'Koungou', 'postalCode' => '97600', 'countryCode' => 'YT', 'sortOrder' => 450],
        ['name' => 'Majicavo-Koropa', 'commune' => 'Koungou', 'postalCode' => '97600', 'countryCode' => 'YT', 'sortOrder' => 460],
        ['name' => 'Majicavo-Lamir', 'commune' => 'Koungou', 'postalCode' => '97600', 'countryCode' => 'YT', 'sortOrder' => 470],
        ['name' => 'Mamoudzou', 'commune' => 'Mamoudzou', 'postalCode' => '97600', 'countryCode' => 'YT', 'sortOrder' => 480],
        ['name' => 'Mtsapéré', 'commune' => 'Mamoudzou', 'postalCode' => '97600', 'countryCode' => 'YT', 'sortOrder' => 490],
        ['name' => 'Kawéni', 'commune' => 'Mamoudzou', 'postalCode' => '97600', 'countryCode' => 'YT', 'sortOrder' => 500],
        ['name' => 'Passamaïnty', 'commune' => 'Mamoudzou', 'postalCode' => '97600', 'countryCode' => 'YT', 'sortOrder' => 510],
        ['name' => 'Tsoundzou I', 'commune' => 'Mamoudzou', 'postalCode' => '97600', 'countryCode' => 'YT', 'sortOrder' => 520],
        ['name' => 'Vahibé', 'commune' => 'Mamoudzou', 'postalCode' => '97600', 'countryCode' => 'YT', 'sortOrder' => 530],
        ['name' => 'Tsoundzou II', 'commune' => 'Mamoudzou', 'postalCode' => '97600', 'countryCode' => 'YT', 'sortOrder' => 540],
        ['name' => 'Kavani', 'commune' => 'Mamoudzou', 'postalCode' => '97600', 'countryCode' => 'YT', 'sortOrder' => 550],
        ['name' => 'Mtsamboro', 'commune' => 'Mtsamboro', 'postalCode' => '97630', 'countryCode' => 'YT', 'sortOrder' => 560],
        ['name' => 'Hamjago', 'commune' => 'Mtsamboro', 'postalCode' => '97630', 'countryCode' => 'YT', 'sortOrder' => 570],
        ['name' => 'Mtsahara', 'commune' => 'Mtsamboro', 'postalCode' => '97630', 'countryCode' => 'YT', 'sortOrder' => 580],
        ['name' => 'M\'Tsangamouji', 'commune' => 'M\'Tsangamouji', 'postalCode' => '97650', 'countryCode' => 'YT', 'sortOrder' => 590],
        ['name' => 'Chembenyouba', 'commune' => 'M\'Tsangamouji', 'postalCode' => '97650', 'countryCode' => 'YT', 'sortOrder' => 600],
        ['name' => 'Mliha', 'commune' => 'M\'Tsangamouji', 'postalCode' => '97650', 'countryCode' => 'YT', 'sortOrder' => 610],
        ['name' => 'Ouangani', 'commune' => 'Ouangani', 'postalCode' => '97670', 'countryCode' => 'YT', 'sortOrder' => 620],
        ['name' => 'Barakani', 'commune' => 'Ouangani', 'postalCode' => '97670', 'countryCode' => 'YT', 'sortOrder' => 630],
        ['name' => 'Coconi', 'commune' => 'Ouangani', 'postalCode' => '97670', 'countryCode' => 'YT', 'sortOrder' => 640],
        ['name' => 'Kahani', 'commune' => 'Ouangani', 'postalCode' => '97670', 'countryCode' => 'YT', 'sortOrder' => 650],
        ['name' => 'Pamandzi', 'commune' => 'Pamandzi', 'postalCode' => '97615', 'countryCode' => 'YT', 'sortOrder' => 660],
        ['name' => 'Sada', 'commune' => 'Sada', 'postalCode' => '97640', 'countryCode' => 'YT', 'sortOrder' => 670],
        ['name' => 'Mangajou', 'commune' => 'Sada', 'postalCode' => '97640', 'countryCode' => 'YT', 'sortOrder' => 680],
        ['name' => 'Tsingoni', 'commune' => 'Tsingoni', 'postalCode' => '97680', 'countryCode' => 'YT', 'sortOrder' => 690],
        ['name' => 'Mroalé', 'commune' => 'Tsingoni', 'postalCode' => '97680', 'countryCode' => 'YT', 'sortOrder' => 700],
        ['name' => 'Combani', 'commune' => 'Tsingoni', 'postalCode' => '97680', 'countryCode' => 'YT', 'sortOrder' => 710],
        ['name' => 'Miréréni', 'commune' => 'Tsingoni', 'postalCode' => '97680', 'countryCode' => 'YT', 'sortOrder' => 720],
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Applique les changements. Sans cette option, la commande reste en simulation.');
    }

    private function findDeliveryCommune(string $name): ?DeliveryCommune
    {
        $expected = AddressLocality::normalizeName($name);
        if ($expected === '') {
            return null;
        }

        $communes = $this->entityManager->getRepository(DeliveryCommune::class)->findBy([
            'isActive' => true,
            'isLogisticsPoint' => true,
        ]);

        foreach ($communes as $commune) {
            if (!$commune instanceof DeliveryCommune) {
                continue;
            }

            $normalizedName = AddressLocality::normalizeName($commune->getName());
            $normalizedSlug = AddressLocality::normalizeName((string) $commune->getSlug());

            if ($normalizedName === $expected || ($normalizedSlug !== '' && $normalizedSlug === $expected)) {
                return $commune;
            }
        }

        return null;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apply = (bool) $input->getOption('apply');
        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $errors = 0;

        $output->writeln('');
        $output->writeln(sprintf('<info>J5AA-A seed localités d’adresse — mode %s</info>', $apply ? 'APPLICATION' : 'SIMULATION'));
        $output->writeln('');

        foreach (self::INITIAL_LOCALITIES as $row) {
            $deliveryCommune = $this->findDeliveryCommune((string) $row['commune']);

            if (!$deliveryCommune instanceof DeliveryCommune || !$deliveryCommune->isActive() || !$deliveryCommune->isLogisticsPoint()) {
                $errors++;
                $output->writeln(sprintf(
                    '<error>ERREUR</error> localité %s : commune livrée active introuvable (%s).',
                    $row['name'],
                    $row['commune']
                ));
                continue;
            }

            $normalizedName = AddressLocality::normalizeName($row['name']);
            $existing = $this->entityManager->getRepository(AddressLocality::class)->findOneBy([
                'normalizedName' => $normalizedName,
                'deliveryCommune' => $deliveryCommune,
            ]);

            if (!$existing instanceof AddressLocality) {
                $locality = (new AddressLocality())
                    ->setName($row['name'])
                    ->setDeliveryCommune($deliveryCommune)
                    ->setPostalCode($row['postalCode'])
                    ->setCountryCode($row['countryCode'])
                    ->setSortOrder($row['sortOrder'])
                    ->setIsActive(true);

                $this->entityManager->persist($locality);
                $created++;
                $output->writeln(sprintf('<comment>CREATION</comment> %s — %s', $row['name'], $row['commune']));
                continue;
            }

            $changed = false;

            if ($existing->getName() !== $row['name']) {
                $existing->setName($row['name']);
                $changed = true;
            }

            if ((string) $existing->getPostalCode() !== $row['postalCode']) {
                $existing->setPostalCode($row['postalCode']);
                $changed = true;
            }

            if ((string) $existing->getCountryCode() !== $row['countryCode']) {
                $existing->setCountryCode($row['countryCode']);
                $changed = true;
            }

            if ($existing->getSortOrder() !== $row['sortOrder']) {
                $existing->setSortOrder($row['sortOrder']);
                $changed = true;
            }

            if (!$existing->isActive()) {
                $existing->setIsActive(true);
                $changed = true;
            }

            if ($changed) {
                $updated++;
                $output->writeln(sprintf('<comment>MAJ</comment> %s — %s', $row['name'], $row['commune']));
            } else {
                $unchanged++;
                $output->writeln(sprintf('<info>OK</info> %s — %s', $row['name'], $row['commune']));
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
            'Résumé : %d création(s), %d mise(s) à jour, %d inchangée(s), %d erreur(s).',
            $created,
            $updated,
            $unchanged,
            $errors
        ));

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}

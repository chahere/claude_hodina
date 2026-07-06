<?php

namespace App\Command;

use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée ou met à jour un utilisateur admin Hodina'
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email admin')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Mot de passe admin')
            ->addOption('phone', null, InputOption::VALUE_REQUIRED, 'Téléphone admin', '0600000000')
            ->addOption('first-name', null, InputOption::VALUE_REQUIRED, 'Prénom admin', 'Admin')
            ->addOption('last-name', null, InputOption::VALUE_REQUIRED, 'Nom admin', 'Hodina');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = strtolower(trim((string) $input->getArgument('email')));
        $plainPassword = (string) ($input->getOption('password') ?: 'HodinaAdmin2026!');
        $phone = (string) $input->getOption('phone');
        $firstName = (string) $input->getOption('first-name');
        $lastName = (string) $input->getOption('last-name');

        $repo = $this->em->getRepository(Customer::class);

        /** @var Customer|null $customer */
        $customer = $repo->findOneBy(['email' => $email]);

        if (!$customer) {
            $customer = new Customer();

            if (method_exists($customer, 'setEmail')) {
                $customer->setEmail($email);
            }

            $output->writeln('<info>Nouvel utilisateur admin créé.</info>');
        } else {
            $output->writeln('<comment>Utilisateur existant trouvé, mise à jour admin.</comment>');
        }

        if (method_exists($customer, 'setFirstName')) {
            $customer->setFirstName($firstName);
        }

        if (method_exists($customer, 'setLastName')) {
            $customer->setLastName($lastName);
        }

        if (method_exists($customer, 'setPhone')) {
            $customer->setPhone($phone);
        }

        if (method_exists($customer, 'setRoles')) {
            $customer->setRoles(['ROLE_ADMIN', 'ROLE_CUSTOMER']);
        }

        if (method_exists($customer, 'setPassword')) {
            $hashedPassword = $this->passwordHasher->hashPassword($customer, $plainPassword);
            $customer->setPassword($hashedPassword);
        }

        $this->em->persist($customer);
        $this->em->flush();

        $output->writeln('');
        $output->writeln('<info>Admin prêt.</info>');
        $output->writeln('Email : ' . $email);
        $output->writeln('Téléphone : ' . $phone);
        $output->writeln('Mot de passe : ' . $plainPassword);

        return Command::SUCCESS;
    }
}
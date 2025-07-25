<?php


namespace App\Command;


use App\Entity\User;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Console\Command\Command;

use Symfony\Component\Console\Input\InputInterface;

use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Style\SymfonyStyle;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;




class CreateAdminUserCommand extends Command

{

    protected static $defaultName = 'app:create-admin-user';

    private $entityManager;

    private $passwordHasher;


    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)

    {

        parent::__construct();

        $this->entityManager = $entityManager;

        $this->passwordHasher = $passwordHasher;

    }


    protected function configure()

    {

        $this

            ->setName('app:create-admin-user')

            ->setDescription('Creates a new admin user');

    }


    protected function execute(InputInterface $input, OutputInterface $output): int

    {

        $io = new SymfonyStyle($input, $output);


        $user = new User();

        $user->setFirstname('Admin');

        $user->setLastname('Admin');

        $user->setEmail('admin@admin.com');

        $user->setPassword($this->passwordHasher->hashPassword($user, '123456'));

        $user->setRoles(['ROLE_ADMIN']);
        $user->setSurname('admin');
        $user->setPhone('1234567890');
        $user->setPhoto('default.jpg'); // Assuming you have a default photo
        $user->setAddress('123 Admin St, Admin City, Admin Country');
        $user->setBirthday(new \DateTimeImmutable('2000-01-01')); // Default birthday, adjust as needed

        $this->entityManager->persist($user);

        $this->entityManager->flush();


        $io->success('Admin user created successfully.');


        return Command::SUCCESS;

    }

}
<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const ADMIN_EMAIL = 'anteur.ap@gmail.com';

    private string $plainPassword;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        #[Autowire('%env(APP_USER_PASSWORD)%')] string $plainPassword,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->plainPassword = $plainPassword;
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail(self::ADMIN_EMAIL)
            ->setRoles(['ROLE_ADMIN'])
            ->setDisplayName('Antonin');

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $this->plainPassword
        );

        $user->setPassword($hashedPassword);

        $manager->persist($user);
        $manager->flush();
    }
}

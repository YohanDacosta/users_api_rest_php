<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\User;
use DateTimeImmutable;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        self::bootKernel([
            'environment' => 'test',
            'debug' => false,
        ]);

        $this->em = self::$kernel->getContainer()->get('doctrine')->getManager();

        $hasherFactory = new PasswordHasherFactory([
            User::class => ['algorithm' => 'auto'],
        ]);

        $this->passwordHasher = new UserPasswordHasher($hasherFactory);
    }

    public function testGetUsers(): void
    {
        $users = $this->em->getRepository(User::class)->findAll();

        $this->assertNotEmpty($users);
        $this->assertIsArray($users);
    }

    public function testCreateUser(): void
    {
        $date = new DateTimeImmutable('now');

        $user = new User();
        $user->setFirstName('Yohan');
        $user->setLastName('Diaz Acosta');
        $user->setEmail('yohan@gmail.com');

        $hashedPass = $this->passwordHasher->hashPassword($user, 'admin');
        $user->setPassword($hashedPass);
        $user->setCreatedAt($date);

        $this->em->persist($user);
        $this->em->flush();

        $created_model_user = $this->em->getRepository(User::class)->findOneBy(["first_name" => 'Barbara']);
        $this->assertNotNull($created_model_user);
        $this->assertEquals('barbara@gmail.com', $created_model_user->getEmail());

    } 

    public function testGetuserByEmail(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'barbara@gmail.com']);
        $this->assertNotEmpty($user);
        $this->assertIsObject($user);
        $this->assertInstanceOf(User::class, $user);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }
}

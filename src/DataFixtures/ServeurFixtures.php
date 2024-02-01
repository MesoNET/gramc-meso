<?php

namespace App\DataFixtures;

use App\Factory\ServeurFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ServeurFixtures extends Fixture
{

    public function load(ObjectManager $manager): void
    {
        ServeurFactory::createOne([
            'nom' => 'test',
          'admname' => 'test',
          'password' => 'test',
        ]);
    }
}

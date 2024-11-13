<?php

namespace App\DataFixtures;

use App\Factory\ServiceExterneFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ServiceExterneFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        ServiceExterneFactory::createOne([
            'username' => 'testexterne_dev',
            'password' => 'test',
        ]);
    }
}

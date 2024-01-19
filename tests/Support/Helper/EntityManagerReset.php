<?php

declare(strict_types=1);

namespace App\Tests\Support\Helper;

use Codeception\Exception\ModuleException;
use Codeception\Module;
use Codeception\Module\Symfony;
use Codeception\TestInterface;
use Doctrine\Persistence\ManagerRegistry;

class EntityManagerReset extends Module
{
    /**
     * Executed before tests.
     *
     * Fix "The EntityManager is closed" after test failure. If EntityManager is closed, reset it.
     */
    public function _before(TestInterface $test): void
    {
        parent::_before($test);

        try {
            /** @var Symfony $symfonyModule */
            $symfonyModule = $this->getModule('Symfony');
            /** @var ManagerRegistry $doctrine */
            $doctrine = $symfonyModule->grabService('doctrine');
            if (!$doctrine->getManager()->isOpen()) {
                $doctrine->resetManager();
            }
        } catch (ModuleException) {
        }
    }
}

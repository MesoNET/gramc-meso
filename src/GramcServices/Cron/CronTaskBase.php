<?php

namespace App\GramcServices\Cron;

use App\GramcServices\GramcDate;
use App\GramcServices\ServiceJournal;
use App\GramcServices\ServiceProjets;
use Doctrine\ORM\EntityManagerInterface;

/********************************************
 * CronTaskBase - Une classe abstraite dont dérive toutes les CronTasks
 * Les crontasks sont des tâches exécutées par la classe Cron
 ********************************************/
abstract class CronTaskBase
{
    public function __construct(protected EntityManagerInterface $em,
        protected ServiceJournal $sj,
        protected ServiceProjets $sp,
        protected GramcDate $grdt)
    {
    }

    abstract public function cronExecute(): void;
}

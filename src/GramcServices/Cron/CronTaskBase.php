<?php

namespace App\GramcServices\Cron;

use Doctrine\ORM\EntityManagerInterface;

use App\GramcServices\GramcDate;
use App\GramcServices\ServiceProjets;
use App\GramcServices\ServiceJournal;
use App\GramcServices\Workflow\Version4\Version4Workflow;
use App\GramcServices\Workflow\Projet4\Projet4Workflow;


/********************************************
 * CronTaskBase - Une classe abstraite dont dérive toutes les CronTasks
 * Les crontasks sont des tâches exécutées par la classe Cron
 ********************************************/
abstract class CronTaskBase
{
    public function __construct(protected EntityManagerInterface $em,
                                protected ServiceJournal $sj,
                                protected ServiceProjets $sp,
                                protected GramcDate $grdt,
                                protected version4Workflow $v4w,
                                protected projet4Workflow $p4w)
                                
    {}
    
    abstract public function cronExecute();
}

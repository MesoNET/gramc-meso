<?php

namespace App\GramcServices\Cron;

use Doctrine\ORM\EntityManagerInterface;

use App\GramcServices\GramcDate;
use App\GramcServices\ServiceProjets;
use App\GramcServices\Workflow\ProjetWorkflow\ProjetWorkflow;
use App\GramcServices\Workflow\PrestationWorkflow\RessourcesWorkflow;
use App\GramcServices\Workflow\PrestationWorkflow\TempsWorkflow;


/********************************************
 * CronTaskBase - Une classe abstraite dont dérive toutes les CronTasks
 * Les crontasks sont des tâches exécutées par la classe Cron
 ********************************************/
abstract class CronTaskBase
{
//    protected $em = null;
//    protected $sprj = null;
//    protected $grdt = null;
//    protected $prjw = null;
//    protected $resw = null;
//    protected $tpsw = null;

    public function __construct (protected EntityManagerInterface $em)
    {
        $this->em = $em;
//        $this->sprj = $sprj;
//        $this->grdt = $grdt;
//        $this->prjw = $prjw;
//        $this->resw = $resw;
//        $this->tpsw = $tpsw;
    }
    
    abstract public function cronExecute();
}

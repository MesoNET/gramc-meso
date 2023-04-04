<?php

namespace App\GramcServices\Cron;

use App\GramcServices\Cron\GramcCronTask\NopCronTask;
use App\GramcServices\Cron\GramcCronTask\OverQuotaCronTask;
use App\GramcServices\Cron\GramcCronTask\TempsCronTask;
use App\GramcServices\Cron\GramcCronTask\TempsStdbyCronTask;


/***********
 * La classe Cron maintient un tableau d'objets qui dérivent de la classe abstraite CronTaskBase
 * Lorsqu'on appelle la fonction execute, celle-ci appelle en séquence toutes les fonction cronExecute() des objets du tableau
 * 
 * ATTENTION On n'a pas de garantie que Cron.execute n'est appelé qu'une fois par jour, donc il faut que les cronTasks puissent
 *           être appelées plusieurs fois à la même date sans dommage.
 * 
 * TODO - 1/ Comment automatiser l'initialisation du tableau ?
 *        2/ Garder la trace de cet appel afin d'éviter deux appels quotidiens des CronTasks
 * 
 ***************************/
class Cron
{
    private $taches = [];
    public function __construct(private NopCronTask $nct,
                                private TempsCronTask $tct,
                                private TempsStdbyCronTask $tsct)
    {
        $this->taches[] = $nct;
        $this->taches[] = $tct;
        $this->taches[] = $tsct;
    }

    public function execute()
    {
        foreach ($this->taches as $t)
        {
            $t->cronExecute();
        }
    }
}


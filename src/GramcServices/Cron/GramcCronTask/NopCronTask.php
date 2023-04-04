<?php

namespace App\GramcServices\Cron\GramcCronTask;

use App\GramcServices\Cron\CronTaskBase;
use App\GramcServices\GramcDate;
use App\GramcServices\ServiceProjets;
use App\GramcServices\ServiceJournal;
use App\GramcServices\Workflow\Version4Workflow\Version4Workflow;
use App\GramcServices\Workflow\Projet4\Projet4Workflow;

/*********
 * No oPeration CronTask - Ne fait rien, mais si ça marche on est content !
 * 
 ****************/
class NopCronTask extends CronTaskBase
{
    public function cronExecute():void {}
}

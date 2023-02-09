<?php

namespace App\GramcServices\Cron\GramcCronTask;

use App\GramcServices\Cron\CronTaskBase;

/*********
 * No oPeration CronTask - Ne fait rien, mais si ça marche on est contents !
 * 
 ****************/
class NopCronTask extends CronTaskBase
{
    public function cronExecute() {}
}

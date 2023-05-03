<?php

namespace App\GramcServices\Cron\GramcCronTask;

use App\GramcServices\Cron\CronTaskBase;

use App\GramcServices\GramcDate;
use App\GramcServices\ServiceJournal;
use App\GramcServices\ServiceProjets;

use App\Entity\Invitation;

use Doctrine\ORM\EntityManagerInterface;


/**********************************************************
 * 
 * Invit CronTask - Recherche les invitations périmées et les supprime
 * 
 ************************************************************************************/
class InvitCronTask extends CronTaskBase
{
    public function __construct(private $invit_duree,
                                protected EntityManagerInterface $em,
                                protected ServiceJournal $sj,
                                protected ServiceProjets $sp,
                                protected GramcDate $grdt)
    {
        parent::__construct($em,$sj,$sp,$grdt);
    }

    public function cronExecute() : void
    {
        $em = $this->em;
        $sj = $this->sj;
        $grdt = $this->grdt;
        $invit_duree = $this->invit_duree;
        
        $age_max = new \DateInterval($invit_duree);
        $invitations = $em->getRepository(Invitation::class)->findAll();
        $cpt = 0;
        foreach ($invitations as $i)
        {
            $stamp = $i->getCreationStamp();
            $date_max = $stamp->add($age_max);
            if ($date_max->getTimestamp() < $grdt->getTimestamp())
            {
                $em->remove($i);
                $cpt++;

            }
        }
        if ($cpt > 0)
        {
            $em->flush();
            $sj->warningMessage(__METHOD__ .':' . __LINE__ . " Suppression de $cpt invitations");
        }
    }
}


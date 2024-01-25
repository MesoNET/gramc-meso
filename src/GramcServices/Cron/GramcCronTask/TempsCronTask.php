<?php

namespace App\GramcServices\Cron\GramcCronTask;

use App\Entity\Projet;
use app\Entity\Version;
use App\GramcServices\Cron\CronTaskBase;
use App\GramcServices\Etat;
use App\GramcServices\GramcDate;
use App\GramcServices\ServiceJournal;
use App\GramcServices\ServiceProjets;
use App\GramcServices\Signal;
use App\GramcServices\Workflow\Projet4\Projet4Workflow;
use App\GramcServices\Workflow\Version4\Version4Workflow;
use Doctrine\ORM\EntityManagerInterface;

/**********************************************************
 *
 * Temps CronTask - Recherche tous les projets dont la dernière version est en état ACTIF ou ACTIF_R
 *                  et leur envoie des signaux en fonction du temps qui reste avant la date limite
 *
 ************************************************************************************/
class TempsCronTask extends CronTaskBase
{
    public function __construct(private $dyn_duree_post,
        protected EntityManagerInterface $em,
        protected ServiceJournal $sj,
        protected ServiceProjets $sp,
        protected GramcDate $grdt,
        protected Version4Workflow $v4w,
        protected Projet4Workflow $p4w)
    {
        parent::__construct($em, $sj, $sp, $grdt);
    }

    public function cronExecute(): void
    {
        $em = $this->em;
        $dyn_duree_post = $this->dyn_duree_post;
        $sp = $this->sp;
        $grdt = $this->grdt->getNew();
        // echo "date = ".$grdt->format('Y-m-d')."\n";
        $workflow = $this->v4w;
        $workflow_p = $this->p4w;
        $sj = $this->sj;

        $projet_repository = $em->getRepository(Projet::class);
        $projets = $projet_repository->findAll();

        // Recherche les projets dont la dernière version est active
        foreach ($projets as $p) {
            $derver = $p->getVersionDerniere();
            // echo "$derver...\n";
            if (null === $derver) {
                $sj->errorMessage(__METHOD__.':'.__LINE__." Projet $p - Pas de versionDerniere !");
                continue;
            }

            $etat_version = $derver->getEtatVersion();
            if (Etat::ACTIF === $etat_version || Etat::ACTIF_R === $etat_version) {
                $ld = $derver->getLimitDate();
                if (null === $ld) {
                    $sj->errorMessage(__METHOD__.':'.__LINE__." Version $derver - Pas de LimitDate !");
                    continue;
                }
                if ($grdt <= $ld) {
                    // Si on est à moins de 30 jours de la date limite, on passe en ACTIF_R
                    $r_date = $ld->sub(new \DateInterval('P30D'));
                    if ($grdt >= $r_date) {
                        // echo "$derver - DAT_ACTR\n";
                        $signal = Signal::DAT_ACTR;
                        $rtn = $workflow->execute($signal, $derver);
                        if (false == $rtn) {
                            $sj->warningMessage(__METHOD__.':'.__LINE__." Version $derver - Etat $etat_version - Signal $signal - Echec de la transition");
                        }
                        $em->flush();
                    }
                }

                // Si la date limite est dépassée, on ferme la version
                elseif ($grdt > $ld) {
                    // echo "$derver - CLK_FERM\n";
                    $derver->setEndDate($grdt);
                    $signal = Signal::CLK_FERM;
                    $rtn = $workflow->execute($signal, $derver);
                    if (false == $rtn) {
                        $sj->warningMessage(__METHOD__.':'.__LINE__." Version $derver - Etat $etat_version - Signal $signal - Echec de la transition");
                    }
                    $em->persist($derver);
                    $em->flush();
                }
            }

            // Si toutes les versions sont terminées et si on est à la date limite du projet + dyn_duree_post (def = 365 jours),
            // on ferme le projet
            if (0 === count($sp->getVersionsNonTerminees($p))) {
                $ld = $p->getLimitDate();
                if (null != $ld) {
                    if ($grdt > $ld->add(new \DateInterval($dyn_duree_post))) {
                        $signal = Signal::CLK_FERM;
                        $rtn = $workflow_p->execute($signal, $p);
                        if (false == $rtn) {
                            $etat_projet = $p->getEtatProjet();
                            $sj->warningMessage(__METHOD__.':'.__LINE__." Projet $p - Etat $etat_projet - Signal $signal - Echec de la transition");
                        }
                        $em->persist($derver);
                        $em->flush();
                    }
                }
            }
        } // foreach ($projets as $p)
    } // public function cronExecute()
}

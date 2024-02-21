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
use App\GramcServices\Workflow\Projet4\TProjet4Workflow;
use Doctrine\ORM\EntityManagerInterface;

/**********************************************************
 *
 * Temps Stdby CronTask - Recherche tous les projets dont la dernière version est en état STANDBY
 *                  et leur envoie un signal correspondant à la durée restant
 *
 ************************************************************************************/
class TempsStdbyCronTask extends CronTaskBase
{
    public function __construct(private $dyn_duree_post,
        protected EntityManagerInterface $em,
        protected ServiceJournal $sj,
        protected ServiceProjets $sp,
        protected GramcDate $grdt,
        protected TProjet4Workflow $tp4w)
    {
        parent::__construct($em, $sj, $sp, $grdt);
    }

    public function cronExecute(): void
    {
        $em = $this->em;
        $dyn_duree_post = $this->dyn_duree_post;
        $grdt = $this->grdt->getNew();
        // echo "date = ".$grdt->format('Y-m-d')."\n";
        $workflow = $this->tp4w;
        $sj = $this->sj;

        $projet_repository = $em->getRepository(Projet::class);
        $projets = $projet_repository->findAll();

        // Envoie des signaux différents suivant le nombre de jours restant avant la fin du projet
        // Tableau indexé par le nombre de jours
        $signaux = [1 => Signal::DAT_CAL_0,
                     7 => Signal::DAT_CAL_1,
                     15 => Signal::DAT_CAL_7,
                     30 => Signal::DAT_CAL_15,
                     99 => Signal::DAT_CAL_30];

        // Recherche les projets dont la dernière version est en standby
        foreach ($projets as $p) {
            $derver = $p->getVersionDerniere();
            // echo "$derver...\n";
            if (null === $derver) {
                $sj->errorMessage(__METHOD__.':'.__LINE__." Projet $p - Pas de versionDerniere !");
                continue;
            }
            $etat_version = $derver->getEtatVersion();
            if (Etat::TERMINE === $etat_version) {    // Projet en standby = projet RENOUVELABLE dont la dernière version est terminée
                $ld = clone $derver->getLimitDate();
                if (null === $ld) {
                    $sj->errorMessage(__METHOD__.':'.__LINE__." Version $derver - Pas de LimitDate !");
                    continue;
                }
                $ld = $ld->add(new \DateInterval($dyn_duree_post));
                if ($grdt <= $ld) {
                    foreach ([1, 7, 15, 30, 99] as $duree) {
                        $r_date = $ld->sub(new \DateInterval('P'.$duree.'D'));
                        // echo "Projet = $p grdt = " . $grdt->format('Y-M-d') . " date = " . $r_date->format('Y-M-d')."\n";
                        if ($grdt >= $r_date) {
                            $signal = $signaux[$duree];
                            // echo "projet = $p signal = $signal\n";
                            $rtn = $workflow->execute($signal, $p);
                            if (false === $rtn) {
                                $tetat = $p->getTetatProjet();
                                // echo "Projet = $p - Etat $tetat - Signal $signal - Echec de la transition\n";
                                $sj->warningMessage(__METHOD__.':'.__LINE__." Projet $p - Etat $tetat - Signal $signal - Echec de la transition");
                            } else {
                                $tetat = $p->getTetatProjet();
                                // echo "Projet = $p - Etat $tetat - Signal $signal - REUSSITE de la transition\n";
                            }
                            $em->flush();
                            break;
                        }
                    }
                }
            }
        } // foreach ($projets as $p)
    } // public function cronExecute()
}

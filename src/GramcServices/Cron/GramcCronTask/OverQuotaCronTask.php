<?php

namespace App\GramcServices\Cron\GramcCronTask;

use App\GramcServices\Cron\CronTaskBase;
use App\GramcServices\Workflow\ProjetWorkflow\ProjetWorkflow;
use App\GramcServices\Workflow\PrestationWorkflow\RessourcesWorkflow;
use App\GramcServices\Workflow\PrestationWorkflow\RessourcesTransition;

use App\Utils\Netat;
use App\Entity\Projet;
use App\Entity\Consommation;
use App\Entity\Machine;


/******************************
 * 
 * OverQuota CronTask - Recherche toutes les prestations actives, et utilise le workflow RessourcesWorkflow de Prestation
 *                      pour suivre l'évolution du quota et envoyer des notifications si nécessaire
 * 
 ****************/
class OverQuotaCronTask extends CronTaskBase
{
    
    public function cronExecute() 
    {
        $sprj = $this->sprj;
        $em = $this->em;
        $projet_repository = $em->getRepository(Projet::class);
        $conso_repository  = $em->getRepository(Consommation::class);

        $projets = $projet_repository->findAll();

        $w_prj = $this->prjw;
        $w_res = $this->resw;
        $sprj = $this->sprj;
        
        // Recherche des prestations actives (0 ou 1 par projet)
        // Ne recherche QUE des projets calcul
        foreach ($projets as $p)
        {
            $gunix  = $p->getGunix();
        
            // Pas de groupe unix, pas de conso !
            if (empty($gunix)) continue;

            // Projet stockage, on ignore !
            if ($p->getMachine()->getType() == Machine::STOCKAGE) continue;

            // Conso aujourd'hui
            $today = $this->grdt;

            // Prise en compte des cpu + gpu
            $conso_today = $sprj->getConso($p);
            $quota_today = $sprj->getQuota($p);
    
            $presta = $sprj->getPrestationActif($p);
    
            // Pas de presta active, on vérifie quota vs consommation (quotas exceptionnels mis hors prestation !)
            if (empty($presta))
            {
                if ($quota_today <= $conso_today)
                {
                    $w_prj->execute($p, Netat::DAT_DEVALID);
                }            
            }
        
            // Presta active, on gère tout ça par rapport à la prestation trouvée
            else
            {
                // Heures restantes avant dépassement de quota
                $conso_restante = $quota_today - $conso_today;
                if ($conso_restante < 0) $conso_restante = 0;

                // Quota de la prestation
                $quota_presta = $presta->getNbHeuresCpu();
                
                // Pas de date de début, pas de conso !
                //if($presta->getDateDebut() == null) continue;
        
                // Conso à la date de démarrage de la presta
                //$date_deb = $presta->getDateDebut();
                //$conso_deb= $conso_repository->findOneBy( ['gunix' => $gunix, 'date' => $date_deb] );
        
                // pb dans la table Consommation, on laisse tomber
                //if ( $conso_deb === null) continue;
                
                //AppBundle::getLogger()->error("coucou $gunix ".$today->format('d-m-Y')." ".$conso_today->getConso());
                //AppBundle::getLogger()->error("coucou $gunix ".$date_deb->format('d-m-Y')." ".$conso_deb->getConso());
                //AppBundle::getLogger()->error("coucou $gunix ".$conso_deb->getConso()." ".$conso_today->getConso()."  diff=". intval($conso_today->getConso() - $conso_deb->getConso()));
        
                // Quota de la prestation = quota aujourd'hui - quota en début de presta
                //$quota_presta = $conso_today->getQuota() - $conso_deb->getConso();
        
                // Conso relative
                //$conso_rel = floatval(($conso_today->getConso() - $conso_deb->getConso())) / floatval($quota_presta);
                
                if ($quota_presta==0) continue;
                
                // Conso relative
                // Possible si on interrompt la prestation précédente
                if ( $conso_restante >= $quota_presta) 
                {
                    $conso_rel = 0;
                }
                else
                {
                    $conso_rel = 1 - floatval($conso_restante/$quota_presta);
                }

                //echo "$presta $quota_today $conso_today $conso_rel\n";
                //continue;
                

                // On envoie - ou pas - le signal correspondant à la consommation
                if ($conso_rel < 0.5) 
                {
                    continue;
                }
                else
                {
                    if ($conso_rel < 0.9) 
                    {
                        $signal = Netat::DAT_EPUISE_50;
                    }
                    elseif ($conso_rel < 1.0)
                    {
                        $signal = Netat::DAT_EPUISE_90;
                    }
                    else
                    {
                        $signal = Netat::DAT_EPUISE;
                    }
                    $w_res->execute($presta, $signal);
                }
            }
        }
    }
}

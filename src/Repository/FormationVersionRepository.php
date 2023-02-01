<?php

namespace App\Repository;

use App\Entity\FormationVersion;


/**
 * @ method FormationVersion|null find($id, $lockMode = null, $lockVersion = null)
 * @ method FormationVersion|null findOneBy(array $criteria, array $orderBy = null)
 * @ method FormationVersion[]    findAll()
 * @ method FormationVersion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormationVersionRepository extends \Doctrine\ORM\EntityRepository
{
    public function findStats()
    {
        return $this->createQueryBuilder('fv')
            ->select("fv, sum(fv.nombre) as nb")
            ->groupBy("fv.formation")
            ->getQuery()
            ->getResult();
    }
}

<?php

namespace App\Repository;

use App\Entity\Formation;

/**
 * @ method Formation|null find($id, $lockMode = null, $lockVersion = null)
 *
 * @ method Formation|null findOneBy(array $criteria, array $orderBy = null)
 *
 * @ method Formation[]    findAll()
 *
 * @ method Formation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormationRepository extends \Doctrine\ORM\EntityRepository
{
    // Renvoie les formations telles que startDate <= today <= endDate
    // Les formations sont triées selon numeroForm
    public function findAllCurrentDate()
    {
        $today = new \DateTime();

        return $this->createQueryBuilder('f')
                    ->where('f.startDate <= ?1 and ?1 <= f.endDate')
                    ->orderBy('f.numeroForm', 'ASC')
                    ->setParameter(1, $today)
                    ->getQuery()
                    ->getResult();
    }
}

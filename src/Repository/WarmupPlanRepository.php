<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\WarmupPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method WarmupPlan|null find($id, $lockMode = null, $lockVersion = null)
 * @method WarmupPlan|null findOneBy(array $criteria, array $orderBy = null)
 * @method WarmupPlan[]    findAll()
 * @method WarmupPlan[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WarmupPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WarmupPlan::class);
    }

    /**
     * Returns WarmupPlans created by the User, or shared by other Users
     *
     * @param User $user
     * @return mixed
     */
    public function getAccessibleWarmupPlans(User $user)
    {
        return $this->createQueryBuilder('s')
            ->orWhere('s.user = :user')
            ->orWhere('s.shared = true')
            ->andWhere('s.visible = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult()
            ;
    }

    // /**
    //  * @return WarmupPlan[] Returns an array of WarmupPlan objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?WarmupPlan
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

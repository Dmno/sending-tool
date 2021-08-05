<?php

namespace App\Repository;

use App\Entity\CampaignContent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method CampaignContent|null find($id, $lockMode = null, $lockVersion = null)
 * @method CampaignContent|null findOneBy(array $criteria, array $orderBy = null)
 * @method CampaignContent[]    findAll()
 * @method CampaignContent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CampaignContentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CampaignContent::class);
    }

    // /**
    //  * @return CampaignContent[] Returns an array of CampaignContent objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CampaignContent
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

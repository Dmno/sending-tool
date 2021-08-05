<?php

namespace App\Repository;

use App\Entity\ContactList;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method ContactList|null find($id, $lockMode = null, $lockVersion = null)
 * @method ContactList|null findOneBy(array $criteria, array $orderBy = null)
 * @method ContactList[]    findAll()
 * @method ContactList[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContactListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContactList::class);
    }

    /**
     * Get ContactLists which are created by the User, or a shared by other Users
     *
     * @param User $user
     * @return mixed
     */
    public function getAccessibleLists(User $user)
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

    /**
     * Get ContactLists which are created by the User, or a shared by other Users by locale
     *
     * @param User $user
     * @param string $locale
     * @return mixed
     */
    public function getAccessibleListsByLocale(User $user, string $locale = NULL)
    {
        return $this->createQueryBuilder('s')
            ->orWhere('s.user = :user')
            ->orWhere('s.shared = true')
            ->andWhere('s.visible = true')
            ->andWhere('s.locale = :locale')
            ->setParameter('user', $user)
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getResult()
            ;
    }

    // /**
    //  * @return ContactList[] Returns an array of ContactList objects
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
    public function findOneBySomeField($value): ?ContactList
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

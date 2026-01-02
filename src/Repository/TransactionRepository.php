<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }
    public function findByFilters(?string $categoryId, ?string $startDate, ?string $endDate, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->join('t.product', 'p')
            ->addSelect('p')
            ->orderBy('t.createdAt', 'DESC');

        // Category Filter
        if ($categoryId && $categoryId !== 'all') {
            $qb->andWhere('p.category = :cat')->setParameter('cat', $categoryId);
        }

        // NEW: Search Filter (Case-insensitive name search)
        if ($search) {
            $qb->andWhere('p.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Date Filters
        if ($startDate) {
            $qb->andWhere('t.createdAt >= :start')->setParameter('start', new \DateTime($startDate . ' 00:00:00'));
        }
        if ($endDate) {
            $qb->andWhere('t.createdAt <= :end')->setParameter('end', new \DateTime($endDate . ' 23:59:59'));
        }

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return Transaction[] Returns an array of Transaction objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Transaction
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

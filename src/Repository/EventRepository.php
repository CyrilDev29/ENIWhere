<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    //    /**
    //     * @return Event[] Returns an array of Event objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Event
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function searchByFilters(array $f, ?\App\Entity\User $user): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.registrations', 'r')
            ->addSelect('r')
            ->leftJoin('e.site', 's')
            ->addSelect('s')
            ->leftJoin('e.place', 'p')
            ->addSelect('p')
            ->leftJoin('e.organizer', 'o')
            ->addSelect('o')
            ->orderBy('e.startDateTime', 'ASC')
            ->distinct();

        // Site
        if (!empty($f['site'])) {
            $qb->andWhere('e.site = :site')
                ->setParameter('site', $f['site']);
        }

        // Recherche texte
        if (!empty($f['q'])) {
            $qb->andWhere('LOWER(e.name) LIKE :q')
                ->setParameter('q', '%' . strtolower($f['q']) . '%');
        }

        // Dates
        if (!empty($f['dateFrom'])) {
            $qb->andWhere('e.startDateTime >= :from')
                ->setParameter('from', $f['dateFrom']);
        }
        if (!empty($f['dateTo'])) {
            $qb->andWhere('e.startDateTime <= :to')
                ->setParameter('to', $f['dateTo']);
        }

        // Filtrage des événements archivés
        $oneMonthAgo = (new \DateTime())->modify('-1 month');
        $qb->andWhere('e.startDateTime >= :oneMonthAgo')
            ->setParameter('oneMonthAgo', $oneMonthAgo);

        // Organisateur
        if (!empty($f['isOrganizer']) && $user) {
            $qb->andWhere('e.organizer = :user')
                ->setParameter('user', $user);
        }

        // Inscrit ou non inscrit
        if (!empty($f['isRegistered']) && $user && empty($f['isNotRegistered'])) {
            $qb->andWhere(':user MEMBER OF e.registrations')
                ->setParameter('user', $user);
        }
        if (!empty($f['isNotRegistered']) && $user && empty($f['isRegistered'])) {
            $qb->andWhere(':user NOT MEMBER OF e.registrations')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }





}







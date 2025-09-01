<?php

namespace App\Repository;

use App\Entity\Registration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Registration>
 */
class RegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Registration::class);
    }

    /**
     * -------------Compte le nombre d'inscriptions pour un événement donné
     */
    public function countByEvent(int $eventId): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.event = :eventId')
            ->andWhere('r.workflowState != :canceled')
            ->setParameter('eventId', $eventId)
            ->setParameter('canceled', 'CANCELED')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * --------Vérifie si un utilisateur est déjà inscrit à un événement------
     */
    public function findOneByEventAndUser(int $eventId, int $userId): ?Registration
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.event = :eventId')
            ->andWhere('r.participant = :userId')
            ->setParameter('eventId', $eventId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();


    }


    public function findRegistrationsForNotification(\DateTime $targetDateTime)
    {
        $tz = new \DateTimeZone('Europe/Paris');
        $start = (clone $targetDateTime)->setTime(0, 0, 0)->setTimezone($tz);
        $end   = (clone $targetDateTime)->setTime(23, 59, 59)->setTimezone($tz);

        $qb = $this->createQueryBuilder('r')
            ->join('r.event', 'e')
            ->where('r.workflowState = :state')
            ->andWhere('e.startDateTime BETWEEN :start AND :end')
            ->setParameter('state', 'REGISTERED')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        // ------Dump de la requête SQL pour voir ce qui est exécuté------
        dump($qb->getQuery()->getSQL());

        // ------------------Dump des paramètres
        dump($start, $end);
        $results = $qb->getQuery()->getResult();
        // D---------ump des résultats pour voir ce qui est trouvé-------
        dump($results);
        return $results;
    }




    // Exemple généré par Symfony si besoin
    /*
    public function findByExampleField($value): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function findOneBySomeField($value): ?Registration
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult();
    }
    */
}

<?php

namespace App\Repository;

use App\Entity\Fonction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Fonction>
 *
 * @method Fonction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Fonction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Fonction[]    findAll()
 * @method Fonction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FonctionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fonction::class);
    }

    public function save(Fonction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Fonction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find functions that allow driving
     */
    public function findDrivingFunctions(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.canDrive = :canDrive')
            ->setParameter('canDrive', true)
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find functions that allow being passenger
     */
    public function findPassengerFunctions(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.canBePassenger = :canBePassenger')
            ->setParameter('canBePassenger', true)
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

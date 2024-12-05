<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @extends ServiceEntityRepository<Event>
 *
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, Event::class);
    }
    
    public function paginateRecipes($page, ?int $userId): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('e')->select('e');
        $queryBuilder->addOrderBy('e.id', 'DESC');
        if ($userId) {
            $queryBuilder->andWhere('e.user = :userId')
                ->setParameter('userId', $userId);
        };

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            10,
            [
                'distinct' => false,
                'shortFieldAllowList' => ['id', 'title', 'startEvent', 'endEvent', 'createdAt'],
            ]
        );
    }

    public function findAvailablePlacesByDate(\DateTimeInterface $date): array
    {
        $startOfDay = (new \DateTime($date->format('Y-m-d')))->setTime(0, 0, 0);
        $endOfDay = (clone $date)->setTime(23, 59, 59);

        $queryBuilder = $this->createQueryBuilder('e')
            ->innerJoin('e.places', 'p')
            ->where('e.startEvent <= :endOfDay')
            ->andWhere('e.endEvent >= :startOfDay')
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->select('e.id, e.title, e.duration, p.id as placeId')
            ->groupBy('e.id, p.id');

        return $queryBuilder->getQuery()->getResult();
    }
}
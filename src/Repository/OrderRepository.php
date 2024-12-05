<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Order>
 *
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, Order::class);
    }
    
    public function paginateRecipes($page, ?int $userId): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('o')->select('o');
        $queryBuilder->addOrderBy('o.id', 'ASC');
        if ($userId) {
            $queryBuilder->andWhere('o.user = :userId')
                ->setParameter('userId', $userId);
        };

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            10,
            [
                'distinct' => false,
                'shortFieldAllowList' => ['id', 'status', 'orderDate', 'totalAmount', 'bankedAt', 'updatedAt'],
            ]
        );
    }
}

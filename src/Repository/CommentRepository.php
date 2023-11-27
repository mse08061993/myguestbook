<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Conference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\QueryBuilder;

class CommentRepository extends ServiceEntityRepository
{
    public const COMMENTS_PER_PAGE = 2;
    private const DAYS_BEFORE_REJECTED_REMOVAL = 7;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function getPaginator(Conference $conference, int $offset): Paginator
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.conference = :conference')
            ->andWhere('c.state = :state')
            ->setParameters([
                'conference' => $conference,
                'state' => 'published',
            ])
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(self::COMMENTS_PER_PAGE)
            ->setFirstResult($offset)
            ->getQuery()
        ;

        return new Paginator($query);
    }

    public function countOldRejected(): int
    {
        return $this->getOldRejectedQueryBuilder()->select('COUNT(c.id)')->getQuery()->getSingleScalarResult();
    }

    public function deleteOldRejected(): int
    {
        return $this->getOldRejectedQueryBuilder()->delete()->getQuery()->execute();
    }

    private function getOldRejectedQueryBuilder(): QueryBuilder
    {
        $builder = $this->createQueryBuilder('c')
            ->andWhere('c.state = :spam_state or c.state = :rejected_state')
            ->andWhere('c.createdAt < :date')
            ->setParameters([
                'spam_state' => 'spam',
                'rejected_state' => 'rejected',
                'date' => new \DateTimeImmutable(-self::DAYS_BEFORE_REJECTED_REMOVAL . ' days')
            ])
        ;

        return $builder;
    }
}

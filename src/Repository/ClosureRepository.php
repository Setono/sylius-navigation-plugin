<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Repository;

use Setono\SyliusNavigationPlugin\Model\ClosureInterface;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webmozart\Assert\Assert;

class ClosureRepository extends EntityRepository implements ClosureRepositoryInterface
{
    public function findAncestors(ItemInterface $item): array
    {
        $objs = $this->findBy([
            'descendant' => $item,
        ]);

        Assert::allIsInstanceOf($objs, ClosureInterface::class);

        return $objs;
    }

    public function findGraph(ItemInterface $root, int $maxDepth = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->join($this->getClassName(), 'root', 'WITH', 'root.descendant = o.ancestor')
            ->andWhere('root.ancestor = :root')
            ->setParameter('root', $root)
        ;

        if (null !== $maxDepth) {
            $qb->andWhere('o.depth <= :maxDepth')
                ->setParameter('maxDepth', $maxDepth)
            ;
        }

        $objs = $qb->getQuery()->getResult();

        Assert::isArray($objs);
        Assert::isList($objs);
        Assert::allIsInstanceOf($objs, ClosureInterface::class);

        return $objs;
    }
}

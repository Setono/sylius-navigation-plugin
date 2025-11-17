<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Repository;

use Setono\SyliusNavigationPlugin\Model\ClosureInterface;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webmozart\Assert\Assert;

class ClosureRepository extends EntityRepository implements ClosureRepositoryInterface
{
    public function findAncestors(ItemInterface $item): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.descendant = :item')
            ->setParameter('item', $item)
            ->orderBy('c.depth', 'ASC');

        $objs = $qb->getQuery()->getResult();

        Assert::isArray($objs);
        Assert::isList($objs);
        Assert::allIsInstanceOf($objs, ClosureInterface::class);

        return $objs;
    }

    public function findGraph(ItemInterface $root): array
    {
        $qb = $this->createQueryBuilder('o')
            ->join($this->getClassName(), 'root', 'WITH', 'root.descendant = o.ancestor')
            ->andWhere('root.ancestor = :root')
            ->setParameter('root', $root)
        ;

        $objs = $qb->getQuery()->getResult();

        Assert::isArray($objs);
        Assert::isList($objs);
        Assert::allIsInstanceOf($objs, ClosureInterface::class);

        return $objs;
    }

    public function findByNavigation(NavigationInterface $navigation): array
    {
        $qb = $this->createQueryBuilder('o')
            ->join('o.descendant', 'item')
            ->andWhere('item.navigation = :navigation')
            ->setParameter('navigation', $navigation)
        ;

        $objs = $qb->getQuery()->getResult();

        Assert::isArray($objs);
        Assert::isList($objs);
        Assert::allIsInstanceOf($objs, ClosureInterface::class);

        return $objs;
    }

    public function findRootItems(NavigationInterface $navigation): array
    {
        // Find items where depth = 0 (self-reference only) and they don't have any ancestors with depth > 0
        $qb = $this->_em->createQueryBuilder();
        $qb->select('DISTINCT item')
            ->from(ItemInterface::class, 'item')
            ->leftJoin($this->getClassName(), 'c', 'WITH', 'c.descendant = item AND c.depth > 0')
            ->where('item.navigation = :navigation')
            ->andWhere('c.id IS NULL')
            ->orderBy('item.position', 'ASC')
            ->setParameter('navigation', $navigation)
        ;

        $objs = $qb->getQuery()->getResult();

        Assert::isArray($objs);
        Assert::isList($objs);
        Assert::allIsInstanceOf($objs, ItemInterface::class);

        return $objs;
    }
}

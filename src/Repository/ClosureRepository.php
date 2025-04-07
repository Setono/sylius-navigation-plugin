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
}

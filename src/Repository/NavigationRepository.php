<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Repository;

use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Channel\Model\ChannelInterface;
use Webmozart\Assert\Assert;

class NavigationRepository extends EntityRepository implements NavigationRepositoryInterface
{
    public function findOneEnabledByCode(string $code, ChannelInterface $channel = null): ?NavigationInterface
    {
        $qb = $this->createQueryBuilder('o')
            ->andWhere('o.enabled = true')
            ->andWhere('o.code = :code')
            ->setParameter('code', $code)
        ;

        if (null === $channel) {
            $qb->andWhere('o.channels IS EMPTY');
        } else {
            $qb->andWhere('o.channels IS EMPTY OR :channel MEMBER OF o.channels')
                ->setParameter('channel', $channel)
            ;
        }

        $obj = $qb->getQuery()->getOneOrNullResult();

        Assert::nullOrIsInstanceOf($obj, NavigationInterface::class);

        return $obj;
    }
}

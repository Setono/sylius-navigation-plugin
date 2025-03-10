<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Repository;

use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Channel\Model\ChannelInterface;
use Webmozart\Assert\Assert;

class NavigationRepository extends EntityRepository implements NavigationRepositoryInterface
{
    public function findOneEnabledByCode(string $code, ChannelInterface $channel): ?NavigationInterface
    {
        $obj = $this->createQueryBuilder('o')
            ->andWhere('o.enabled = true')
            ->andWhere('o.code = :code')
            ->andWhere(':channel MEMBER OF o.channels')
            ->setParameter('code', $code)
            ->setParameter('channel', $channel)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        Assert::nullOrIsInstanceOf($obj, NavigationInterface::class);

        return $obj;
    }
}

<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Repository;

use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

class TaxonItemRepository extends EntityRepository implements TaxonItemRepositoryInterface
{
    public function findByTaxon(TaxonInterface $taxon): array
    {
        /** @var array<array-key, TaxonItemInterface> $result */
        $result = $this->createQueryBuilder('o')
            ->andWhere('o.taxon = :taxon')
            ->setParameter('taxon', $taxon)
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }
}

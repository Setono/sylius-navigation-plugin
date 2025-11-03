<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\EventListener\Doctrine;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Setono\SyliusNavigationPlugin\Model\Item;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use function Symfony\Component\String\u;

final class ItemDiscriminatorMapListener
{
    public function __construct(
        /** @var array<string, array{classes: array{model: class-string}}> $resources */
        private readonly array $resources,
    ) {
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $metadata = $eventArgs->getClassMetadata();
        if ($metadata->getName() !== Item::class) {
            return;
        }

        $metadata->discriminatorMap = $this->getDiscriminatorMap();
    }

    /**
     * @return array<string, class-string>
     */
    private function getDiscriminatorMap(): array
    {
        $discriminatorMap = [];

        foreach ($this->resources as $resource) {
            ['model' => $model] = $resource['classes'];

            if (!is_a($model, ItemInterface::class, true)) {
                continue;
            }

            $discriminatorMap[self::getDiscriminatorKey($model)] = $model;
        }

        return $discriminatorMap;
    }

    /**
     * @param class-string $class
     */
    private static function getDiscriminatorKey(string $class): string
    {
        return u((new \ReflectionClass($class))->getShortName())->snake()->toString();
    }
}

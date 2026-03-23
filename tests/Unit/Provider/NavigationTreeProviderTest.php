<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusNavigationPlugin\Factory\ItemFactoryInterface;
use Setono\SyliusNavigationPlugin\Model\ClosureInterface;
use Setono\SyliusNavigationPlugin\Model\Item;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Model\Navigation;
use Setono\SyliusNavigationPlugin\Provider\ItemLabelProviderInterface;
use Setono\SyliusNavigationPlugin\Provider\NavigationTreeProvider;
use Setono\SyliusNavigationPlugin\Registry\ItemType;
use Setono\SyliusNavigationPlugin\Registry\ItemTypeRegistryInterface;
use Setono\SyliusNavigationPlugin\Repository\ClosureRepositoryInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

final class NavigationTreeProviderTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<ClosureRepositoryInterface> */
    private ObjectProphecy $closureRepository;

    /** @var ObjectProphecy<ItemTypeRegistryInterface> */
    private ObjectProphecy $itemTypeRegistry;

    /** @var ObjectProphecy<ItemLabelProviderInterface> */
    private ObjectProphecy $itemLabelProvider;

    private NavigationTreeProvider $provider;

    protected function setUp(): void
    {
        $this->closureRepository = $this->prophesize(ClosureRepositoryInterface::class);
        $this->itemTypeRegistry = $this->prophesize(ItemTypeRegistryInterface::class);
        $this->itemLabelProvider = $this->prophesize(ItemLabelProviderInterface::class);

        $this->provider = new NavigationTreeProvider(
            $this->closureRepository->reveal(),
            $this->itemTypeRegistry->reveal(),
            $this->itemLabelProvider->reveal(),
        );
    }

    /**
     * @test
     */
    public function it_returns_an_empty_tree_when_navigation_has_no_items(): void
    {
        $navigation = new Navigation();

        $this->closureRepository->findRootItems($navigation)->willReturn([]);

        self::assertSame([], $this->provider->getTree($navigation));
    }

    /**
     * @test
     */
    public function it_returns_tree_nodes_for_root_items(): void
    {
        $navigation = new Navigation();
        $item = $this->createItem(1, true);

        $this->closureRepository->findRootItems($navigation)->willReturn([$item]);
        $this->closureRepository->findDirectChildren($item)->willReturn([]);
        $this->itemTypeRegistry->getByEntity($item::class)->willReturn($this->createItemTypeMetadata());
        $this->itemLabelProvider->getLabel($item, null)->willReturn('Home');

        $tree = $this->provider->getTree($navigation, false);

        self::assertCount(1, $tree);
        self::assertSame('1', $tree[0]['id']);
        self::assertSame('Home', $tree[0]['text']);
        self::assertSame('link', $tree[0]['type']);
        self::assertFalse($tree[0]['children']);
    }

    /**
     * @test
     */
    public function it_returns_children_of_a_given_item(): void
    {
        $parent = $this->createItem(1, true);
        $child = $this->createItem(2, true);

        $this->closureRepository->findDirectChildren($parent)->willReturn([$child]);
        $this->closureRepository->findDirectChildren($child)->willReturn([]);
        $this->itemTypeRegistry->getByEntity($child::class)->willReturn($this->createItemTypeMetadata());
        $this->itemLabelProvider->getLabel($child, null)->willReturn('About');

        $children = $this->provider->getChildren($parent, false);

        self::assertCount(1, $children);
        self::assertSame('2', $children[0]['id']);
        self::assertSame('About', $children[0]['text']);
    }

    /**
     * @test
     */
    public function it_marks_disabled_items_with_css_class(): void
    {
        $navigation = new Navigation();
        $item = $this->createItem(1, false);

        $this->closureRepository->findRootItems($navigation)->willReturn([$item]);
        $this->closureRepository->findDirectChildren($item)->willReturn([]);
        $this->itemTypeRegistry->getByEntity($item::class)->willReturn($this->createItemTypeMetadata());
        $this->itemLabelProvider->getLabel($item, null)->willReturn('Disabled');

        $tree = $this->provider->getTree($navigation, false);

        self::assertStringContainsString('item-disabled', $tree[0]['li_attr']['class']);
        self::assertSame('false', $tree[0]['a_attr']['data-enabled']);
    }

    /**
     * @test
     */
    public function it_marks_channel_hidden_items_with_css_class(): void
    {
        $navigation = new Navigation();
        $channel = $this->prophesize(ChannelInterface::class);

        $item = $this->prophesize(ItemInterface::class);
        $item->getId()->willReturn(1);
        $item->isEnabled()->willReturn(true);
        $item->hasChannel($channel->reveal())->willReturn(false);

        $this->closureRepository->findRootItems($navigation)->willReturn([$item->reveal()]);
        $this->closureRepository->findDirectChildren($item->reveal())->willReturn([]);
        $this->itemTypeRegistry->getByEntity($item->reveal()::class)->willReturn($this->createItemTypeMetadata());
        $this->itemLabelProvider->getLabel($item->reveal(), null)->willReturn('Hidden');

        $tree = $this->provider->getTree($navigation, false, $channel->reveal());

        self::assertStringContainsString('item-channel-hidden', $tree[0]['li_attr']['class']);
    }

    /**
     * @test
     */
    public function it_searches_items_and_includes_ancestor_ids(): void
    {
        $navigation = new Navigation();
        $root = $this->createItem(1, true);
        $child = $this->createItem(2, true);

        $this->closureRepository->findRootItems($navigation)->willReturn([$root]);
        $this->closureRepository->findDirectChildren($root)->willReturn([$child]);
        $this->closureRepository->findDirectChildren($child)->willReturn([]);
        $this->itemLabelProvider->getLabel($child)->willReturn('Contact Us');

        $closure = $this->prophesize(ClosureInterface::class);
        $closure->getAncestor()->willReturn($root);
        $this->closureRepository->findBy(['descendant' => $child])->willReturn([$closure->reveal()]);

        $result = $this->provider->searchItems($navigation, 'contact');

        self::assertContains('2', $result);
        self::assertContains('1', $result);
    }

    /**
     * @test
     */
    public function it_returns_empty_array_when_search_finds_no_matches(): void
    {
        $navigation = new Navigation();
        $root = $this->createItem(1, true);

        $this->closureRepository->findRootItems($navigation)->willReturn([$root]);
        $this->closureRepository->findDirectChildren($root)->willReturn([]);

        $result = $this->provider->searchItems($navigation, 'nonexistent');

        self::assertSame([], $result);
    }

    /**
     * @test
     */
    public function it_builds_recursive_tree_with_children_array(): void
    {
        $navigation = new Navigation();
        $parent = $this->createItem(1, true);
        $child = $this->createItem(2, true);

        $this->closureRepository->findRootItems($navigation)->willReturn([$parent]);
        $this->closureRepository->findDirectChildren($parent)->willReturn([$child]);
        $this->closureRepository->findDirectChildren($child)->willReturn([]);
        $this->itemTypeRegistry->getByEntity($parent::class)->willReturn($this->createItemTypeMetadata());
        $this->itemTypeRegistry->getByEntity($child::class)->willReturn($this->createItemTypeMetadata());
        $this->itemLabelProvider->getLabel($parent, null)->willReturn('Parent');
        $this->itemLabelProvider->getLabel($child, null)->willReturn('Child');

        $tree = $this->provider->getTree($navigation, true);

        self::assertCount(1, $tree);
        self::assertIsArray($tree[0]['children']);
        self::assertCount(1, $tree[0]['children']);
        self::assertSame('2', $tree[0]['children'][0]['id']);
    }

    /**
     * @return ItemInterface|ObjectProphecy<ItemInterface>
     */
    private function createItem(int $id, bool $enabled): ItemInterface
    {
        $item = new class() extends Item {
        };

        $item->setCurrentLocale('en_US');
        $item->setFallbackLocale('en_US');
        $item->setEnabled($enabled);

        $reflection = new \ReflectionClass(Item::class);
        $property = $reflection->getProperty('id');
        $property->setValue($item, $id);

        return $item;
    }

    private function createItemTypeMetadata(): ItemType
    {
        $factory = $this->prophesize(ItemFactoryInterface::class);

        return new ItemType(
            name: 'link',
            label: 'Link',
            entity: Item::class,
            form: 'App\Form\LinkType',
            template: '@SetonoSyliusNavigationPlugin/navigation/build/_form.html.twig',
            factory: $factory->reveal(),
        );
    }
}

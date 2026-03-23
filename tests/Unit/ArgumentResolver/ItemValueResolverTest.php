<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Unit\ArgumentResolver;

use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusNavigationPlugin\ArgumentResolver\ItemValueResolver;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ItemValueResolverTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<ObjectRepository<ItemInterface>> */
    private ObjectProphecy $itemRepository;

    private ItemValueResolver $resolver;

    protected function setUp(): void
    {
        $this->itemRepository = $this->prophesize(ObjectRepository::class);
        $this->resolver = new ItemValueResolver($this->itemRepository->reveal());
    }

    /**
     * @test
     */
    public function it_resolves_item_interface_argument(): void
    {
        $item = $this->prophesize(ItemInterface::class)->reveal();

        $this->itemRepository->find(42)->willReturn($item);

        $request = new Request();
        $request->attributes->set('item', '42');

        $argument = new ArgumentMetadata('item', ItemInterface::class, false, false, null);

        $result = $this->resolver->resolve($request, $argument);

        self::assertIsArray($result);
        self::assertCount(1, $result);
        self::assertSame($item, $result[0]);
    }

    /**
     * @test
     */
    public function it_returns_empty_array_when_argument_type_is_not_item_interface(): void
    {
        $request = new Request();
        $request->attributes->set('item', '42');

        $argument = new ArgumentMetadata('item', 'string', false, false, null);

        $result = $this->resolver->resolve($request, $argument);

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @test
     */
    public function it_returns_empty_array_when_item_parameter_is_not_in_request(): void
    {
        $request = new Request();

        $argument = new ArgumentMetadata('item', ItemInterface::class, false, false, null);

        $result = $this->resolver->resolve($request, $argument);

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @test
     */
    public function it_throws_not_found_exception_when_item_id_is_not_numeric(): void
    {
        $request = new Request();
        $request->attributes->set('item', 'not-a-number');

        $argument = new ArgumentMetadata('item', ItemInterface::class, false, false, null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Item id is not a valid numeric identifier');

        $this->resolver->resolve($request, $argument);
    }

    /**
     * @test
     */
    public function it_throws_not_found_exception_when_item_does_not_exist(): void
    {
        $this->itemRepository->find(999)->willReturn(null);

        $request = new Request();
        $request->attributes->set('item', '999');

        $argument = new ArgumentMetadata('item', ItemInterface::class, false, false, null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Item with id "999" not found');

        $this->resolver->resolve($request, $argument);
    }

    /**
     * @test
     */
    public function it_resolves_with_different_parameter_names(): void
    {
        $item = $this->prophesize(ItemInterface::class)->reveal();

        $this->itemRepository->find(123)->willReturn($item);

        $request = new Request();
        $request->attributes->set('customParam', '123');

        $argument = new ArgumentMetadata('customParam', ItemInterface::class, false, false, null);

        $result = $this->resolver->resolve($request, $argument);

        self::assertIsArray($result);
        self::assertCount(1, $result);
        self::assertSame($item, $result[0]);
    }
}

<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\ArgumentResolver;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusNavigationPlugin\ArgumentResolver\NavigationValueResolver;
use Setono\SyliusNavigationPlugin\Model\Navigation;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Repository\NavigationRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class NavigationValueResolverTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<NavigationRepositoryInterface> */
    private ObjectProphecy $navigationRepository;

    private NavigationValueResolver $resolver;

    protected function setUp(): void
    {
        $this->navigationRepository = $this->prophesize(NavigationRepositoryInterface::class);
        $this->resolver = new NavigationValueResolver($this->navigationRepository->reveal());
    }

    /**
     * @test
     */
    public function it_resolves_navigation_interface_argument(): void
    {
        $navigation = new Navigation();
        $navigation->setCode('test_nav');

        $this->navigationRepository->find(42)->willReturn($navigation);

        $request = new Request();
        $request->attributes->set('navigation', '42');

        $argument = new ArgumentMetadata('navigation', NavigationInterface::class, false, false, null);

        $result = $this->resolver->resolve($request, $argument);

        self::assertIsArray($result);
        self::assertCount(1, $result);
        self::assertSame($navigation, $result[0]);
    }

    /**
     * @test
     */
    public function it_returns_empty_array_when_argument_type_is_not_navigation_interface(): void
    {
        $request = new Request();
        $request->attributes->set('navigation', '42');

        $argument = new ArgumentMetadata('navigation', 'string', false, false, null);

        $result = $this->resolver->resolve($request, $argument);

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @test
     */
    public function it_returns_empty_array_when_navigation_parameter_is_not_in_request(): void
    {
        $request = new Request();
        // No navigation attribute set

        $argument = new ArgumentMetadata('navigation', NavigationInterface::class, false, false, null);

        $result = $this->resolver->resolve($request, $argument);

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @test
     */
    public function it_throws_not_found_exception_when_navigation_id_is_not_numeric(): void
    {
        $request = new Request();
        $request->attributes->set('navigation', 'not-a-number');

        $argument = new ArgumentMetadata('navigation', NavigationInterface::class, false, false, null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Navigation id is not a valid numeric identifier');

        $this->resolver->resolve($request, $argument);
    }

    /**
     * @test
     */
    public function it_throws_not_found_exception_when_navigation_does_not_exist(): void
    {
        $this->navigationRepository->find(999)->willReturn(null);

        $request = new Request();
        $request->attributes->set('navigation', '999');

        $argument = new ArgumentMetadata('navigation', NavigationInterface::class, false, false, null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Navigation with id "999" not found');

        $this->resolver->resolve($request, $argument);
    }

    /**
     * @test
     */
    public function it_resolves_with_different_parameter_names(): void
    {
        $navigation = new Navigation();
        $navigation->setCode('another_nav');

        $this->navigationRepository->find(123)->willReturn($navigation);

        $request = new Request();
        $request->attributes->set('customParam', '123');

        $argument = new ArgumentMetadata('customParam', NavigationInterface::class, false, false, null);

        $result = $this->resolver->resolve($request, $argument);

        self::assertIsArray($result);
        self::assertCount(1, $result);
        self::assertSame($navigation, $result[0]);
    }

    /**
     * @test
     */
    public function it_handles_numeric_string_ids(): void
    {
        $navigation = new Navigation();
        $navigation->setCode('numeric_nav');

        $this->navigationRepository->find(789)->willReturn($navigation);

        $request = new Request();
        $request->attributes->set('navigation', '789');

        $argument = new ArgumentMetadata('navigation', NavigationInterface::class, false, false, null);

        $result = $this->resolver->resolve($request, $argument);

        self::assertIsArray($result);
        self::assertCount(1, $result);
        self::assertSame($navigation, $result[0]);
    }
}

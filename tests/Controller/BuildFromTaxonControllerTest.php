<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests for BuildFromTaxonController
 *
 * Note: The build logic tests have been moved to NavigationBuilderTest.
 * This controller now only dispatches messages, which would require integration testing.
 * TODO: Add integration tests for message dispatching if needed.
 */
final class BuildFromTaxonControllerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_exists(): void
    {
        $this->assertTrue(class_exists(\Setono\SyliusNavigationPlugin\Controller\BuildFromTaxonController::class));
    }
}

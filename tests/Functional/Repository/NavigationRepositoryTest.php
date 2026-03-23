<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Functional\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Repository\NavigationRepositoryInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class NavigationRepositoryTest extends KernelTestCase
{
    private NavigationRepositoryInterface $repository;

    private EntityManagerInterface $entityManager;

    private LocaleInterface $locale;

    private CurrencyInterface $currency;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        $this->repository = $container->get('setono_sylius_navigation.repository.navigation');
        $this->entityManager = $container->get('doctrine.orm.entity_manager');

        $this->locale = $container->get('sylius.factory.locale')->createNew();
        $this->locale->setCode('en_US');
        $this->entityManager->persist($this->locale);

        $this->currency = $container->get('sylius.factory.currency')->createNew();
        $this->currency->setCode('USD');
        $this->entityManager->persist($this->currency);

        $this->entityManager->flush();
    }

    /**
     * @test
     */
    public function it_finds_enabled_navigation_by_code_and_channel(): void
    {
        $channel = $this->createChannel('web');
        $navigation = $this->createNavigation('main_menu', true);
        $navigation->addChannel($channel);

        $this->entityManager->flush();

        $result = $this->repository->findOneEnabledByCode('main_menu', $channel);

        self::assertNotNull($result);
        self::assertSame($navigation->getId(), $result->getId());
    }

    /**
     * @test
     */
    public function it_returns_null_when_navigation_is_disabled(): void
    {
        $channel = $this->createChannel('web_disabled');
        $navigation = $this->createNavigation('disabled_nav', false);
        $navigation->addChannel($channel);

        $this->entityManager->flush();

        $result = $this->repository->findOneEnabledByCode('disabled_nav', $channel);

        self::assertNull($result);
    }

    /**
     * @test
     */
    public function it_returns_null_when_code_does_not_match(): void
    {
        $channel = $this->createChannel('web_nomatch');
        $navigation = $this->createNavigation('existing_nav', true);
        $navigation->addChannel($channel);

        $this->entityManager->flush();

        $result = $this->repository->findOneEnabledByCode('non_existing_code', $channel);

        self::assertNull($result);
    }

    /**
     * @test
     */
    public function it_returns_null_when_navigation_does_not_belong_to_channel(): void
    {
        $channel1 = $this->createChannel('channel_one');
        $channel2 = $this->createChannel('channel_two');
        $navigation = $this->createNavigation('channel_nav', true);
        $navigation->addChannel($channel1);

        $this->entityManager->flush();

        $result = $this->repository->findOneEnabledByCode('channel_nav', $channel2);

        self::assertNull($result);
    }

    /**
     * @test
     */
    public function it_finds_correct_navigation_among_multiple(): void
    {
        $channel = $this->createChannel('web_multi');
        $nav1 = $this->createNavigation('footer', true);
        $nav1->addChannel($channel);
        $nav2 = $this->createNavigation('header', true);
        $nav2->addChannel($channel);

        $this->entityManager->flush();

        $result = $this->repository->findOneEnabledByCode('header', $channel);

        self::assertNotNull($result);
        self::assertSame($nav2->getId(), $result->getId());
    }

    private function createNavigation(string $code, bool $enabled): NavigationInterface
    {
        $factory = self::getContainer()->get('setono_sylius_navigation.factory.navigation');
        /** @var NavigationInterface $navigation */
        $navigation = $factory->createNew();
        $navigation->setCode($code);
        $navigation->setEnabled($enabled);

        $this->entityManager->persist($navigation);

        return $navigation;
    }

    private function createChannel(string $code): ChannelInterface
    {
        /** @var ChannelInterface $channel */
        $channel = self::getContainer()->get('sylius.factory.channel')->createNew();
        $channel->setCode($code);
        $channel->setName($code);
        $channel->setDefaultLocale($this->locale);
        $channel->setBaseCurrency($this->currency);

        $this->entityManager->persist($channel);
        $this->entityManager->flush();

        return $channel;
    }
}

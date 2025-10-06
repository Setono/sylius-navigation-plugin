<?php

declare(strict_types=1);

use Setono\SyliusNavigationPlugin\Tests\Application\Kernel;

require __DIR__ . '/../vendor/autoload.php';

$kernel = new Kernel('test', true);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();

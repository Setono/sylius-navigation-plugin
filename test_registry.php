<?php

use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/tests/Application/config/bootstrap.php';

$kernel = new \Tests\Setono\SyliusNavigationPlugin\Application\Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();

try {
    $registry = $container->get('Setono\SyliusNavigationPlugin\Form\Registry\ItemFormRegistryInterface');
    
    echo "Registry found!\n";
    echo "All registered forms:\n";
    var_dump($registry->all());
    
    echo "\nForm types for dropdown:\n";
    var_dump($registry->getFormTypesForDropdown());
    
    if ($registry->has('text')) {
        echo "\n'text' form is registered\n";
        echo "Form class: " . $registry->getFormClass('text') . "\n";
    } else {
        echo "\n'text' form is NOT registered\n";
    }
    
    if ($registry->has('taxon')) {
        echo "\n'taxon' form is registered\n";
        echo "Form class: " . $registry->getFormClass('taxon') . "\n";
    } else {
        echo "\n'taxon' form is NOT registered\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
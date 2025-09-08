<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Command;

use Setono\SyliusNavigationPlugin\Form\Registry\ItemFormRegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class TestRegistryCommand extends Command
{
    protected static $defaultName = 'setono:navigation:test-registry';
    
    public function __construct(
        private readonly ItemFormRegistryInterface $registry
    ) {
        parent::__construct();
    }
    
    protected function configure(): void
    {
        $this->setDescription('Test the navigation item form registry');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Navigation Item Form Registry Test');
        
        $all = $this->registry->all();
        
        if (empty($all)) {
            $io->error('No forms registered in the registry!');
            return Command::FAILURE;
        }
        
        $io->section('Registered Forms');
        foreach ($all as $name => $data) {
            $io->writeln(sprintf(
                '- Name: <info>%s</info>, Class: <comment>%s</comment>, Label: <comment>%s</comment>',
                $name,
                $data['class'],
                $data['metadata']->getLabel()
            ));
        }
        
        $io->section('Dropdown Options');
        foreach ($this->registry->getFormTypesForDropdown() as $name => $label) {
            $io->writeln(sprintf('- %s => %s', $name, $label));
        }
        
        $io->section('Testing Specific Forms');
        
        if ($this->registry->has('text')) {
            $io->success('✓ "text" form is registered');
            $io->writeln('  Class: ' . $this->registry->getFormClass('text'));
        } else {
            $io->error('✗ "text" form is NOT registered');
        }
        
        if ($this->registry->has('taxon')) {
            $io->success('✓ "taxon" form is registered');
            $io->writeln('  Class: ' . $this->registry->getFormClass('taxon'));
        } else {
            $io->error('✗ "taxon" form is NOT registered');
        }
        
        return Command::SUCCESS;
    }
}
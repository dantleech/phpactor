<?php

namespace Phpactor\Extension\ClassToFile\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Phpactor\Extension\Core\Console\Dumper\DumperRegistry;
use Phpactor\Extension\Core\Console\Handler\FormatHandler;
use Phpactor\Extension\ClassToFile\Application\FileInfo;

class FileInfoCommand extends Command
{
    private $infoForOffset;
    private $dumperRegistry;

    public function __construct(
        FileInfo $infoForOffset,
        DumperRegistry $dumperRegistry
    ) {
        parent::__construct();
        $this->infoForOffset = $infoForOffset;
        $this->dumperRegistry = $dumperRegistry;
    }

    public function configure()
    {
        $this->setName('file:info');
        $this->setDescription('Return information about given file');
        $this->addArgument('path', InputArgument::REQUIRED, 'Source path or FQN');
        FormatHandler::configure($this);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $info = $this->infoForOffset->infoForFile(
            $input->getArgument('path')
        );

        $format = $input->getOption('format');
        $this->dumperRegistry->get($format)->dump($output, $info);
    }
}

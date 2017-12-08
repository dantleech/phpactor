<?php

namespace Phpactor\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Phpactor\Application\FileInfo;
use Phpactor\Console\Dumper\DumperRegistry;

class FileInfoCommand extends Command
{
    /**
     * @var FileInfo
     */
    private $infoForOffset;

    /**
     * @var DumperRegistry
     */
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
        Handler\FormatHandler::configure($this);
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

<?php

namespace Phpactor\Extension\Core\Command;

use Phpactor\FilePathResolver\Expanders;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Phpactor\Extension\Core\Console\Dumper\DumperRegistry;
use Symfony\Component\Console\Input\InputOption;
use Phpactor\Config\Paths;
use Symfony\Component\Console\Terminal;

class ConfigDumpCommand extends Command
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var DumperRegistry
     */
    private $registry;

    /**
     * @var Paths
     */
    private $paths;

    /**
     * @var Expanders
     */
    private $expanders;

    public function __construct(
        array $config,
        DumperRegistry $registry,
        Paths $paths,
        Expanders $expanders
    ) {
        parent::__construct();

        $this->config = $config;
        $this->registry = $registry;
        $this->paths = $paths;
        $this->expanders = $expanders;
    }

    public function configure()
    {
        $this->setDescription('Show loaded config files and dump current configuration.');
        $this->addOption('config-only', null, InputOption::VALUE_NONE, 'Do not output configuration file locations');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (false === $input->getOption('config-only')) {
            $this->dumpMetaInformation($output);
        }

        $output->writeln(json_encode($this->config, JSON_PRETTY_PRINT));
    }

    private function dumpMetaInformation(OutputInterface $output)
    {
        $output->writeln('<info>Config files:</>');
        $output->write(PHP_EOL);
        foreach ($this->paths->configFiles() as $i => $file) {
            if (!file_exists($file)) {
                $output->write('  [✖]');
            } else {
                $output->write('  [<info>✔</>]');
            }
            $output->writeln(' ' .$file);
        }
        
        $output->write(PHP_EOL);
        $output->writeln('<info>File path tokens:</info>');
        $output->write(PHP_EOL);
        foreach ($this->expanders->toArray() as $tokenName => $value) {
            $output->writeln(sprintf('  <comment>%%%s%%</>: %s', $tokenName, $value));
        }
        $terminal = new Terminal();
        $output->write(PHP_EOL);
        $output->writeln(str_repeat('-', $terminal->getWidth()));
        $output->write(PHP_EOL);
    }
}

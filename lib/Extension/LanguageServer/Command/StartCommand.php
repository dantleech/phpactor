<?php

namespace Phpactor\Extension\LanguageServer\Command;

use Phpactor\LanguageServer\Core\Server;
use Phpactor\LanguageServer\LanguageServerBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends Command
{
    /**
     * @var LanguageServerBuilder
     */
    private $languageServerBuilder;

    public function __construct(LanguageServerBuilder $languageServerBuilder)
    {
        parent::__construct();
        $this->languageServerBuilder = $languageServerBuilder;
    }

    protected function configure()
    {
        $this->setName('lsp:start');
        $this->addOption('stdio', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $builder = $this->languageServerBuilder;
        $builder->tcpServer();

        if ($input->getOption('stdio')) {
            $builder->stdIoServer();
        }

        $builder->build()->start();
    }


}
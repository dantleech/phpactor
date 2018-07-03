<?php

namespace Phpactor\Extension\Core\Rpc;

use Phpactor\Container\Schema;
use Phpactor\Extension\Rpc\Handler;
use Phpactor\Extension\Rpc\Response\InformationResponse;

class ConfigHandler implements Handler
{
    const CONFIG = 'config';

    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function name(): string
    {
        return self::CONFIG;
    }

    public function configure(Schema $schema): void
    {
    }

    public function handle(array $arguments)
    {
        return InformationResponse::fromString(json_encode($this->config, JSON_PRETTY_PRINT));
    }
}

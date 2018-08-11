<?php

namespace Phpactor\Extension\ClassToFile\Rpc;

use Phpactor\Extension\ClassToFile\Application\FileInfo;
use Phpactor\Extension\Rpc\Handler\AbstractHandler;
use Phpactor\Extension\Rpc\Response\ReturnResponse;
use Phpactor\MapResolver\Resolver;

class FileInfoHandler extends AbstractHandler
{
    const NAME = 'file_info';
    const PARAM_PATH = 'path';

    public function __construct(FileInfo $fileInfo)
    {
        $this->fileInfo = $fileInfo;
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function configure(Resolver $resolver)
    {
        $resolver->setRequired([
            self::PARAM_PATH,
        ]);
    }

    public function handle(array $arguments)
    {
        $fileInfo = $this->fileInfo->infoForFile($arguments[self::PARAM_PATH]);

        return ReturnResponse::fromValue($fileInfo);
    }
}

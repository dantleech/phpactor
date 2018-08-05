<?php

namespace Phpactor\Extension\Rpc\Response;

use Phpactor\Extension\Rpc\Diff\TextEditBuilder;
use Phpactor\Extension\Rpc\Response;

class UpdateFileSourceResponse implements Response
{
    /**
     * @var string
     */
    private $replacementSource;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $originalSource;

    /**
     * @var TextEditBuilder
     */
    private $textEditBuilder;

    private function __construct(string $path, string $originalSource, string $replacementSource)
    {
        $this->replacementSource = $replacementSource;
        $this->path = $path;
        $this->originalSource = $originalSource;

        // TODO: This should be a service
        $this->textEditBuilder = new TextEditBuilder();
    }

    public static function fromPathOldAndNewSource(string $path, string $originalSource, string $replacementSource)
    {
        return new self($path, $originalSource, $replacementSource);
    }

    public function name(): string
    {
        return 'update_file_source';
    }

    public function parameters(): array
    {
        return [
            'path' => $this->path,
            'source' => $this->replacementSource,
            'edits' => $this->textEditBuilder->calculateTextEdits($this->originalSource, $this->replacementSource),
        ];
    }

    public function replacementSource(): string
    {
        return $this->replacementSource;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function newSource(): string
    {
        return $this->newSource;
    }
}
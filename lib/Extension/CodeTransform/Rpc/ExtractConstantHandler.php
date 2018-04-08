<?php

namespace Phpactor\Extension\CodeTransform\Rpc;

use Phpactor\CodeTransform\Domain\Refactor\ExtractConstant;
use Phpactor\Extension\Rpc\Response\Input\TextInput;
use Phpactor\Extension\Rpc\Response\ReplaceFileSourceResponse;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\Extension\Rpc\Handler\AbstractHandler;

class ExtractConstantHandler extends AbstractHandler
{
    const NAME = 'extract_constant';
    const PARAM_CONSTANT_NAME = 'constant_name';
    const PARAM_OFFSET = 'offset';
    const PARAM_SOURCE = 'source';
    const PARAM_PATH = 'path';
    const PARAM_CONSTANT_NAME_SUGGESTION = 'constant_name_suggestion';
    const INPUT_LABEL_NAME = 'Constant name: ';

    /**
     * @var ExtractConstant
     */
    private $extractConstant;

    public function __construct(ExtractConstant $extractConstant)
    {
        $this->extractConstant = $extractConstant;
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function defaultParameters(): array
    {
        return [
            self::PARAM_PATH => null,
            self::PARAM_SOURCE => null,
            self::PARAM_OFFSET => null,
            self::PARAM_CONSTANT_NAME => null,
            self::PARAM_CONSTANT_NAME_SUGGESTION => null,
        ];
    }

    public function handle(array $arguments)
    {
        $this->requireInput(TextInput::fromNameLabelAndDefault(
            self::PARAM_CONSTANT_NAME,
            self::INPUT_LABEL_NAME,
            $arguments[self::PARAM_CONSTANT_NAME_SUGGESTION] ?: ''
        ));

        if ($this->hasMissingArguments($arguments)) {
            return $this->createInputCallback($arguments);
        }

        $sourceCode = $this->extractConstant->extractConstant(
            SourceCode::fromStringAndPath($arguments[self::PARAM_SOURCE], $arguments[self::PARAM_PATH]),
            $arguments[self::PARAM_OFFSET],
            $arguments[self::PARAM_CONSTANT_NAME]
        );

        return ReplaceFileSourceResponse::fromPathAndSource(
            $sourceCode->path(),
            (string) $sourceCode
        );
    }
}

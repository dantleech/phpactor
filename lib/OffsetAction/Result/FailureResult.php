<?php

namespace Phpactor\OffsetAction\Result;

use Phpactor\OffsetAction\Result;
use Phpactor\OffsetAction\Result\FailureResult;

final class FailureResult implements Result
{
    private $reason;

    private function __construct($reason)
    {
        $this->reason = $reason;
    }

    public static function withReason(string $reason): FailureResult
    {
         return new self($reason);
    }

    public function action(): string
    {
        return 'fail';
    }

    public function arguments(): array
    {
        return [
            'reason' => $this->reason
        ];
    }

    public function __toString()
    {
        return $this->reason;
    }
}

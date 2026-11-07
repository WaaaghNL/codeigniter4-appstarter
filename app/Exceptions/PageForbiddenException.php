<?php

declare(strict_types=1);

namespace App\Exceptions;

use CodeIgniter\Exceptions\DebugTraceableTrait;
use CodeIgniter\Exceptions\HTTPExceptionInterface;
use CodeIgniter\Exceptions\RuntimeException;

class PageForbiddenException extends RuntimeException implements HTTPExceptionInterface
{
    use DebugTraceableTrait;

    protected $code = 403;

    public static function forPageForbidden(?string $message = null): self
    {
        return new self($message ?? lang('HTTP.forbidden'));
    }
}

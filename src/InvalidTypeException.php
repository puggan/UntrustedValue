<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue;

/**
 * Thrown when an untrusted value has the right type but does not pass a filter.
 */
class InvalidTypeException extends UntrustedValueException
{
    /**
     * @param string $message Human-readable description of the error.
     * @param string $filter The filter the value failed, e.g. "email".
     * @param string $path Where in the input the value was found, e.g. "user.email".
     */
    public function __construct(
        string $message,
        public readonly string $filter,
        string $path,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $path, $previous);
    }

    /**
     * @return array{message: string, filter: string, path: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'message' => $this->getMessage(),
            'filter' => $this->filter,
            'path' => $this->path,
        ];
    }
}

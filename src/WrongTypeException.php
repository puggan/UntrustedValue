<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue;

/**
 * Thrown when an untrusted value is not of the expected type.
 */
class WrongTypeException extends UntrustedValueException
{
    /**
     * @param string $message Human-readable description of the error.
     * @param string $expectedType The type that was expected, e.g. "int".
     * @param string $givenType The type that was actually given, e.g. "string".
     * @param string $path Where in the input the value was found, e.g. "user.age".
     */
    public function __construct(
        string $message,
        public readonly string $expectedType,
        public readonly string $givenType,
        string $path,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $path, $previous);
    }

    /**
     * @return array{message: string, expectedType: string, givenType: string, path: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'message' => $this->getMessage(),
            'expectedType' => $this->expectedType,
            'givenType' => $this->givenType,
            'path' => $this->path,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue;

/**
 * Parent of every exception this library throws about an untrusted value, so callers can catch
 * them all with one catch block.
 */
abstract class UntrustedValueException extends \RuntimeException implements \JsonSerializable
{
    /**
     * @param string $message Human-readable description of the error.
     * @param string $path Where in the input the value was found, e.g. "user.age".
     */
    public function __construct(
        string $message,
        /** @readonly */
        public string $path,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return array<string, string>
     */
    abstract public function jsonSerialize(): array;
}

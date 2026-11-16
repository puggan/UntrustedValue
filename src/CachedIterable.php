<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue;

/**
 * A repeatable view of a generator, which by itself can only be iterated once. The generator is
 * read at most once, lazily, into a cache, and every iteration replays the cache, reading further
 * from the generator only when it gets past what is cached. So an infinite generator works as long
 * as each iteration stops, and two loops over it, even nested ones, share what has been read.
 *
 * Keys are kept as the generator yields them, of any type and repeated or not. When the generator
 * throws, the same exception is thrown at the same position in every iteration. What has been read
 * stays in memory; for a large source that only needs streaming, use a LazyIterable with a factory
 * that opens the source again for each iteration instead.
 *
 * The constructor of every untrusted value wraps a bare generator in this, so values stay
 * immutable.
 *
 * @template TKey
 * @template TItem
 *
 * @implements \IteratorAggregate<TKey, TItem>
 */
final class CachedIterable implements \IteratorAggregate
{
    /** @var list<TKey> */
    private array $keys = [];

    /** @var list<TItem> */
    private array $items = [];

    private ?\Throwable $failure = null;

    private bool $started = false;

    private bool $done = false;

    /**
     * @param \Generator<TKey, TItem, mixed, mixed> $source Read at most once, as far as iterations need.
     */
    public function __construct(
        private readonly \Generator $source,
    ) {}

    /**
     * The cached items, then the rest of the generator, read into the cache as they are reached.
     *
     * @return \Generator<TKey, TItem, mixed, void>
     */
    public function getIterator(): \Generator
    {
        $position = 0;
        while (true) {
            if ($position < count($this->items)) {
                yield $this->keys[$position] => $this->items[$position];
                $position++;

                continue;
            }

            if (!$this->readNext()) {
                return;
            }
        }
    }

    /**
     * Reads the generator's next item into the cache. False when it has no more.
     *
     * @throws \Throwable What the generator throws, now and in every later iteration.
     */
    private function readNext(): bool
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }

        if ($this->done) {
            return false;
        }

        try {
            if ($this->started) {
                $this->source->next();
            }
            $this->started = true;

            if (!$this->source->valid()) {
                $this->done = true;

                return false;
            }

            $this->keys[] = $this->source->key();
            $this->items[] = $this->source->current();
        } catch (\Throwable $exception) {
            $this->failure = $exception;

            throw $exception;
        }

        return true;
    }
}

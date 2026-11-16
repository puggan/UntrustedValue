<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue;

/**
 * An immutable, lazy, repeatable sequence: it holds a factory and starts a fresh generator from it
 * each time it is iterated. A bare generator can only be iterated once, so filter() and map() wrap
 * their generators in this instead, keeping their results as immutable as every other value.
 *
 * Each iteration runs the factory again, so callbacks given to filter() or map() run again too.
 *
 * @template TKey
 * @template-covariant TItem
 *
 * @implements \IteratorAggregate<TKey, TItem>
 */
final class LazyIterable implements \IteratorAggregate
{
    /**
     * @param \Closure(): iterable<TKey, TItem> $factory Called for each iteration, returning a new generator or other iterable.
     */
    public function __construct(
        private readonly \Closure $factory,
    ) {}

    /**
     * A new generator over what the factory returns, so every iteration starts from the beginning.
     *
     * @return \Generator<TKey, TItem, mixed, void>
     */
    public function getIterator(): \Generator
    {
        yield from ($this->factory)();
    }
}

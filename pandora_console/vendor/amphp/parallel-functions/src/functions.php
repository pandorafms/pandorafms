<?php

namespace Amp\ParallelFunctions;

use Amp\MultiReasonException;
use Amp\Parallel\Sync\SerializationException;
use Amp\Parallel\Worker\Pool;
use Amp\Promise;
use Opis\Closure\SerializableClosure;
use function Amp\call;
use function Amp\Parallel\Worker\enqueue;
use function Amp\Promise\any;

/**
 * Parallelizes a callable.
 *
 * @param callable  $callable Callable to parallelize.
 * @param Pool|null $pool Worker pool instance to use or null to use the global pool.
 *
 * @return callable Callable executing in another thread / process.
 * @throws SerializationException If the passed callable is not safely serializable.
 */
function parallel(callable $callable, Pool $pool = null): callable {
    if ($callable instanceof \Closure) {
        $callable = new SerializableClosure($callable);
    }

    try {
        $callable = \serialize($callable);
    } catch (\Throwable $e) {
        throw new SerializationException("Unsupported callable: " . $e->getMessage(), 0, $e);
    }

    return function (...$args) use ($pool, $callable): Promise {
        $task = new Internal\SerializedCallableTask($callable, $args);
        return $pool ? $pool->enqueue($task) : enqueue($task);
    };
}

/**
 * Parallel version of array_map, but with an argument order consistent with the filter function.
 *
 * @param array     $array
 * @param callable  $callable
 * @param Pool|null $pool Worker pool instance to use or null to use the global pool.
 *
 * @return Promise Resolves to the result once the operation finished.
 * @throws \Error If the passed callable is not safely serializable.
 */
function parallelMap(array $array, callable $callable, Pool $pool = null): Promise {
    return call(function () use ($array, $callable, $pool) {
        // Amp\Promise\any() guarantees that all operations finished prior to resolving. Amp\Promise\all() doesn't.
        // Additionally, we return all errors as a MultiReasonException instead of throwing on the first error.
        list($errors, $results) = yield any(\array_map(parallel($callable, $pool), $array));

        if ($errors) {
            throw new MultiReasonException($errors);
        }

        return $results;
    });
}

/**
 * Parallel version of array_filter.
 *
 * @param array     $array
 * @param callable  $callable
 * @param int       $flag
 * @param Pool|null $pool Worker pool instance to use or null to use the global pool.
 *
 * @return Promise
 * @throws \Error If the passed callable is not safely serializable.
 */
function parallelFilter(array $array, callable $callable = null, int $flag = 0, Pool $pool = null): Promise {
    return call(function () use ($array, $callable, $flag, $pool) {
        if ($callable === null) {
            if ($flag === \ARRAY_FILTER_USE_BOTH || $flag === \ARRAY_FILTER_USE_KEY) {
                throw new \Error('A valid $callable must be provided if $flag is set.');
            }

            $callable = function ($value) {
                return (bool) $value;
            };
        }

        // Amp\Promise\any() guarantees that all operations finished prior to resolving. Amp\Promise\all() doesn't.
        // Additionally, we return all errors as a MultiReasonException instead of throwing on the first error.
        if ($flag === \ARRAY_FILTER_USE_BOTH) {
            list($errors, $results) = yield any(\array_map(parallel($callable, $pool), $array, \array_keys($array)));
        } elseif ($flag === \ARRAY_FILTER_USE_KEY) {
            list($errors, $results) = yield any(\array_map(parallel($callable, $pool), \array_keys($array)));
        } else {
            list($errors, $results) = yield any(\array_map(parallel($callable, $pool), $array));
        }

        if ($errors) {
            throw new MultiReasonException($errors);
        }

        foreach ($array as $key => $arg) {
            if (!$results[$key]) {
                unset($array[$key]);
            }
        }

        return $array;
    });
}

<?php

namespace Krak\Fun;

// ACCESS
/**
 * @param object $data
 * @param mixed ...$optionalArgs
 * @return mixed
 * @psalm-pure
 */
function method(string $name, $data, ...$optionalArgs) {
    /** @psalm-suppress MixedMethodCall */
    return $data->{$name}(...$optionalArgs);
}

/**
 * @template TElse
 * @param object $data
 * @param TElse $else
 * @return mixed|TElse
 * @psalm-pure
 */
function prop(string $key, $data, $else = null) {
    return \property_exists($data, $key) ? $data->{$key} : $else;
}

/**
 * @template TData as array
 * @template TKey as array-key
 * @template TElse
 * @param TKey $key
 * @param TData $data
 * @param TElse $else
 * @return TData[TKey]|TElse
 * @psalm-suppress MixedReturnStatement
 * @psalm-pure
 */
function index($key, array $data, $else = null) {
    return \array_key_exists($key, $data) ? $data[$key] : $else;
}
/**
 * @template TValue
 * @template TData as object
 * @param TValue $value
 * @param TData $data
 * @return TData
 */
function setProp(string $key, $value, $data) {
    $data->{$key} = $value;
    return $data;
}

/**
 * @template TValue
 * @template TData as array
 * @param array-key $key
 * @param TValue $value
 * @param TData $data
 * @return TData
 */
function setIndex($key, $value, array $data) {
    $data[$key] = $value;
    return $data;
}

/**
 * @param list<string> $keys
 * @param mixed $value
 */
function setIndexIn(array $keys, $value, array $data): array {
    return \Krak\Fun\updateIndexIn(
        $keys,
        /** @return mixed */
        function() use ($value) {
            return $value;
        },
        $data
    );
}

/**
 * @param string[] $props
 * @param object $obj
 * @param mixed $else
 * @return mixed
 */
function propIn(array $props, /* object */ $obj, $else = null) {
    foreach ($props as $prop) {
        if (!\is_object($obj) || !\property_exists($obj, $prop)) {
            return $else;
        }

        /** @psalm-suppress MixedAssignment */
        $obj = $obj->{$prop};
    }

    return $obj;
}

/**
 * @param string[] $keys
 * @param mixed $else
 * @return mixed
 */
function indexIn(array $keys, array $data, $else = null) {
    foreach ($keys as $part) {
        if (!\is_array($data) || !\array_key_exists($part, $data)) {
            return $else;
        }

        /** @psalm-suppress MixedAssignment */
        $data = $data[$part];
    }

    return $data;
}

/**
 * @param list<array-key> $keys
 */
function hasIndexIn(array $keys, array $data): bool {
    foreach ($keys as $key) {
        if (!\is_array($data) || !\array_key_exists($key, $data)) {
            return false;
        }
        /** @psalm-suppress MixedAssignment */
        $data = $data[$key];
    }

    return true;
}

/**
 * @param list<array-key> $keys
 * @param callable(mixed): mixed $update
 * @return array
 */
function updateIndexIn(array $keys, callable $update, array $data): array {
    $curData = &$data;
    foreach (\array_slice($keys, 0, -1) as $key) {
        if (!is_array($curData) || !\array_key_exists($key, $curData)) {
            throw new \RuntimeException('Could not updateIn because the keys ' . \implode(' -> ', $keys) . ' could not be found.');
        }
        /** @psalm-suppress MixedAssignment */
        $curData = &$curData[$key];
    }

    $lastKey = $keys[count($keys) - 1];
    /** @psalm-suppress MixedAssignment */
    $curData[$lastKey] = $update($curData[$lastKey] ?? null);

    return $data;
}

// UTILITY

/**
 * @template TObj as object
 * @param TObj $obj
 * @return TObj
 */
function assign($obj, iterable $iter) {
    /** @psalm-suppress MixedAssignment */
    foreach ($iter as $key => $value) {
        $obj->{$key} = $value;
    }
    return $obj;
}

/**
 * @param iterable<string> $iter
 */
function join(string $sep, iterable $iter): string {
    return \Krak\Fun\reduce(function(string $acc, string $v) use ($sep): string {
        return $acc ? $acc . $sep . $v : $v;
    }, $iter, "");
}

/**
 * @template T
 * @param class-string<T> $className
 * @param mixed ...$args
 * @return T
 */
function construct(string $className, ...$args) {
    return new $className(...$args);
}

/**
 * @template TReturn
 * @param callable(...mixed): TReturn $fn
 * @return TReturn
 */
function spread(callable $fn, array $data) {
    return $fn(...$data);
}

/**
 * @param mixed $value
 * @never-returns
 */
function dd($value, callable $dump = null, callable $die = null): void {
    $dump = $dump ?: (function_exists('dump') ? 'dump' : 'var_dump');
    $dump($value);
    ($die ?? function() { die; })();
}

// SLICING

/**
 * @template TValue
 * @param callable(TValue): bool $predicate
 * @param iterable<TValue> $iter
 * @return iterable<TValue>
 */
function takeWhile(callable $predicate, iterable $iter): iterable {
    foreach ($iter as $k => $v) {
        if ($predicate($v)) {
            yield $k => $v;
        } else {
            return;
        }
    }
}

/**
 * @template TValue
 * @param callable(TValue): bool $predicate
 * @param iterable<TValue> $iter
 * @return iterable<TValue>
 */
function dropWhile(callable $predicate, iterable $iter): iterable {
    $stillDropping = true;
    foreach ($iter as $k => $v) {
        if ($stillDropping && $predicate($v)) {
            continue;
        } else if ($stillDropping) {
            $stillDropping = false;
        }

        yield $k => $v;
    }
}

/**
 * @template TValue
 * @param iterable<TValue> $iter
 * @return iterable<TValue>
 */
function take(int $num, iterable $iter): iterable {
    return \Krak\Fun\slice(0, $iter, $num);
}

/**
 * @template TValue
 * @param iterable<TValue> $iter
 * @return iterable<TValue>
 */
function drop(int $num, iterable $iter): iterable {
    return \Krak\Fun\slice($num, $iter);
}

/**
 * @template TValue
 * @param iterable<TValue> $iter
 * @param int|float $length
 * @return iterable<TValue>
 */
function slice(int $start, iterable $iter, $length = INF): iterable {
    assert($start >= 0);

    $i = 0;
    $end = $start + $length - 1;
    foreach ($iter as $k => $v) {
        if ($start <= $i && $i <= $end) {
            yield $k => $v;
        }

        $i += 1;
        if ($i > $end) {
            return;
        }
    }
}

/**
 * @template TValue
 * @param iterable<TValue> $iter
 * @return ?TValue
 */
function head(iterable $iter) {
    foreach ($iter as $v) {
        return $v;
    }
}

/**
 * @template TValue
 * @param iterable<TValue> $iter
 * @return iterable<array<TValue>>
 */
function chunk(int $size, iterable $iter): iterable {
    assert($size > 0);

    $chunk = [];
    foreach ($iter as $v) {
        $chunk[] = $v;
        if (\count($chunk) == $size) {
            yield $chunk;
            $chunk = [];
        }
    }

    if ($chunk) {
        yield $chunk;
    }
}

/**
 * @template TValue
 * @template TKey as array-key
 * @param callable(TValue): TKey $fn
 * @param iterable<TValue> $iter
 * @return iterable<array<TValue>>
 */
function chunkBy(callable $fn, iterable $iter, ?int $maxSize = null): iterable {
    assert($maxSize === null || $maxSize > 0);
    $group = [];
    $groupKey = null;
    foreach ($iter as $v) {
        $curGroupKey = $fn($v);
        $shouldYieldGroup = ($groupKey !== null && $groupKey !== $curGroupKey)
            || ($maxSize !== null && \count($group) >= $maxSize);
        if ($shouldYieldGroup) {
            yield $group;
            $group = [];
        }

        $group[] = $v;
        $groupKey = $curGroupKey;
    }

    if (\count($group)) {
        yield $group;
    }
}

/**
 * @template TValue
 * @template TKey as array-key
 * @param callable(TValue): TKey $fn
 * @param iterable<TValue> $iter
 * @return iterable<array<TValue>>
 */
function groupBy(callable $fn, iterable $iter, ?int $maxSize = null): iterable {
    return \Krak\Fun\chunkBy($fn, $iter, $maxSize);
}

// GENERATORS

/** @return iterable<int> */
function range(int $start, int $end, ?int $step = null) {
    if ($start == $end) {
        yield $start;
    } else if ($start < $end) {
        $step = $step ?: 1;
        if ($step <= 0) {
            throw new \InvalidArgumentException('Step must be greater than 0.');
        }
        for ($i = $start; $i <= $end; $i += $step) {
            yield $i;
        }
    } else {
        $step = $step ?: -1;
        if ($step >= 0) {
            throw new \InvalidArgumentException('Step must be less than 0.');
        }
        for ($i = $start; $i >= $end; $i += $step) {
            yield $i;
        }
    }
}

// OPERATORS

/**
 * @param mixed $b
 * @param mixed $a
 * @return mixed
 */
function op(string $op, $b, $a) {
    switch ($op) {
    case '==':
    case 'eq':
        return $a == $b;
    case '!=':
    case 'neq':
        return $a != $b;
    case '===':
        return $a === $b;
    case '!==':
        return $a !== $b;
    case '>':
    case 'gt':
        return $a > $b;
    case '>=':
    case 'gte':
        return $a >= $b;
    case '<':
    case 'lt':
        return $a < $b;
    case '<=':
    case 'lte':
        return $a <= $b;
    case '+':
        /** @psalm-suppress MixedOperand */
        return $a + $b;
    case '-':
        /** @psalm-suppress MixedOperand */
        return $a - $b;
    case '*':
        /** @psalm-suppress MixedOperand */
        return $a * $b;
    case '**':
        /** @psalm-suppress MixedOperand */
        return $a ** $b;
    case '/':
        /** @psalm-suppress MixedOperand */
        return $a / $b;
    case '%':
        /** @psalm-suppress MixedOperand */
        return $a % $b;
    case '.':
        /** @psalm-suppress MixedOperand */
        return $a . $b;
    default:
        throw new \LogicException('Invalid operator '.$op);
    }
}

/**
 * @template T
 * @param callable(T): bool ...$fns
 */
function andf(callable ...$fns): callable {
    return
    /** @param T $el */
    function($el) use ($fns): bool {
        foreach ($fns as $fn) {
            if (!$fn($el)) {
                return false;
            }
        }
        return true;
    };
}

/**
 * @template T
 * @param callable(T): bool ...$fns
 */
function orf(callable ...$fns): callable {
    return
    /** @param T $el */
    function($el) use ($fns) {
        foreach ($fns as $fn) {
            if ($fn($el)) {
                return true;
            }
        }
        return false;
    };
}

/**
 * @template T
 * @param iterable<T> ...$iters
 * @return iterable<T>
 */
function chain(iterable ...$iters) {
    foreach ($iters as $iter) {
        foreach ($iter as $k => $v) {
            yield $k => $v;
        }
    }
}

function zip(iterable ...$iters): \Iterator {
    if (count($iters) == 0) {
        return;
    }

    $iters = \array_map('Krak\Fun\iter', $iters);

    while (true) {
        $tup = [];
        foreach ($iters as $iter) {
            if (!$iter->valid()) {
                return;
            }
            /** @psalm-suppress MixedAssignment */
            $tup[] = $iter->current();
            $iter->next();
        }
        yield $tup;
    }
}

/**
 * @template A
 * @template B
 * @param iterable<A> $iterA
 * @param iterable<B> $iterB
 * @return iterable<array{0: A, 1: B}>
 */
function zip2(iterable $iterA, iterable $iterB): iterable {
    return zip($iterA, $iterB);
}

/**
 * @template A
 * @template B
 * @template C
 * @param iterable<A> $iterA
 * @param iterable<B> $iterB
 * @param iterable<C> $iterC
 * @return iterable<array{0: A, 1: B, 2: C}>
 */
function zip3(iterable $iterA, iterable $iterB, iterable $iterC): iterable {
    return zip($iterA, $iterB, $iterC);
}

/**
 * @template A
 * @template B
 * @template C
 * @template D
 * @param iterable<A> $iterA
 * @param iterable<B> $iterB
 * @param iterable<C> $iterC
 * @param iterable<D> $iterD
 * @return iterable<array{0: A, 1: B, 2: C, 3: D}>
 */
function zip4(iterable $iterA, iterable $iterB, iterable $iterC, iterable $iterD): iterable {
    return zip($iterA, $iterB, $iterC, $iterD);
}

/**
 * @template A
 * @template B
 * @template C
 * @template D
 * @template E
 * @param iterable<A> $iterA
 * @param iterable<B> $iterB
 * @param iterable<C> $iterC
 * @param iterable<D> $iterD
 * @param iterable<E> $iterE
 * @return iterable<array{0: A, 1: B, 2: C, 3: D, 4: E}>
 */
function zip5(iterable $iterA, iterable $iterB, iterable $iterC, iterable $iterD, iterable $iterE): iterable {
    return zip($iterA, $iterB, $iterC, $iterD, $iterE);
}

/**
 * @template T
 * @template U
 * @param callable(T): iterable<U> $map
 * @param iterable<T> $iter
 * @return iterable<U>
 */
function flatMap(callable $map, iterable $iter): iterable {
    foreach ($iter as $k => $v) {
        foreach ($map($v) as $k => $v) {
            yield $k => $v;
        }
    }
}

/**
 * @param int|float $levels
 */
function flatten(iterable $iter, $levels = INF): iterable {
    if ($levels == 0) {
        yield from $iter;
    } else if ($levels == 1) {
        /** @psalm-suppress MixedAssignment */
        foreach ($iter as $k => $v) {
            if (\is_iterable($v)) {
                foreach ($v as $k1 => $v1) {
                    yield $k1 => $v1;
                }
            } else {
                yield $k => $v;
            }
        }
    } else {
        /** @psalm-suppress MixedAssignment */
        foreach ($iter as $k => $v) {
            if (\is_iterable($v)) {
                foreach (flatten($v, $levels - 1) as $k1 => $v1) {
                    yield $k1 => $v1;
                }
            } else {
                yield $k => $v;
            }
        }
    }
}

/**
 * @template T
 * @return iterable<array<T>>
 */
function product(iterable ...$iters): iterable {
    if (count($iters) === 0) {
        yield from [];
        return;
    }
    if (count($iters) === 1) {
        yield from \Krak\Fun\map(
            /**
             * @template T
             * @param T $v
             * @return list<T>
             */
            function($v) { return [$v]; },
            $iters[0]
        );
        return;
    }

    /** @psalm-suppress MixedAssignment */
    foreach ($iters[0] as $value) {
        yield from \Krak\Fun\map(function(array $tup) use ($value): array {
            array_unshift($tup, $value);
            return $tup;
        }, \Krak\Fun\product(...\array_slice($iters, 1)));
    }
}

/**
 * @template TValue
 * @template TReturn
 * @param callable(TValue): bool $if
 * @param callable(TValue): TReturn $then
 * @param TValue $value
 * @return TReturn|TValue
 */
function when(callable $if, callable $then, $value) {
    return $if($value) ? $then($value) : $value;
}

/**
 * @template V
 * @template K
 * @param iterable<K, V> $iter
 * @return iterable<array{0: K, 1: V}>
 */
function toPairs(iterable $iter): iterable {
    foreach ($iter as $key => $val) {
        yield [$key, $val];
    }
}

/**
 * @template V
 * @template K
 * @param iterable<array{0: K, 1: V}> $iter
 * @return iterable<K, V>
 */
function fromPairs(iterable $iter): iterable {
    foreach ($iter as list($key, $val)) {
        yield $key => $val;
    }
}

/**
 * @template K as array-key
 * @param iterable<K> $fields
 */
function pick(iterable $fields, array $data): array {
    $pickedData = [];
    foreach ($fields as $field) {
        /** @psalm-suppress MixedAssignment */
        $pickedData[$field] = $data[$field] ?? null;
    }
    return $pickedData;
}

/**
 * @param callable(array{0: array-key, 1: mixed}): bool $pick
 */
function pickBy(callable $pick, array $data): array {
    $pickedData = [];
    /** @psalm-suppress MixedAssignment */
    foreach ($data as $key => $value) {
        if ($pick([$key, $value])) {
            $pickedData[$key] = $value;
        }
    }
    return $pickedData;
}

function within(array $fields, iterable $iter): iterable {
    return \Krak\Fun\filterKeys(\Krak\Fun\Curried\inArray($fields), $iter);
}
function without(array $fields, iterable $iter): iterable {
    return \Krak\Fun\filterKeys(\Krak\Fun\Curried\not(\Krak\Fun\Curried\inArray($fields)), $iter);
}

/**
 * @template T
 * @param iterable<T|null> $iter
 * @return iterable<T>
 */
function compact(iterable $iter): iterable {
    foreach ($iter as $key => $val) {
        if ($val !== null) {
            yield $key => $val;
        }
    }
}

/**
 * @template T
 * @template K as array-key
 * @param iterable<K, T|null> $iter
 * @return array<K, T>
 */
function arrayCompact(iterable $iter): array {
    $vals = [];
    foreach ($iter as $key => $val) {
        if ($val !== null) {
            $vals[$key] = $val;
        }
    }
    return $vals;
}

/**
 * @template T
 * @template U
 * @param iterable<T> $iter
 * @param U $padValue
 * @return iterable<T|U>
 */
function pad(int $size, iterable $iter, $padValue = null): iterable {
    $i = 0;
    foreach ($iter as $key => $value) {
        yield $value;
        $i += 1;
    }

    if ($i >= $size) {
        return;
    }

    foreach (\Krak\Fun\range($i, $size - 1) as $index) {
        yield $padValue;
    }
}


// ALIASES

/**
 * @template T
 * @param array<T> $set
 * @param T $item
 */
function inArray(array $set, $item): bool {
    return \in_array($item, $set);
}

/**
 * @template T
 * @template U
 * @param callable(T): U $predicate
 * @param iterable<T> $iter
 * @return array<array-key, U>
 */
function arrayMap(callable $fn, iterable $data): array {
    return \array_map($fn, \is_array($data) ? $data : \Krak\Fun\toArray($data));
}

/**
 * @template V
 * @param callable(V): bool $fn
 * @param iterable<V> $iter
 * @return array<array-key, V>
 */
function arrayFilter(callable $fn, iterable $data): array {
    return \array_filter(\is_array($data) ? $data : \Krak\Fun\toArray($data), $fn);
}

/**
 * @template T
 * @param callable(T): bool $predicate
 * @param iterable<T> $iter
 */
function all(callable $predicate, iterable $iter): bool {
    foreach ($iter as $key => $value) {
        if (!$predicate($value)) {
            return false;
        }
    }

    return true;
}

/**
 * @template T
 * @param callable(T): bool $predicate
 * @param iterable<T> $iter
 */
function any(callable $predicate, iterable $iter): bool {
    foreach ($iter as $key => $value) {
        if ($predicate($value)) {
            return true;
        }
    }

    return false;
}

/**
 * @template T
 * @param callable(T): bool $predicate
 * @param iterable<T> $iter
 * @return ?T
 */
function search(callable $predicate, iterable $iter) {
    foreach ($iter as $value) {
        if ($predicate($value)) {
            return $value;
        }
    }
}

/**
 * @template T
 * @template K
 * @param callable(T): bool $predicate
 * @param iterable<K, T> $iter
 * @return ?K
 */
function indexOf(callable $predicate, iterable $iter) {
    foreach ($iter as $key => $value) {
        if ($predicate($value)) {
            return $key;
        }
    }
}
/**
 * @template T
 * @template U
 * @template V
 * @param callable(T): U $trans
 * @param callable(U): V $fn
 * @param T $data
 * @return V
 */
function trans(callable $trans, callable $fn, $data) {
    return $fn($trans($data));
}
/**
 * @template T
 * @param callable(...T): bool $fn
 * @param T ...$args
 * @return bool
 */
function not(callable $fn, ...$args): bool {
    return !$fn(...$args);
}
/**
 * @param class-string|object $class
 * @param object $item
 */
function isInstance($class, $item): bool {
    return $item instanceof $class;
}
/** @param mixed $val */
function isNull($val): bool {
    return \is_null($val);
}
/**
 * @template T
 * @template U
 * @param callable(T): U $fn
 * @param ?T $value
 * @return ?U
 */
function nullable(callable $fn, $value) {
    return $value === null ? $value : $fn($value);
}

/**
 * @template T
 * @template U as int|bool
 * @param callable(T): U $partition
 * @param iterable<T> $iter
 * @return array<array<T>>
 */
function partition(callable $partition, iterable $iter, int $numParts = 2): array {
    $parts = \array_fill(0, $numParts, []);
    foreach ($iter as $val) {
        $index = (int) $partition($val);
        $parts[$index][] = $val;
    }

    return $parts;
}

/**
 * @template T
 * @template K
 * @template U
 * @param callable(T): U $predicate
 * @param iterable<K, T> $iter
 * @return iterable<K, U>
 */
function map(callable $predicate, iterable $iter): iterable {
    foreach ($iter as $key => $value) {
        yield $key => $predicate($value);
    }
}

/**
 * @template T
 * @template K
 * @template U
 * @param callable(K): U $predicate
 * @param iterable<K, T> $iter
 * @return iterable<U, T>
 */
function mapKeys(callable $predicate, iterable $iter): iterable {
    foreach ($iter as $key => $value) {
        yield $predicate($key) => $value;
    }
}

/**
 * @template T
 * @template U
 * @template K
 * @template V
 * @param callable(array{0: U, 1: T}): array{0: K, 1: V} $fn
 * @param iterable<U, T> $iter
 * @return iterable<K, V>
 */
function mapKeyValue(callable $fn, iterable $iter): iterable {
    foreach ($iter as $key => $value) {
        [$key, $value] = $fn([$key, $value]);
        yield $key => $value;
    }
}

/**
 * @param array<callable> $maps
 * @param iterable<array-key, mixed> $iter
 */
function mapOn(array $maps, iterable $iter): iterable {
    /** @psalm-suppress MixedAssignment */
    foreach ($iter as $key => $value) {
        if (isset($maps[$key])) {
            yield $key => $maps[$key]($value);
        } else {
            yield $key => $value;
        }
    }
}

/**
 * @template T
 * @template U
 * @template A
 * @param callable(A, T): array{0: A, 1: U} $fn
 * @param iterable<T> $iter
 * @param A $acc
 * @return array{0: A, 1: list<U>}
 */
function mapAccum(callable $fn, iterable $iter, $acc = null): array {
    $data = [];
    foreach ($iter as $key => $value) {
        [$acc, $value] = $fn($acc, $value);
        $data[] = $value;
    }

    return [$acc, $data];
}

/**
 * @template T
 * @template U
 * @param callable(T, ...mixed): array{0: T, 1: U} $fn
 * @param T $initialState
 */
function withState(callable $fn, $initialState = null): callable {
    $state = $initialState;
    return
    /**
     * @param mixed ...$args
     * @return U
     */
    function(...$args) use ($fn, &$state) {
        /** @psalm-suppress MixedArgument */
        [$state, $res] = $fn($state, ...$args);
        return $res;
    };
}

/**
 * @template T
 * @template U as array-key
 * @param callable(T): U $fn
 * @param iterable<T> $iter
 * @return array<U, T>
 */
function arrayReindex(callable $fn, iterable $iter): array {
    $res = [];
    foreach ($iter as $key => $value) {
        $res[$fn($value)] = $value;
    }
    return $res;
}

/**
 * @template T
 * @template U
 * @param callable(T): U $fn
 * @param iterable<T> $iter
 * @return iterable<U, T>
 */
function reindex(callable $fn, iterable $iter): iterable {
    foreach ($iter as $key => $value) {
        yield $fn($value) => $value;
    }
}

/**
 * @template TAcc
 * @template TValue
 * @param callable(TAcc, TValue): TAcc $reduce
 * @param iterable<TValue> $iter
 * @param TAcc $acc
 * @return TAcc
 */
function reduce(callable $reduce, iterable $iter, $acc = null) {
    foreach ($iter as $key => $value) {
        $acc = $reduce($acc, $value);
    }
    return $acc;
}

/**
 * @template TAcc
 * @template TValue
 * @template TKey
 * @param callable(TAcc, array{0: TKey, 1: TValue}): TAcc $reduce
 * @param iterable<TKey, TValue> $iter
 * @param TAcc $acc
 * @return TAcc
 */
function reduceKeyValue(callable $reduce, iterable $iter, $acc = null) {
    foreach ($iter as $key => $value) {
        $acc = $reduce($acc, [$key, $value]);
    }
    return $acc;
}

/**
 * @template V
 * @template K
 * @param callable(V): bool $predicate
 * @param iterable<K, V> $iter
 * @return iterable<K, V>
 */
function filter(callable $predicate, iterable $iter): iterable {
    foreach ($iter as $key => $value) {
        if ($predicate($value)) {
            yield $key => $value;
        }
    }
}

/**
 * @template V
 * @template K
 * @param callable(K): bool $predicate
 * @param iterable<K, V> $iter
 * @return iterable<K, V>
 */
function filterKeys(callable $predicate, iterable $iter): iterable {
    foreach ($iter as $key => $value) {
        if ($predicate($key)) {
            yield $key => $value;
        }
    }
}

/**
 * @template V
 * @template K
 * @param iterable<K, V> $iter
 * @return iterable<V>
 */
function values(iterable $iter): iterable {
    foreach ($iter as $v) {
        yield $v;
    }
}

/**
 * @template V
 * @template K
 * @param iterable<K, V> $iter
 * @return iterable<K>
 */
function keys(iterable $iter): iterable {
    foreach ($iter as $k => $v) {
        yield $k;
    }
}

/**
 * @template V as array-key
 * @template K as array-key
 * @param iterable<K, V> $iter
 * @return iterable<V, K>
 */
function flip(iterable $iter): iterable {
    foreach ($iter as $k => $v) {
        yield $v => $k;
    }
}

function curry(callable $fn, int $num = 1): callable {
    if ($num == 0) {
        return $fn;
    }

    return
    /**
     * @param mixed $arg1
     * @return mixed
     */
    function($arg1) use ($fn, $num) {
        return curry(
            /**
             * @param mixed ...$args
             * @return mixed
             */
            function(...$args) use ($fn, $arg1) {
                return $fn($arg1, ...$args);
            },
            $num - 1
        );
    };
}

/** @return object */
function placeholder() {
    static $v;
    /** @var object */
    $v = $v ?: new class {};
    return $v;
}
/** @return object */
function _() {
    return placeholder();
}

/**
 * @template T
 * @param callable(...mixed): T $fn
 * @param mixed ...$appliedArgs
 */
function partial(callable $fn, ...$appliedArgs): callable {
    return
    /**
     * @param mixed ...$args
     * @return T
     */
    function(...$args) use ($fn, $appliedArgs) {
        /** @psalm-suppress MixedAssignment */
        [$appliedArgs, $args] = \array_reduce(
            $appliedArgs,
            /**
             * @param array<array> $acc
             * @param mixed $arg
             */
            function(array $acc, $arg) {
                [$appliedArgs, $args] = $acc;
                if ($arg === \Krak\Fun\placeholder()) {
                    $arg = array_shift($args);
                }

                $appliedArgs[] = $arg;
                return [$appliedArgs, $args];
            },
            [[], $args]
        );

        return $fn(...$appliedArgs, ...$args);
    };
}

/**
 * @template T
 * @param callable(...mixed): T $fn
 * @return T|callable
 */
function autoCurry(array $args, int $numArgs, callable $fn) {
    if (\count($args) >= $numArgs) {
        return $fn(...$args);
    }
    if (\count($args) == $numArgs - 1) {
        return \Krak\Fun\partial($fn, ...$args);
    }
    if (\count($args) == 0) {
        return \Krak\Fun\curry($fn, $numArgs - 1);
    }

    return \Krak\Fun\curry(
        \Krak\Fun\partial($fn, ...$args),
        ($numArgs - 1 - \count($args))
    );
}

/**
 * @template T
 * @param iterable<T> $iter
 * @return list<T>
 */
function toArray(iterable $iter): array {
    $data = [];
    foreach ($iter as $key => $val) {
        $data[] = $val;
    }
    return $data;
}

/**
 * @template V
 * @template K as array-key
 * @param iterable<K, V> $iter
 * @return array<K, V>
 */
function toArrayWithKeys(iterable $iter): array {
    $data = [];
    foreach ($iter as $key => $val) {
        $data[$key] = $val;
    }
    return $data;
}

/**
 * @template T
 * @param T $v
 * @return T
 */
function id($v) {
    return $v;
}


// UTILITY
/**
 * @template A
 * @template B
 * @param callable(A, B): bool $cmp
 * @param iterable<A> $a
 * @param iterable<B> $b
 * @return iterable<A>
 */
function differenceWith(callable $cmp, iterable $a, iterable $b): iterable {
    return \Krak\Fun\filter(/** @param A $aItem */ function($aItem) use ($cmp, $b) {
        return \Krak\Fun\indexOf(/** @param B $bItem */ function($bItem) use ($cmp, $aItem) {
            return $cmp($aItem, $bItem);
        }, $b) === null;
    }, $a);
}

/**
 * @template T
 * @template U as array-key
 * @template K as array-key
 * @param callable(T): U $fn
 * @param array<K> $orderedElements
 * @param iterable<T> $iter
 * @return array<T>
 */
function sortFromArray(callable $fn, array $orderedElements, iterable $iter): array {
    $data = [];
    $flippedElements = \array_flip($orderedElements);

    foreach ($iter as $value) {
        /** @var array-key */
        $key = $fn($value);
        if (!\array_key_exists($key, $flippedElements)) {
            throw new \LogicException('Cannot sort element key '  . $key . ' because it does not exist in the ordered elements.');
        }

        $data[$flippedElements[$key]] = $value;
    }

    ksort($data);
    return $data;
}

/**
 * @template T
 * @param callable(int): T $fn
 * @param null|int|callable(int, ?\Throwable): bool $shouldRetry
 * @return T
 */
function retry(callable $fn, $shouldRetry = null) {
    if (\is_null($shouldRetry)) {
        $shouldRetry = function(int $numRetries, \Throwable $t = null): bool { return true; };
    }
    if (\is_int($shouldRetry)) {
        $maxTries = $shouldRetry;
        if ($maxTries < 0) {
            throw new \LogicException("maxTries must be greater than or equal to 0");
        }
        $shouldRetry = function(int $numRetries, \Throwable $t = null) use ($maxTries): bool { return $numRetries <= $maxTries; };
    }
    if (!\is_callable($shouldRetry)) {
        throw new \InvalidArgumentException('shouldRetry must be an int or callable');
    }

    $numRetries = 0;
    do {
        try {
           return $fn($numRetries);
        } catch (\Throwable $t) {}
        $numRetries += 1;
    } while ($shouldRetry($numRetries, $t));

    throw $t;
}

function pipe(callable ...$fns): callable {
    assert(count($fns) > 0);
    return
    /**
     * @param mixed ...$args
     * @return mixed
     */
    function(...$args) use ($fns) {
        $isFirstPass = true;
        foreach ($fns as $fn) {
            if ($isFirstPass) {
                /** @psalm-suppress MixedAssignment */
                $arg = $fn(...$args);
                $isFirstPass = false;
            } else {
                /** @psalm-suppress MixedAssignment */
                $arg = $fn($arg);
            }

        }
        return $arg;
    };
}

function compose(callable ...$fns): callable {
    return \Krak\Fun\pipe(...\array_reverse($fns));
}

/**
 * @template T
 * @param array<T> $funcs
 * @param ?callable(T): callable $resolve
 */
function stack(array $funcs, callable $last = null, callable $resolve = null): callable {
    return
    /**
     * @param mixed ...$args
     * @return mixed
     */
    function(...$args) use ($funcs, $resolve, $last) {
        return \Krak\Fun\reduce(/** @param T $func */ function(callable $acc, $func) use ($resolve) {
            return
            /**
             * @param mixed ...$args
             * @return mixed
             */
            function(...$args) use ($acc, $func, $resolve) {
                /** @psalm-suppress MixedAssignment */
                $args[] = $acc;
                /** @psalm-suppress MixedAssignment */
                $func = $resolve ? $resolve($func) : $func;
                if (!is_callable($func)) {
                    throw new \RuntimeException('Func was not resolved to a callable.');
                }
                return $func(...$args);
            };
        }, $funcs, $last ?: function() { throw new \LogicException('No stack handler was able to capture this request'); });
    };
}

/**
 * @template V
 * @param callable(V): void $handle
 * @param iterable<V> $iter
 */
function each(callable $handle, iterable $iter): void {
    foreach ($iter as $v) {
        $handle($v);
    }
}
/**
 * @deprecated
 * @template V
 * @param callable(V): void $handle
 * @param iterable<V> $iter
 */
function onEach(callable $handle, iterable $iter): void {
    foreach ($iter as $v) {
        $handle($v);
    }
}

/**
 * @param array|object|iterable|\Iterator|string $iter
 * @psalm-suppress RedundantConditionGivenDocblockType
 * @psalm-suppress DocblockTypeContradiction
 */
function iter($iter): \Iterator {
    if (\is_array($iter)) {
        return new \ArrayIterator($iter);
    } else if ($iter instanceof \Iterator) {
        return $iter;
    } else if (\is_string($iter)) {
        return (function(string $s): \Generator {
            for ($i = 0; $i < \strlen($s); $i++) {
                yield $i => $s[$i];
            }
        })($iter);
    } else if (\is_object($iter) || \is_iterable($iter)) {
        return (/** @param object|iterable $iter */ function($iter): \Generator {
            /** @psalm-suppress MixedAssignment */
            foreach ($iter as $key => $value) {
                yield $key => $value;
            }
        })($iter);
    }

    throw new \LogicException('Iter could not be converted into an iterable.');
}

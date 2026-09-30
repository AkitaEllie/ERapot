<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUniqueStringIds;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Gives a model a short, readable, sortable primary key such as "R-2600001":
 * the model's prefix, the two-digit year the row was created in, and a counter
 * restarting at 1 each year.
 *
 * The year is what makes resetting the counter safe, and it also sorts keys
 * chronologically. The counter is derived from the keys already stored, so
 * there is no separate column to keep in step, and the primary key's unique
 * index is the backstop if two inserts race for the same number.
 *
 * @phpstan-require-extends Model
 */
trait HasShortSequenceKey
{
    use HasUniqueStringIds;

    /**
     * The key prefix for this model, e.g. "R-". The widest prefix in the schema
     * is three characters, so a key is at most 11 characters.
     */
    abstract protected static function codePrefix(): string;

    /**
     * How many digits the per-year counter is padded to. Five allows 99,999
     * records per table per year.
     */
    protected static function codeCounterWidth(): int
    {
        return 5;
    }

    /**
     * How many times to retry when a generated key is already taken.
     */
    protected static function codeAttempts(): int
    {
        return 5;
    }

    /**
     * Generate a new unique key for this model.
     *
     * @throws RuntimeException
     */
    public function newUniqueId(): string
    {
        $base = static::codePrefix().now()->format('y');
        $width = static::codeCounterWidth();
        $max = (int) str_repeat('9', $width);

        for ($attempt = 1; $attempt <= static::codeAttempts(); $attempt++) {
            $next = static::nextCounter($base, $width) + $attempt - 1;

            if ($next > $max) {
                throw new RuntimeException(sprintf(
                    'The %s key counter for %s has run out of room; widen codeCounterWidth().',
                    class_basename(static::class),
                    $base,
                ));
            }

            $code = $base.str_pad((string) $next, $width, '0', STR_PAD_LEFT);

            if (! static::codeExists($code)) {
                return $code;
            }
        }

        throw new RuntimeException(sprintf(
            'Unable to generate a unique %s key after %d attempts.',
            class_basename(static::class),
            static::codeAttempts(),
        ));
    }

    /**
     * The highest counter already in use for the given prefix and year.
     */
    protected static function nextCounter(string $base, int $width): int
    {
        $key = static::query()->getModel()->getKeyName();

        $last = static::query()
            ->where($key, 'like', $base.'%')
            ->orderByDesc($key)
            ->value($key);

        if ($last === null) {
            return 1;
        }

        $counter = (int) substr((string) $last, strlen($base));

        if (strlen(substr((string) $last, strlen($base))) !== $width) {
            throw new RuntimeException(sprintf(
                'Stored key "%s" does not match the expected %s format.',
                $last,
                $base.'[0-9]{'.$width.'}',
            ));
        }

        return $counter + 1;
    }

    /**
     * Determine whether the given key is already taken.
     */
    protected static function codeExists(string $code): bool
    {
        return static::query()->whereKey($code)->exists();
    }

    /**
     * Determine whether the given key is one of ours.
     *
     * @param  mixed  $value
     */
    protected function isValidUniqueId($value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $prefix = static::codePrefix();
        $width = static::codeCounterWidth();

        return (bool) preg_match(
            '/^'.preg_quote($prefix, '/').'\d{2}\d{'.$width.'}$/',
            $value,
        );
    }
}

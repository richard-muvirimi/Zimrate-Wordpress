<?php
/**
 * Decimal arithmetic for rates
 *
 * @package    Zimrate
 * @subpackage Zimrate/Helpers
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.1.6
 */

namespace RichardMuvirimi\Zimrate\Helpers;

/**
 * Arithmetic on rates as decimal strings.
 *
 * The api works its figures out in decimal rather than binary floats, and this
 * keeps the plugin's side of the sum the same way when bcmath is loaded: every
 * operation takes and returns a numeric string, at twenty decimal places, and
 * the result is only cast to a float at the edge where a host plugin wants one.
 * Without bcmath the same calls fall back on float arithmetic, so callers are
 * written once either way.
 *
 * @package    Zimrate
 * @subpackage Zimrate/Helpers
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.1.6
 */
class Arithmetic
{
    /**
     * Decimal places carried through a calculation
     *
     * @since 1.1.6
     */
    const SCALE = 20;

    /**
     * Whether bcmath is loaded
     *
     * @since 1.1.6
     * @return bool
     */
    public static function available(): bool
    {
        return function_exists('bcdiv');
    }

    /**
     * Add two numbers
     *
     * @since 1.1.6
     * @param float|int|string $a
     * @param float|int|string $b
     * @return string
     */
    public static function add($a, $b): string
    {
        if (self::available()) {
            return bcadd(self::normalise($a), self::normalise($b), self::SCALE);
        }

        return self::normalise(floatval($a) + floatval($b));
    }

    /**
     * Multiply two numbers
     *
     * @since 1.1.6
     * @param float|int|string $a
     * @param float|int|string $b
     * @return string
     */
    public static function mul($a, $b): string
    {
        if (self::available()) {
            return bcmul(self::normalise($a), self::normalise($b), self::SCALE);
        }

        return self::normalise(floatval($a) * floatval($b));
    }

    /**
     * Divide two numbers, zero when the divisor is zero rather than an error
     *
     * @since 1.1.6
     * @param float|int|string $dividend
     * @param float|int|string $divisor
     * @return string
     */
    public static function div($dividend, $divisor): string
    {
        if (floatval($divisor) == 0) {
            return '0';
        }

        if (self::available()) {
            return bcdiv(self::normalise($dividend), self::normalise($divisor), self::SCALE);
        }

        return self::normalise(floatval($dividend) / floatval($divisor));
    }

    /**
     * Round half away from zero to a fixed number of decimal places
     *
     * The api rounds its figures the same way, so a rate displayed here lands
     * on the digit the api would show.
     *
     * @since 1.1.6
     * @param float|int|string $value
     * @param int $precision
     * @return string
     */
    public static function round($value, int $precision): string
    {
        $precision = max(0, $precision);

        if (self::available()) {
            $value = self::normalise($value);
            $half = '0.' . str_repeat('0', $precision) . '5';

            return substr($value, 0, 1) === '-'
                ? bcsub($value, $half, $precision)
                : bcadd($value, $half, $precision);
        }

        return number_format(round(floatval($value), $precision), $precision, '.', '');
    }

    /**
     * A number as the plain decimal string bcmath reads
     *
     * A float is written at the shortest length that reads back to the same
     * value, a plain string cast stops at the ini precision of fourteen digits.
     * Small enough values come out in scientific notation, which bcmath
     * rejects, so those are expanded in full.
     *
     * @since 1.1.6
     * @param float|int|string $value
     * @return string
     */
    private static function normalise($value): string
    {
        if (is_string($value) && is_numeric($value) && stripos($value, 'e') === false) {
            return $value;
        }

        $value = floatval($value);

        if (!is_finite($value)) {
            return '0';
        }

        $string = var_export($value, true);

        if (stripos($string, 'e') !== false) {
            $string = rtrim(rtrim(sprintf('%.' . self::SCALE . 'F', $value), '0'), '.');
        }

        return $string;
    }
}

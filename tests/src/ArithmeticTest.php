<?php declare(strict_types=1);

/**
 * File for decimal arithmetic tests
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.1.6
 * @version 1.1.6
 */

namespace RichardMuvirimi\Zimrate\Tests;

use PHPUnit\Framework\TestCase;
use RichardMuvirimi\Zimrate\Helpers\Arithmetic;

/**
 * Arithmetic Test Cases class
 *
 * The same assertions hold with and without bcmath, the rounding ones are the
 * point of the helper: a binary float sits a hair below the half way mark and
 * rounds the wrong way, a decimal does not.
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.1.6
 * @version 1.1.6
 */
class ArithmeticTest extends TestCase
{
    /**
     * Test half way values round away from zero
     *
     * @return void
     */
    public function testRoundsHalfAwayFromZero(): void
    {
        self::assertSame('1.01', Arithmetic::round(1.005, 2));
        self::assertSame('2.68', Arithmetic::round(2.675, 2));
        self::assertSame('-1.01', Arithmetic::round(-1.005, 2));
        self::assertSame('27.00', Arithmetic::round(27, 2));
        self::assertSame('27', Arithmetic::round('26.5', 0));
    }

    /**
     * Test the operations return plain numeric strings
     *
     * @return void
     */
    public function testReturnsPlainNumericStrings(): void
    {
        foreach (array(Arithmetic::add(0.1, 0.2), Arithmetic::mul(1e-7, 1), Arithmetic::div('26.6291', '16.648')) as $result) {
            self::assertTrue(is_numeric($result), $result);
            self::assertStringNotContainsStringIgnoringCase('e', $result);
        }

        self::assertEquals(1234.567891234567, Arithmetic::mul(1234.567891234567, 1));
    }

    /**
     * Test division by zero is zero rather than an error
     *
     * @return void
     */
    public function testDivisionByZeroIsZero(): void
    {
        self::assertSame('0', Arithmetic::div(1, 0));
        self::assertSame('0', Arithmetic::div(1, '0.0'));
    }

    /**
     * Test bcmath carries the api's twenty digits
     *
     * @return void
     */
    public function testMatchesApiPrecisionWithBcmath(): void
    {
        if (!Arithmetic::available()) {
            self::markTestSkipped('bcmath is not loaded');
        }

        // decimal.js on the server: new Decimal(26.6291).div(16.648)
        self::assertSame('1.59953748197981739548', Arithmetic::div('26.6291', '16.648'));
        self::assertSame('0.30000000000000000000', Arithmetic::add(0.1, 0.2));
    }
}

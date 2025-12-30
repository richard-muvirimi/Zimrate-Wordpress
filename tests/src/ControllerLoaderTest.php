<?php declare(strict_types=1);
/**
 * File for php unit testcases
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 * @version 1.0.0
 */

namespace RichardMuvirimi\Zimrate\Tests;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use RichardMuvirimi\Zimrate\Zimrate;

/**
 * Test Cases class
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 * @version 1.0.0
 */
class ControllerLoaderTest extends TestCase
{

    // Adds Mockery expectations to the PHPUnit assertions count.
    use MockeryPHPUnitIntegration;

    /**
     * Test the loader class magic methods
     *
     * @return void
     * @version 1.0.0
     * @since 1.0.0
     */
    public function testHooks(): void
    {
        $loader = Zimrate::instance();
        $loader->add_action('init', '__return_true', 25);
        $loader->add_filter('the_title', '__return_true', 25);

        // constants loaded
        self::assertTrue(ZIMRATE_VERSION !== null, "ZIMRATE_VERSION is null");
        self::assertTrue(ZIMRATE_NAME !== null, "ZIMRATE_NAME is null");
        self::assertTrue(ZIMRATE_FILE !== null,  "ZIMRATE_FILE is null");
        self::assertTrue(ZIMRATE_SLUG !== null, "ZIMRATE_SLUG is null");

        // assert added.
        self::assertNotFalse(has_action('init', '__return_true'), "init action not added");
        self::assertNotFalse(has_filter('the_title', '__return_true'), "the_title filter not added");

        // assert priority.
        self::assertSame(25, has_action('init', '__return_true'), "init action priority not 25");
        self::assertSame(25, has_filter('the_title', '__return_true'), "the_title filter priority not 25");
    }

    /**
     * Tear Down
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * SetUp
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

}

<?php declare(strict_types=1);

/**
 * File for plugin integration support tests
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.1.6
 * @version 1.1.6
 */

namespace RichardMuvirimi\Zimrate\Tests;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use RichardMuvirimi\Zimrate\Integrations\WooMultiCurrency;
use RichardMuvirimi\Zimrate\Integrations\WcMultiCurrency;
use RichardMuvirimi\Zimrate\Integrations\CurrencyConverter;
use RichardMuvirimi\Zimrate\Integrations\CurrencySwitcherWoocommerce;
use RichardMuvirimi\Zimrate\Integrations\CurrencyExchangeWoocommerce;
use RichardMuvirimi\Zimrate\Integrations\Woocs;
use ZipArchive;

/**
 * Check the supported plugins still provide what the integrations bind to.
 *
 * Each integration declares the hooks, classes and api hosts it needs.  The
 * current release of each plugin is pulled from WordPress.org and searched for
 * them, so a hook being renamed upstream fails here rather than silently
 * leaving the integration dead on live sites.
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.1.6
 * @version 1.1.6
 */
class PluginIntegrationSupportTest extends TestCase
{
    // Adds Mockery expectations to the PHPUnit assertions count.
    use MockeryPHPUnitIntegration;

    /**
     * Downloaded plugin sources, keyed by slug, kept for the whole run
     *
     * @var array
     */
    private static $sources = [];

    /**
     * Test WooMultiCurrency still provides what the integration binds to
     *
     * @return void
     */
    public function testWooMultiCurrencySupport(): void
    {
        $this->checkSupport('woo-multi-currency', new WooMultiCurrency());
    }

    /**
     * Test WcMultiCurrency still provides what the integration binds to
     *
     * @return void
     */
    public function testWcMultiCurrencySupport(): void
    {
        $this->checkSupport('wc-multi-currency', new WcMultiCurrency());
    }

    /**
     * Test CurrencyConverter still provides what the integration binds to
     *
     * @return void
     */
    public function testCurrencyConverterSupport(): void
    {
        $this->checkSupport('currencyconverter', new CurrencyConverter());
    }

    /**
     * Test CurrencySwitcherWoocommerce still provides what the integration binds to
     *
     * @return void
     */
    public function testCurrencySwitcherWoocommerceSupport(): void
    {
        $this->checkSupport('currency-switcher-woocommerce', new CurrencySwitcherWoocommerce());
    }

    /**
     * Test CurrencyExchangeWoocommerce still provides what the integration binds to
     *
     * @return void
     */
    public function testCurrencyExchangeWoocommerceSupport(): void
    {
        $this->checkSupport('currency-exchange-for-woocommerce', new CurrencyExchangeWoocommerce());
    }

    /**
     * Test Woocs still provides what the integration binds to
     *
     * @return void
     */
    public function testWoocsSupport(): void
    {
        $this->checkSupport('woocommerce-currency-switcher', new Woocs());
    }

    /**
     * Search a plugin's published source for everything an integration needs
     *
     * @param string $slug WordPress.org plugin slug
     * @param object $integration The integration binding to it
     * @return void
     */
    private function checkSupport(string $slug, $integration): void
    {
        $symbols = $integration->get_required_symbols();

        self::assertNotEmpty(
            $symbols,
            $slug . ' integration declares no required symbols, so nothing is being checked'
        );

        $source = $this->getSource($slug);

        foreach ($symbols as $symbol) {
            self::assertNotFalse(
                strpos($source, $symbol),
                $slug . ' no longer contains "' . $symbol . '", the integration binds to it'
            );
        }
    }

    /**
     * Download a plugin and concatenate its php source
     *
     * @param string $slug WordPress.org plugin slug
     * @return string
     */
    private function getSource(string $slug): string
    {
        if (isset(self::$sources[$slug])) {
            return self::$sources[$slug];
        }

        if (!class_exists(ZipArchive::class)) {
            self::markTestSkipped('The zip extension is needed to read plugin sources');
        }

        $archive = @file_get_contents('https://downloads.wordpress.org/plugin/' . $slug . '.zip');

        if ($archive === false) {
            self::markTestSkipped('Could not download ' . $slug . ' from WordPress.org');
        }

        $file = tempnam(sys_get_temp_dir(), 'zimrate');
        file_put_contents($file, $archive);

        $zip = new ZipArchive();

        if ($zip->open($file) !== true) {
            unlink($file);
            self::markTestSkipped('Could not read the ' . $slug . ' archive');
        }

        $source = '';

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if (substr($name, -4) === '.php') {
                $source .= $zip->getFromIndex($i);
            }
        }

        $zip->close();
        unlink($file);

        self::$sources[$slug] = $source;

        return $source;
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

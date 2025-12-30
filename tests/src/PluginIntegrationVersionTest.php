<?php declare(strict_types=1);

/**
 * File for plugin integration version tests
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.1.0
 * @version 1.1.0
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

/**
 * Test plugin integration versions against WordPress.org
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.1.0
 * @version 1.1.0
 */
class PluginIntegrationVersionTest extends TestCase
{
    // Adds Mockery expectations to the PHPUnit assertions count.
    use MockeryPHPUnitIntegration;

    /**
     * Test WooMultiCurrency tested version against WordPress.org
     */
    public function testWooMultiCurrencyVersion(): void
    {
        $integration = new WooMultiCurrency();
        $this->checkPluginVersion(
            'woo-multi-currency',
            $integration->get_tested_version(),
            $integration->get_plugin_name()
        );
    }

    /**
     * Test WcMultiCurrency tested version against WordPress.org
     */
    public function testWcMultiCurrencyVersion(): void
    {
        $integration = new WcMultiCurrency();
        $this->checkPluginVersion(
            'wc-multi-currency',
            $integration->get_tested_version(),
            $integration->get_plugin_name()
        );
    }

    /**
     * Test CurrencyConverter tested version against WordPress.org
     */
    public function testCurrencyConverterVersion(): void
    {
        $integration = new CurrencyConverter();
        $this->checkPluginVersion(
            'currencyconverter',
            $integration->get_tested_version(),
            $integration->get_plugin_name()
        );
    }

    /**
     * Test CurrencySwitcherWoocommerce tested version against WordPress.org
     */
    public function testCurrencySwitcherWoocommerceVersion(): void
    {
        $integration = new CurrencySwitcherWoocommerce();
        $this->checkPluginVersion(
            'currency-switcher-woocommerce',
            $integration->get_tested_version(),
            $integration->get_plugin_name()
        );
    }

    /**
     * Test CurrencyExchangeWoocommerce tested version against WordPress.org
     */
    public function testCurrencyExchangeWoocommerceVersion(): void
    {
        $integration = new CurrencyExchangeWoocommerce();
        $this->checkPluginVersion(
            'currency-exchange-for-woocommerce',
            $integration->get_tested_version(),
            $integration->get_plugin_name()
        );
    }

    /**
     * Test Woocs tested version against WordPress.org
     */
    public function testWoocsVersion(): void
    {
        $integration = new Woocs();
        $this->checkPluginVersion(
            'woocommerce-currency-switcher',
            $integration->get_tested_version(),
            $integration->get_plugin_name()
        );
    }

    /**
     * Check plugin version against WordPress.org latest version
     *
     * @param string $pluginSlug WordPress.org plugin slug
     * @param string $testedVersion Version we claim to have tested
     * @param string $pluginName Human-readable plugin name for error messages
     */
    private function checkPluginVersion(string $pluginSlug, string $testedVersion, string $pluginName): void
    {
        $response = @file_get_contents("https://api.wordpress.org/plugins/info/1.0/{$pluginSlug}.json");

        if ($response) {
            $data = json_decode($response, true);

            if (isset($data['version'])) {
                $latestVersion = $data['version'];

                $this->assertTrue(
                    version_compare($latestVersion, $testedVersion, '='),
                    sprintf(
                        "%s: Tested version (%s) does not match latest WordPress.org version (%s). Consider updating get_tested_version() in the integration class.",
                        $pluginName,
                        $testedVersion,
                        $latestVersion
                    )
                );
            } else {
                $this->markTestSkipped("Could not retrieve version information for {$pluginName} from WordPress.org");
            }
        } else {
            $this->markTestSkipped("Could not connect to WordPress.org API for {$pluginName}");
        }
    }
}

<?php
/**
 * Integration classes test
 *
 * @package Zimrate
 */

namespace RichardMuvirimi\Zimrate\Tests;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use RichardMuvirimi\Zimrate\Integrations\BasePluginIntegration;
use RichardMuvirimi\Zimrate\Integrations\WooMultiCurrency;
use RichardMuvirimi\Zimrate\Integrations\WcMultiCurrency;
use RichardMuvirimi\Zimrate\Integrations\CurrencyConverter;
use RichardMuvirimi\Zimrate\Integrations\CurrencySwitcherWoocommerce;
use RichardMuvirimi\Zimrate\Integrations\CurrencyExchangeWoocommerce;
use RichardMuvirimi\Zimrate\Integrations\Woocs;

/**
 * Test integration classes
 */
class IntegrationsTest extends TestCase
{
    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        
        // Mock WordPress functions that integration classes might need
        Functions\when('add_filter')->justReturn(true);
        Functions\when('add_action')->justReturn(true);
    }

    /**
     * Teardown test environment
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test all integrations implement required methods
     */
    public function test_integrations_have_required_methods()
    {
        $integrations = [
            new WooMultiCurrency(),
            new WcMultiCurrency(),
            new CurrencyConverter(),
            new CurrencySwitcherWoocommerce(),
            new CurrencyExchangeWoocommerce(),
            new Woocs(),
        ];

        foreach ($integrations as $integration) {
            $this->assertInstanceOf(BasePluginIntegration::class, $integration);
            $this->assertIsString($integration->get_plugin_slug());
            $this->assertNotEmpty($integration->get_plugin_slug());
            $this->assertIsString($integration->get_plugin_name());
            $this->assertNotEmpty($integration->get_plugin_name());
            $this->assertIsString($integration->get_tested_version());
            $this->assertNotEmpty($integration->get_tested_version());
            $this->assertIsString($integration->get_supported_from());
            $this->assertNotEmpty($integration->get_supported_from());
        }
    }

    /**
     * Test integration metadata structure
     */
    public function test_integration_metadata()
    {
        $integration = new WooMultiCurrency();
        $metadata = $integration->get_metadata();

        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('slug', $metadata);
        $this->assertArrayHasKey('name', $metadata);
        $this->assertArrayHasKey('tested', $metadata);
        $this->assertArrayHasKey('supported_from', $metadata);
    }

    /**
     * Test that supported_plugins() uses integration classes
     */
    public function test_supported_plugins_uses_integrations()
    {
        Functions\expect('apply_filters')
            ->once()
            ->with('zimrate-plugins', \Mockery::type('array'))
            ->andReturnUsing(function($hook, $value) {
                return $value;
            });

        $plugins = \RichardMuvirimi\Zimrate\Helpers\Functions::supported_plugins();

        $this->assertIsArray($plugins);
        $this->assertArrayHasKey('woo-multi-currency/woo-multi-currency.php', $plugins);
        $this->assertArrayHasKey('wc-multi-currency/wcmilticurrency.php', $plugins);
        $this->assertArrayHasKey('currencyconverter/plugin.php', $plugins);
        $this->assertArrayHasKey('currency-switcher-woocommerce/currency-switcher-woocommerce.php', $plugins);
        $this->assertArrayHasKey('currency-exchange-for-woocommerce/woocommerce-currency-exchange.php', $plugins);
        $this->assertArrayHasKey('woocommerce-currency-switcher/index.php', $plugins);

        // Verify structure of each plugin entry
        foreach ($plugins as $plugin) {
            $this->assertArrayHasKey('name', $plugin);
            $this->assertArrayHasKey('tested', $plugin);
            $this->assertArrayHasKey('supported_from', $plugin);
        }
    }

    /**
     * Test that tested_version is a valid version format
     */
    public function test_supported_from_equals_tested_version()
    {
        $integrations = [
            new WooMultiCurrency(),
            new WcMultiCurrency(),
            new CurrencyConverter(),
            new CurrencySwitcherWoocommerce(),
            new CurrencyExchangeWoocommerce(),
            new Woocs(),
        ];

        foreach ($integrations as $integration) {
            // Test that tested_version is a valid semantic version
            $this->assertRegExp(
                '/^\d+\.\d+(\.\d+)?(\.\d+)?$/',
                $integration->get_tested_version(),
                get_class($integration) . ' tested_version should be a valid version format'
            );
            
            // Test that supported_from is also a valid semantic version
            $this->assertRegExp(
                '/^\d+\.\d+(\.\d+)?(\.\d+)?$/',
                $integration->get_supported_from(),
                get_class($integration) . ' supported_from should be a valid version format'
            );
            
            // Test that tested_version is >= supported_from
            $this->assertGreaterThanOrEqual(
                0,
                version_compare($integration->get_tested_version(), $integration->get_supported_from()),
                get_class($integration) . ' tested_version should be greater than or equal to supported_from'
            );
        }
    }
}

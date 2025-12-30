<?php
/**
 * Currency Exchange for WooCommerce integration
 *
 * @package    Zimrate
 * @subpackage Zimrate/Integrations
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */

namespace RichardMuvirimi\Zimrate\Integrations;

use RichardMuvirimi\Zimrate\Helpers\Functions;
use RichardMuvirimi\Zimrate\Zimrate;

/**
 * Currency Exchange for WooCommerce integration class
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */
class CurrencyExchangeWoocommerce extends BasePluginIntegration
{
    /**
     * {@inheritdoc}
     */
    public function get_plugin_slug(): string
    {
        return 'currency-exchange-for-woocommerce/woocommerce-currency-exchange.php';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_plugin_name_fallback(): string
    {
        return 'Currency Exchange for WooCommerce';
    }

    /**
     * {@inheritdoc}
     */
    public function get_tested_version(): string
    {
        return '3.6.2.1';
    }

    /**
     * {@inheritdoc}
     */
    public function get_supported_from(): string
    {
        return '3.5.1.5';
    }

    /**
     * {@inheritdoc}
     */
    public function register_hooks(): void
    {
        Zimrate::instance()->add_filter('berocket_ce_apis_sanitize_oer', $this, 'currency_exchange_for_woocommerce');
        Zimrate::instance()->add_filter('berocket_ce_apis_sanitize_currencylayer', $this, 'currency_exchange_for_woocommerce');
        Zimrate::instance()->add_filter('berocket_ce_apis_sanitize_fixerio', $this, 'currency_exchange_for_woocommerce');
        Zimrate::instance()->add_filter('berocket_ce_apis_sanitize_floatrates', $this, 'currency_exchange_for_woocommerce');
    }

    /**
     * Provide exchange rates for plugin
     *
     * @since 1.0.0
     * @param array $rates
     * @return array
     */
    public function currency_exchange_for_woocommerce($rates): array
    {
        $rates[Functions::get_iso()] = Functions::apply_cushion(Functions::get_rate());

        return $rates;
    }
}

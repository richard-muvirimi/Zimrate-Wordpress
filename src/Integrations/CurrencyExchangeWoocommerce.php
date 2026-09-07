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
    public function get_required_symbols(): array
    {
        // the host builds these as 'berocket_ce_apis_sanitize_' . $api_slug,
        // so only the stem is ever a literal in its source
        return [
            'berocket_ce_apis_sanitize_',
        ];
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
        return array_merge($rates, Functions::get_rates_from_usd());
    }
}

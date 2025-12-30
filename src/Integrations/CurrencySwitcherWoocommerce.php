<?php
/**
 * Currency Switcher for WooCommerce integration
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
 * Currency Switcher for WooCommerce integration class
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */
class CurrencySwitcherWoocommerce extends BasePluginIntegration
{
    /**
     * {@inheritdoc}
     */
    public function get_plugin_slug(): string
    {
        return 'currency-switcher-woocommerce/currency-switcher-woocommerce.php';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_plugin_name_fallback(): string
    {
        return 'Currency Switcher for WooCommerce';
    }

    /**
     * {@inheritdoc}
     */
    public function get_tested_version(): string
    {
        return '2.16.4';
    }

    /**
     * {@inheritdoc}
     */
    public function get_supported_from(): string
    {
        return '2.12.3';
    }

    /**
     * {@inheritdoc}
     */
    public function register_hooks(): void
    {
        Zimrate::instance()->add_filter('alg_wc_cs_get_exchange_rate', $this, 'currency_switcher_woocommerce', 10, 4);
    }

    /**
     * Convert rate to specified currency
     *
     * @since 1.0.0
     * @param float  $rate
     * @param string $server
     * @param string $from
     * @param string $to
     * @return float
     */
    public function currency_switcher_woocommerce($rate, $server, $from, $to): float
    {
        if (count(array_intersect([$from, $to], Functions::get_isos())) === 0) {
            return $rate;
        }
        
        return $this->convert_currency($from, $to, $server);
    }

    /**
     * {@inheritdoc}
     */
    protected function get_usd_rate(string $currency, ...$args): float
    {
        [$server] = $args + [0 => 'ecb'];
        
        $rate = alg_wc_cs_get_exchange_rate($currency, "USD", $server);

        /**
         * Undo the offset applied in Currency Switcher for WooCommerce
         * 
         * @source currency-switcher-woocommerce/includes/functions/alg-switcher-exchange-rates-functions.php
         * @see alg_wc_cs_get_exchange_rate()
         */
        if ('default' === get_option('alg_currency_switcher_exchange_rate_offset_type_' . $currency . '_USD', 'default')) {
            $offset = get_option('alg_currency_switcher_exchange_rate_offset', 0);
        } else {
            $offset = get_option('alg_currency_switcher_exchange_rate_offset_' . $currency . '_USD', 0);
        }

        // Undo the offset that was already applied by alg_wc_cs_get_exchange_rate
        return (0 != $offset) ? floatval($rate / (1 + ($offset / 100))) : floatval($rate);
    }
}

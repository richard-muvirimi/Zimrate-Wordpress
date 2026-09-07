<?php
/**
 * WOOCS - WooCommerce Currency Switcher integration
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
 * WOOCS integration class
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */
class Woocs extends BasePluginIntegration
{
    /**
     * {@inheritdoc}
     */
    public function get_plugin_slug(): string
    {
        return 'woocommerce-currency-switcher/index.php';
    }

    /**
     * {@inheritdoc}
     */
    public function get_required_symbols(): array
    {
        return [
            'woocs_add_custom_rate',
            'WOOCS',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_plugin_name_fallback(): string
    {
        return 'WOOCS - WooCommerce Currency Switcher';
    }

    /**
     * {@inheritdoc}
     */
    public function get_tested_version(): string
    {
        return '1.4.3.1';
    }

    /**
     * {@inheritdoc}
     */
    public function get_supported_from(): string
    {
        return '1.3.1.1';
    }

    /**
     * {@inheritdoc}
     */
    public function register_hooks(): void
    {
        Zimrate::instance()->add_filter('woocs_add_custom_rate', $this, 'woocs_add_custom_rate', 11, 3);
    }

    /**
     * Convert for plugin
     *
     * @since 1.1.0
     * @param float|boolean $rate
     * @param string $from
     * @param string $to
     * @return float|boolean
     */
    public function woocs_add_custom_rate($rate, $from, $to): mixed
    {

        if (count(array_intersect([$from, $to], array_keys(Functions::supported_currencies()))) === 0) {
            return $rate;
        }
        
        return $this->convert_currency($from, $to);
    }

    /**
     * {@inheritdoc}
     */
    protected function get_usd_rate(string $currency, ...$args): float
    {
        global $WOOCS;
        return floatval($WOOCS->get_rate());
    }

    /**
     * {@inheritdoc}
     */
    protected function save_conversion_state(string $currency, array $args): ?array
    {

        global $WOOCS;

        $default_currency = $WOOCS->default_currency;
        $request_currency = $_REQUEST['currency_name'] ?? '';
        $no_ajax = $_REQUEST['no_ajax'] ?? false;

        $_REQUEST['currency_name'] = 'USD';
        $WOOCS->default_currency = $currency;
        $_REQUEST['no_ajax'] = true;

        return compact('default_currency', 'request_currency', 'no_ajax');
    }

    /**
     * {@inheritdoc}
     */
    protected function restore_conversion_state(?array $state): void
    {

        global $WOOCS;

        extract($state ?? []);

        $WOOCS->default_currency = $default_currency;
        $_REQUEST['currency_name'] = $request_currency;
        if ($no_ajax) {
            $_REQUEST['no_ajax'] = $no_ajax;
        } else {
            unset($_REQUEST['no_ajax']);
        }
    }
}

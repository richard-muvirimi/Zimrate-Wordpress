<?php
/**
 * Plugin lifecycle hooks
 *
 * @package    Zimrate
 * @subpackage Zimrate/Controller
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */

namespace RichardMuvirimi\Zimrate\Controller;

use RichardMuvirimi\Zimrate\Helpers\Functions;
use RichardMuvirimi\Zimrate\Helpers\Logger;

/**
 * Plugin lifecycle class
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */
class Plugin extends BaseController
{
    /**
     * Run on plugin activation
     *
     * @return void
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    public static function on_activation(): void
    {
        // Get rates for caching on activate
        Functions::get_rates();

        if (boolval(get_transient(Functions::get_plugin_slug('-rate'))) === false) {
            set_transient(Functions::get_plugin_slug('-rate'), true, defined("MONTH_IN_SECONDS") ? MONTH_IN_SECONDS * 3 : YEAR_IN_SECONDS / 4);
        }

        Logger::logEvent("activate_plugin");
    }

    /**
     * Run on plugin deactivation
     *
     * @return void
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    public static function on_deactivation(): void
    {
        // Nothing to do on deactivation
        Logger::logEvent("deactivate_plugin");
    }

    /**
     * Run on plugin uninstall
     *
     * @return void
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    public static function on_uninstall(): void
    {
        // Delete plugin settings
        delete_option('zimrate-prefer');
        delete_option('zimrate-interval');
        delete_option('zimrate-cushion');
        delete_option('zimrate-currencies');
        delete_option('zimrate-analytics');
        
        // Clean up any plugin data
        Logger::logEvent("uninstall_plugin");
    }
}

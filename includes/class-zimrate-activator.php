<?php

/**
 * Fired during plugin activation
 *
 * @link       https://tyganeutronics.com
 * @since      1.0.0
 *
 * @package    Zimrate
 * @subpackage Zimrate/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @version    1.1.2
 * @since      1.0.0
 * @package    Zimrate
 * @subpackage Zimrate/includes
 * @author     Richard Muvirimi <tygalive@gmail.com>
 */
class Zimrate_Activator
{
    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     * @version  1.1.2
     */
    public static function activate()
    {

        //get rates for caching on activate
        zimrate_get_rates();

        if (boolval(get_transient("zimrate-rate")) === false) {
            set_transient("zimrate-rate", true, defined("MONTH_IN_SECONDS") ? MONTH_IN_SECONDS * 3 : YEAR_IN_SECONDS / 4);
        }
    }
}
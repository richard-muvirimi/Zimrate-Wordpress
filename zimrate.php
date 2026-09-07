<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://tyganeutronics.com
 * @since             1.0.0
 * @package           Zimrate
 *
 * @wordpress-plugin
 * Plugin Name:       ZimRate
 * Plugin URI:        https://github.com/richard-muvirimi/zimrate-wordpress
 * Description:       All Zimbabwean exchange rates from multiple sites in one plugin. No need to scrounge the internet for the current days rate.
 * Version:           1.1.5
 * Author:            Richard Muvirimi
 * Author URI:        https://richard.co.zw
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       zimrate
 * Domain Path:       /languages
 */

use RichardMuvirimi\Zimrate\Zimrate;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Reference to this file, and this file only, (well, plugin entry point)
 */
const ZIMRATE_FILE = __FILE__;

#region Constants 

/**
 * The plugin slug, one source of truth for context
 */
const ZIMRATE_SLUG = 'zimrate';

/**
 * Plugin version number
 */
const ZIMRATE_VERSION = '1.1.5';

/**
 * Plugin name as known to WordPress
 */
define( 'ZIMRATE_NAME', plugin_basename( ZIMRATE_FILE ) );

#endregion Constants

/**
 * Load composer
 */
require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

/**
 * And away we go
 */
Zimrate::instance()->run();
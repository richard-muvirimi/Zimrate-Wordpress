<?php
/**
 * Internationalization functionality
 *
 * @package    Zimrate
 * @subpackage Zimrate/Locale
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */

namespace RichardMuvirimi\Zimrate\Locale;

use RichardMuvirimi\Zimrate\Helpers\Functions;

/**
 * Internationalization class
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */
class I18n
{
    /**
     * Load the plugin translation files
     *
     * @return void
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    public function load_plugin_textdomain(): void
    {
        load_plugin_textdomain(Functions::get_plugin_slug(), false, plugin_dir_path(ZIMRATE_FILE) . 'languages');
    }
}

<?php

namespace RichardMuvirimi\Zimrate\Views;

use RichardMuvirimi\Zimrate\Helpers\Functions;

if (!defined('WPINC')) {
    die(); // Exit if accessed directly.
}

/**
 * Ads disclaimer for about page
 *
 * @package Zimrate
 * @subpackage Zimrate/Views
 *
 * @link http://richard.co.zw
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */

?>
<div>
    <small>
        <?php _e("Third-party advertisements help support the ongoing development and maintenance of this plugin.", Functions::get_plugin_slug()) ?>
    </small>
</div>

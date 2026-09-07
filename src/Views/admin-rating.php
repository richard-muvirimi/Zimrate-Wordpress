<?php

if (!defined('WPINC')) {
    die(); // Exit if accessed directly.
}

/**
 * Provide a admin area view for the plugin
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 *
 * @package ZimRate
 * @subpackage ZimRate/admin/partials
 *
 * @link https://tyganeutronics.com
 * @since 1.1.0
 * @version 1.1.1
 */

use RichardMuvirimi\Zimrate\Helpers\Functions;

?>

<div class="<?php esc_attr_e(Functions::get_plugin_slug()) ?> notice notice-info is-dismissible">
    <div>
        <div class="<?php esc_attr_e(Functions::get_plugin_slug()) ?>-prompt">
            <?php printf(__('Please consider rating %s as it will encourage others to install it too.', Functions::get_plugin_slug()), __("ZimRate", Functions::get_plugin_slug())); ?>
        </div>
        <div class="<?php esc_attr_e(Functions::get_plugin_slug()) ?>-button">
            <a class="button btn-rate" href="#" data-nonce="<?php esc_attr_e(wp_create_nonce("wcg-rate")) ?>"
                data-action="<?php esc_attr_e("rate") ?>">
                <?php _e('Rate ', Functions::get_plugin_slug()); ?>
                <span style="color:#ffb900;">&starf;&starf;&starf;&starf;&starf;</span>
            </a>
            <a class="button btn-remind" href="#" data-nonce="<?php esc_attr_e(wp_create_nonce("wcg-remind")) ?>"
                data-action="<?php esc_attr_e("remind") ?>">
                <span><?php _e('Remind me later', Functions::get_plugin_slug()); ?></span>
            </a>
            <a class="button btn-cancel" href="#" data-nonce="<?php esc_attr_e(wp_create_nonce("wcg-cancel")) ?>"
                data-action="<?php esc_attr_e("cancel") ?>">
                <span><?php _e('Never', Functions::get_plugin_slug()); ?></span>
            </a>
        </div>
    </div>
</div>
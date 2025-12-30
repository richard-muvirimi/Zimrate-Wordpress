<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Zimrate
 * @subpackage Zimrate/admin/partials
 *
 * @link       https://tyganeutronics.com
 * @since      1.0.0
 */

use RichardMuvirimi\Zimrate\Helpers\Functions;

?>
<h2>
    <?php _e('Supported Plugins', Functions::get_plugin_slug()); ?>
</h2>
<div class="zimrate-plugins" style="overflow-x:auto;">
    <table class="zimrate-plugins-table">
        <thead>
            <tr>
                <th>
                    <?php _e('Plugin', Functions::get_plugin_slug()); ?>
                </th>
                <th>
                    <?php _e('Status', Functions::get_plugin_slug()); ?>
                </th>
                <th>
                    <?php _e('Tested Version', Functions::get_plugin_slug()); ?>
                </th>
                <th>
                    <?php _e('Supported From', Functions::get_plugin_slug()); ?>
                </th>
            </tr>
        </thead>
        <tbody>

            <?php foreach (Functions::supported_plugins() as $id => $data) :

                $active = Functions::plugin_active($id);
                $installed = file_exists(
                    WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $id
                );
                if ($installed) {
                    $plugin = get_plugin_data(
                        WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $id
                    );
                }
                $base_url = 'https://wordpress.org/plugins/' . dirname($id);
                $plugin_slug = dirname($id);
                $details_url = admin_url('plugin-install.php?tab=plugin-information&plugin=' . $plugin_slug . '&TB_iframe=true&width=600&height=550');
                $activate_url = $installed ? wp_nonce_url(admin_url('plugins.php?action=activate&plugin=' . urlencode($id)), 'activate-plugin_' . $id) : '';
                
                // Determine status badge and action
                $badge_style = 'padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; display: inline-block;';
                $action_button = '';
                
                if ($active) {
                    $badge_color = 'background: #46b450; color: white;';
                    $badge_text = '✓ Active & Compatible';
                    $badge_title = 'Plugin is active and compatible';
                } elseif (!$installed) {
                    $badge_color = 'background: #dba617; color: white;';
                    $badge_text = '⚠ Not Installed';
                    $badge_title = 'Plugin needs to be installed';
                    $action_button = sprintf(
                        '<a href="%s" class="button button-small thickbox open-plugin-details-modal" aria-label="%s" style="margin-left: 8px;">%s</a>',
                        esc_url($details_url),
                        esc_attr(sprintf(__('View details and install %s', Functions::get_plugin_slug()), $data['name'])),
                        __('View Details & Install', Functions::get_plugin_slug())
                    );
                } elseif (version_compare($plugin['Version'], $data['supported_from'], '<')) {
                    $badge_color = 'background: #dc3232; color: white;';
                    $badge_text = '✗ Update Required';
                    $badge_title = sprintf('Current version %s is below minimum %s', $plugin['Version'], $data['supported_from']);
                    $update_url = wp_nonce_url(admin_url('update.php?action=upgrade-plugin&plugin=' . urlencode($id)), 'upgrade-plugin_' . $id);
                    $action_button = sprintf(
                        '<a href="%s" class="button button-small" aria-label="%s" style="margin-left: 8px;">%s</a>',
                        esc_url($update_url),
                        esc_attr(sprintf(__('Update %s now', Functions::get_plugin_slug()), $plugin['Name'])),
                        __('Update Now', Functions::get_plugin_slug())
                    );
                } else {
                    $badge_color = 'background: #72aee6; color: white;';
                    $badge_text = '○ Inactive';
                    $badge_title = 'Plugin is installed but not active';
                    $action_button = sprintf(
                        '<a href="%s" class="button button-small" aria-label="%s" style="margin-left: 8px;">%s</a>',
                        esc_url($activate_url),
                        esc_attr(sprintf(__('Activate %s', Functions::get_plugin_slug()), $plugin['Name'])),
                        __('Activate', Functions::get_plugin_slug())
                    );
                }
            ?>
                <tr>
                    <td>
                        <a href="<?php echo esc_url($details_url); ?>" class="thickbox open-plugin-details-modal" aria-label="<?php echo esc_attr(sprintf(__('More information about %s', Functions::get_plugin_slug()), $installed ? $plugin['Name'] : $data['name'])); ?>">
                            <?php if ($installed) {
                                esc_html_e($plugin['Name']);
                            } else {
                                esc_attr_e($data['name']);
                            } ?>
                        </a>
                        <?php if ($installed): ?>
                            <br><small style="color: #666;">v<?php esc_attr_e($plugin['Version']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="<?php echo $badge_style . $badge_color; ?>" title="<?php esc_attr_e($badge_title); ?>">
                            <?php echo $badge_text; ?>
                        </span>
                        <?php echo $action_button; ?>
                    </td>
                    <td>
                        <?php esc_attr_e($data['tested']); ?>
                    </td>
                    <td>
                        <?php esc_attr_e($data['supported_from']); ?>
                    </td>
                </tr>
            <?php
            endforeach; ?>

        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">
                    <?php _e(
                        'You can use any of the above plugins and their rates will be automatically modified to include the Zimbabwean rate.',
                        Functions::get_plugin_slug()
                    ); ?>
                </td>
            </tr>
        </tfoot>
    </table>
</div>
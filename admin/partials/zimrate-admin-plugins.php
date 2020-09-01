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
?>
<h2>
    <?php _e('Supported Plugins', $this->plugin_name); ?>
</h2>
<div class="zimrate-plugins" style="overflow-x:auto;">
    <table class="zimrate-plugins-table">
        <thead>
            <tr>
                <th>
                    &nbsp;
                </th>
                <th>
                    <?php _e('Plugin', $this->plugin_name); ?>
                </th>
                <th>
                    <?php _e('Version', $this->plugin_name); ?>
                </th>
                <th>
                    <?php _e('Required', $this->plugin_name); ?>
                </th>
                <th>
                    <?php _e('Website', $this->plugin_name); ?>
                </th>
            </tr>
        </thead>
        <tbody>

            <?php foreach (zimrate_supported_plugins() as $id => $data) :

                $active = zimrate_plugin_active($id);
                $installed = file_exists(
                    WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $id
                );
                if ($installed) {
                    $plugin = get_plugin_data(
                        WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $id
                    );
                }
                $base_url = 'https://wordpress.org/plugins/' . dirname($id);
                $url = $installed ? $plugin['PluginURI'] : $base_url;
            ?>
                <tr>
                    <td>
                        <?php if ($active) {
                            _e('&checkmark; Enabled', $this->plugin_name);
                        } elseif (!$installed) {
                            _e('Not Installed', $this->plugin_name);
                        } else {
                            _e('&cross; Disabled', $this->plugin_name);
                        } ?>
                    </td>
                    <td>
                        <a href="<?php esc_attr_e($base_url); ?>">
                            <?php if ($installed) {
                                esc_html_e($plugin['Name']);
                            } else {
                                esc_attr_e($data['name']);
                            } ?>
                        </a>
                    </td>
                    <td>
                        <?php if ($installed) {
                            esc_attr_e($plugin['Version']);
                        } else {
                            echo '&hellip;';
                        } ?>
                    </td>
                    <td>
                        <?php if (!$installed) {
                            printf(__('&cross; %s'), $data['tested']);
                        } elseif (
                            version_compare(
                                $plugin['Version'],
                                $data['tested'],
                                '>='
                            )
                        ) {
                            printf(__('&checkmark; %s'), $data['tested']);
                        } else {
                            printf(__('&cross; %s (Update)'), $data['tested']);
                        } ?>
                    </td>
                    <td>
                        <a href="<?php esc_attr_e($url); ?>">
                            <?php esc_attr_e(zimrate_url_host($url)); ?>
                        </a>
                    </td>
                </tr>
            <?php
            endforeach; ?>

        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">
                    <?php _e(
                        'You can use any of the above plugins and their rates will be automatically modified to include the Zimbabwean rate.',
                        $this->plugin_name
                    ); ?>
                </td>
            </tr>
        </tfoot>
    </table>
</div>
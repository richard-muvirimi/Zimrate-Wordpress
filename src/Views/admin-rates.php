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
 * @version    1.1.2
 */

use RichardMuvirimi\Zimrate\Helpers\Functions;

$rates = Functions::get_rates();
$base = Functions::default_base(); ?>

<h2>
    <?php _e('Rates', Functions::get_plugin_slug()); ?>
</h2>

<?php if (is_wp_error($rates)) : ?>
    <p>
        <?php _e(
            'Cannot retrieve rates at this time, please try refreshing the page',
            Functions::get_plugin_slug()
        ); ?>
    </p>

<?php else : ?>

    <div class="zimrate-rates" style="overflow-x:auto;">
        <table class="zimrate-rates-table">
            <thead>
                <tr>
                    <th>
                        &nbsp;
                    </th>
                    <th>
                        <?php _e('Currency', Functions::get_plugin_slug()); ?>
                    </th>
                    <th>
                        <?php printf(
                            __('Rate (per 1 %s)', Functions::get_plugin_slug()),
                            esc_html($base)
                        ); ?>
                    </th>
                    <th>
                        <?php _e('Last Updated', Functions::get_plugin_slug()); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php
                $format = 'D d M Y G:i e';

                $supported = array_keys(Functions::supported_currencies());

                foreach ($rates[$base] as $index => $rate) :
                    if (in_array($rate['currency'], $supported)) : ?>
                        <tr>
                            <td>
                                <?php esc_attr_e($index + 1); ?>
                            </td>
                            <td>
                                <?php esc_html_e($rate['currency']); ?>
                            </td>
                            <td>
                                <?php esc_html_e($rate['rate']); ?>
                            </td>
                            <td>
                                <?php esc_html_e(
                                    wp_date($format, $rate['last_updated'])
                                ); ?>
                            </td>
                        </tr>
                <?php endif;
                endforeach;
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4">
                        <?php echo wpautop(wptexturize($rates['info'])); ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="4">
                        <?php
                        $last_checked = empty($rates[$base]) ? time() : max(
                            array_column($rates[$base], 'last_checked')
                        );
                        printf(
                            __('Last Checked %s', Functions::get_plugin_slug()),
                            esc_html(wp_date($format,  $last_checked))
                        );
                        ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
<?php endif;

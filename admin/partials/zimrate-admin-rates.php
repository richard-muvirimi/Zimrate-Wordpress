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

$rates = zimrate_get_rates(); ?>

<h2>
    <?php _e('Rates', $this->plugin_name); ?>
</h2>

<?php if (is_wp_error($rates)) : ?>
    <p>
        <?php _e(
            'Cannot retrieve rates at this time, please try refreshing the page',
            $this->plugin_name
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
                        <?php _e('Currency', $this->plugin_name); ?>
                    </th>
                    <th>
                        <?php _e('Rate', $this->plugin_name); ?>
                    </th>
                    <th>
                        <?php _e('Last Updated', $this->plugin_name); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php
                $format = 'D d M Y G:i e';

                $supported = array_keys(zimrate_supported_currencies());

                foreach ($rates['USD'] as $index => $rate) :
                    if (in_array($rate['currency'], $supported)) : ?>
                        <tr>
                            <td>
                                <?php esc_attr_e($index + 1); ?>
                            </td>
                            <td>
                                <?php esc_html_e(
                                    zimrate_supported_currencies()[$rate['currency']] .
                                        ' (' .
                                        $rate['currency'] .
                                        ')'
                                ); ?>
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
                        $last_checked = empty($rates['USD']) ? time() : max(
                            array_column($rates['USD'], 'last_checked')
                        );
                        printf(
                            __('Last Checked %s', $this->plugin_name),
                            esc_html(wp_date($format,  $last_checked))
                        );
                        ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
<?php endif;

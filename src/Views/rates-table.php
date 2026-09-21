<?php

/**
 * Current rates table
 *
 * Rendered on its own by the rates table block and inside the dashboard
 * calculator, whose script redraws the cells when the base changes.
 *
 * @package    Zimrate
 * @subpackage Zimrate/Views
 *
 * @link       https://tyganeutronics.com
 * @since      1.1.6
 * @version    1.1.6
 *
 * @var string $base
 * @var int $precision
 * @var array $rows currency => [label, rate]
 * @var string $info
 */

defined('ABSPATH') || exit();
?>

<?php if (empty($rows)) : ?>

    <p class="zimrate-calculator-empty">
        <?php echo esc_html($info ?: __('No rates are available right now.', 'zimrate')); ?>
    </p>

<?php else : ?>

    <div class="zimrate-calculator-scroll">
        <table>
            <thead>
                <tr>
                    <th><?php esc_html_e('Currency', 'zimrate'); ?></th>
                    <th data-zimrate-heading>
                        <?php printf(
                            /* translators: %s: base currency code */
                            esc_html__('Rate (per 1 %s)', 'zimrate'),
                            esc_html($base)
                        ); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $code => $row) : ?>
                    <tr data-zimrate-row="<?php echo esc_attr($code); ?>">
                        <td><?php echo esc_html($row['label']); ?></td>
                        <td data-zimrate-cell><?php echo esc_html(
                            number_format_i18n(floatval($row['rate']), $precision)
                        ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($info) : ?>
        <p class="zimrate-calculator-info"><?php echo esc_html($info); ?></p>
    <?php endif; ?>

<?php endif; ?>

<?php
/**
 * Template for FLASHPAY Payment meta box.
 *
 * @var string $status
 * @var string $logo
 * @var bool $is_test
 * @var int $recurring_id
 */
?>

<ul class="order_action">
    <?php if (isset ($status) && !empty ($status)): ?>
        <li class="wide ep-meta-box-header">
            <p class="ep-full-width">
                <img class="ep-pm-logo" src="<?php echo esc_url_raw($logo); ?>" alt="" title="" />
                <mark class="order-status status-<?php echo esc_attr($status); ?> subscription-status right">
                    <span>
                        <?php if ($is_test): ?>
                            <?php esc_html_e('Test', 'woo-flashpay'); ?>
                        <?php endif; ?>
                        <?php echo esc_html(ep_get_subscription_status_name($status)); ?>
                    </span>
                </mark>
            </p>
        </li>
    <?php endif; ?>

    <li class="wide">
        <?php if ($is_test): ?>
            <p class="ep-full-width is_test">
                <strong>
                    <?php esc_html_e('Test subscription', 'woo-flashpay'); ?>
                </strong>
            </p>
        <?php endif; ?>
        <p class="ep-full-width">
            <small>
                <strong>
                    <?php esc_html_e('Recurring ID', 'woo-flashpay'); ?>:
                </strong>
                <?php echo esc_html($recurring_id); ?>
            </small>
        </p>
        <?php if (isset ($transaction_order_id) && !empty ($transaction_order_id)): ?>
            <p class="ep-full-width">
                <small>
                    <strong>
                        <?php esc_html_e('Transaction Order ID', 'woo-flashpay'); ?>:
                    </strong>
                    <?php echo esc_html($transaction_order_id); ?>
                </small>
            </p>
        <?php endif; ?>
    </li>
</ul>
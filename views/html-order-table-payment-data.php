<?php
/**
 * Template for column FLASHPAY Payment.
 *
 * @var bool $transaction_is_test Flag of test payment
 * @var string $payment_status Status of payment
 */
?>
<?php if ($transaction_is_test): ?>
    <mark
        class="ep-payment-status status-<?php echo esc_html(Ep_Gateway_Payment_Status::get_status_code($payment_status)); ?> tips"
        data-tip="<?php esc_attr_e('This order is paid for by test integration!', 'woo-flashpay') ?>">
        <span>
            <?php esc_html_e('Test', 'woo-flashpay'); ?>
            <?php echo esc_html(Ep_Gateway_Payment_Status::get_status_name($payment_status)); ?>
        </span>
    </mark>
<?php else: ?>
    <mark
        class="ep-payment-status status-<?php echo esc_html(Ep_Gateway_Payment_Status::get_status_code($payment_status)); ?>">
        <span>
            <?php echo esc_html(Ep_Gateway_Payment_Status::get_status_name($payment_status)); ?>
        </span>
    </mark>
<?php endif; ?>
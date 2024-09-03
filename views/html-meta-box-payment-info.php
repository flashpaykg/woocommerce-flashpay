<?php
/**
 * Template for FLASHPAY Payment meta box.
 *
 * @var Ep_Gateway_Info_Payment $data
 * @var string $status
 * @var string $status_name
 * @var string $operation_type
 * @var int|int[] $operation_code
 * @var string|string[] $operation_message
 * @var string $payment_method
 * @var ?int $transaction_order_id
 * @var ?string $payment_id
 * @var ?string $logo
 * @var ?string $amount
 * @var bool $is_test
 */

?>
<ul class="order_actions">
    <?php if (isset ($status) && !empty ($status)): ?>
        <li class="wide ep-meta-box-header">
            <p class="ep-full-width">
                <img class="ep-pm-logo" src="<?php echo esc_attr($logo); ?>" alt="" title="" />
                <mark class="ep-payment-status status-<?php echo esc_html($status); ?> right">
                    <span>
                        <?php if ($is_test): ?>
                            <?php esc_html_e('Test', 'woo-flashpay'); ?>
                        <?php endif; ?>
                        <?php echo esc_html(Ep_Gateway_Payment_Status::get_status_name($status)); ?>
                    </span>
                </mark>
            </p>
        </li>
    <?php endif; ?>

    <li class="wide">
        <?php if ($is_test): ?>
            <p class="ep-full-width is_test">
                <strong>
                    <?php esc_html_e('Test payment', 'woo-flashpay'); ?>
                </strong>
            </p>
        <?php endif; ?>
        <?php if (isset ($payment_id) && !empty ($payment_id)): ?>
            <p class="ep-full-width">
                <small>
                    <strong>
                        <?php esc_html_e('Payment ID', 'woo-flashpay'); ?>:
                    </strong>
                    <?php echo esc_html($payment_id); ?>
                </small>
            </p>
        <?php endif; ?>

        <?php if (isset ($payment_method) && !empty ($payment_method)): ?>
            <p class="ep-full-width">
                <small>
                    <strong>
                        <?php esc_html_e('Payment method', 'woo-flashpay'); ?>:
                    </strong>
                    <?php echo esc_html($payment_method); ?>
                </small>
            </p>
        <?php endif; ?>

        <?php if (isset ($operation_type) && !empty ($operation_type)): ?>
            <p class="ep-full-width">
                <small>
                    <strong>
                        <?php esc_html_e('Operation type', 'woo-flashpay'); ?>:
                    </strong>
                    <?php echo esc_html($operation_type); ?>
                </small>
            </p>
        <?php endif; ?>

        <?php if (isset ($operation_code) && !empty ($operation_code)): ?>
            <p class="ep-full-width">
                <small>
                    <strong>
                        <?php esc_html_e('Code', 'woo-flashpay'); ?>:
                    </strong>
                    <?php if (is_array($operation_code)): ?>
                        <?php foreach ($operation_code as $code): ?>
                            <a target="_blank" href="<?php echo esc_url_raw(ep_error_code_link($code)); ?>">
                                <?php echo esc_html($code); ?>
                            </a>,
                        <?php endforeach; ?>
                    <?php else: ?>
                        <a target="_blank" href="<?php echo esc_url_raw(ep_error_code_link($operation_code)); ?>">
                            <?php echo esc_html($operation_code); ?>
                        </a>
                    <?php endif; ?>
                </small>
            </p>
        <?php endif; ?>

        <?php if (isset ($operation_message) && !empty ($operation_message)): ?>
            <p class="ep-full-width">
                <small>
                    <strong>
                        <?php esc_html_e('Message', 'woo-flashpay'); ?>:
                    </strong>
                    <?php if (is_array($operation_message)): ?>
                        <?php echo implode('<br>', esc_html($operation_message)); ?>
                    <?php else: ?>
                        <?php echo esc_html($operation_message); ?>
                    <?php endif; ?>
                </small>
            </p>
        <?php endif; ?>

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
    <li class="wide">
        <strong class="ep-amount">
            <?php echo wp_kses_post($amount); ?>
        </strong>
        <button type="button" data-action="refresh" class="button refresh-info button-secondary" name="save"
            value="Refresh">Refresh</button>
    </li>
</ul>
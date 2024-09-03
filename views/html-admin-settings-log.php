<?php
/**
 * Template for log-panel on Admin Settings panel.
 */

defined('ABSPATH') || exit;
?>
<p class="right">
    <a id="wc-ep_wiki" class="wc-ep-debug-button button button-primary"
        href="<?php echo esc_url_raw(ep_doc_link()); ?>" target="_blank">
        <?php esc_html_e('Got problems? Go get help.', 'woo-flashpay'); ?>
    </a>
    <a id="wc-ep_logs" class="wc-ep-debug-button button" href="<?php echo esc_url_raw(ep_admin_link()); ?>">
        <?php esc_html_e('View debug logs', 'woo-flashpay'); ?>
    </a>

    <?php if (woocommerce_flashpay_can_user_empty_logs()): ?>
        <button role="button" id="wc-ep_logs_clear" class="wc-ep-debug-button button">
            <?php esc_html_e('Empty debug logs', 'woo-flashpay'); ?>
        </button>
    <?php endif; ?>
    <?php if (woocommerce_flashpay_can_user_flush_cache()): ?>
        <button role="button" id="wc-ep_flush_cache" class="wc-ep-debug-button button">
            <?php esc_html_e('Empty transaction cache', 'woo-flashpay'); ?>
        </button>
    <?php endif; ?>
</p>
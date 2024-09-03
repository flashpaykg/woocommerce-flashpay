<?php
/**
 * Template for FLASHPAY Payment meta box error message.
 */

?>
<ul class="order_actions">
    <li class="wide">
        <p class="ep-full-width">
            <?php esc_html_e('An error occurred. For more information check out the', 'woo-flashpay'); ?>
            <strong>
                <?php echo esc_url_raw(ep_get_log()->get_domain()); ?>
            </strong>
            <?php esc_html_e('logs inside'); ?>
            <strong>
                <?php esc_html_e('WooCommerce -> System Status -> Logs'); ?>
            </strong>.
        </p>
    </li>
    <li class="wide">
        <strong class="ep-amount"></strong>
        <button type="button" data-action="refresh" class="button refresh-info button-secondary" name="save"
            value="Refresh">Refresh</button>
    </li>
</ul>
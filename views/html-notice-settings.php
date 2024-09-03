<?php
/**
 * @var string[] $errors
 */

defined('ABSPATH') || exit;
?>
<div class="notice notice-error">
    <h2>
        <?php esc_html_e("FLASHPAY Payments", 'woo-flashpay'); ?>
    </h2>
    <p>
        <?php esc_html_e('You have missing or incorrect settings.', 'woo-flashpay'); ?>
        <?php esc_html_e('Go to the ', 'woo-flashpay'); ?>
        <a href="<?php echo esc_url_raw(ep_settings_page_url()) ?>">
            <?php esc_html_e('settings page', 'woo-flashpay'); ?>
        </a>
    </p>
    <ul>
        <?php foreach ($errors as $error): ?>
            <li><strong>
                    <?php echo esc_html($error); ?>
                </strong>
                <?php esc_html_e('is mandatory.', 'woo-flashpay'); ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
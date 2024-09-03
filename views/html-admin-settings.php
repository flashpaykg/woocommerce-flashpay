<?php
/**
 * Template for setting FLASHPAY Payment Gateway Plugin.
 *
 * @var array $tabs Setting pages
 * @var string $current_tab Identifier of current tab
 * @var string $transaction_brand Payment method
 * @var bool $transaction_is_test Flag of test payment
 * @var string $payment_status Status of payment
 */

defined('ABSPATH') || exit;

$tab_exists = isset ($tabs[$current_tab])
    || has_action('ep_sections_' . $current_tab)
    || has_action('ep_settings_' . $current_tab)
    || has_action('ep_settings_tabs_' . $current_tab);
$current_tab_label = isset ($tabs[$current_tab]) ? $tabs[$current_tab] : '';

if (!$tab_exists) {
    wp_safe_redirect(ep_settings_page_url());
    exit;
}

?>
<div class="wrap ep">
    <nav class="nav-tab-wrapper wpm-nav-tab-wrapper ep-relative">
        <?php foreach ($tabs as $slug => $data): ?>
            <a href="<?php echo esc_url_raw(ep_settings_page_url($slug)); ?>"
                class="nav-tab <?php echo $current_tab === $slug ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($data['label']); ?>
            </a>
        <?php endforeach; ?>
        <span id="ep-version">Version:
            <?php echo esc_html(Ep_Core::WC_EP_VERSION); ?>
        </span>
    </nav>
    <h2 class="screen-reader-text">
        <?php echo esc_html($current_tab_label); ?>
    </h2>
    <?php
    do_action('ep_settings_' . $current_tab);
    ?>
</div>
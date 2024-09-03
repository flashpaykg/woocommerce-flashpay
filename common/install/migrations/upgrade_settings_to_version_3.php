<?php

ep_get_log()->emergency('Run update settings to version 3.0.0');

// Previous plugin settings
$prev_settings = get_option(Ep_Gateway_Install::SETTINGS_NAME, null);

// New default settings
$form_fields = Ep_Form::get_instance()->get_default_settings();
$all_fields = array_column(
    Ep_Form::get_instance()->get_all_form_fields(),
    Ep_Gateway_Settings::FIELD_ID
);

$map = [
    Ep_Gateway_Settings_General::ID => [
        'test' => Ep_Gateway_Settings_General::OPTION_TEST,
        'language' => Ep_Gateway_Settings_General::OPTION_LANGUAGE,
        'caching_enabled' => Ep_Gateway_Settings_General::OPTION_CACHING_ENABLED,
        'caching_expiration' => Ep_Gateway_Settings_General::OPTION_CACHING_EXPIRATION,
        'log_level' => Ep_Gateway_Settings_General::OPTION_LOG_LEVEL,
        'orders_transaction_info' => Ep_Gateway_Settings_General::OPTION_TRANSACTION_INFO,
        'project_id' => Ep_Gateway_Settings_General::OPTION_PROJECT_ID,
        'salt' => Ep_Gateway_Settings_General::OPTION_SECRET_KEY,
        'custom_variables' => Ep_Gateway_Settings_General::OPTION_CUSTOM_VARIABLES,
    ],
    Ep_Gateway_Settings_Card::ID => [
        'enabled' => Ep_Gateway_Settings::OPTION_ENABLED,
        'mode' => Ep_Gateway_Settings::OPTION_MODE,
        'close_on_miss_click' => Ep_Gateway_Settings::OPTION_POPUP_MISS_CLICK,
    ],
];

// Clean old unused settings via map
foreach ($prev_settings as $key => $value) {
    $key = str_replace('flashpay_', '', $key);
    foreach ($map as $section => $options) {
        if (array_key_exists($key, $options)) {
            $form_fields[$section][$options[$key]] = $value;
        }
    }
}

// Update plugin settings
update_option(Ep_Gateway_Install::SETTINGS_NAME, $form_fields);

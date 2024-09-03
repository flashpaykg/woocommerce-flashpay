<?php

ep_get_log()->emergency('Run update settings to version 2.0.3');

// Previous plugin settings
$prev_settings = get_option(Ep_Gateway_Install::SETTINGS_NAME, null);
// New default settings
$form_fields = Ep_Form::get_instance()->get_default_settings();
$all_fields = array_column(
    Ep_Form::get_instance()->get_all_form_fields(),
    Ep_Gateway_Settings::FIELD_ID
);
$map = [
    'mode' => Ep_Gateway_Settings::OPTION_MODE,
    'project_id' => Ep_Gateway_Settings_General::OPTION_PROJECT_ID,
    'salt' => Ep_Gateway_Settings_General::OPTION_SECRET_KEY,
    'test' => Ep_Gateway_Settings_General::OPTION_TEST,
];

// Clean old unused settings via map
foreach ($prev_settings as $key => $value) {
    if (array_key_exists($key, $map)) {
        $prev_settings[$map[$key]] = $value;
    }

    if (!in_array($key, $all_fields)) {
        unset($prev_settings[$key]);
    }
}

// Merged settings
$settings = array_merge($form_fields, $prev_settings);

// Update plugin settings
update_option(Ep_Gateway_Install::SETTINGS_NAME, $settings);

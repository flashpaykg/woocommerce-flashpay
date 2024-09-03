<?php
/**
 * <h2>Autoloader for FLASHPAY Gateway.</h2>
 */

// Import external helpers
require_once __DIR__ . '/../helpers/ep-helper.php';                                // Base functions
require_once __DIR__ . '/../helpers/ep-order.php';                                 // Order functions
require_once __DIR__ . '/../helpers/ep-payment.php';                               // Payment functions
require_once __DIR__ . '/../helpers/ep-subscription.php';                          // Subscription functions
require_once __DIR__ . '/../helpers/notices.php';                                   // Notice functions
require_once __DIR__ . '/../helpers/permissions.php';                               // Permission functions

// Import interfaces
require_once __DIR__ . '/interfaces/interface-ep-gateway-serializer.php';

// Import install package
require_once __DIR__ . '/install/class-ep-gateway-install.php';

// Import internal helpers
require_once __DIR__ . '/helpers/abstract-ep-gateway-registry.php';                // Abstract registry
require_once __DIR__ . '/helpers/class-ep-gateway-array.php';                      // Base array object
require_once __DIR__ . '/helpers/class-ep-gateway-json.php';                       // Base JSON object
require_once __DIR__ . '/helpers/class-ep-gateway-operation-status.php';           // Internal transaction statuses
require_once __DIR__ . '/helpers/class-ep-gateway-operation-types.php';            // Internal transaction types
require_once __DIR__ . '/helpers/class-ep-gateway-payment-status.php';             // Internal payment statuses
require_once __DIR__ . '/helpers/class-ep-gateway-payment-status-transition.php';  // Payment statuses transition
require_once __DIR__ . '/helpers/class-ep-gateway-payment-methods.php';            // Internal payment methods
require_once __DIR__ . '/helpers/class-ep-gateway-recurring-status.php';           // Internal recurring statuses
require_once __DIR__ . '/helpers/class-ep-gateway-recurring-types.php';            // Internal recurring types
require_once __DIR__ . '/helpers/class-ep-gateway-api-protocol.php';               // Internal API protocol

// Import log package
require_once __DIR__ . '/log/class-ep-gateway-log.php';

// Import exception package
require_once __DIR__ . '/exceptions/abstract-ep-gateway-exception.php';
require_once __DIR__ . '/exceptions/class-ep-gateway-error.php';
require_once __DIR__ . '/exceptions/class-ep-gateway-error-exception.php';
require_once __DIR__ . '/exceptions/class-ep-gateway-api-exception.php';
require_once __DIR__ . '/exceptions/class-ep-gateway-duplicate-exception.php';
require_once __DIR__ . '/exceptions/class-ep-gateway-invalid-argument-exception.php';
require_once __DIR__ . '/exceptions/class-ep-gateway-key-not-found-exception.php';
require_once __DIR__ . '/exceptions/class-ep-gateway-logic-exception.php';
require_once __DIR__ . '/exceptions/class-ep-gateway-not-available-exception.php';
require_once __DIR__ . '/exceptions/class-ep-gateway-not-implemented-exception.php';
require_once __DIR__ . '/exceptions/class-ep-gateway-signature-exception.php';

// Import API package
require_once __DIR__ . '/api/class-ep-gateway-api.php';                            // Base API
require_once __DIR__ . '/api/class-ep-gateway-api-payment.php';                    // Payment API

// Import includes
require_once __DIR__ . '/includes/trait-ep-gateway-order-extension.php';           // Trait order extension
require_once __DIR__ . '/includes/class-ep-gateway-callbacks.php';                 // Callback handler
require_once __DIR__ . '/includes/class-ep-gateway-form-handler.php';              // Form handler
require_once __DIR__ . '/includes/class-ep-gateway-order.php';                     // Order wrapper
require_once __DIR__ . '/includes/class-ep-gateway-payment.php';                   // Payment object
require_once __DIR__ . '/includes/class-ep-gateway-refund.php';                    // Refund wrapper
require_once __DIR__ . '/includes/class-ep-gateway-payment-provider.php';          // Payment provider

// Import modules
require_once __DIR__ . '/modules/class-ep-gateway-module-admin-ui.php';            // Admin UI
require_once __DIR__ . '/modules/class-ep-gateway-module-payment-page.php';        // Payment Page
require_once __DIR__ . '/modules/class-ep-gateway-module-refund.php';              // Refund controller
require_once __DIR__ . '/modules/class-ep-gateway-signer.php';                     // Signer

// Import models
require_once __DIR__ . '/models/class-ep-gateway-info-account.php';                // Account data
require_once __DIR__ . '/models/class-ep-gateway-info-acs.php';                    // ACS data
require_once __DIR__ . '/models/class-ep-gateway-info-billing.php';                // Billing data
require_once __DIR__ . '/models/class-ep-gateway-info-callback.php';               // Callback data
require_once __DIR__ . '/models/class-ep-gateway-info-customer.php';               // Customer data
require_once __DIR__ . '/models/class-ep-gateway-info-error.php';                  // Error data
require_once __DIR__ . '/models/class-ep-gateway-info-operation.php';              // Transaction data
require_once __DIR__ . '/models/class-ep-gateway-info-operation-fee.php';          // Transaction fee data
require_once __DIR__ . '/models/class-ep-gateway-info-payment.php';                // Payment data
require_once __DIR__ . '/models/class-ep-gateway-info-provider.php';               // Provider data
require_once __DIR__ . '/models/class-ep-gateway-info-response.php';               // Response data
require_once __DIR__ . '/models/class-ep-gateway-info-status.php';                 // Status data
require_once __DIR__ . '/models/class-ep-gateway-info-sum.php';                    // Amount data

// Import settings
require_once __DIR__ . '/settings/abstract-ep-gateway-settings.php';               // Abstract settings
require_once __DIR__ . '/settings/class-ep-gateway-settings-general.php';          // General settings
require_once __DIR__ . '/settings/class-ep-gateway-settings-card.php';             // Card settings
require_once __DIR__ . '/settings/class-ep-gateway-settings-paypal.php';           // PayPal settings
require_once __DIR__ . '/settings/class-ep-gateway-settings-paypal-paylater.php';  // PayPal PayLater settings
require_once __DIR__ . '/settings/class-ep-gateway-settings-klarna.php';           // Klarna settings
require_once __DIR__ . '/settings/class-ep-gateway-settings-giropay.php';          // Giropay settings
require_once __DIR__ . '/settings/class-ep-gateway-settings-sofort.php';           // Sofort settings
require_once __DIR__ . '/settings/class-ep-gateway-settings-blik.php';             // Blik settings
require_once __DIR__ . '/settings/class-ep-gateway-settings-ideal.php';            // iDEAL settings
require_once __DIR__ . '/settings/class-ep-gateway-settings-banks.php';            // Banks settings
require_once __DIR__ . '/settings/class-ep-gateway-settings-applepay.php';         // Apple Pay settings
require_once __DIR__ . '/settings/class-ep-gateway-settings-more.php';             // More payments settings
require_once __DIR__ . '/settings/class-ep-gateway-settings-googlepay.php';        // GooglePay settings
require_once __DIR__ . '/settings/class-ep-gateway-settings-brazil.php';           // Brazil online banks settings
require_once __DIR__ . '/settings/class-ep-form.php';                              // Settings main page

if (ep_subscription_is_active()) {
    require_once __DIR__ . '/api/class-ep-gateway-api-subscription.php';               // Subscription API
    require_once __DIR__ . '/includes/class-ep-gateway-subscription.php';              // Subscription wrapper
    require_once __DIR__ . '/modules/class-ep-gateway-module-subscription.php';        // Subscription controller
    require_once __DIR__ . '/models/class-ep-gateway-info-recurring.php';              // Recurring data
}

// Import main class
require_once __DIR__ . '/class-ep-core.php';                                            // Core
require_once __DIR__ . '/gateways/abstract-ep-gateway.php';                             // Abstract Gateway
require_once __DIR__ . '/gateways/class-ep-gateway-card.php';                           // Card Gateway
require_once __DIR__ . '/gateways/class-ep-gateway-paypal.php';                         // PayPal Gateway
require_once __DIR__ . '/gateways/class-ep-gateway-paypal-paylater.php';                // PayPal PayLater Gateway
require_once __DIR__ . '/gateways/class-ep-gateway-klarna.php';                         // Klarna Gateway
require_once __DIR__ . '/gateways/class-ep-gateway-giropay.php';                        // Giropay Gateway
require_once __DIR__ . '/gateways/class-ep-gateway-sofort.php';                         // Sofort Gateway
require_once __DIR__ . '/gateways/class-ep-gateway-blik.php';                           // Blik Gateway
require_once __DIR__ . '/gateways/class-ep-gateway-ideal.php';                          // iDEAL Gateway
require_once __DIR__ . '/gateways/class-ep-gateway-banks.php';                          // Banks Gateway
require_once __DIR__ . '/gateways/class-ep-gateway-googlepay.php';                      // Banks Gateway
require_once __DIR__ . '/gateways/class-ep-gateway-applepay.php';                       // Apple Pay Gateway
require_once __DIR__ . '/gateways/class-ep-gateway-more.php';                           // More PM Gateway
require_once __DIR__ . '/gateways/class-ep-gateway-brazil.php';                       // Brazil online banks Gateway

// Import payment method class for checkout blocks
require_once __DIR__ . '/includes/class-ep-gateway-blocks-support.php';

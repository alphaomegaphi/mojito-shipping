<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://mojitowp.com
 * @since      1.0.0
 *
 * @package    Mojito_Shipping
 * @subpackage Mojito_Shipping/admin/partials
 */
namespace Mojito_Shipping;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
$settings = new Mojito_Shipping_Admin(__( 'Settings', 'mojito-shipping' ));
/**
 * Setup settings
 */
$settings->set_settings();
$settings->load_settings();
/**
 * Process Form and save options
 */
$settings->process_settings();
/**
 * Should load settings forms?
 */
$load = true;
/**
 * Is multisite?
 */
if ( function_exists( 'is_multisite' ) && is_multisite() ) {
    if ( function_exists( 'is_plugin_active' ) && !is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        $load = false;
        require_once MOJITO_SHIPPING_DIR . 'admin/partials/mojito-shipping-require-plugins-woocommerce.php';
    }
} else {
    if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
        $load = false;
        require_once MOJITO_SHIPPING_DIR . 'admin/partials/mojito-shipping-require-plugins-woocommerce.php';
    }
}
if ( $load ) {
    /**
     * Display settings form
     */
    $settings->display( 'shipping' );
}
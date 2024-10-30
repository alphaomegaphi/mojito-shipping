<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Mojito_Shipping
 * @author     Mojito Team <support@mojitowp.com>
 */
namespace Mojito_Shipping;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Desactivator class
 */
class Mojito_Shipping_Deactivator {
    /**
     * Plugin deactivate.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        mojito_shipping_debug( 'Plugin deactivated' );
        if ( !class_exists( 'Mojito_Shipping\\Mojito_Shipping_Address' ) ) {
            require_once MOJITO_SHIPPING_DIR . 'includes/class-mojito-shipping-address.php';
        }
        // Set CR as a default country/region on deactivation.
        update_option( 'woocommerce_default_country', 'CR' );
        /**
         * Clear cron jobs
         */
        wp_clear_scheduled_hook( 'mojito-shippping-cron-ccr' );
        wp_clear_scheduled_hook( 'mojito-shippping-cron-pymexpress' );
        // Clear data
        $address = new Mojito_Shipping_Address();
        $address->clear_pymexpress_locations();
    }

}

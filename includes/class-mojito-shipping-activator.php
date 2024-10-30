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
 * Plugin activation class
 */
class Mojito_Shipping_Activator {
    /**
     * Plugin activation.
     *
     * @since    1.0.0
     */
    public static function activate() {
        mojito_shipping_debug( 'Plugin activated' );
        if ( !class_exists( 'Mojito_Shipping\\Mojito_Shipping_Address' ) ) {
            require_once MOJITO_SHIPPING_DIR . 'includes/class-mojito-shipping-address.php';
        }
        // Preload
        $address = new Mojito_Shipping_Address();
        $address->pre_load_pymexpress_locations();
        // Set San Jose - CR as a default country/region on activation.
        update_option( 'woocommerce_default_country', 'CR:SJ' );
        /**
         * Cron jobs
         */
        if ( !wp_next_scheduled( 'mojito-shippping-cron-ccr' ) ) {
            wp_schedule_event( time(), 'hourly', 'mojito-shippping-cron-ccr' );
        }
        if ( !wp_next_scheduled( 'mojito-shippping-cron-pymexpress' ) ) {
            wp_schedule_event( time(), 'hourly', 'mojito-shippping-cron-pymexpress' );
        }
    }

}

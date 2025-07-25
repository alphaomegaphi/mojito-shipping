<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 *
 * Freemius start
 *
 */
if ( !function_exists( 'mojito_shipping_fs' ) ) {
    // Create a helper function for easy SDK access.
    function mojito_shipping_fs() {
        global $mojito_shipping_fs;
        if ( !isset( $mojito_shipping_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $mojito_shipping_fs = fs_dynamic_init( array(
                'id'             => '5620',
                'slug'           => 'mojito-shipping',
                'type'           => 'plugin',
                'public_key'     => 'pk_99df9dad8a61014b5f20ea03e10b0',
                'is_premium'     => false,
                'has_addons'     => false,
                'has_paid_plans' => true,
                'trial'          => array(
                    'days'               => 30,
                    'is_require_payment' => false,
                ),
                'menu'           => array(
                    'slug'       => 'mojito-shipping',
                    'first-path' => 'options-general.php?page=mojito-shipping',
                    'parent'     => array(
                        'slug' => 'options-general.php',
                    ),
                ),
                'is_live'        => true,
            ) );
        }
        return $mojito_shipping_fs;
    }

    // Init Freemius.
    mojito_shipping_fs();
    // Signal that SDK was initiated.
    do_action( 'mojito_shipping_fs_loaded' );
}

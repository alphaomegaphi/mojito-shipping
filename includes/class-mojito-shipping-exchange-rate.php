<?php
/**
 * Define the exchange rates functionality.
 *
 * Loads and defines the exchange rates files for this plugin.
 *
 * @since      1.3.7
 * @package    Mojito_Shipping
 * @subpackage Mojito_Shipping/includes
 * @author     Mojito Team <support@mojitowp.com>
 */

namespace Mojito_Shipping;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Exchange rate class
 */
class Mojito_Shipping_Exchange_Rate {

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function get_exchange_rate_crc_usd() {

        $transient_key         = 'mojito-shipping-exchange-rate-crc-usd-hacienda';
        $exchange_rate_default = 500;
        $exchange_rate         = get_transient( $transient_key );
        mojito_shipping_debug('$exchange_rate ' . $exchange_rate );
        if ( empty( $exchange_rate ) ) {
            $exchange_rate = $exchange_rate_default;
        }else {
            return apply_filters( 'mojito_shipping_default_exchange_rate', $exchange_rate );
        }

        if ( ! class_exists( 'Mojito\\ExchangeRate\\Factory' ) ) {
            return $exchange_rate;
        }

        $rates    = \Mojito\ExchangeRate\Factory::create( \Mojito\ExchangeRate\ProviderTypes::CR_Hacienda );
        $response = $rates->getRates();

        if ( empty( $response ) ) {
            mojito_shipping_debug( 'Error with exchange rate' );
            mojito_shipping_debug( $response );
            return $exchange_rate;
        }

        if ( ! empty( $response->dolar->venta->valor ) ) {
            $exchange_rate = $response->dolar->venta->valor;
        }
        mojito_shipping_debug( sprintf( __( 'Exchange rate from Hacienda: %s', 'mojito-shipping' ), $exchange_rate ) );

        set_transient( $transient_key, $exchange_rate, HOUR_IN_SECONDS * 6 );

        // Save exchange rate.
        update_option( 'mojito-shipping-pymexpress-exchange-rate-rate', $exchange_rate );
        return $exchange_rate;
    }
}

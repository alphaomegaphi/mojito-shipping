<?php

/**
 * WooCommerce compatibility of the plugin.
 *
 * @link       https://mojitowp.com
 * @since      1.0.0
 * WooCommerce compatibility of the plugin.
 *
 * @package    Mojito_Shipping
 * @subpackage Mojito_Shipping/public
 * @author     Mojito Team <support@mojitowp.com>
 */
namespace Mojito_Shipping;

use WC_Shipping_Method;
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Correos de Costa Rica Base Class
 * Updated to 2021 TYT scheme
 */
class Mojito_Shipping_Method_Pymexpress extends WC_Shipping_Method {
    /**
     * WS Client
     *
     * @var Mojito_Shipping_Method_Pymexpress_WSC $pymexpress_ws_client
     */
    private $pymexpress_ws_client;

    /**
     * Constructor for shipping class
     *
     * @param string $instance_id Shipping instance ID.
     * @access public
     * @return void
     */
    public function __construct( $instance_id = 0 ) {
        $enable_value = get_option( 'woocommerce_mojito_shipping_pymexpress_enabled' );
        $title_value = get_option( 'woocommerce_mojito_shipping_pymexpress_title', __( 'Mojito Shipping: Correos de Costa Rica', 'mojito-shipping' ) );
        $this->instance_id = absint( $instance_id );
        $this->id = 'mojito_shipping_pymexpress';
        $this->method_title = __( 'Mojito Shipping: Pymexpress', 'mojito-shipping' );
        $this->method_description = __( 'Send packages using Pymexpress (Correos de Costa Rica) services', 'mojito-shipping' );
        $this->enabled = 'yes';
        $this->supports = array('shipping-zones', 'instance-settings', 'instance-settings-modal');
        $this->title = $title_value;
        $this->enabled = ( isset( $enable_value ) ? $enable_value : 'yes' );
        $this->init();
        /**
         * Local
         * - pymexpress
         */
        $this->ccr_services = array(
            'pymexpress' => 'Pymexpress',
        );
        $this->minimum_rate = 0;
    }

    /**
     * Init your settings
     *
     * @access public
     * @return void
     */
    public function init() {
        // Load the settings API.
        $this->init_form_fields();
        // This is part of the settings API. Override the method to add your own settings.
        $this->init_settings();
        // This is part of the settings API. Loads settings you previously init.
        // Save settings in admin if you have any defined.
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options') );
        // Control when orders total weight is over 30 kg, the maximum allowed by Correos de Costa Rica.
        add_filter( 'woocommerce_package_rates', array($this, 'max_weight_control'), 100 );
        /**
         * Show logo
         */
        add_filter(
            'woocommerce_cart_shipping_method_full_label',
            array($this, 'show_ccr_logo'),
            10,
            2
        );
    }

    /**
     * Add form field to WooCommerce Settings
     *
     * @return void
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'    => __( 'Enable/Disable', 'mojito-shipping' ),
                'type'     => 'checkbox',
                'label'    => __( 'Enable this shipping method', 'mojito-shipping' ),
                'default'  => get_option( 'woocommerce_mojito_shipping_pymexpress_enabled' ),
                'required' => false,
            ),
            'title'   => array(
                'title'       => __( 'Title', 'mojito-shipping' ),
                'type'        => 'text',
                'description' => __( 'Title to be display on site', 'mojito-shipping' ),
                'default'     => get_option( 'woocommerce_mojito_shipping_pymexpress_title', __( 'Mojito Shipping: Correos de Costa Rica Pymexpress', 'mojito-shipping' ) ),
                'desc_tip'    => true,
                'required'    => true,
            ),
        );
    }

    /**
     * Process admin options
     */
    public function process_admin_options() {
        if ( isset( $_POST['woocommerce_mojito_shipping_pymexpress_enabled'] ) ) {
            update_option( 'woocommerce_mojito_shipping_pymexpress_enabled', 'yes' );
            $this->settings['enabled'] = 'yes';
        } else {
            update_option( 'woocommerce_mojito_shipping_pymexpress_enabled', 'no' );
            $this->settings['enabled'] = 'no';
        }
        if ( empty( $_POST['woocommerce_mojito_shipping_pymexpress_title'] ) ) {
            $title = __( 'Mojito Shipping: Correos de Costa Rica', 'mojito-shipping' );
            update_option( 'woocommerce_mojito_shipping_pymexpress_title', $title );
            $this->settings['title'] = $title;
        } else {
            $title = sanitize_text_field( $_POST['woocommerce_mojito_shipping_pymexpress_title'] );
            update_option( 'woocommerce_mojito_shipping_pymexpress_title', $title );
            $this->settings['title'] = $title;
        }
    }

    /**
     * Calculate_shipping function.
     *
     * @access public
     * @param mixed $package Package data.
     * @return void
     */
    public function calculate_shipping( $package = array() ) {
        $this->calculate_shipping_process( $package, 'pymexpress' );
    }

    /**
     * Main calculation method
     *
     * @param array $package package data.
     * @param string $variant shipping method variant.
     * @return void
     */
    public function calculate_shipping_process( $package, $variant = 'pymexpress' ) {
        $country = $package['destination']['country'];
        $shipping_rate = 2500;
        $products = $package['contents'];
        $weight_unit = get_option( 'woocommerce_weight_unit' );
        $items = array();
        $address = new Mojito_Shipping_Address();
        $mojito_free_shipping = false;
        $ws_response_code = '';
        $ws_response_message = '';
        /**
         * Convert packages weight to grams
         */
        foreach ( $products as $id => $item ) {
            $product = \wc_get_product( $item['product_id'] );
            $attributes = $product->get_attributes();
            $items[$id] = array(
                'quantity'      => $item['quantity'],
                'weight'        => $item['data']->get_weight(),
                'free-shipping' => ( !empty( $attributes['mojito-free-shipping'] ) ? true : false ),
            );
        }
        $shipping_weight = 0;
        $free_shipping_weight = 0;
        $free_shipping_calc = false;
        foreach ( $items as $id => $data ) {
            $quantity = ( is_numeric( $data['quantity'] ) ? $data['quantity'] : 1 );
            $weight = ( is_numeric( $data['weight'] ) ? $data['weight'] : 0 );
            $product_weight = $quantity * $weight;
            if ( $data['free-shipping'] ) {
                $free_shipping_weight += $product_weight;
                $free_shipping_calc = true;
            }
            $shipping_weight += $product_weight;
        }
        if ( 'g' === $weight_unit ) {
            // no changes.
        } elseif ( 'kg' === $weight_unit ) {
            $shipping_weight = $shipping_weight * 1000;
            $free_shipping_weight = $free_shipping_weight * 1000;
        } elseif ( 'lbs' === $weight_unit ) {
            $shipping_weight = $shipping_weight / 0.0022046;
            $free_shipping_weight = $free_shipping_weight / 0.0022046;
        } elseif ( 'oz' === $weight_unit ) {
            $shipping_weight = $shipping_weight / 0.035274;
            $free_shipping_weight = $free_shipping_weight / 0.035274;
        }
        $carrier_service = '';
        if ( 'CR' === $country ) {
            // It's local shipping.
            $carrier_service = 'pymexpress';
        } else {
            // It's international shipping.
            return;
        }
        if ( 'disabled' === $carrier_service ) {
            return;
        }
        /*
         * If Destination > Postcode is empty, try to get it using country, state and city
         */
        if ( 'CR' === $package['destination']['country'] && empty( $package['destination']['postcode'] ) ) {
            if ( class_exists( 'Mojito_Shipping\\Mojito_Shipping_Address' ) ) {
                $package['destination']['postcode'] = $address->find_postcode_legacy( $package['destination']['state'], $package['destination']['city'] );
            }
        }
        $service_id = get_option( 'mojito-shipping-' . $variant . '-web-service-service-id' );
        $provincia_origen = get_option( 'mojito-shipping-' . $variant . '-store-province' );
        if ( empty( $provincia_origen ) || is_array( $provincia_origen ) && count( $provincia_origen ) === 0 ) {
            return;
        }
        $canton_origen = get_option( 'mojito-shipping-' . $variant . '-store-canton' );
        if ( empty( $canton_origen ) || is_array( $canton_origen ) && count( $canton_origen ) === 0 ) {
            return;
        }
        $distrito_origen = get_option( 'mojito-shipping-' . $variant . '-store-district' );
        if ( empty( $distrito_origen ) || is_array( $distrito_origen ) && count( $distrito_origen ) === 0 ) {
            return;
        }
        $postcode_origen = $address->get_pymexpress_postcode( $provincia_origen, $canton_origen, $distrito_origen );
        /**
         * Destination
         */
        $postcode_destino = $package['destination']['postcode'];
        $target_locations = $address->pymexpress_locations( $postcode_destino );
        $provincia_destino = $target_locations['province'] ?? '';
        $canton_destino = $target_locations['canton'] ?? '';
        $distrito_destino = $target_locations['district'] ?? '';
        if ( empty( $provincia_destino ) ) {
            //return;
        }
        if ( empty( $canton_destino ) ) {
            //return;
        }
        if ( empty( $distrito_destino ) ) {
            //return;
        }
        if ( 'custom' == $provincia_destino || 'custom' == $canton_destino || 'custom' == $distrito_destino ) {
            $postcode = apply_filters( 'mojito_shipping_pymexpress_postcode', $package['destination']['postcode'] );
            $data = $address->find_location_using_postcode( $postcode );
            if ( !empty( $data ) ) {
                $provincia_destino = $data['province'];
                $canton_destino = $data['canton'];
                $distrito_destino = $data['district'];
            }
        }
        if ( empty( $postcode_destino ) && !empty( $provincia_destino ) && !empty( $canton_destino ) && !empty( $distrito_destino ) ) {
            $postcode_destino = $address->get_pymexpress_postcode( $provincia_destino, $canton_destino, $distrito_destino );
        }
        if ( empty( $postcode_destino ) && 'yes' == get_option( 'mojito-shipping-pymexpress-cart-and-checkout-address-preselection', 'yes' ) ) {
            $postcode_destino = apply_filters( 'mojito_shipping_pymexpress_default_post_code', '10101' );
            $data = $address->find_location_using_postcode( $postcode_destino );
            if ( !empty( $data ) ) {
                $provincia_destino = $data['province'];
                $canton_destino = $data['canton'];
                $distrito_destino = $data['district'];
            }
        }
        /**
         * Dado que Correos de Costa Rica cobra cada 1000 gramos (cada kilo) se hace la modificación para que el peso del
         * paquete se redondee hacia arriba.
         * Se usa un filtro para inhabilitar si se desea.
         *
         * Esto aplica únicamente para pymexpress
         */
        $shipping_weight_for_label = $shipping_weight + $free_shipping_weight;
        if ( apply_filters( 'mojito_shipping_pymexpress_strict_shipping_weight', true ) && 'pymexpress' === $carrier_service ) {
            if ( 0 !== $shipping_weight % 1000 ) {
                $shipping_weight = (int) ($shipping_weight + (1000 - $shipping_weight % 1000));
            }
        }
        if ( 0 === $shipping_weight || !is_numeric( $shipping_weight ) ) {
            $shipping_weight = 1000;
        }
        if ( !$mojito_free_shipping ) {
            $this->pymexpress_ws_client = new Mojito_Shipping_Method_Pymexpress_WSC();
            $use_cache = get_option( 'mojito-shipping-pymexpress-ws-cache-control-allow-cache', 'no' );
            $cache_lifestime = get_option( 'mojito-shipping-pymexpress-ws-cache-control-lifetime', 600 );
            $cache_key = 'mojito_shipping_pymexpress_' . $provincia_origen . '_' . $canton_origen . '_' . $distrito_origen . '_' . $provincia_destino . '_' . $canton_destino . '_' . $distrito_destino . '_' . $service_id . '_' . $shipping_weight;
            $cache_data = get_transient( $cache_key );
            if ( 'yes' === $use_cache && !empty( $cache_data ) ) {
                mojito_shipping_debug( 'Using cache for ' . $cache_key );
                mojito_shipping_debug( $cache_data );
                $shipping_rate_data = $cache_data;
            } else {
                $shipping_rate_data = $this->pymexpress_ws_client->get_tarifa( array(
                    'provincia_origen'  => $provincia_origen,
                    'canton_origen'     => $canton_origen,
                    'distrito_origen'   => $distrito_origen,
                    'provincia_destino' => $provincia_destino,
                    'canton_destino'    => $canton_destino,
                    'distrito_destino'  => $distrito_destino,
                    'servicio'          => $service_id,
                    'peso'              => $shipping_weight,
                ) );
                if ( '00' === $shipping_rate_data['respuesta'] ) {
                    set_transient( $cache_key, $shipping_rate_data, $cache_lifestime );
                    $shipping_rate = $shipping_rate_data['tarifa'];
                }
            }
            $ws_response_code = $shipping_rate_data['respuesta'];
            $ws_response_message = $shipping_rate_data['mensaje'];
        } else {
            $shipping_rate_data['impuesto'] = 0;
            $shipping_rate = 0;
        }
        // Add IVA.
        if ( 'yes' === get_option( 'mojito-shipping-' . $variant . '-store-iva-ccr', 'yes' ) ) {
            $shipping_rate = $shipping_rate + $shipping_rate_data['impuesto'];
        }
        /**
         * Add packing costs
         */
        if ( 'enable' === get_option( 'mojito-shipping-' . $variant . '-packing-costs-enable', 'disabled' ) ) {
            $packing_costs = 0;
            $packing_size = get_option( 'mojito-shipping-' . $variant . '-packing-costs-size', 'none' );
            if ( $packing_size === 'small' ) {
                $packing_costs = 100;
            } elseif ( $packing_size === 'medium' ) {
                $packing_costs = 130;
            } elseif ( $packing_size === 'big' ) {
                $packing_costs = 150;
            }
            $custom_packing_costs = get_option( 'mojito-shipping-' . $variant . '-packing-costs-custom-packing-cost', 0 );
            if ( is_numeric( $custom_packing_costs ) && $custom_packing_costs > 0 ) {
                $packing_costs = $custom_packing_costs;
            }
            $packing_costs = apply_filters( 'mojito_shipping_pymexpress_packing_costs', $packing_costs, $items );
            $shipping_rate = $shipping_rate + $packing_costs;
        }
        /**
         * Exchange rates
         */
        if ( 'enable' === get_option( 'mojito-shipping-' . $variant . '-exchange-rate-enable', 'disabled' ) ) {
            $exchange_rate = get_option( 'mojito-shipping-' . $variant . '-exchange-rate-rate', 520 );
            $exchange_rate = apply_filters( 'mojito_shipping_pymexpress_exchange_rate', $exchange_rate );
            if ( $exchange_rate <= 0 ) {
                $exchange_rate = 1;
            }
            $shipping_rate = $shipping_rate / $exchange_rate;
        }
        /**
         * Minimal rate
         */
        if ( 'enable' === get_option( 'mojito-shipping-' . $variant . '-minimal-enable', 'disabled' ) ) {
            $this->calc_minimum_rate( $postcode_origen, $postcode_destino, $variant );
            $general_minimal_rate = (int) get_option( 'mojito-shipping-' . $variant . '-minimal-amount-general', 0 );
            if ( $shipping_rate < $general_minimal_rate ) {
                $shipping_rate = $general_minimal_rate;
            }
        }
        /**
         * Fixed rates
         */
        if ( 'enable' === get_option( 'mojito-shipping-' . $variant . '-fixed-rates-enable', 'disabled' ) ) {
            $target = $this->local_zip_code_location( $package['destination']['postcode'] );
            if ( false !== $target ) {
                if ( 'gam' === $target ) {
                    $shipping_rate = (int) get_option( 'mojito-shipping-' . $variant . '-fixed-rates-gam-rate', 0 );
                } elseif ( 'not-gam' === $target ) {
                    $shipping_rate = (int) get_option( 'mojito-shipping-' . $variant . '-fixed-rates-no-gam-rate', 0 );
                }
            }
        }
        /**
         * Coupons support
         * do we have a coupon that gives free shipping?
         */
        $all_applied_coupons = \WC()->cart->get_applied_coupons();
        if ( $all_applied_coupons ) {
            foreach ( $all_applied_coupons as $coupon_code ) {
                $this_coupon = new \WC_Coupon($coupon_code);
                if ( $this_coupon->get_free_shipping() ) {
                    $shipping_rate = 0;
                    $mojito_free_shipping = true;
                }
            }
        }
        /**
         * Create label for checkout
         */
        $country_name = WC()->countries->countries[$package['destination']['country']];
        $label_shipping_to = __( 'shipping to', 'mojito-shipping' );
        $label_grams = __( 'grams', 'mojito-shipping' );
        /**
         * Create label for checkout.
         */
        $label = get_option( 'mojito-shipping-' . $variant . '-label-label', '' );
        if ( empty( $label ) ) {
            $label = $this->title . ' ( ' . $this->ccr_services[$carrier_service] . '), ' . $label_shipping_to . ' ' . $country_name . ', ' . $shipping_weight_for_label . ' ' . $label_grams;
        } else {
            $label = str_replace( '%rate%', $shipping_rate, $label );
            $label = str_replace( '%country%', $country_name, $label );
            $label = str_replace( '%weight%', $shipping_weight_for_label, $label );
            $label = str_replace( '%weight-ccr%', $shipping_weight, $label );
        }
        /**
         * Free Shipping label.
         */
        if ( true === $mojito_free_shipping ) {
            $label .= ': ' . __( 'Free shipping', 'mojito-shipping' );
        }
        if ( $shipping_weight > 30000 ) {
            $label .= ' ';
            $label .= __( 'Alert: The maximum weight allowed by Correos de Costa Rica is 30,000 grams (30 kg )', 'mojito-shipping' );
        }
        /**
         * Mensaje de alerta.
         */
        if ( '00' !== $ws_response_code ) {
            $label = $ws_response_message;
            $shipping_rate = 0;
        }
        /**
         * Filtrar mensajes de error del WS de Correos de Costa Rica.
         */
        $label = apply_filters( 'mojito_shipping_pymexpress_ws_error_' . $ws_response_code, $label );
        $rate = array(
            'id'    => $this->id,
            'label' => $label,
            'cost'  => $shipping_rate,
        );
        /**
         * Filter per custom rate
         */
        $filter_params['shipping_weight'] = $shipping_weight;
        $filter_params['carrier_service'] = $carrier_service;
        $filter_params['carrier_name'] = $this->ccr_services[$carrier_service];
        $rate = apply_filters( 'mojito_shipping_pymexpress_checkout_custom_rate', $rate, $filter_params );
        $this->add_rate( $rate );
    }

    /**
     * Return the GAM/No Gam of a Zip code
     *
     * @param Int $code Code.
     * @return sting
     */
    private function local_zip_code_location( $code ) {
        /**
         * GAM based in Ministerio de Vivienda y Asentamientos Humanos
         * Link: https://www.mivah.go.cr/Documentos/PlanGAM2013/03-CARTOGRAFIA/1_Dimension_Urbano_Regional/Division_Politico_administrativa_Limites_GAM.pdf
         * Codes from https://correos.go.cr/codigo-postal/
         * Last updated: 2024-03-12
         */
        $codes = array(
            '10101' => 'gam',
            '10102' => 'gam',
            '10103' => 'gam',
            '10104' => 'gam',
            '10105' => 'gam',
            '10106' => 'gam',
            '10107' => 'gam',
            '10108' => 'gam',
            '10109' => 'gam',
            '10110' => 'gam',
            '10111' => 'gam',
            '10201' => 'gam',
            '10202' => 'gam',
            '10203' => 'gam',
            '10301' => 'gam',
            '10302' => 'gam',
            '10303' => 'gam',
            '10304' => 'gam',
            '10305' => 'gam',
            '10306' => 'not-gam',
            '10307' => 'gam',
            '10308' => 'not-gam',
            '10309' => 'not-gam',
            '10310' => 'gam',
            '10311' => 'gam',
            '10312' => 'gam',
            '10313' => 'gam',
            '10401' => 'not-gam',
            '10402' => 'not-gam',
            '10403' => 'not-gam',
            '10404' => 'not-gam',
            '10405' => 'not-gam',
            '10406' => 'not-gam',
            '10407' => 'not-gam',
            '10408' => 'not-gam',
            '10409' => 'not-gam',
            '10501' => 'gam',
            '10502' => 'not-gam',
            '10503' => 'not-gam',
            '10601' => 'gam',
            '10602' => 'not-gam',
            '10603' => 'not-gam',
            '10604' => 'not-gam',
            '10605' => 'not-gam',
            '10606' => 'not-gam',
            '10607' => 'gam',
            '10701' => 'gam',
            '10702' => 'not-gam',
            '10703' => 'not-gam',
            '10704' => 'not-gam',
            '10705' => 'not-gam',
            '10706' => 'not-gam',
            '10707' => 'not-gam',
            '10801' => 'gam',
            '10802' => 'gam',
            '10803' => 'gam',
            '10804' => 'gam',
            '10805' => 'gam',
            '10806' => 'gam',
            '10807' => 'gam',
            '10901' => 'gam',
            '10902' => 'gam',
            '10903' => 'gam',
            '10904' => 'gam',
            '10905' => 'gam',
            '10906' => 'gam',
            '11001' => 'gam',
            '11002' => 'gam',
            '11003' => 'gam',
            '11004' => 'gam',
            '11005' => 'gam',
            '11101' => 'gam',
            '11102' => 'gam',
            '11103' => 'not-gam',
            '11104' => 'gam',
            '11105' => 'not-gam',
            '11201' => 'gam',
            '11202' => 'not-gam',
            '11203' => 'not-gam',
            '11204' => 'not-gam',
            '11205' => 'not-gam',
            '11301' => 'gam',
            '11302' => 'gam',
            '11303' => 'gam',
            '11304' => 'gam',
            '11305' => 'gam',
            '11401' => 'gam',
            '11402' => 'gam',
            '11403' => 'gam',
            '11501' => 'gam',
            '11502' => 'gam',
            '11503' => 'gam',
            '11504' => 'gam',
            '11601' => 'not-gam',
            '11602' => 'not-gam',
            '11603' => 'not-gam',
            '11604' => 'not-gam',
            '11605' => 'not-gam',
            '11701' => 'not-gam',
            '11702' => 'not-gam',
            '11703' => 'not-gam',
            '11801' => 'gam',
            '11802' => 'gam',
            '11803' => 'gam',
            '11804' => 'gam',
            '11901' => 'not-gam',
            '11902' => 'not-gam',
            '11903' => 'not-gam',
            '11904' => 'not-gam',
            '11905' => 'not-gam',
            '11906' => 'not-gam',
            '11907' => 'not-gam',
            '11908' => 'not-gam',
            '11909' => 'not-gam',
            '11910' => 'not-gam',
            '11911' => 'not-gam',
            '11912' => 'not-gam',
            '12001' => 'gam',
            '12002' => 'not-gam',
            '12003' => 'not-gam',
            '12004' => 'not-gam',
            '12005' => 'not-gam',
            '12006' => 'not-gam',
            '20101' => 'gam',
            '20102' => 'gam',
            '20103' => 'gam',
            '20104' => 'gam',
            '20105' => 'gam',
            '20106' => 'gam',
            '20107' => 'gam',
            '20108' => 'gam',
            '20109' => 'gam',
            '20110' => 'gam',
            '20111' => 'gam',
            '20112' => 'gam',
            '20113' => 'gam',
            '20114' => 'not-gam',
            '20201' => 'gam',
            '20202' => 'not-gam',
            '20203' => 'not-gam',
            '20204' => 'not-gam',
            '20205' => 'not-gam',
            '20206' => 'not-gam',
            '20207' => 'not-gam',
            '20208' => 'not-gam',
            '20209' => 'not-gam',
            '20210' => 'not-gam',
            '20211' => 'not-gam',
            '20212' => 'not-gam',
            '20213' => 'not-gam',
            '20301' => 'gam',
            '20302' => 'not-gam',
            '20303' => 'not-gam',
            '20304' => 'not-gam',
            '20305' => 'not-gam',
            '20306' => 'not-gam',
            '20307' => 'not-gam',
            '20308' => 'not-gam',
            '20401' => 'not-gam',
            '20402' => 'not-gam',
            '20403' => 'not-gam',
            '20404' => 'not-gam',
            '20501' => 'not-gam',
            '20502' => 'gam',
            '20503' => 'gam',
            '20504' => 'not-gam',
            '20505' => 'gam',
            '20506' => 'not-gam',
            '20507' => 'not-gam',
            '20508' => 'gam',
            '20601' => 'gam',
            '20602' => 'not-gam',
            '20603' => 'not-gam',
            '20604' => 'not-gam',
            '20605' => 'not-gam',
            '20606' => 'not-gam',
            '20607' => 'not-gam',
            '20608' => 'not-gam',
            '20701' => 'gam',
            '20702' => 'not-gam',
            '20703' => 'not-gam',
            '20704' => 'not-gam',
            '20705' => 'not-gam',
            '20706' => 'not-gam',
            '20707' => 'not-gam',
            '20801' => 'gam',
            '20802' => 'gam',
            '20803' => 'gam',
            '20804' => 'gam',
            '20805' => 'gam',
            '20901' => 'not-gam',
            '20902' => 'not-gam',
            '20903' => 'not-gam',
            '20904' => 'not-gam',
            '20905' => 'not-gam',
            '21001' => 'not-gam',
            '21002' => 'not-gam',
            '21003' => 'not-gam',
            '21004' => 'not-gam',
            '21005' => 'not-gam',
            '21006' => 'not-gam',
            '21007' => 'not-gam',
            '21008' => 'not-gam',
            '21009' => 'not-gam',
            '21010' => 'not-gam',
            '21011' => 'not-gam',
            '21012' => 'not-gam',
            '21013' => 'not-gam',
            '21101' => 'not-gam',
            '21102' => 'not-gam',
            '21103' => 'not-gam',
            '21104' => 'not-gam',
            '21105' => 'not-gam',
            '21106' => 'not-gam',
            '21107' => 'not-gam',
            '21201' => 'not-gam',
            '21202' => 'not-gam',
            '21203' => 'not-gam',
            '21204' => 'not-gam',
            '21205' => 'not-gam',
            '21301' => 'not-gam',
            '21302' => 'not-gam',
            '21303' => 'not-gam',
            '21304' => 'not-gam',
            '21305' => 'not-gam',
            '21306' => 'not-gam',
            '21307' => 'not-gam',
            '21308' => 'not-gam',
            '21401' => 'not-gam',
            '21402' => 'not-gam',
            '21403' => 'not-gam',
            '21404' => 'not-gam',
            '21501' => 'not-gam',
            '21502' => 'not-gam',
            '21503' => 'not-gam',
            '21504' => 'not-gam',
            '21601' => 'not-gam',
            '21602' => 'not-gam',
            '21603' => 'not-gam',
            '30101' => 'gam',
            '30102' => 'gam',
            '30103' => 'gam',
            '30104' => 'gam',
            '30105' => 'gam',
            '30106' => 'gam',
            '30107' => 'not-gam',
            '30108' => 'gam',
            '30109' => 'gam',
            '30110' => 'gam',
            '30111' => 'gam',
            '30201' => 'gam',
            '30202' => 'gam',
            '30203' => 'not-gam',
            '30204' => 'gam',
            '30205' => 'gam',
            '30301' => 'gam',
            '30302' => 'gam',
            '30303' => 'gam',
            '30304' => 'gam',
            '30305' => 'gam',
            '30306' => 'gam',
            '30307' => 'gam',
            '30308' => 'gam',
            '30401' => 'gam',
            '30402' => 'not-gam',
            '30403' => 'not-gam',
            '30501' => 'gam',
            '30502' => 'not-gam',
            '30503' => 'not-gam',
            '30504' => 'not-gam',
            '30505' => 'not-gam',
            '30506' => 'not-gam',
            '30507' => 'not-gam',
            '30508' => 'not-gam',
            '30509' => 'not-gam',
            '30510' => 'not-gam',
            '30511' => 'not-gam',
            '30512' => 'not-gam',
            '30601' => 'gam',
            '30602' => 'gam',
            '30603' => 'gam',
            '30701' => 'gam',
            '30702' => 'not-gam',
            '30703' => 'not-gam',
            '30704' => 'not-gam',
            '30705' => 'not-gam',
            '30801' => 'gam',
            '30802' => 'gam',
            '30803' => 'gam',
            '30804' => 'not-gam',
            '40101' => 'gam',
            '40102' => 'gam',
            '40103' => 'gam',
            '40104' => 'gam',
            '40105' => 'gam',
            '40201' => 'gam',
            '40202' => 'gam',
            '40203' => 'gam',
            '40204' => 'gam',
            '40205' => 'gam',
            '40206' => 'not-gam',
            '40301' => 'gam',
            '40302' => 'gam',
            '40303' => 'gam',
            '40304' => 'gam',
            '40305' => 'gam',
            '40306' => 'gam',
            '40307' => 'gam',
            '40308' => 'gam',
            '40401' => 'gam',
            '40402' => 'gam',
            '40403' => 'gam',
            '40404' => 'gam',
            '40405' => 'gam',
            '40406' => 'gam',
            '40501' => 'gam',
            '40502' => 'gam',
            '40503' => 'gam',
            '40504' => 'gam',
            '40505' => 'gam',
            '40601' => 'gam',
            '40602' => 'gam',
            '40603' => 'gam',
            '40604' => 'gam',
            '40701' => 'gam',
            '40702' => 'gam',
            '40703' => 'gam',
            '40801' => 'gam',
            '40802' => 'gam',
            '40803' => 'gam',
            '40901' => 'gam',
            '40902' => 'not-gam',
            '41001' => 'not-gam',
            '41002' => 'not-gam',
            '41003' => 'not-gam',
            '41004' => 'not-gam',
            '41005' => 'not-gam',
            '50101' => 'not-gam',
            '50102' => 'not-gam',
            '50103' => 'not-gam',
            '50104' => 'not-gam',
            '50105' => 'not-gam',
            '50201' => 'not-gam',
            '50202' => 'not-gam',
            '50203' => 'not-gam',
            '50204' => 'not-gam',
            '50205' => 'not-gam',
            '50206' => 'not-gam',
            '50207' => 'not-gam',
            '50301' => 'not-gam',
            '50302' => 'not-gam',
            '50303' => 'not-gam',
            '50304' => 'not-gam',
            '50305' => 'not-gam',
            '50306' => 'not-gam',
            '50307' => 'not-gam',
            '50308' => 'not-gam',
            '50309' => 'not-gam',
            '50401' => 'not-gam',
            '50402' => 'not-gam',
            '50403' => 'not-gam',
            '50404' => 'not-gam',
            '50501' => 'not-gam',
            '50502' => 'not-gam',
            '50503' => 'not-gam',
            '50504' => 'not-gam',
            '50601' => 'not-gam',
            '50602' => 'not-gam',
            '50603' => 'not-gam',
            '50604' => 'not-gam',
            '50605' => 'not-gam',
            '50701' => 'not-gam',
            '50702' => 'not-gam',
            '50703' => 'not-gam',
            '50704' => 'not-gam',
            '50801' => 'not-gam',
            '50802' => 'not-gam',
            '50803' => 'not-gam',
            '50804' => 'not-gam',
            '50805' => 'not-gam',
            '50806' => 'not-gam',
            '50807' => 'not-gam',
            '50808' => 'not-gam',
            '50901' => 'not-gam',
            '50902' => 'not-gam',
            '50903' => 'not-gam',
            '50904' => 'not-gam',
            '50905' => 'not-gam',
            '50906' => 'not-gam',
            '51001' => 'not-gam',
            '51002' => 'not-gam',
            '51003' => 'not-gam',
            '51004' => 'not-gam',
            '51101' => 'not-gam',
            '51102' => 'not-gam',
            '51103' => 'not-gam',
            '51104' => 'not-gam',
            '51105' => 'not-gam',
            '60101' => 'not-gam',
            '60102' => 'not-gam',
            '60103' => 'not-gam',
            '60104' => 'not-gam',
            '60105' => 'not-gam',
            '60106' => 'not-gam',
            '60107' => 'not-gam',
            '60108' => 'not-gam',
            '60109' => 'not-gam',
            '60110' => 'not-gam',
            '60111' => 'not-gam',
            '60112' => 'not-gam',
            '60113' => 'not-gam',
            '60114' => 'not-gam',
            '60115' => 'not-gam',
            '60116' => 'not-gam',
            '60201' => 'not-gam',
            '60202' => 'not-gam',
            '60203' => 'not-gam',
            '60204' => 'not-gam',
            '60205' => 'not-gam',
            '60206' => 'not-gam',
            '60301' => 'not-gam',
            '60302' => 'not-gam',
            '60303' => 'not-gam',
            '60304' => 'not-gam',
            '60305' => 'not-gam',
            '60306' => 'not-gam',
            '60307' => 'not-gam',
            '60308' => 'not-gam',
            '60309' => 'not-gam',
            '60401' => 'not-gam',
            '60402' => 'not-gam',
            '60403' => 'not-gam',
            '60501' => 'not-gam',
            '60502' => 'not-gam',
            '60503' => 'not-gam',
            '60504' => 'not-gam',
            '60505' => 'not-gam',
            '60506' => 'not-gam',
            '60601' => 'not-gam',
            '60602' => 'not-gam',
            '60603' => 'not-gam',
            '60701' => 'not-gam',
            '60702' => 'not-gam',
            '60703' => 'not-gam',
            '60704' => 'not-gam',
            '60801' => 'not-gam',
            '60802' => 'not-gam',
            '60803' => 'not-gam',
            '60804' => 'not-gam',
            '60805' => 'not-gam',
            '60806' => 'not-gam',
            '60901' => 'not-gam',
            '61001' => 'not-gam',
            '61002' => 'not-gam',
            '61003' => 'not-gam',
            '61004' => 'not-gam',
            '61101' => 'not-gam',
            '61102' => 'not-gam',
            '61201' => 'not-gam',
            '70101' => 'not-gam',
            '70102' => 'not-gam',
            '70103' => 'not-gam',
            '70104' => 'not-gam',
            '70201' => 'not-gam',
            '70202' => 'not-gam',
            '70203' => 'not-gam',
            '70204' => 'not-gam',
            '70205' => 'not-gam',
            '70206' => 'not-gam',
            '70207' => 'not-gam',
            '70301' => 'not-gam',
            '70302' => 'not-gam',
            '70303' => 'not-gam',
            '70304' => 'not-gam',
            '70305' => 'not-gam',
            '70306' => 'not-gam',
            '70307' => 'not-gam',
            '70401' => 'not-gam',
            '70402' => 'not-gam',
            '70403' => 'not-gam',
            '70404' => 'not-gam',
            '70501' => 'not-gam',
            '70502' => 'not-gam',
            '70503' => 'not-gam',
            '70601' => 'not-gam',
            '70602' => 'not-gam',
            '70603' => 'not-gam',
            '70604' => 'not-gam',
            '70605' => 'not-gam',
        );
        if ( isset( $codes[$code] ) ) {
            return $codes[$code];
        }
        mojito_shipping_debug( 'Local zip code location not found for ' . $code );
        return false;
    }

    /**
     * Calculate minimum rate.
     *
     * @param string $postcode_origen Postcode.
     * @param string $postcode_destino Postcode.
     * @return void
     */
    private function calc_minimum_rate( $postcode_origen, $postcode_destino, $variant = 'pymexpress' ) {
        $origin = $this->local_zip_code_location( $postcode_origen );
        $target = $this->local_zip_code_location( $postcode_destino );
        if ( false === $origin || false === $target ) {
            return;
        }
        if ( 'gam' === $origin && 'gam' === $target ) {
            $this->minimum_rate = (int) get_option( 'mojito-shipping-' . $variant . '-minimal-amount-inside-gam', 0 );
        } elseif ( 'gam' === $origin && 'not-gam' === $target ) {
            $this->minimum_rate = (int) get_option( 'mojito-shipping-' . $variant . '-minimal-amount-outside-gam', 0 );
        } elseif ( 'not-gam' === $origin && 'gam' === $target ) {
            $this->minimum_rate = (int) get_option( 'mojito-shipping-' . $variant . '-minimal-amount-inside-gam', 0 );
        } elseif ( 'not-gam' === $origin && 'not-gam' === $target ) {
            $this->minimum_rate = (int) get_option( 'mojito-shipping-' . $variant . '-minimal-amount-outside-gam', 0 );
        }
    }

    /**
     * Enable or disable CCR when max order weight is over 30 kg
     *
     * @param WC_Shipping_Rate $rates Rates.
     * @return WC_Shipping_Rate
     */
    public function max_weight_control( $rates ) {
        $weight_unit = get_option( 'woocommerce_weight_unit' );
        $enable_ccr = get_option( 'mojito-shipping-pymexpress-max-weight-enable', 'disabled' );
        // "enable" means "Do not control de max weight"
        if ( 'enable' === $enable_ccr ) {
            return $rates;
        }
        // Set weight variable.
        $cart_weight = 0;
        foreach ( WC()->cart->cart_contents as $key => $value ) {
            $product = wc_get_product( $value['product_id'] );
            $product_weight = $product->get_weight();
            if ( !is_numeric( $product_weight ) ) {
                $product_weight = 0;
            }
            $product_quantity = $value['quantity'];
            if ( !is_numeric( $product_quantity ) ) {
                $product_quantity = 1;
            }
            $cart_weight += $product_weight * $product_quantity;
        }
        if ( 'g' === $weight_unit ) {
            // no changes.
        } elseif ( 'kg' === $weight_unit ) {
            $cart_weight = $cart_weight * 1000;
        } elseif ( 'lbs' === $weight_unit ) {
            $cart_weight = $cart_weight / 0.0022046;
        } elseif ( 'oz' === $weight_unit ) {
            $cart_weight = $cart_weight / 0.035274;
        }
        if ( $cart_weight > 30000 ) {
            unset($rates['mojito_shipping_pymexpress']);
        }
        return $rates;
    }

    /**
     * Show CCR logo
     * called by woocommerce_cart_shipping_method_full_label filter
     */
    public function show_ccr_logo( $label, $method ) {
        return $label;
    }

}

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

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
use WC_Shipping_Method;
/**
 * Correos de Costa Rica Base Class
 */
class Mojito_Shipping_Method_CCR extends WC_Shipping_Method {
    /**
     * WS Client
     *
     * @var Mojito_Shipping_Method_CCR_WSC $ccr_ws_client
     */
    private $ccr_ws_client;

    /**
     * Constructor for shipping class
     *
     * @param string $instance_id Shipping instance ID.
     * @access public
     * @return void
     */
    public function __construct( $instance_id = 0 ) {
        $enable_value = get_option( 'woocommerce_mojito_shipping_ccr_enabled' );
        $title_value = get_option( 'woocommerce_mojito_shipping_ccr_title', __( 'Mojito Shipping: Correos de Costa Rica', 'mojito-shipping' ) );
        $this->instance_id = absint( $instance_id );
        $this->id = 'mojito_shipping_ccr';
        $this->method_title = __( 'Mojito Shipping: Correos de Costa Rica', 'mojito-shipping' );
        $this->method_description = __( 'Send packages using Correos de Costa Rica services', 'mojito-shipping' );
        $this->enabled = 'yes';
        $this->supports = array('shipping-zones', 'instance-settings');
        $this->title = $title_value;
        $this->enabled = ( isset( $enable_value ) ? $enable_value : 'yes' );
        $this->init();
        /**
         * Local
         * - pymexpress
         * - ems-courier
         *
         * International
         * - exporta-facil
         * - ems-premium
         * - correo-internacional-prioritario
         * - correo-internacional-no-prioritario
         * - correo-internacional-prioritario-certificado
         */
        $this->ccr_services = array(
            'pymexpress'                                   => 'Pymexpress',
            'ems-courier'                                  => 'EMS Courier Nacional',
            'exporta-facil'                                => 'Exporta Fácil',
            'ems-premium'                                  => 'EMS Premium',
            'correo-internacional-prioritario'             => 'Internacional Prioritario',
            'correo-internacional-no-prioritario'          => 'Internacional No Prioritario',
            'correo-internacional-prioritario-certificado' => 'Internacional Prioritario Certificado',
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
                'default'  => get_option( 'woocommerce_mojito_shipping_ccr_enabled' ),
                'required' => false,
            ),
            'title'   => array(
                'title'       => __( 'Title', 'mojito-shipping' ),
                'type'        => 'text',
                'description' => __( 'Title to be display on site', 'mojito-shipping' ),
                'default'     => get_option( 'woocommerce_mojito_shipping_ccr_title', __( 'Mojito Shipping: Correos de Costa Rica', 'mojito-shipping' ) ),
                'desc_tip'    => true,
                'required'    => true,
            ),
        );
    }

    /**
     * Process admin options
     */
    public function process_admin_options() {
        if ( isset( $_POST['woocommerce_mojito_shipping_ccr_enabled'] ) ) {
            update_option( 'woocommerce_mojito_shipping_ccr_enabled', 'yes' );
            $this->settings['enabled'] = 'yes';
        } else {
            update_option( 'woocommerce_mojito_shipping_ccr_enabled', 'no' );
            $this->settings['enabled'] = 'no';
        }
        if ( empty( $_POST['woocommerce_mojito_shipping_ccr_title'] ) ) {
            $title = __( 'Mojito Shipping: Correos de Costa Rica', 'mojito-shipping' );
            update_option( 'woocommerce_mojito_shipping_ccr_title', $title );
            $this->settings['title'] = $title;
        } else {
            $title = sanitize_text_field( $_POST['woocommerce_mojito_shipping_ccr_title'] );
            update_option( 'woocommerce_mojito_shipping_ccr_title', $title );
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
        $this->calculate_shipping_process( $package, 'ccr' );
    }

    /**
     * Main calculation method
     *
     * @param Object $package package data.
     * @param string $variant shipping method variant.
     * @return void
     */
    public function calculate_shipping_process( $package, $variant = 'ccr' ) {
        /**
         * Define si la tienda está en el GAM o no (origen)
         * Luego define si el envio es:
         * - origen -> GAM (si es pymexpress, usar ccrMovilTarifaCCR)
         * - origen -> Fuera del GAM (si es pymexpress, usar ccrMovilTarifaCCR)
         * - origen -> Internacional
         * Verifica la unidad de peso y hace la conversión a gramos
         * Hace el cálculo del monto
         * Agrega IVA si amerita
         * Verifica los mínimos
         */
        $country = $package['destination']['country'];
        $shipping_rate = 2000;
        $products = $package['contents'];
        $weight_unit = get_option( 'woocommerce_weight_unit' );
        $items = array();
        /**
         * Convert packages weight to grams
         */
        foreach ( $products as $id => $item ) {
            $items[$id] = array(
                'quantity' => $item['quantity'],
                'weight'   => $item['data']->get_weight(),
            );
        }
        $shipping_weight = 0;
        foreach ( $items as $id => $data ) {
            $quantity = ( is_numeric( $data['quantity'] ) ? $data['quantity'] : 1 );
            $weight = ( is_numeric( $data['weight'] ) ? $data['weight'] : 0 );
            $product_weight = $quantity * $weight;
            $shipping_weight += $product_weight;
        }
        if ( 'g' === $weight_unit ) {
            // no changes.
        } elseif ( 'kg' === $weight_unit ) {
            $shipping_weight = $shipping_weight * 1000;
        } elseif ( 'lbs' === $weight_unit ) {
            $shipping_weight = $shipping_weight / 0.0022046;
        } elseif ( 'oz' === $weight_unit ) {
            $shipping_weight = $shipping_weight / 0.035274;
        }
        /**
         * Detect CCR service to use
         */
        $carrier_service = '';
        if ( 'CR' === $country ) {
            // It's local shipping.
            $carrier_service = get_option( 'mojito-shipping-' . $variant . '-store-local-shipping', 'pymexpress' );
        } else {
            // It's international shipping.
            $carrier_service = get_option( 'mojito-shipping-' . $variant . '-store-international-shipping', 'ems-premium' );
            $this->minimum_rate = (int) get_option( 'mojito-shipping-' . $variant . '-minimal-amount-international', 0 );
        }
        if ( 'disabled' === $carrier_service ) {
            return;
        }
        /**
         * Dado que Correos de Costa Rica cobra cada 1000 gramos (cada kilo) se hace la modificación para que el peso del
         * paquete se redondee hacia arriba.
         * Se usa un filtro para inhabilitar si se desea.
         */
        $shipping_weight_for_label = $shipping_weight;
        if ( apply_filters( 'mojito_shipping_ccr_strict_shipping_weight', true ) && ('pymexpress' === $carrier_service || 'ems-courier' === $carrier_service) ) {
            if ( 0 !== $shipping_weight % 1000 ) {
                $shipping_weight = (int) ($shipping_weight + (1000 - $shipping_weight % 1000));
            }
        }
        /*
         * If Destination > Postcode is empty, try to get it using country, state and city
         */
        if ( 'CR' === $package['destination']['country'] && empty( $package['destination']['postcode'] ) ) {
            if ( class_exists( 'Mojito_Shipping\\Mojito_Shipping_Address' ) ) {
                $address = new Mojito_Shipping_Address();
                $package['destination']['postcode'] = $address->find_postcode_legacy( $package['destination']['state'], $package['destination']['city'] );
            }
        }
        switch ( $carrier_service ) {
            case 'pymexpress':
                $rate = $this->tarifas_pymexpress( $package['destination'], $shipping_weight, $variant );
                break;
            case 'ems-courier':
                $rate = $this->tarifas_ems_courier( $package['destination'], $shipping_weight, $variant );
                break;
            default:
                $rate = false;
                break;
        }
        if ( false !== $rate ) {
            $shipping_rate = $rate;
        }
        /**
         * Exchange rates
         */
        if ( 'enable' === get_option( 'mojito-shipping-' . $variant . '-exchange-rate-enable', 'disabled' ) ) {
            $exchange_rate = (int) get_option( 'mojito-shipping-' . $variant . '-exchange-rate-rate', 590 );
            if ( $exchange_rate <= 0 ) {
                $exchange_rate = 1;
            }
            $shipping_rate = $shipping_rate / $exchange_rate;
        }
        /**
         * Minimal rate
         */
        if ( 'enable' === get_option( 'mojito-shipping-' . $variant . '-minimal-enable', 'disabled' ) ) {
            $general_minimal_rate = (int) get_option( 'mojito-shipping-' . $variant . '-minimal-amount-general', 0 );
            if ( $shipping_rate < $general_minimal_rate ) {
                $shipping_rate = $general_minimal_rate;
            }
        }
        /**
         * Fixed rates
         */
        if ( 'enable' === get_option( 'mojito-shipping-' . $variant . '-fixed-rates-enable', 'do-not-round' ) ) {
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
        $mojito_free_shipping = false;
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
        $rate = apply_filters( 'mojito_shipping_checkout_custom_rate', $rate, $filter_params );
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
         * Codes from https://sucursal.correos.go.cr/web/codigoPostal
         * Last updated: 2020-05-13
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
            '20701' => 'gam',
            '20702' => 'not-gam',
            '20703' => 'not-gam',
            '20704' => 'not-gam',
            '20705' => 'not-gam',
            '20706' => 'not-gam',
            '20701' => 'not-gam',
            '20707' => 'not-gam',
            '20702' => 'not-gam',
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
            '21401' => 'not-gam',
            '21402' => 'not-gam',
            '21403' => 'not-gam',
            '21404' => 'not-gam',
            '21501' => 'not-gam',
            '21502' => 'not-gam',
            '21503' => 'not-gam',
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
            '60901' => 'not-gam',
            '61001' => 'not-gam',
            '61002' => 'not-gam',
            '61003' => 'not-gam',
            '61004' => 'not-gam',
            '61101' => 'not-gam',
            '61102' => 'not-gam',
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
            '70301' => 'not-gam',
            '70302' => 'not-gam',
            '70303' => 'not-gam',
            '70304' => 'not-gam',
            '70305' => 'not-gam',
            '70306' => 'not-gam',
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
        } else {
            return false;
        }
    }

    /**
     * Tarifas_pymexpress.
     * Only for local shipping
     * https://correos.go.cr/tarifas/#1575566701405-3cf2de0e-e8c4
     * Based on La Gaceta page 52: https://www.imprentanacional.go.cr/pub/2020/05/21/COMP_21_05_2020.pdf
     *
     * @param Array $destination Destination data.
     * @param Int   $shipping_weight package weight.
     */
    private function tarifas_pymexpress( $destination = array(), $shipping_weight = 1000, $variant = 'ccr' ) {
        $origin = get_option( 'mojito-shipping-' . $variant . '-store-location', 'inside-gam' );
        $target = $this->local_zip_code_location( $destination['postcode'] );
        if ( false === $target ) {
            return;
        }
        $first_kg = 1700;
        $aditional_kg = 1000;
        if ( 'inside-gam' === $origin && 'gam' === $target ) {
            $first_kg = 1700;
            $aditional_kg = 1000;
            $this->minimum_rate = (int) get_option( 'mojito-shipping-' . $variant . '-minimal-amount-inside-gam', 0 );
        } elseif ( 'inside-gam' === $origin && 'not-gam' === $target ) {
            $first_kg = 2350;
            $aditional_kg = 1000;
            $this->minimum_rate = (int) get_option( 'mojito-shipping-' . $variant . '-minimal-amount-outside-gam', 0 );
        } elseif ( 'outside-gam' === $origin && 'gam' === $target ) {
            $first_kg = 2350;
            $aditional_kg = 1000;
            $this->minimum_rate = (int) get_option( 'mojito-shipping-' . $variant . '-minimal-amount-inside-gam', 0 );
        } elseif ( 'outside-gam' === $origin && 'not-gam' === $target ) {
            $first_kg = 2950;
            $aditional_kg = 1200;
            $this->minimum_rate = (int) get_option( 'mojito-shipping-' . $variant . '-minimal-amount-outside-gam', 0 );
        }
        if ( $shipping_weight <= 1000 ) {
            return $first_kg;
        } else {
            $rate = $first_kg;
            $aditional_rate = $shipping_weight - 1000;
            $aditional_rate = $aditional_rate / 1000;
            // weight in kg.
            $aditional_rate = $aditional_rate * $aditional_kg;
            $rate += $aditional_rate;
            return $rate;
        }
    }

    /**
     * Tarifas_ems_courier
     * Only for local shipping
     * https://correos.go.cr/tarifas/#1575566701405-a7035c6b-0f9c
     * Based on La Gaceta page 52: https://www.imprentanacional.go.cr/pub/2020/05/21/COMP_21_05_2020.pdf
     *
     * @param array $destination Destination.
     * @param int   $shipping_weight Package weight.
     */
    private function tarifas_ems_courier( $destination = array(), $shipping_weight = 1000, $variant = 'ccr' ) {
        $origin = get_option( 'mojito-shipping-ccr-store-location', 'inside-gam' );
        $target = $this->local_zip_code_location( $destination['postcode'] );
        if ( false === $target ) {
            return;
        }
        if ( 'inside-gam' === $origin && 'gam' === $target ) {
            $first_kg = 2100;
            $aditional_kg = 1200;
            $this->minimum_rate = (int) get_option( 'mojito-shipping-' . $variant . '-minimal-amount-inside-gam', 0 );
        } elseif ( 'inside-gam' === $origin && 'not-gam' === $target ) {
            $first_kg = 2850;
            $aditional_kg = 1300;
            $this->minimum_rate = (int) get_option( 'mojito-shipping-' . $variant . '-minimal-amount-outside-gam', 0 );
        } elseif ( 'outside-gam' === $origin && 'gam' === $target ) {
            $first_kg = 2850;
            $aditional_kg = 1300;
            $this->minimum_rate = (int) get_option( 'mojito-shipping-' . $variant . '-minimal-amount-inside-gam', 0 );
        } elseif ( 'outside-gam' === $origin && 'not-gam' === $target ) {
            $first_kg = 3650;
            $aditional_kg = 1500;
            $this->minimum_rate = (int) get_option( 'mojito-shipping-' . $variant . '-minimal-amount-outside-gam', 0 );
        }
        if ( $shipping_weight <= 1000 ) {
            return $first_kg;
        } else {
            $rate = $first_kg;
            $aditional_rate = $shipping_weight - 1000;
            $aditional_rate = $aditional_rate / 1000;
            // weight in kg.
            $aditional_rate = $aditional_rate * $aditional_kg;
            $rate += $aditional_rate;
            return $rate;
        }
    }

    /**
     * Tarifas_exporta_facil.
     * Only for international shipping
     * https://correos.go.cr/tarifas/#1575566947309-e834cf93-5aff
     *
     * @param array $destination Destination.
     * @param int   $weight Package weight.
     */
    private function tarifas_exporta_facil( $destination = array(), $weight = 1000, $variant = 'ccr' ) {
        $zone = $this->detect_destination_zone( $destination, $variant );
        if ( 'internal' === $zone || 'none' === $zone ) {
            return false;
        }
        if ( 'central_america' === $zone || 'us_miami' === $zone ) {
            if ( $weight > 0 && $weight <= 100 ) {
                $rate = 5700;
            } elseif ( $weight >= 101 && $weight <= 500 ) {
                $rate = 6630;
            } elseif ( $weight >= 501 && $weight <= 1000 ) {
                $rate = 8000;
            } elseif ( $weight >= 1001 && $weight <= 1500 ) {
                $rate = 8800;
            } elseif ( $weight >= 1501 && $weight <= 2000 ) {
                $rate = 9970;
            } elseif ( $weight >= 2001 && $weight <= 2500 ) {
                $rate = 12310;
            } elseif ( $weight >= 2501 && $weight <= 3000 ) {
                $rate = 13800;
            } elseif ( $weight >= 3001 && $weight <= 3500 ) {
                $rate = 15200;
            } elseif ( $weight >= 3501 && $weight <= 4000 ) {
                $rate = 16720;
            } elseif ( $weight >= 4001 && $weight <= 4500 ) {
                $rate = 18190;
            } elseif ( $weight >= 4501 && $weight <= 5000 ) {
                $rate = 19530;
            } elseif ( $weight >= 5001 ) {
                $rate = 19530 + ($weight - 5000) / 1000 * 3130;
            }
        } elseif ( 'us_all' === $zone ) {
            // Exporta Fácil.
            if ( $weight > 0 && $weight <= 100 ) {
                $rate = 8800;
            } elseif ( $weight >= 101 && $weight <= 500 ) {
                $rate = 10660;
            } elseif ( $weight >= 501 && $weight <= 1000 ) {
                $rate = 11680;
            } elseif ( $weight >= 1001 && $weight <= 1500 ) {
                $rate = 13800;
            } elseif ( $weight >= 1501 && $weight <= 2000 ) {
                $rate = 15800;
            } elseif ( $weight >= 2001 && $weight <= 2500 ) {
                $rate = 18230;
            } elseif ( $weight >= 2501 && $weight <= 3000 ) {
                $rate = 21220;
            } elseif ( $weight >= 3001 && $weight <= 3500 ) {
                $rate = 25125;
            } elseif ( $weight >= 3501 && $weight <= 4000 ) {
                $rate = 27090;
            } elseif ( $weight >= 4001 && $weight <= 4500 ) {
                $rate = 29900;
            } elseif ( $weight >= 4501 && $weight <= 5000 ) {
                $rate = 31370;
            } elseif ( $weight >= 5001 ) {
                $rate = 31370 + ($weight - 5000) / 1000 * 3130;
            }
        } elseif ( 'sur_america_and_caribbean' === $zone ) {
            // Exporta Fácil.
            if ( $weight > 0 && $weight <= 100 ) {
                $rate = 11000;
            } elseif ( $weight >= 101 && $weight <= 500 ) {
                $rate = 13300;
            } elseif ( $weight >= 501 && $weight <= 1000 ) {
                $rate = 14600;
            } elseif ( $weight >= 1001 && $weight <= 1500 ) {
                $rate = 16995;
            } elseif ( $weight >= 1501 && $weight <= 2000 ) {
                $rate = 20670;
            } elseif ( $weight >= 2001 && $weight <= 2500 ) {
                $rate = 24040;
            } elseif ( $weight >= 2501 && $weight <= 3000 ) {
                $rate = 27000;
            } elseif ( $weight >= 3001 && $weight <= 3500 ) {
                $rate = 31430;
            } elseif ( $weight >= 3501 && $weight <= 4000 ) {
                $rate = 37065;
            } elseif ( $weight >= 4001 && $weight <= 4500 ) {
                $rate = 40820;
            } elseif ( $weight >= 4501 && $weight <= 5000 ) {
                $rate = 41760;
            } elseif ( $weight >= 5001 ) {
                $rate = 41760 + ($weight - 5000) / 1000 * 3130;
            }
        } elseif ( 'europe_and_canada' === $zone ) {
            // Exporta Fácil.
            if ( $weight > 0 && $weight <= 100 ) {
                $rate = 13450;
            } elseif ( $weight >= 101 && $weight <= 500 ) {
                $rate = 15950;
            } elseif ( $weight >= 501 && $weight <= 1000 ) {
                $rate = 17850;
            } elseif ( $weight >= 1001 && $weight <= 1500 ) {
                $rate = 20400;
            } elseif ( $weight >= 1501 && $weight <= 2000 ) {
                $rate = 23950;
            } elseif ( $weight >= 2001 && $weight <= 2500 ) {
                $rate = 28000;
            } elseif ( $weight >= 2501 && $weight <= 3000 ) {
                $rate = 31250;
            } elseif ( $weight >= 3001 && $weight <= 3500 ) {
                $rate = 38350;
            } elseif ( $weight >= 3501 && $weight <= 4000 ) {
                $rate = 41850;
            } elseif ( $weight >= 4001 && $weight <= 4500 ) {
                $rate = 45350;
            } elseif ( $weight >= 4501 && $weight <= 5000 ) {
                $rate = 47850;
            } elseif ( $weight >= 5001 ) {
                $rate = 47850 + ($weight - 5000) / 1000 * 6275;
            }
        } else {
            // Exporta Fácil.
            if ( $weight > 0 && $weight <= 100 ) {
                $rate = 16000;
            } elseif ( $weight >= 101 && $weight <= 500 ) {
                $rate = 19800;
            } elseif ( $weight >= 501 && $weight <= 1000 ) {
                $rate = 22800;
            } elseif ( $weight >= 1001 && $weight <= 1500 ) {
                $rate = 26300;
            } elseif ( $weight >= 1501 && $weight <= 2000 ) {
                $rate = 30000;
            } elseif ( $weight >= 2001 && $weight <= 2500 ) {
                $rate = 33500;
            } elseif ( $weight >= 2501 && $weight <= 3000 ) {
                $rate = 37500;
            } elseif ( $weight >= 3001 && $weight <= 3500 ) {
                $rate = 44335;
            } elseif ( $weight >= 3501 && $weight <= 4000 ) {
                $rate = 48850;
            } elseif ( $weight >= 4001 && $weight <= 4500 ) {
                $rate = 52340;
            } elseif ( $weight >= 4501 && $weight <= 5000 ) {
                $rate = 55480;
            } elseif ( $weight >= 5001 ) {
                $rate = 55480 + ($weight - 5000) / 1000 * 8155;
            }
        }
        return $rate;
    }

    /**
     * Tarifas_internacional_prioritario.
     * Only for international shipping
     * https://correos.go.cr/tarifas/#1575566947309-e834cf93-5aff
     *
     * @param array $destination Destination.
     * @param int   $weight Package weight.
     */
    private function tarifas_internacional_prioritario( $destination = array(), $weight = 1000, $variant = 'ccr' ) {
        $zone = $this->detect_destination_zone( $destination, $variant );
        if ( 'internal' === $zone || 'none' === $zone ) {
            return false;
        }
        if ( 'central_america' === $zone ) {
            // Correo Básico > Correo Internacional Prioritario.
            if ( $weight > 0 && $weight <= 20 ) {
                $rate = 610;
            } elseif ( $weight >= 21 && $weight <= 50 ) {
                $rate = 720;
            } elseif ( $weight >= 51 && $weight <= 100 ) {
                $rate = 1215;
            } elseif ( $weight >= 101 ) {
                $rate = 1215 + ($weight - 100) / 100 * 1165;
            }
        } elseif ( 'north_and_south_america_and_caribbean' === $zone ) {
            // Correo Básico > Correo Internacional Prioritario.
            if ( $weight > 0 && $weight <= 20 ) {
                $rate = 665;
            } elseif ( $weight >= 21 && $weight <= 50 ) {
                $rate = 885;
            } elseif ( $weight >= 51 && $weight <= 100 ) {
                $rate = 1545;
            } elseif ( $weight >= 101 ) {
                $rate = 1545 + ($weight - 100) / 100 * 1385;
            }
        } elseif ( 'europe' === $zone ) {
            // Correo Básico > Correo Internacional Prioritario.
            if ( $weight > 0 && $weight <= 20 ) {
                $rate = 720;
            } elseif ( $weight >= 21 && $weight <= 50 ) {
                $rate = 1215;
            } elseif ( $weight >= 51 && $weight <= 100 ) {
                $rate = 2320;
            } elseif ( $weight >= 101 ) {
                $rate = 2320 + ($weight - 100) / 100 * 2155;
            }
        } elseif ( 'rest_of_the_world' === $zone ) {
            // Correo Básico > Correo Internacional Prioritario.
            if ( $weight > 0 && $weight <= 20 ) {
                $rate = 885;
            } elseif ( $weight >= 21 && $weight <= 50 ) {
                $rate = 1545;
            } elseif ( $weight >= 51 && $weight <= 100 ) {
                $rate = 3035;
            } elseif ( $weight >= 101 ) {
                $rate = 3035 + ($weight - 100) / 100 * 2815;
            }
        }
        return $rate;
    }

    /**
     * Tarifas_internacional_no_prioritario
     * Only for international shipping
     * https://correos.go.cr/tarifas/#1575566947309-e834cf93-5aff
     *
     * @param array $destination Destination.
     * @param int   $weight Package weight.
     */
    private function tarifas_internacional_no_prioritario( $destination = array(), $weight = 1000, $variant = 'ccr' ) {
        $zone = $this->detect_destination_zone( $destination, $variant );
        if ( 'internal' === $zone || 'none' === $zone ) {
            return false;
        }
        if ( 'central_america' === $zone ) {
            // Correo Básico > Correo Internacional Prioritario.
            if ( $weight > 0 && $weight <= 20 ) {
                $rate = 610;
            } elseif ( $weight >= 21 && $weight <= 50 ) {
                $rate = 655;
            } elseif ( $weight >= 51 && $weight <= 100 ) {
                $rate = 995;
            } elseif ( $weight >= 101 ) {
                $rate = 995 + ($weight - 100) / 100 * 995;
            }
        } elseif ( 'north_and_south_america_and_caribbean' === $zone ) {
            // Correo Básico > Correo Internacional Prioritario.
            if ( $weight > 0 && $weight <= 20 ) {
                $rate = 610;
            } elseif ( $weight >= 21 && $weight <= 50 ) {
                $rate = 885;
            } elseif ( $weight >= 51 && $weight <= 100 ) {
                $rate = 1215;
            } elseif ( $weight >= 101 ) {
                $rate = 1215 + ($weight - 100) / 100 * 1105;
            }
        } elseif ( 'europe' === $zone ) {
            // Correo Básico > Correo Internacional Prioritario.
            if ( $weight > 0 && $weight <= 20 ) {
                $rate = 610;
            } elseif ( $weight >= 21 && $weight <= 50 ) {
                $rate = 940;
            } elseif ( $weight >= 51 && $weight <= 100 ) {
                $rate = 1435;
            } elseif ( $weight >= 101 ) {
                $rate = 1435 + ($weight - 100) / 100 * 1435;
            }
        } elseif ( 'rest_of_the_world' === $zone ) {
            // Correo Básico > Correo Internacional Prioritario.
            if ( $weight > 0 && $weight <= 20 ) {
                $rate = 830;
            } elseif ( $weight >= 21 && $weight <= 50 ) {
                $rate = 1325;
            } elseif ( $weight >= 51 && $weight <= 100 ) {
                $rate = 2760;
            } elseif ( $weight >= 101 ) {
                $rate = 2760 + ($weight - 100) / 100 * 2760;
            }
        }
        return $rate;
    }

    /**
     * Tarifas_internacional_no_prioritario.
     * Only for international shipping
     * https://correos.go.cr/tarifas/#1575566947309-e834cf93-5aff
     *
     * @param array $destination Destination.
     * @param int   $weight Package weight.
     */
    private function tarifas_internacional_prioritario_certificado( $destination = array(), $weight = 1000, $variant = 'ccr' ) {
        $rate = $this->tarifas_internacional_prioritario( $destination, $weight, $variant ) + 940;
        return $rate;
    }

    /**
     * Tarifas_ems_premium.
     * Only for international shipping
     * https://correos.go.cr/ems-premium/
     *
     * @param array $destination Destination.
     * @param int   $weight Package weight.
     */
    private function tarifas_ems_premium( $destination = array(), $weight = 1000 ) {
        $zone = $this->detect_destination_zone( $destination );
        $rates = array(
            '500'   => array(
                'zone-1' => 19700,
                'zone-2' => 21100,
                'zone-3' => 29600,
                'zone-4' => 34100,
                'zone-5' => 41300,
                'zone-6' => 44800,
                'zone-7' => 59900,
            ),
            '1000'  => array(
                'zone-1' => 22400,
                'zone-2' => 23700,
                'zone-3' => 34900,
                'zone-4' => 40800,
                'zone-5' => 48900,
                'zone-6' => 53500,
                'zone-7' => 72500,
            ),
            '1500'  => array(
                'zone-1' => 25000,
                'zone-2' => 26300,
                'zone-3' => 40100,
                'zone-4' => 47500,
                'zone-5' => 56600,
                'zone-6' => 62300,
                'zone-7' => 85300,
            ),
            '2000'  => array(
                'zone-1' => 27700,
                'zone-2' => 29000,
                'zone-3' => 45300,
                'zone-4' => 54300,
                'zone-5' => 64200,
                'zone-6' => 71100,
                'zone-7' => 98000,
            ),
            '2500'  => array(
                'zone-1' => 30300,
                'zone-2' => 31700,
                'zone-3' => 50500,
                'zone-4' => 61100,
                'zone-5' => 71900,
                'zone-6' => 79900,
                'zone-7' => 110800,
            ),
            '3000'  => array(
                'zone-1' => 32400,
                'zone-2' => 33900,
                'zone-3' => 53500,
                'zone-4' => 65000,
                'zone-5' => 78500,
                'zone-6' => 86900,
                'zone-7' => 119800,
            ),
            '3500'  => array(
                'zone-1' => 34400,
                'zone-2' => 36200,
                'zone-3' => 56400,
                'zone-4' => 68900,
                'zone-5' => 85100,
                'zone-6' => 93800,
                'zone-7' => 128800,
            ),
            '4000'  => array(
                'zone-1' => 36500,
                'zone-2' => 38500,
                'zone-3' => 59400,
                'zone-4' => 72900,
                'zone-5' => 91700,
                'zone-6' => 100800,
                'zone-7' => 137800,
            ),
            '4500'  => array(
                'zone-1' => 38500,
                'zone-2' => 40800,
                'zone-3' => 62400,
                'zone-4' => 76800,
                'zone-5' => 98300,
                'zone-6' => 107800,
                'zone-7' => 146800,
            ),
            '5000'  => array(
                'zone-1' => 40500,
                'zone-2' => 43100,
                'zone-3' => 65400,
                'zone-4' => 80700,
                'zone-5' => 104900,
                'zone-6' => 114700,
                'zone-7' => 155800,
            ),
            '5500'  => array(
                'zone-1' => 41700,
                'zone-2' => 44400,
                'zone-3' => 66600,
                'zone-4' => 82400,
                'zone-5' => 107600,
                'zone-6' => 117300,
                'zone-7' => 159700,
            ),
            '6000'  => array(
                'zone-1' => 42800,
                'zone-2' => 45800,
                'zone-3' => 67900,
                'zone-4' => 84100,
                'zone-5' => 110300,
                'zone-6' => 119800,
                'zone-7' => 163600,
            ),
            '6500'  => array(
                'zone-1' => 44000,
                'zone-2' => 47100,
                'zone-3' => 69100,
                'zone-4' => 85700,
                'zone-5' => 113000,
                'zone-6' => 122300,
                'zone-7' => 167600,
            ),
            '7000'  => array(
                'zone-1' => 45200,
                'zone-2' => 48400,
                'zone-3' => 70400,
                'zone-4' => 87400,
                'zone-5' => 115700,
                'zone-6' => 124900,
                'zone-7' => 171500,
            ),
            '7500'  => array(
                'zone-1' => 46300,
                'zone-2' => 49700,
                'zone-3' => 71700,
                'zone-4' => 89100,
                'zone-5' => 118400,
                'zone-6' => 127400,
                'zone-7' => 175400,
            ),
            '8000'  => array(
                'zone-1' => 47500,
                'zone-2' => 51100,
                'zone-3' => 72900,
                'zone-4' => 90800,
                'zone-5' => 121100,
                'zone-6' => 130000,
                'zone-7' => 179400,
            ),
            '8500'  => array(
                'zone-1' => 48700,
                'zone-2' => 52400,
                'zone-3' => 74200,
                'zone-4' => 92400,
                'zone-5' => 123800,
                'zone-6' => 132500,
                'zone-7' => 183300,
            ),
            '9000'  => array(
                'zone-1' => 49800,
                'zone-2' => 53700,
                'zone-3' => 75400,
                'zone-4' => 94100,
                'zone-5' => 126500,
                'zone-6' => 135000,
                'zone-7' => 187200,
            ),
            '9500'  => array(
                'zone-1' => 51000,
                'zone-2' => 55100,
                'zone-3' => 76700,
                'zone-4' => 95800,
                'zone-5' => 129100,
                'zone-6' => 137600,
                'zone-7' => 191200,
            ),
            '10000' => array(
                'zone-1' => 52100,
                'zone-2' => 56400,
                'zone-3' => 77900,
                'zone-4' => 97500,
                'zone-5' => 131800,
                'zone-6' => 140100,
                'zone-7' => 195100,
            ),
            '10500' => array(
                'zone-1' => 53400,
                'zone-2' => 57800,
                'zone-3' => 79300,
                'zone-4' => 99300,
                'zone-5' => 134800,
                'zone-6' => 143200,
                'zone-7' => 199500,
            ),
            '11000' => array(
                'zone-1' => 54600,
                'zone-2' => 59200,
                'zone-3' => 80700,
                'zone-4' => 101200,
                'zone-5' => 137700,
                'zone-6' => 146400,
                'zone-7' => 204000,
            ),
            '11500' => array(
                'zone-1' => 55800,
                'zone-2' => 60600,
                'zone-3' => 82100,
                'zone-4' => 103000,
                'zone-5' => 140600,
                'zone-6' => 149600,
                'zone-7' => 208400,
            ),
            '12000' => array(
                'zone-1' => 57100,
                'zone-2' => 62000,
                'zone-3' => 83500,
                'zone-4' => 104800,
                'zone-5' => 143500,
                'zone-6' => 152700,
                'zone-7' => 212800,
            ),
            '12500' => array(
                'zone-1' => 58300,
                'zone-2' => 63400,
                'zone-3' => 84900,
                'zone-4' => 106700,
                'zone-5' => 146500,
                'zone-6' => 155800,
                'zone-7' => 217300,
            ),
            '13000' => array(
                'zone-1' => 59500,
                'zone-2' => 64900,
                'zone-3' => 86300,
                'zone-4' => 108500,
                'zone-5' => 149400,
                'zone-6' => 159000,
                'zone-7' => 221700,
            ),
            '13500' => array(
                'zone-1' => 60800,
                'zone-2' => 66300,
                'zone-3' => 87700,
                'zone-4' => 110300,
                'zone-5' => 152300,
                'zone-6' => 162200,
                'zone-7' => 226100,
            ),
            '14000' => array(
                'zone-1' => 62000,
                'zone-2' => 67700,
                'zone-3' => 89100,
                'zone-4' => 112200,
                'zone-5' => 155200,
                'zone-6' => 165300,
                'zone-7' => 230600,
            ),
            '14500' => array(
                'zone-1' => 63500,
                'zone-2' => 69100,
                'zone-3' => 90500,
                'zone-4' => 114000,
                'zone-5' => 158100,
                'zone-6' => 168400,
                'zone-7' => 235000,
            ),
            '15000' => array(
                'zone-1' => 64500,
                'zone-2' => 70500,
                'zone-3' => 91900,
                'zone-4' => 115900,
                'zone-5' => 161100,
                'zone-6' => 171600,
                'zone-7' => 239400,
            ),
            '15500' => array(
                'zone-1' => 65700,
                'zone-2' => 71900,
                'zone-3' => 93300,
                'zone-4' => 117700,
                'zone-5' => 164000,
                'zone-6' => 174700,
                'zone-7' => 243800,
            ),
            '16000' => array(
                'zone-1' => 66900,
                'zone-2' => 73300,
                'zone-3' => 94700,
                'zone-4' => 119500,
                'zone-5' => 166900,
                'zone-6' => 177900,
                'zone-7' => 248300,
            ),
            '16500' => array(
                'zone-1' => 68200,
                'zone-2' => 74700,
                'zone-3' => 96100,
                'zone-4' => 121400,
                'zone-5' => 169800,
                'zone-6' => 181000,
                'zone-7' => 252700,
            ),
            '17000' => array(
                'zone-1' => 69400,
                'zone-2' => 76100,
                'zone-3' => 97500,
                'zone-4' => 123200,
                'zone-5' => 172800,
                'zone-6' => 184200,
                'zone-7' => 257100,
            ),
            '17500' => array(
                'zone-1' => 70600,
                'zone-2' => 77500,
                'zone-3' => 98900,
                'zone-4' => 125100,
                'zone-5' => 175700,
                'zone-6' => 187300,
                'zone-7' => 261600,
            ),
            '18000' => array(
                'zone-1' => 71900,
                'zone-2' => 78900,
                'zone-3' => 100300,
                'zone-4' => 126900,
                'zone-5' => 178600,
                'zone-6' => 190500,
                'zone-7' => 266000,
            ),
            '18500' => array(
                'zone-1' => 73100,
                'zone-2' => 80300,
                'zone-3' => 101700,
                'zone-4' => 128700,
                'zone-5' => 181500,
                'zone-6' => 193600,
                'zone-7' => 270400,
            ),
            '19000' => array(
                'zone-1' => 74300,
                'zone-2' => 81700,
                'zone-3' => 103100,
                'zone-4' => 130600,
                'zone-5' => 184500,
                'zone-6' => 196800,
                'zone-7' => 274900,
            ),
            '19500' => array(
                'zone-1' => 75600,
                'zone-2' => 83200,
                'zone-3' => 104500,
                'zone-4' => 132400,
                'zone-5' => 187400,
                'zone-6' => 199900,
                'zone-7' => 279300,
            ),
            '20000' => array(
                'zone-1' => 76800,
                'zone-2' => 84600,
                'zone-3' => 105900,
                'zone-4' => 134200,
                'zone-5' => 190300,
                'zone-6' => 203100,
                'zone-7' => 283700,
            ),
            '20500' => array(
                'zone-1' => 78100,
                'zone-2' => 85900,
                'zone-3' => 107300,
                'zone-4' => 135700,
                'zone-5' => 234400,
                'zone-6' => 205400,
                'zone-7' => 287700,
            ),
            '21000' => array(
                'zone-1' => 79400,
                'zone-2' => 87200,
                'zone-3' => 108700,
                'zone-4' => 137200,
                'zone-5' => 236600,
                'zone-6' => 207600,
                'zone-7' => 291700,
            ),
            '21500' => array(
                'zone-1' => 80700,
                'zone-2' => 88500,
                'zone-3' => 110100,
                'zone-4' => 138600,
                'zone-5' => 238800,
                'zone-6' => 209900,
                'zone-7' => 295800,
            ),
            '22000' => array(
                'zone-1' => 82000,
                'zone-2' => 89800,
                'zone-3' => 111600,
                'zone-4' => 140100,
                'zone-5' => 241100,
                'zone-6' => 212200,
                'zone-7' => 299800,
            ),
            '22500' => array(
                'zone-1' => 83300,
                'zone-2' => 91100,
                'zone-3' => 113000,
                'zone-4' => 141500,
                'zone-5' => 243300,
                'zone-6' => 214400,
                'zone-7' => 303800,
            ),
            '23000' => array(
                'zone-1' => 84600,
                'zone-2' => 92400,
                'zone-3' => 114400,
                'zone-4' => 143000,
                'zone-5' => 245500,
                'zone-6' => 216700,
                'zone-7' => 307800,
            ),
            '23500' => array(
                'zone-1' => 85900,
                'zone-2' => 93800,
                'zone-3' => 115800,
                'zone-4' => 144500,
                'zone-5' => 247700,
                'zone-6' => 219000,
                'zone-7' => 311800,
            ),
            '24000' => array(
                'zone-1' => 87200,
                'zone-2' => 95100,
                'zone-3' => 117200,
                'zone-4' => 145900,
                'zone-5' => 249900,
                'zone-6' => 221200,
                'zone-7' => 315800,
            ),
            '24500' => array(
                'zone-1' => 88500,
                'zone-2' => 96400,
                'zone-3' => 118600,
                'zone-4' => 147400,
                'zone-5' => 252100,
                'zone-6' => 223500,
                'zone-7' => 319800,
            ),
            '25000' => array(
                'zone-1' => 89800,
                'zone-2' => 97700,
                'zone-3' => 120000,
                'zone-4' => 148800,
                'zone-5' => 254300,
                'zone-6' => 225800,
                'zone-7' => 323900,
            ),
            '25500' => array(
                'zone-1' => 91100,
                'zone-2' => 99000,
                'zone-3' => 121400,
                'zone-4' => 150300,
                'zone-5' => 256500,
                'zone-6' => 228000,
                'zone-7' => 327900,
            ),
            '26000' => array(
                'zone-1' => 92400,
                'zone-2' => 100300,
                'zone-3' => 122800,
                'zone-4' => 151700,
                'zone-5' => 258700,
                'zone-6' => 230300,
                'zone-7' => 331900,
            ),
            '26500' => array(
                'zone-1' => 93700,
                'zone-2' => 101600,
                'zone-3' => 124200,
                'zone-4' => 153200,
                'zone-5' => 260900,
                'zone-6' => 232600,
                'zone-7' => 335900,
            ),
            '27000' => array(
                'zone-1' => 95000,
                'zone-2' => 103000,
                'zone-3' => 125700,
                'zone-4' => 154700,
                'zone-5' => 263100,
                'zone-6' => 234800,
                'zone-7' => 339900,
            ),
            '27500' => array(
                'zone-1' => 96300,
                'zone-2' => 104300,
                'zone-3' => 127100,
                'zone-4' => 156100,
                'zone-5' => 265300,
                'zone-6' => 237100,
                'zone-7' => 343900,
            ),
            '28000' => array(
                'zone-1' => 97600,
                'zone-2' => 105600,
                'zone-3' => 128500,
                'zone-4' => 157600,
                'zone-5' => 267500,
                'zone-6' => 239400,
                'zone-7' => 347900,
            ),
            '28500' => array(
                'zone-1' => 98900,
                'zone-2' => 106900,
                'zone-3' => 129900,
                'zone-4' => 159000,
                'zone-5' => 269700,
                'zone-6' => 241600,
                'zone-7' => 352000,
            ),
            '29000' => array(
                'zone-1' => 100200,
                'zone-2' => 108200,
                'zone-3' => 131300,
                'zone-4' => 160500,
                'zone-5' => 271900,
                'zone-6' => 243900,
                'zone-7' => 356000,
            ),
            '29500' => array(
                'zone-1' => 101500,
                'zone-2' => 109500,
                'zone-3' => 132700,
                'zone-4' => 162000,
                'zone-5' => 274100,
                'zone-6' => 246200,
                'zone-7' => 360000,
            ),
            '30000' => array(
                'zone-1' => 102700,
                'zone-2' => 110800,
                'zone-3' => 134200,
                'zone-4' => 163400,
                'zone-5' => 234400,
                'zone-6' => 248500,
                'zone-7' => 363900,
            ),
        );
        if ( $weight % 500 !== 0 ) {
            $weight = $weight + (500 - $weight % 500);
        }
        $rate = $rates[$weight]['zone-' . $zone];
        return $rate;
    }

    /**
     * Detect destination zone.
     *
     * @param array $destination Package destination.
     * @return string destination.
     */
    private function detect_destination_zone( $destination, $variant = 'ccr' ) {
        $country = $destination['country'];
        $state = $destination['state'];
        $postcode = $destination['postcode'];
        $zone = 'internal';
        $service = get_option( 'mojito-shipping-' . $variant . '-store-international-shipping' );
        $miami_post_codes = array(
            '33101',
            '33102',
            '33106',
            '33111',
            '33112',
            '33116',
            '33122',
            '33124',
            '33125',
            '33126',
            '33127',
            '33128',
            '33129',
            '33130',
            '33131',
            '33132',
            '33133',
            '33134',
            '33135',
            '33136',
            '33137',
            '33138',
            '33142',
            '33143',
            '33144',
            '33145',
            '33146',
            '33147',
            '33150',
            '33151',
            '33152',
            '33153',
            '33155',
            '33156',
            '33157',
            '33158',
            '33161',
            '33162',
            '33163',
            '33164',
            '33165',
            '33166',
            '33167',
            '33168',
            '33169',
            '33170',
            '33172',
            '33173',
            '33174',
            '33175',
            '33176',
            '33177',
            '33178',
            '33179',
            '33180',
            '33181',
            '33182',
            '33183',
            '33184',
            '33185',
            '33186',
            '33187',
            '33188',
            '33189',
            '33190',
            '33191',
            '33192',
            '33193',
            '33194',
            '33195',
            '33196',
            '33197',
            '33198',
            '33199',
            '33206',
            '33222',
            '33231',
            '33233',
            '33234',
            '33238',
            '33242',
            '33243',
            '33245',
            '33247',
            '33255',
            '33256',
            '33257',
            '33261',
            '33265',
            '33266',
            '33269',
            '33280',
            '33283',
            '33296',
            '33299'
        );
        if ( 'CR' === $country ) {
            $zone = 'internal';
            return $zone;
        }
        if ( 'exporta-facil' === $service ) {
            // Zones: according with Correos de Costa Rica.
            $central_america = array(
                'BZ',
                'SV',
                'GT',
                'HN',
                'NI',
                'PA',
                'MX'
            );
            $sur_america_and_caribbean = array(
                'AR',
                'BO',
                'BR',
                'CL',
                'CO',
                'EC',
                'GF',
                'GY',
                'PY',
                'PE',
                'TT',
                'SR',
                'UY',
                'VE',
                'AG',
                'BS',
                'BB',
                'CU',
                'DM',
                'GD',
                'HT',
                'JM',
                'DO',
                'KN',
                'VC',
                'LC',
                'AI',
                'VG',
                'AW',
                'KY',
                'CW',
                'BQ',
                'TC',
                'SX',
                'MF',
                'BL',
                'VI',
                'MQ',
                'MS',
                'PR',
                'GP'
            );
            $europe_and_canada = array(
                'CA',
                'AL',
                'DE',
                'AD',
                'AM',
                'AT',
                'AZ',
                'BE',
                'BY',
                'BA',
                'BG',
                'CY',
                'HR',
                'DK',
                'SK',
                'SI',
                'ES',
                'EE',
                'FI',
                'FR',
                'GE',
                'GR',
                'HU',
                'IE',
                'IT',
                'IS',
                'KZ',
                'LV',
                'LI',
                'LT',
                'LU',
                'MK',
                'MT',
                'MD',
                'MC',
                'ME',
                'NO',
                'NL',
                'PL',
                'PT',
                'GB',
                'CZ',
                'RO',
                'RU',
                'SM',
                'RS',
                'SE',
                'CH',
                'TR',
                'UA',
                'VA',
                'JE',
                'GI',
                'IM',
                'AX',
                'GL'
            );
            if ( in_array( $country, $central_america, true ) ) {
                $zone = 'central_america';
            } elseif ( 'US' === $country ) {
                if ( 'FL' === $state && in_array( $postcode, $miami_post_codes, true ) ) {
                    $zone = 'us_miami';
                } else {
                    $zone = 'us_all';
                }
            } elseif ( in_array( $country, $sur_america_and_caribbean, true ) ) {
                $zone = 'sur_america_and_caribbean';
            } elseif ( in_array( $country, $europe_and_canada, true ) ) {
                $zone = 'europe_and_canada';
            } else {
                $zone = 'rest_of_the_world';
            }
            return $zone;
        } elseif ( 'correo-internacional-prioritario' === $service || 'correo-internacional-no-prioritario' === $service || 'correo-internacional-prioritario-certificado' === $service ) {
            // Zones: according with Correos de Costa Rica.
            $central_america = array(
                'BZ',
                'SV',
                'GT',
                'HN',
                'NI',
                'PA'
            );
            $north_and_south_america_and_caribbean = array(
                'CA',
                'US',
                'MX',
                'AR',
                'BO',
                'BR',
                'CL',
                'CO',
                'EC',
                'GF',
                'GY',
                'PY',
                'PE',
                'TT',
                'SR',
                'UY',
                'VE',
                'AG',
                'BS',
                'BB',
                'CU',
                'DM',
                'GD',
                'HT',
                'JM',
                'DO',
                'KN',
                'VC',
                'LC',
                'AI',
                'VG',
                'AW',
                'KY',
                'CW',
                'BQ',
                'TC',
                'SX',
                'MF',
                'BL',
                'VI',
                'MQ',
                'MS',
                'PR',
                'GP'
            );
            $europe = array(
                'AL',
                'DE',
                'AD',
                'AM',
                'AT',
                'AZ',
                'BE',
                'BY',
                'BA',
                'BG',
                'CY',
                'HR',
                'DK',
                'SK',
                'SI',
                'ES',
                'EE',
                'FI',
                'FR',
                'GE',
                'GR',
                'HU',
                'IE',
                'IT',
                'IS',
                'KZ',
                'LV',
                'LI',
                'LT',
                'LU',
                'MK',
                'MT',
                'MD',
                'MC',
                'ME',
                'NO',
                'NL',
                'PL',
                'PT',
                'GB',
                'CZ',
                'RO',
                'RU',
                'SM',
                'RS',
                'SE',
                'CH',
                'TR',
                'UA',
                'VA',
                'JE',
                'GI',
                'IM',
                'AX',
                'GL'
            );
            if ( in_array( $country, $central_america, true ) ) {
                $zone = 'central_america';
            } elseif ( in_array( $country, $north_and_south_america_and_caribbean, true ) ) {
                $zone = 'north_and_south_america_and_caribbean';
            } elseif ( in_array( $country, $europe, true ) ) {
                $zone = 'europe';
            } else {
                $zone = 'rest_of_the_world';
            }
            return $zone;
        } elseif ( 'ems-premium' ) {
            $zones = array();
            $zones['AD'] = '6';
            $zones['AE'] = '7';
            $zones['AF'] = '7';
            $zones['AG'] = '4';
            $zones['AI'] = '4';
            $zones['AL'] = '6';
            $zones['AM'] = '7';
            $zones['AN'] = '4';
            $zones['AO'] = '7';
            $zones['AR'] = '4';
            $zones['AS'] = '7';
            $zones['AT'] = '6';
            $zones['AU'] = '7';
            $zones['AW'] = '4';
            $zones['AZ'] = '7';
            $zones['BA'] = '6';
            $zones['BB'] = '4';
            $zones['BD'] = '5';
            $zones['BE'] = '6';
            $zones['BF'] = '7';
            $zones['BG'] = '6';
            $zones['BH'] = '7';
            $zones['BI'] = '7';
            $zones['BJ'] = '7';
            $zones['BL'] = '4';
            $zones['BM'] = '4';
            $zones['BN'] = '5';
            $zones['BO'] = '4';
            $zones['BQ'] = '4';
            $zones['BR'] = '4';
            $zones['BS'] = '4';
            $zones['BT'] = '5';
            $zones['BW'] = '7';
            $zones['BY'] = '6';
            $zones['BZ'] = '2';
            $zones['CA'] = '3';
            $zones['CD'] = '7';
            $zones['CF'] = '7';
            $zones['CG'] = '7';
            $zones['CH'] = '6';
            $zones['CI'] = '7';
            $zones['CK'] = '7';
            $zones['CL'] = '4';
            $zones['CM'] = '7';
            $zones['CN'] = '5';
            $zones['CO'] = '4';
            $zones['CU'] = '4';
            $zones['CV'] = '7';
            $zones['CW'] = '4';
            $zones['CY'] = '7';
            $zones['CZ'] = '6';
            $zones['DE'] = '6';
            $zones['DJ'] = '7';
            $zones['DK'] = '6';
            $zones['DM'] = '4';
            $zones['DO'] = '4';
            $zones['DZ'] = '7';
            $zones['EC'] = '4';
            $zones['EE'] = '6';
            $zones['EG'] = '7';
            $zones['ER'] = '7';
            $zones['ES'] = '6';
            $zones['ET'] = '7';
            $zones['FI'] = '6';
            $zones['FJ'] = '7';
            $zones['FK'] = '6';
            $zones['FL'] = '1';
            $zones['FM'] = '7';
            $zones['FO'] = '6';
            $zones['FR'] = '6';
            $zones['GA'] = '7';
            $zones['GB'] = '6';
            $zones['GD'] = '4';
            $zones['GE'] = '7';
            $zones['GF'] = '4';
            $zones['GG'] = '6';
            $zones['GH'] = '7';
            $zones['GI'] = '6';
            $zones['GL'] = '6';
            $zones['GM'] = '7';
            $zones['GN'] = '7';
            $zones['GP'] = '4';
            $zones['GQ'] = '7';
            $zones['GR'] = '6';
            $zones['GT'] = '2';
            $zones['GU'] = '7';
            $zones['GW'] = '7';
            $zones['GY'] = '4';
            $zones['HK'] = '5';
            $zones['HN'] = '2';
            $zones['HR'] = '6';
            $zones['HT'] = '4';
            $zones['HU'] = '6';
            $zones['ID'] = '5';
            $zones['IE'] = '6';
            $zones['IL'] = '7';
            $zones['IN'] = '5';
            $zones['IQ'] = '7';
            $zones['IR'] = '7';
            $zones['IS'] = '6';
            $zones['IT'] = '6';
            $zones['JE'] = '6';
            $zones['JM'] = '4';
            $zones['JO'] = '7';
            $zones['JP'] = '5';
            $zones['KE'] = '7';
            $zones['KG'] = '7';
            $zones['KH'] = '5';
            $zones['KI'] = '7';
            $zones['KM'] = '7';
            $zones['KN'] = '4';
            $zones['KP'] = '5';
            $zones['KR'] = '5';
            $zones['KW'] = '7';
            $zones['KY'] = '4';
            $zones['KZ'] = '7';
            $zones['LA'] = '5';
            $zones['LB'] = '7';
            $zones['LC'] = '4';
            $zones['LI'] = '6';
            $zones['LK'] = '5';
            $zones['LR'] = '7';
            $zones['LS'] = '7';
            $zones['LT'] = '6';
            $zones['LU'] = '6';
            $zones['LV'] = '6';
            $zones['LY'] = '7';
            $zones['MA'] = '7';
            $zones['MC'] = '6';
            $zones['MD'] = '6';
            $zones['ME'] = '6';
            $zones['MG'] = '7';
            $zones['MH'] = '7';
            $zones['MK'] = '6';
            $zones['ML'] = '7';
            $zones['MM'] = '5';
            $zones['MN'] = '7';
            $zones['MO'] = '5';
            $zones['MP'] = '7';
            $zones['MQ'] = '4';
            $zones['MR'] = '7';
            $zones['MS'] = '4';
            $zones['MT'] = '6';
            $zones['MU'] = '7';
            $zones['MV'] = '7';
            $zones['MW'] = '7';
            $zones['MX'] = '3';
            $zones['MY'] = '5';
            $zones['MZ'] = '7';
            $zones['NA'] = '7';
            $zones['NC'] = '7';
            $zones['NE'] = '7';
            $zones['NG'] = '7';
            $zones['NI'] = '2';
            $zones['NL'] = '6';
            $zones['NO'] = '6';
            $zones['NP'] = '5';
            $zones['NR'] = '7';
            $zones['NU'] = '7';
            $zones['NZ'] = '7';
            $zones['OM'] = '7';
            $zones['PA'] = '2';
            $zones['PE'] = '4';
            $zones['PF'] = '7';
            $zones['PG'] = '7';
            $zones['PH'] = '5';
            $zones['PK'] = '7';
            $zones['PL'] = '6';
            $zones['PR'] = '3';
            $zones['PT'] = '6';
            $zones['PW'] = '7';
            $zones['PY'] = '4';
            $zones['QA'] = '7';
            $zones['RE'] = '7';
            $zones['RO'] = '6';
            $zones['RS'] = '6';
            $zones['RU'] = '6';
            $zones['RW'] = '7';
            $zones['SA'] = '7';
            $zones['SB'] = '7';
            $zones['SC'] = '7';
            $zones['SD'] = '7';
            $zones['SE'] = '6';
            $zones['SG'] = '5';
            $zones['SH'] = '7';
            $zones['SI'] = '6';
            $zones['SK'] = '6';
            $zones['SL'] = '7';
            $zones['SM'] = '6';
            $zones['SN'] = '7';
            $zones['SO'] = '7';
            $zones['SR'] = '4';
            $zones['SS'] = '7';
            $zones['ST'] = '7';
            $zones['SV'] = '2';
            $zones['SX'] = '4';
            $zones['SY'] = '7';
            $zones['SZ'] = '7';
            $zones['TC'] = '4';
            $zones['TD'] = '7';
            $zones['TG'] = '7';
            $zones['TH'] = '5';
            $zones['TJ'] = '7';
            $zones['TL'] = '5';
            $zones['TM'] = '7';
            $zones['TN'] = '7';
            $zones['TO'] = '7';
            $zones['TR'] = '6';
            $zones['TT'] = '4';
            $zones['TV'] = '7';
            $zones['TW'] = '5';
            $zones['TZ'] = '7';
            $zones['UA'] = '6';
            $zones['UG'] = '7';
            $zones['US'] = '3';
            $zones['UY'] = '4';
            $zones['UZ'] = '7';
            $zones['VA'] = '6';
            $zones['VC'] = '4';
            $zones['VE'] = '4';
            $zones['VG'] = '4';
            $zones['VI'] = '7';
            $zones['VN'] = '5';
            $zones['VU'] = '7';
            $zones['WS'] = '7';
            $zones['XK'] = '6';
            $zones['YE'] = '7';
            $zones['YT'] = '7';
            $zones['ZA'] = '7';
            $zones['ZM'] = '7';
            $zones['ZW'] = '7';
            if ( 'US' === $country ) {
                if ( 'FL' === $state && in_array( $postcode, $miami_post_codes, true ) ) {
                    return $zones['FL'];
                } else {
                    return $zones[$country];
                }
            } else {
                return $zones[$country];
            }
            if ( 'FL' === $state && in_array( $postcode, $miami_post_codes, true ) ) {
                return $zones['FL'];
            }
        } else {
            return 'none';
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
        $enable_ccr = get_option( 'mojito-shipping-ccr-max-weight-enable', 'disabled' );
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
            unset($rates['mojito_shipping_ccr']);
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

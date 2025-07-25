<?php

/**
 * The file that defines the address fields
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://mojitowp.com
 * @since      1.2.0
 *
 * @package    Mojito_Shipping
 * @subpackage Mojito_Shipping/includes
 */
/**
 * Provincia, Cantón and Distrito class
 * Based in WC Provincia-Canton-Distrito. Thank you Keylor Mendoza A.!
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.2.0
 * @package    Mojito_Shipping
 * @subpackage Mojito_Shipping/includes
 * @author     Mojito Team <support@mojitowp.com>
 */
namespace Mojito_Shipping;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Mojito Shipping main class
 */
class Mojito_Shipping_Address {
    /**
     * Addresses in JSON
     *
     * @var string
     */
    private $json;

    /**
     * Construct class
     */
    public function __construct() {
        /*
         * Override billing & shipping admin order fields.
         */
        add_filter( 'woocommerce_admin_billing_fields', array($this, 'admin_order_fields_legacy'), 99 );
        add_filter( 'woocommerce_admin_shipping_fields', array($this, 'admin_order_fields_legacy'), 99 );
        /**
         * Get JSON data
         */
        $this->json = $this->get_addresses_json();
        add_action( 'wp_enqueue_scripts', array($this, 'scripts_legacy') );
        add_action( 'admin_enqueue_scripts', array($this, 'scripts_legacy') );
        /**
         * Populate states
         */
        add_filter( 'woocommerce_states', array($this, 'cr_states_legacy'), 20 );
        /**
         * Populate address fields
         */
        add_filter( 'woocommerce_default_address_fields', array($this, 'address_fields_legacy'), 20 );
        /**
         * Hide zipcode field
         */
        add_action( 'wp_head', array($this, 'hide_styles_legacy') );
        // Init web service client.
        if ( !class_exists( 'Mojito_Shipping_Method_Pymexpress_WSC' ) ) {
            require_once MOJITO_SHIPPING_DIR . 'includes/class-mojito-shipping-method-pymexpress-webservice-client.php';
        }
    }

    /**
     * Set default country/region
     * Old CCR system
     *
     * @param array $fields Form fields.
     * @return array
     */
    public function admin_order_fields_legacy( $fields ) {
        if ( !is_admin() ) {
            return $fields;
        }
        unset($fields['city']);
        unset($fields['state']);
        $country_classes = $fields['country']['class'];
        $fields['country']['class'] = $country_classes . ' country_select country_to_state';
        $fields['state'] = array(
            'label'         => apply_filters( 'mojito_shipping_state_field_label', __( 'State', 'mojito-shipping' ) ),
            'class'         => 'js_field-state select wide form-row-wide state_select',
            'wrapper_class' => 'form-field-wide',
            'show'          => false,
        );
        $fields['city'] = array(
            'label'         => apply_filters( 'mojito_shipping_city_field_label', __( 'City-District', 'mojito-shipping' ) ),
            'class'         => 'select wide city_select',
            'wrapper_class' => 'form-field-wide',
            'show'          => false,
            'placeholder'   => apply_filters( 'mojito_shipping_city_field_placeholder', __( 'Choose a city', 'mojito-shipping' ) ),
        );
        return $fields;
    }

    /**
     * Get json addresses data.
     * Old CCR system
     *
     * @return array
     */
    public function get_addresses_json() {
        $json = file_get_contents( MOJITO_SHIPPING_DIR . '/public/js/addresses-2024.json' );
        $json = apply_filters( 'mojito_shipping_addresses_json_data', $json );
        return ( false !== $json ? $json : '' );
    }

    /**
     * Try to get the postcode using state and city.
     */
    public function find_postcode_legacy( $state = 'SJ', $city = 'San José, Carmen' ) {
        $data = json_decode( $this->json );
        if ( empty( $state ) ) {
            return;
        }
        if ( !is_array( $data->CR->{$state} ) ) {
            return;
        }
        foreach ( $data->CR->{$state} as $key => $location ) {
            if ( $city === $location->city ) {
                return $location->zip;
            }
        }
    }

    /**
     * Try to get the district using state and city.
     *
     * @param string $post_code Postcode to find.
     * @return array
     */
    public function find_location_using_postcode( $post_code ) {
        $province_id = substr( $post_code, 0, 1 );
        $canton_id = substr( $post_code, 1, 2 );
        $distric_id = substr( $post_code, 3, 2 );
        return array(
            'province' => $province_id,
            'canton'   => $canton_id,
            'district' => $distric_id,
        );
    }

    /**
     * WP locations allowed.
     *
     * @return bool
     */
    private function locations_allowed() {
        if ( function_exists( 'is_cart' ) && is_cart() ) {
            return true;
        }
        if ( function_exists( 'is_checkout' ) && is_checkout() ) {
            return true;
        }
        if ( function_exists( 'is_account_page' ) && is_account_page() ) {
            return true;
        }
        if ( function_exists( 'is_admin' ) && is_admin() ) {
            global $pagenow;
            if ( 'post.php' === $pagenow && isset( $_GET['post'] ) ) {
                $post_id = (int) sanitize_text_field( $_GET['post'] );
                $post = wc_get_order( $post_id );
                if ( 'shop_order' === $post->post_type ) {
                    return true;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Load scripts.
     *
     * @return void
     */
    public function scripts_legacy() {
        if ( $this->locations_allowed() ) {
            $handle = 'mojito-shipping-address-script';
            wp_enqueue_script(
                $handle,
                plugin_dir_url( __DIR__ ) . 'public/js/addresses.js',
                array('jquery'),
                MOJITO_SHIPPING_VERSION,
                true
            );
            $ajax_data = array(
                'ajax_url'          => admin_url( 'admin-ajax.php' ),
                'city_first_option' => apply_filters( 'mojito_shipping_city_field_placeholder', __( 'Choose a city', 'mojito-shipping' ) ),
                'json'              => $this->json,
                'preselect_address' => get_option( 'mojito-shipping-pymexpress-cart-and-checkout-address-preselection', 'yes' ),
            );
            $debug_enabled = ( defined( 'MOJITO_SHIPPING_DEBUG' ) ? \MOJITO_SHIPPING_DEBUG : false );
            if ( 'yes' === get_option( 'mojito-shipping-settings-debug', 'no' ) ) {
                $debug_enabled = true;
                $ajax_data['debug'] = true;
                $ajax_data['version'] = MOJITO_SHIPPING_VERSION;
                $ajax_data['type'] = 'free';
            } else {
                $debug_enabled = false;
                $ajax_data['debug'] = false;
            }
            wp_localize_script( $handle, 'mojito_shipping_ajax', $ajax_data );
        }
    }

    /**
     * Set states
     *
     * @param array $states States.
     * @return array
     */
    public function cr_states_legacy( $states ) {
        $states['CR'] = array(
            'SJ' => 'San José',
            'AL' => 'Alajuela',
            'CG' => 'Cartago',
            'HD' => 'Heredia',
            'GT' => 'Guanacaste',
            'PT' => 'Puntarenas',
            'LM' => 'Limón',
        );
        return $states;
    }

    /**
     * Returns province's code using state code
     *
     * @param string $state  State code (SJ, AL, etc).
     * @return string
     */
    public function cr_states_to_code_pymexpress( $state ) {
        $states = array(
            'SJ' => '1',
            'AL' => '2',
            'CG' => '3',
            'HD' => '4',
            'GT' => '5',
            'PT' => '6',
            'LM' => '7',
        );
        return $states[$state];
    }

    /**
     * WOO default address fields
     *
     * @param array $fields Fields.
     * @return array
     */
    public function address_fields_legacy( $fields ) {
        $fields['state']['label'] = apply_filters( 'mojito_shipping_state_field_label', __( 'State', 'mojito-shipping' ) );
        $fields['city']['label'] = apply_filters( 'mojito_shipping_city_field_label', __( 'City-District', 'mojito-shipping' ) );
        $fields['city']['placeholder'] = apply_filters( 'mojito_shipping_city_field_placeholder', __( 'Choose a city', 'mojito-shipping' ) );
        $fields['city']['class'] = array('city_select', 'input-text');
        $fields['state']['priority'] = 42;
        $fields['city']['priority'] = 43;
        $fields['address_1']['priority'] = 44;
        $fields['address_2']['priority'] = 45;
        /* Fix WC 3.5 */
        $fields = $this->order_fields_legacy( $fields );
        if ( 'yes' !== get_option( 'mojito-shipping-ccr-address-fields-show-zipcode', 'yes' ) ) {
            $fields['postcode']['class'] = array('mojito-shipping-hide-zipcode');
        }
        return $fields;
    }

    /**
     * Manage address field in checkout page
     *
     * @param array  $fields Fields.
     * @param string $main_key Key.
     * @return array
     */
    private function order_fields_legacy( $fields, $main_key = '' ) {
        $checkout_new_order = array();
        foreach ( $fields as $key => $single_key ) {
            $checkout_new_order[$key] = $fields[$key];
            if ( preg_match( '/country/', $key ) ) {
                $checkout_new_order[$main_key . 'state'] = $fields[$main_key . 'state'];
                $checkout_new_order[$main_key . 'city'] = $fields[$main_key . 'city'];
                $checkout_new_order[$main_key . 'address_1'] = $fields[$main_key . 'address_1'];
                $checkout_new_order[$main_key . 'address_2'] = $fields[$main_key . 'address_2'];
            }
        }
        return $checkout_new_order;
    }

    /**
     * Hides postcode field in shipping calculator.
     *
     * @return void
     */
    public function hide_styles_legacy() {
        if ( 'yes' !== get_option( 'mojito-shipping-ccr-address-fields-show-zipcode', 'yes' ) && $this->locations_allowed() ) {
            ?>
			<style type="text/css">
				.mojito-shipping-hide-zipcode,
				#calc_shipping_postcode_field {
					display: none !important;
				}
			</style>
			<?php 
        }
    }

    /**
     * PYMEXPRESS NEW METHODS
     */
    /**
     * Get Provinces list from CCR.
     *
     * @return array
     */
    public function get_pymexpress_provinces_list() {
        $transient_key = 'mojito-shipping-provinces';
        $provinces = get_transient( $transient_key );
        if ( is_array( $provinces ) && !empty( $provinces ) ) {
            do_action( 'mojito_shipping_address_pymexpress_provinces_list', $transient_key, $provinces );
            return $provinces;
        } else {
            $ws = new Mojito_Shipping_Method_Pymexpress_WSC();
            $provinces = $ws->get_provincias();
            do_action( 'mojito_shipping_address_pymexpress_provinces_list', $transient_key, $provinces );
            set_transient( $transient_key, $provinces, 0 );
            return $provinces;
        }
    }

    /**
     * GET Provinces list with ajax
     *
     * @return void
     */
    public function get_pymexpress_provinces_list_ajax() {
        $provinces = $this->get_pymexpress_provinces_list();
        asort( $provinces, SORT_STRING );
        echo json_encode( $provinces );
        wp_die();
    }

    /**
     * Get canton list from CCR
     *
     * @param int $province Province code 1 to 7.
     * @return array
     */
    public function get_pymexpress_cantons_list( $province ) {
        if ( empty( $province ) ) {
            return array();
        }
        $transient_key = 'mojito-shipping-cantones-from-province-' . $province;
        $cantones = get_transient( $transient_key );
        if ( is_array( $cantones ) && !empty( $cantones ) ) {
            do_action( 'mojito_shipping_address_pymexpress_cantons_list', $transient_key, $cantones );
            return $cantones;
        } else {
            $ws = new Mojito_Shipping_Method_Pymexpress_WSC();
            $cantones = $ws->get_cantones( $province );
            do_action( 'mojito_shipping_address_pymexpress_cantons_list', $transient_key, $cantones );
            set_transient( $transient_key, $cantones, 0 );
            return $cantones;
        }
    }

    /**
     * GET canton list with ajax
     *
     * @return void
     */
    public function get_pymexpress_cantons_list_ajax() {
        if ( empty( $_POST['province'] ) ) {
            die;
        }
        $province = sanitize_text_field( $_POST['province'] );
        $cantones = $this->get_pymexpress_cantons_list( $province );
        asort( $cantones, SORT_STRING );
        echo json_encode( $cantones );
        wp_die();
    }

    /**
     * Get canton list from CCR
     *
     * @param int $province Province code 1 to 7.
     * @param string $canton Canton code.
     * @return array
     */
    public function get_pymexpress_districts_list( string $province, string $canton ) {
        if ( empty( $province ) ) {
            return array();
        }
        if ( empty( $canton ) ) {
            return array();
        }
        $transient_key = 'mojito-shipping-districts-from-province-' . $province . '-and-canton-' . $canton;
        $districts = get_transient( $transient_key );
        if ( is_array( $districts ) && !empty( $districts ) ) {
            do_action( 'mojito_shipping_address_pymexpress_districts_list', $transient_key, $districts );
            return $districts;
        } else {
            $ws = new Mojito_Shipping_Method_Pymexpress_WSC();
            $districts = $ws->get_distritos( $province, $canton );
            do_action( 'mojito_shipping_address_pymexpress_districts_list', $transient_key, $districts );
            set_transient( $transient_key, $districts, 0 );
            return $districts;
        }
    }

    /**
     * GET district list with ajax
     *
     * @return void
     */
    public function get_pymexpress_districts_list_ajax() {
        if ( empty( $_POST['province'] ) || empty( $_POST['canton'] ) ) {
            die;
        }
        $province = sanitize_text_field( $_POST['province'] );
        $canton = sanitize_text_field( $_POST['canton'] );
        $districts = $this->get_pymexpress_districts_list( (string) $province, (string) $canton );
        asort( $districts, SORT_STRING );
        echo json_encode( $districts );
        wp_die();
    }

    /**
     * Get cities list from CCR
     *
     * @param int $province_id Province code 1 to 7.
     * @return array
     */
    public function get_pymexpress_cities_list( $province_id ) {
        $transient_key = 'mojito-shipping-cities-from-province-' . $province_id;
        $cities = get_transient( $transient_key );
        if ( is_array( $cities ) && !empty( $cities ) ) {
            do_action( 'mojito_shipping_address_pymexpress_cities_list', $transient_key, $cities );
            return $cities;
        } else {
            $cantons = $this->get_pymexpress_cantons_list( $province_id );
            foreach ( $cantons as $canton_id => $canton_name ) {
                $districts = $this->get_pymexpress_districts_list( (string) $province_id, (string) $canton_id );
                foreach ( $districts as $district_id => $district_name ) {
                    $cities[$canton_id . '-' . $district_id] = $canton_name . ', ' . $district_name;
                }
            }
            do_action( 'mojito_shipping_address_pymexpress_cities_list', $transient_key, $cities );
            return $cities;
        }
    }

    /**
     * GET cities list with ajax
     *
     * @return void
     */
    public function get_pymexpress_cities_list_ajax() {
        if ( empty( $_POST['province'] ) ) {
            die;
        }
        $province = sanitize_text_field( $_POST['province'] );
        $cities = $this->get_pymexpress_cities_list( $province );
        asort( $cities, SORT_STRING );
        echo json_encode( $cities );
        wp_die();
    }

    /**
     * Get Postcode from CCR
     *
     * @param string $province_id Province code 1 to 7.
     * @param string $canton_id Canton code.
     * @param string $district_id District code.
     * @return string
     */
    public function get_pymexpress_postcode( $province_id, $canton_id, $district_id ) {
        if ( !is_string( $province_id ) ) {
            $province_id = (string) $province_id;
        }
        if ( !is_string( $canton_id ) ) {
            $canton_id = (string) $canton_id;
        }
        if ( !is_string( $district_id ) ) {
            $district_id = (string) $district_id;
        }
        $transient_key = 'mojito-shipping-postcode-from-province-' . $province_id . '-and-canton-' . $canton_id . '-and-district-' . $district_id;
        $postcode = get_transient( $transient_key );
        if ( $postcode ) {
            do_action( 'mojito_shipping_address_pymexpress_postcode_list', $transient_key, $postcode );
            return $postcode;
        } else {
            $ws = new Mojito_Shipping_Method_Pymexpress_WSC();
            $postcode = $ws->get_codigo_postal( $province_id, $canton_id, $district_id );
            set_transient( $transient_key, $postcode, 0 );
            do_action( 'mojito_shipping_address_pymexpress_postcode_list', $transient_key, $postcode );
            return $postcode;
        }
    }

    /**
     * Load All locations from Pymexpress manually into trasients
     *
     * @return void
     */
    public function pre_load_pymexpress_locations() {
        /**************************/
        /* Cantons from San José */
        set_transient( 'mojito-shipping-cantones-from-province-1', array(
            '01' => 'San José',
            '02' => 'Escazú',
            '03' => 'Desamparados',
            '04' => 'Puriscal',
            '05' => 'Tarrazu',
            '06' => 'Aserrí',
            '07' => 'Mora',
            '08' => 'Goicoechea',
            '09' => 'Santa Ana',
            '10' => 'Alajuelita',
            '11' => 'Vazquez de Coronado',
            '12' => 'Acosta',
            '13' => 'Tibás',
            '14' => 'Moravia',
            '15' => 'Montes de Oca',
            '16' => 'Turrubares',
            '17' => 'Dota',
            '18' => 'Curridabat',
            '19' => 'Pérez Zeledón',
            '20' => 'León Cortes',
        ), 0 );
        /* Districts from San José > San José */
        set_transient( 'mojito-shipping-districts-from-province-1-and-canton-01', array(
            '01' => 'Carmen',
            '02' => 'Merced',
            '03' => 'Hospital',
            '04' => 'Catedral',
            '05' => 'Zapote',
            '06' => 'San Francisco de Dos Ríos',
            '07' => 'Uruca',
            '08' => 'Mata Redonda',
            '09' => 'Pavas',
            '10' => 'Hatillo',
            '11' => 'San Sebastián',
        ), 0 );
        /* Zip Codes from San José > San José */
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-01-and-district-01', 10101, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-01-and-district-02', 10102, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-01-and-district-03', 10103, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-01-and-district-04', 10104, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-01-and-district-05', 10105, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-01-and-district-06', 10106, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-01-and-district-07', 10107, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-01-and-district-08', 10108, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-01-and-district-09', 10109, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-01-and-district-10', 10110, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-01-and-district-11', 10111, 0 );
        /* Districts from San José > Escazú */
        set_transient( 'mojito-shipping-districts-from-province-1-and-canton-02', array(
            '01' => 'Escazú',
            '02' => 'San Antonio',
            '03' => 'San Rafael',
        ), 0 );
        /* Zip Codes from San José > Escazú */
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-02-and-district-01', 10201, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-02-and-district-02', 10202, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-02-and-district-03', 10203, 0 );
        /* Districts from San José > Desamparados */
        set_transient( 'mojito-shipping-districts-from-province-1-and-canton-03', array(
            '01' => 'Desamparados',
            '02' => 'San Miguel',
            '03' => 'San Juan de Dios',
            '04' => 'San Rafael Arriba',
            '05' => 'San Antonio',
            '06' => 'Frailes',
            '07' => 'Patarra',
            '08' => 'San Cristobal',
            '09' => 'Rosario',
            '10' => 'Damas',
            '11' => 'San Rafael Abajo',
            '12' => 'Gravilias',
            '13' => 'Los Guido',
        ), 0 );
        /* Zip Codes from San José > Desamparados */
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-01', 10301, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-02', 10302, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-03', 10303, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-04', 10304, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-05', 10305, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-06', 10306, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-07', 10307, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-08', 10308, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-09', 10309, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-10', 10310, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-11', 10311, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-12', 10312, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-13', 10313, 0 );
        /* Districts from San José > Puriscal */
        set_transient( 'mojito-shipping-districts-from-province-1-and-canton-04', array(
            '01' => 'Santiago',
            '02' => 'Mercedes Sur',
            '03' => 'Barbacoas',
            '04' => 'Grifo Alto',
            '05' => 'San Rafael',
            '06' => 'Candelaria',
            '07' => 'Desamparaditos',
            '08' => 'San Antonio',
            '09' => 'Chires',
        ), 0 );
        /* Zip Codes from San José > Puriscal */
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-04-and-district-01', 10401, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-04-and-district-02', 10402, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-04-and-district-03', 10403, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-04-and-district-04', 10404, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-04-and-district-05', 10405, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-04-and-district-06', 10406, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-04-and-district-07', 10407, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-04-and-district-08', 10408, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-04-and-district-09', 10409, 0 );
        /* Districts from San José > Tarrazu */
        set_transient( 'mojito-shipping-districts-from-province-1-and-canton-05', array(
            '01' => 'San Marcos',
            '02' => 'San Lorenzo',
            '03' => 'San Carlos',
        ), 0 );
        /* Zip Codes from San José > Tarrazu */
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-05-and-district-01', 10501, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-05-and-district-02', 10502, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-05-and-district-03', 10503, 0 );
        /* Districts from San José > Aserrí */
        set_transient( 'mojito-shipping-districts-from-province-1-and-canton-06', array(
            '01' => 'Aserrí',
            '02' => 'Tarbaca',
            '03' => 'Vuelta de Jorco',
            '04' => 'San Gabriel',
            '05' => 'Legua',
            '06' => 'Monterrey',
            '07' => 'Salitrillos',
        ), 0 );
        /* Zip Codes from San José > Aserrí */
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-06-and-district-01', 10601, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-06-and-district-02', 10602, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-06-and-district-03', 10603, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-06-and-district-04', 10604, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-06-and-district-05', 10605, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-06-and-district-06', 10606, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-06-and-district-07', 10607, 0 );
        /* Districts from San José > Mora */
        set_transient( 'mojito-shipping-districts-from-province-1-and-canton-07', array(
            '01' => 'Colón',
            '02' => 'Guayabo',
            '03' => 'Tabarcia',
            '04' => 'Piedras Negras',
            '05' => 'Picagres',
            '06' => 'Jaris',
            '07' => 'Quitirrizi',
        ), 0 );
        /* Zip Codes from San José > Mora */
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-07-and-district-01', 10701, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-07-and-district-02', 10702, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-07-and-district-03', 10703, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-07-and-district-04', 10704, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-07-and-district-05', 10705, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-07-and-district-06', 10706, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-07-and-district-07', 10707, 0 );
        /* Districts from San José > Goicoechea */
        set_transient( 'mojito-shipping-districts-from-province-1-and-canton-08', array(
            '01' => 'Guadalupe',
            '02' => 'San Francisco',
            '03' => 'Calle Blancos',
            '04' => 'Mata de Plátano',
            '05' => 'Ipís',
            '06' => 'Rancho Redondo',
            '07' => 'Purral',
        ), 0 );
        /* Zip Codes from San José > Goicoechea */
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-08-and-district-01', 10801, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-08-and-district-02', 10802, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-08-and-district-03', 10803, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-08-and-district-04', 10804, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-08-and-district-05', 10805, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-08-and-district-06', 10806, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-08-and-district-07', 10807, 0 );
        /* Districts from San José > Santa Ana */
        set_transient( 'mojito-shipping-districts-from-province-1-and-canton-09', array(
            '01' => 'Santa Ana',
            '02' => 'Salitral',
            '03' => 'Pozos',
            '04' => 'Uruca',
            '05' => 'Piedades',
            '06' => 'Brasil',
        ), 0 );
        /* Zip Codes from San José > Santa Ana */
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-09-and-district-01', 10901, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-09-and-district-02', 10902, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-09-and-district-03', 10903, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-09-and-district-04', 10904, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-09-and-district-05', 10905, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-09-and-district-06', 10906, 0 );
        /* Districts from San José > Alajuelita */
        set_transient( 'mojito-shipping-districts-from-province-1-and-canton-10', array(
            '01' => 'Alajuelita',
            '02' => 'San Josecito',
            '03' => 'San Antonio',
            '04' => 'Concepción',
            '05' => 'San Felipe',
        ), 0 );
        /* Zip Codes from San José > Alajuelita */
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-10-and-district-01', 11001, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-10-and-district-02', 11002, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-10-and-district-03', 11003, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-10-and-district-04', 11004, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-10-and-district-05', 11005, 0 );
        /* Districts from San José > Vazquez de Coronado */
        set_transient( 'mojito-shipping-districts-from-province-1-and-canton-11', array(
            '01' => 'San Isidro',
            '02' => 'San Rafael',
            '03' => 'Dulce Nombre de Jesús',
            '04' => 'Patalillo',
            '05' => 'Cascajal',
        ), 0 );
        /* Zip Codes from San José > Vazquez de Coronado */
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-11-and-district-01', 11101, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-11-and-district-02', 11102, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-11-and-district-03', 11103, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-11-and-district-04', 11104, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-11-and-district-05', 11105, 0 );
        /* Districts from San José > Acosta */
        set_transient( 'mojito-shipping-districts-from-province-1-and-canton-12', array(
            '01' => 'San Ignacio de Acosta',
            '02' => 'Guaitil',
            '03' => 'Palmichal',
            '04' => 'Cangrejal',
            '05' => 'Sabanillas',
        ), 0 );
        /* Zip Codes from San José > Acosta */
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-12-and-district-01', 11201, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-12-and-district-02', 11202, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-12-and-district-03', 11203, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-12-and-district-04', 11204, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-12-and-district-05', 11205, 0 );
        /* Districts from San José > Tibás */
        set_transient( 'mojito-shipping-districts-from-province-1-and-canton-13', array(
            '01' => 'San Juan',
            '02' => 'Cinco esquinas',
            '03' => 'Anselmo Llorente',
            '04' => 'Leon XIII',
            '05' => 'Colima',
        ), 0 );
        /* Zip Codes from San José > Tibás */
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-13-and-district-01', 11301, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-13-and-district-02', 11302, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-13-and-district-03', 11303, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-13-and-district-04', 11304, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-13-and-district-05', 11305, 0 );
        /* Districts from San José > Moravia */
        set_transient( 'mojito-shipping-districts-from-province-1-and-canton-14', array(
            '01' => 'San Vicente',
            '02' => 'San Jerónimo',
            '03' => 'Trinidad',
        ), 0 );
        /* Zip Codes from San José > Moravia */
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-14-and-district-01', 11401, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-14-and-district-02', 11402, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-14-and-district-03', 11403, 0 );
        /* Districts from San José > Montes de Oca */
        set_transient( 'mojito-shipping-districts-from-province-1-and-canton-15', array(
            '01' => 'San Pedro',
            '02' => 'Sabanilla',
            '03' => 'Mercedes',
            '04' => 'San Rafael',
        ), 0 );
        /* Zip Codes from San José > Montes de Oca */
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-15-and-district-01', 11501, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-15-and-district-02', 11502, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-15-and-district-03', 11503, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-15-and-district-04', 11504, 0 );
        /* Districts from San José > Turrubares */
        set_transient( 'mojito-shipping-districts-from-province-1-and-canton-16', array(
            '01' => 'San Pablo',
            '02' => 'San Pedro',
            '03' => 'San Juan de Mata',
            '04' => 'San Luis',
            '05' => 'Carara',
        ), 0 );
        /* Zip Codes from San José > Turrubares */
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-16-and-district-01', 11601, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-16-and-district-02', 11602, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-16-and-district-03', 11603, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-16-and-district-04', 11604, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-16-and-district-05', 11605, 0 );
        /* Districts from San José > Dota */
        set_transient( 'mojito-shipping-districts-from-province-1-and-canton-17', array(
            '01' => 'Santa María',
            '02' => 'Jardín',
            '03' => 'Copey',
        ), 0 );
        /* Zip Codes from San José > Dota */
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-17-and-district-01', 11701, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-17-and-district-02', 11702, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-17-and-district-03', 11703, 0 );
        /* Districts from San José > Curridabat */
        set_transient( 'mojito-shipping-districts-from-province-1-and-canton-18', array(
            '01' => 'Curridabat',
            '02' => 'Granadilla',
            '03' => 'Sánchez',
            '04' => 'Tirrases',
        ), 0 );
        /* Zip Codes from San José > Curridabat */
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-18-and-district-01', 11801, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-18-and-district-02', 11802, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-18-and-district-03', 11803, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-18-and-district-04', 11804, 0 );
        /* Districts from San José > Pérez Zeledón */
        set_transient( 'mojito-shipping-districts-from-province-1-and-canton-19', array(
            '01' => 'San Isidro del General',
            '02' => 'General',
            '03' => 'Daniel Flores',
            '04' => 'Rivas',
            '05' => 'San Pedro',
            '06' => 'Platanares',
            '07' => 'Pejibaye',
            '08' => 'Cajón',
            '09' => 'Barú',
            '10' => 'Río Nuevo',
            '11' => 'Páramo',
            '12' => 'LA AMISTAD',
        ), 0 );
        /* Zip Codes from San José > Pérez Zeledón */
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-01', 11901, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-02', 11902, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-03', 11903, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-04', 11904, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-05', 11905, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-06', 11906, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-07', 11907, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-08', 11908, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-09', 11909, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-10', 11910, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-11', 11911, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-12', 11912, 0 );
        /* Districts from San José > León Cortes */
        set_transient( 'mojito-shipping-districts-from-province-1-and-canton-20', array(
            '01' => 'San Pablo',
            '02' => 'San Andrés',
            '03' => 'Llano Bonito',
            '04' => 'San Isidro',
            '05' => 'Santa Cruz',
            '06' => 'San Antonio',
        ), 0 );
        /* Zip Codes from San José > León Cortes */
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-20-and-district-01', 12001, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-20-and-district-02', 12002, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-20-and-district-03', 12003, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-20-and-district-04', 12004, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-20-and-district-05', 12005, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-1-and-canton-20-and-district-06', 12006, 0 );
        /**************************/
        /* Cantons from Alajuela */
        set_transient( 'mojito-shipping-cantones-from-province-2', array(
            '01' => 'Alajuela',
            '02' => 'San Ramon',
            '03' => 'Grecia',
            '04' => 'San Mateo',
            '05' => 'Atenas',
            '06' => 'Naranjo',
            '07' => 'Palmares',
            '08' => 'Poás',
            '09' => 'Orotina',
            '10' => 'San Carlos',
            '11' => 'Alfaro Ruiz',
            '12' => 'Valverde Vega',
            '13' => 'Upala',
            '14' => 'Los Chiles',
            '15' => 'Guatuso',
            '16' => 'Rio Cuarto',
        ), 0 );
        /* Districts from Alajuela > Alajuela */
        set_transient( 'mojito-shipping-districts-from-province-2-and-canton-01', array(
            '01' => 'Alajuela',
            '02' => 'San José',
            '03' => 'Carrizal',
            '04' => 'San Antonio',
            '05' => 'Guácima',
            '06' => 'San Isidro',
            '07' => 'Sabanilla',
            '08' => 'San Rafael',
            '09' => 'Río Segundo',
            '10' => 'Desamparados',
            '11' => 'Turrúcares',
            '12' => 'Tambor',
            '13' => 'Garita',
            '14' => 'Sarapiquí',
        ), 0 );
        /* Zip Codes from Alajuela > Alajuela */
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-01', 20101, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-02', 20102, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-03', 20103, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-04', 20104, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-05', 20105, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-06', 20106, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-07', 20107, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-08', 20108, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-09', 20109, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-10', 20110, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-11', 20111, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-12', 20112, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-13', 20113, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-14', 20114, 0 );
        /* Districts from Alajuela > San Ramon */
        set_transient( 'mojito-shipping-districts-from-province-2-and-canton-02', array(
            '01' => 'San Ramón',
            '02' => 'Santiago',
            '03' => 'San Juan',
            '04' => 'Piedades Norte',
            '05' => 'Piedades Sur',
            '06' => 'San Rafael',
            '07' => 'San Isidro',
            '08' => 'Ángeles',
            '09' => 'Alfaro',
            '10' => 'Volio',
            '11' => 'Concepción',
            '12' => 'Zapotal',
            '13' => 'Peñas Blancas',
        ), 0 );
        /* Zip Codes from Alajuela > San Ramon */
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-01', 20201, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-02', 20202, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-03', 20203, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-04', 20204, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-05', 20205, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-06', 20206, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-07', 20207, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-08', 20208, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-09', 20209, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-10', 20210, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-11', 20211, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-12', 20212, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-13', 20213, 0 );
        /* Districts from Alajuela > Grecia */
        set_transient( 'mojito-shipping-districts-from-province-2-and-canton-03', array(
            '01' => 'Grecia',
            '02' => 'San Isidro',
            '03' => 'San Jose',
            '04' => 'San Roque',
            '05' => 'Tacares',
            '06' => 'Río Cuarto',
            '07' => 'Puente de Piedra',
            '08' => 'Bolivar',
        ), 0 );
        /* Zip Codes from Alajuela > Grecia */
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-03-and-district-01', 20301, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-03-and-district-02', 20302, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-03-and-district-03', 20303, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-03-and-district-04', 20304, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-03-and-district-05', 20305, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-03-and-district-06', 20306, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-03-and-district-07', 20307, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-03-and-district-08', 20308, 0 );
        /* Districts from Alajuela > San Mateo */
        set_transient( 'mojito-shipping-districts-from-province-2-and-canton-04', array(
            '01' => 'San Mateo',
            '02' => 'Desmonte',
            '03' => 'Jesús María',
            '04' => 'Labrador',
        ), 0 );
        /* Zip Codes from Alajuela > San Mateo */
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-04-and-district-01', 20401, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-04-and-district-02', 20402, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-04-and-district-03', 20403, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-04-and-district-04', 20404, 0 );
        /* Districts from Alajuela > Atenas */
        set_transient( 'mojito-shipping-districts-from-province-2-and-canton-05', array(
            '01' => 'Atenas',
            '02' => 'Jesús',
            '03' => 'Mercedes',
            '04' => 'San Isidro',
            '05' => 'Concepción',
            '06' => 'San José',
            '07' => 'Santa Eulalia',
            '08' => 'Escobal',
        ), 0 );
        /* Zip Codes from Alajuela > Atenas */
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-05-and-district-01', 20501, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-05-and-district-02', 20502, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-05-and-district-03', 20503, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-05-and-district-04', 20504, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-05-and-district-05', 20505, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-05-and-district-06', 20506, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-05-and-district-07', 20507, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-05-and-district-08', 20508, 0 );
        /* Districts from Alajuela > Naranjo */
        set_transient( 'mojito-shipping-districts-from-province-2-and-canton-06', array(
            '01' => 'Naranjo',
            '02' => 'San Miguel',
            '03' => 'San José',
            '04' => 'Cirrí Sur',
            '05' => 'San Jerónimo',
            '06' => 'San Juan',
            '07' => 'Rosario',
            '08' => 'PALMITOS',
        ), 0 );
        /* Zip Codes from Alajuela > Naranjo */
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-06-and-district-01', 20601, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-06-and-district-02', 20602, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-06-and-district-03', 20603, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-06-and-district-04', 20604, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-06-and-district-05', 20605, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-06-and-district-06', 20606, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-06-and-district-07', 20607, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-06-and-district-08', 20608, 0 );
        /* Districts from Alajuela > Palmares */
        set_transient( 'mojito-shipping-districts-from-province-2-and-canton-07', array(
            '01' => 'Palmares',
            '02' => 'Zaragoza',
            '03' => 'Buenos Aires',
            '04' => 'Santiago',
            '05' => 'Candelaria',
            '06' => 'Esquipulas',
            '07' => 'Granja',
        ), 0 );
        /* Zip Codes from Alajuela > Palmares */
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-07-and-district-01', 20701, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-07-and-district-02', 20702, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-07-and-district-03', 20703, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-07-and-district-04', 20704, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-07-and-district-05', 20705, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-07-and-district-06', 20706, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-07-and-district-07', 20707, 0 );
        /* Districts from Alajuela > Poás */
        set_transient( 'mojito-shipping-districts-from-province-2-and-canton-08', array(
            '01' => 'San Pedro',
            '02' => 'San Juan',
            '03' => 'San Rafael',
            '04' => 'Carrillos',
            '05' => 'Sabana Redonda',
        ), 0 );
        /* Zip Codes from Alajuela > Poás */
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-08-and-district-01', 20801, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-08-and-district-02', 20802, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-08-and-district-03', 20803, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-08-and-district-04', 20804, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-08-and-district-05', 20805, 0 );
        /* Districts from Alajuela > Orotina */
        set_transient( 'mojito-shipping-districts-from-province-2-and-canton-09', array(
            '01' => 'Orotina',
            '02' => 'Mastate',
            '03' => 'Hacienda Vieja',
            '04' => 'Coyolar',
            '05' => 'Ceiba',
        ), 0 );
        /* Zip Codes from Alajuela > Orotina */
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-09-and-district-01', 20901, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-09-and-district-02', 20902, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-09-and-district-03', 20903, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-09-and-district-04', 20904, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-09-and-district-05', 20905, 0 );
        /* Districts from Alajuela > San Carlos */
        set_transient( 'mojito-shipping-districts-from-province-2-and-canton-10', array(
            '01' => 'Quesada',
            '02' => 'Florencia',
            '03' => 'Buenavista',
            '04' => 'Aguas Zarcas',
            '05' => 'Venecia',
            '06' => 'Pital',
            '07' => 'Fortuna',
            '08' => 'Tigra',
            '09' => 'Palmera',
            '10' => 'Venado',
            '11' => 'Cutris',
            '12' => 'Monterrey',
            '13' => 'Pocosol',
        ), 0 );
        /* Zip Codes from Alajuela > San Carlos */
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-01', 21001, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-02', 21002, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-03', 21003, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-04', 21004, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-05', 21005, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-06', 21006, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-07', 21007, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-08', 21008, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-09', 21009, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-10', 21010, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-11', 21011, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-12', 21012, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-13', 21013, 0 );
        /* Districts from Alajuela > Alfaro Ruiz */
        set_transient( 'mojito-shipping-districts-from-province-2-and-canton-11', array(
            '01' => 'Zarcero',
            '02' => 'Laguna',
            '03' => 'Tapezco',
            '04' => 'Guadalupe',
            '05' => 'Palmira',
            '06' => 'Zapote',
            '07' => 'Brisas',
        ), 0 );
        /* Zip Codes from Alajuela > Alfaro Ruiz */
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-11-and-district-01', 21101, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-11-and-district-02', 21102, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-11-and-district-03', 21103, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-11-and-district-04', 21104, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-11-and-district-05', 21105, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-11-and-district-06', 21106, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-11-and-district-07', 21107, 0 );
        /* Districts from Alajuela > Valverde Vega */
        set_transient( 'mojito-shipping-districts-from-province-2-and-canton-12', array(
            '01' => 'Sarchi Norte',
            '02' => 'Sarchi Sur',
            '03' => 'Toro Amarillo',
            '04' => 'San Pedro',
            '05' => 'Rodriguez',
        ), 0 );
        /* Zip Codes from Alajuela > Valverde Vega */
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-12-and-district-01', 21201, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-12-and-district-02', 21202, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-12-and-district-03', 21203, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-12-and-district-04', 21204, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-12-and-district-05', 21205, 0 );
        /* Districts from Alajuela > Upala */
        set_transient( 'mojito-shipping-districts-from-province-2-and-canton-13', array(
            '01' => 'Upala',
            '02' => 'Aguas Claras',
            '03' => 'San Jose (Pizote)',
            '04' => 'Bijagua',
            '05' => 'Delicias',
            '06' => 'Dos Rios',
            '07' => 'Yoliyllal',
            '08' => 'Canalete',
        ), 0 );
        /* Zip Codes from Alajuela > Upala */
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-13-and-district-01', 21301, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-13-and-district-02', 21302, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-13-and-district-03', 21303, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-13-and-district-04', 21304, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-13-and-district-05', 21305, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-13-and-district-06', 21306, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-13-and-district-07', 21307, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-13-and-district-08', 21308, 0 );
        /* Districts from Alajuela > Los Chiles */
        set_transient( 'mojito-shipping-districts-from-province-2-and-canton-14', array(
            '01' => 'Los Chiles',
            '02' => 'Caño Negro',
            '03' => 'El Amparo',
            '04' => 'San Jorge',
        ), 0 );
        /* Zip Codes from Alajuela > Los Chiles */
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-14-and-district-01', 21401, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-14-and-district-02', 21402, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-14-and-district-03', 21403, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-14-and-district-04', 21404, 0 );
        /* Districts from Alajuela > Guatuso */
        set_transient( 'mojito-shipping-districts-from-province-2-and-canton-15', array(
            '01' => 'San Rafael',
            '02' => 'Buenavista',
            '03' => 'Cote',
            '04' => 'KATIRA',
        ), 0 );
        /* Zip Codes from Alajuela > Guatuso */
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-15-and-district-01', 21501, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-15-and-district-02', 21502, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-15-and-district-03', 21503, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-15-and-district-04', 21504, 0 );
        /* Districts from Alajuela > Rio Cuarto */
        set_transient( 'mojito-shipping-districts-from-province-2-and-canton-16', array(
            '01' => 'Río Cuarto',
            '02' => 'Santa Rita',
            '03' => 'Santa Isabel',
        ), 0 );
        /* Zip Codes from Alajuela > Rio Cuarto */
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-16-and-district-01', 21601, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-16-and-district-02', 21602, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-2-and-canton-16-and-district-03', 21603, 0 );
        /**************************/
        /* Cantons from Cartago */
        set_transient( 'mojito-shipping-cantones-from-province-3', array(
            '01' => 'Cartago',
            '02' => 'Paraíso',
            '03' => 'La Unión',
            '04' => 'Jiménez',
            '05' => 'Turrialba',
            '06' => 'Alvarado',
            '07' => 'Oreamuno',
            '08' => 'El Guarco',
        ), 0 );
        /* Districts from Cartago > Cartago */
        set_transient( 'mojito-shipping-districts-from-province-3-and-canton-01', array(
            '01' => 'Oriental',
            '02' => 'Occidental',
            '03' => 'Carmen',
            '04' => 'San Nicolas',
            '05' => 'Aguacaliente (San Fco)',
            '06' => 'Guadalupe (Arenilla)',
            '07' => 'Corralillo',
            '08' => 'Tierra Blanca',
            '09' => 'Dulce Nombre',
            '10' => 'Llano Grande',
            '11' => 'Quebradilla',
        ), 0 );
        /* Zip Codes from Cartago > Cartago */
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-01-and-district-01', 30101, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-01-and-district-02', 30102, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-01-and-district-03', 30103, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-01-and-district-04', 30104, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-01-and-district-05', 30105, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-01-and-district-06', 30106, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-01-and-district-07', 30107, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-01-and-district-08', 30108, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-01-and-district-09', 30109, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-01-and-district-10', 30110, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-01-and-district-11', 30111, 0 );
        /* Districts from Cartago > Paraíso */
        set_transient( 'mojito-shipping-districts-from-province-3-and-canton-02', array(
            '01' => 'Paraiso',
            '02' => 'Santiago',
            '03' => 'Orosi',
            '04' => 'Cachi',
            '05' => 'Llanos de Santa Lucia',
        ), 0 );
        /* Zip Codes from Cartago > Paraíso */
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-02-and-district-01', 30201, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-02-and-district-02', 30202, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-02-and-district-03', 30203, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-02-and-district-04', 30204, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-02-and-district-05', 30205, 0 );
        /* Districts from Cartago > La Unión */
        set_transient( 'mojito-shipping-districts-from-province-3-and-canton-03', array(
            '01' => 'Tres Rios',
            '02' => 'San Diego',
            '03' => 'San Juan',
            '04' => 'San Rafael',
            '05' => 'Concepcion',
            '06' => 'Dulce Nombre',
            '07' => 'San Ramon',
            '08' => 'Rio Azul',
        ), 0 );
        /* Zip Codes from Cartago > La Unión */
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-03-and-district-01', 30301, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-03-and-district-02', 30302, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-03-and-district-03', 30303, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-03-and-district-04', 30304, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-03-and-district-05', 30305, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-03-and-district-06', 30306, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-03-and-district-07', 30307, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-03-and-district-08', 30308, 0 );
        /* Districts from Cartago > Jiménez */
        set_transient( 'mojito-shipping-districts-from-province-3-and-canton-04', array(
            '01' => 'Juan Viñas',
            '02' => 'Tucurrique',
            '03' => 'Pejibaye',
        ), 0 );
        /* Zip Codes from Cartago > Jiménez */
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-04-and-district-01', 30401, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-04-and-district-02', 30402, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-04-and-district-03', 30403, 0 );
        /* Districts from Cartago > Turrialba */
        set_transient( 'mojito-shipping-districts-from-province-3-and-canton-05', array(
            '01' => 'Turrialba',
            '02' => 'La Suiza',
            '03' => 'Peralta',
            '04' => 'Santa Cruz',
            '05' => 'Santa Teresita',
            '06' => 'Pavones',
            '07' => 'Tuis',
            '08' => 'Tayutic',
            '09' => 'Santa Rosa',
            '10' => 'Tres Equis',
            '11' => 'La Isabel',
            '12' => 'Chirripo',
        ), 0 );
        /* Zip Codes from Cartago > Turrialba */
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-01', 30501, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-02', 30502, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-03', 30503, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-04', 30504, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-05', 30505, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-06', 30506, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-07', 30507, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-08', 30508, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-09', 30509, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-10', 30510, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-11', 30511, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-12', 30512, 0 );
        /* Districts from Cartago > Alvarado */
        set_transient( 'mojito-shipping-districts-from-province-3-and-canton-06', array(
            '01' => 'Pacayas',
            '02' => 'Cervantes',
            '03' => 'Capellades',
        ), 0 );
        /* Zip Codes from Cartago > Alvarado */
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-06-and-district-01', 30601, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-06-and-district-02', 30602, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-06-and-district-03', 30603, 0 );
        /* Districts from Cartago > Oreamuno */
        set_transient( 'mojito-shipping-districts-from-province-3-and-canton-07', array(
            '01' => 'San Rafael',
            '02' => 'Cote',
            '03' => 'Potrero Cerrado',
            '04' => 'Cipreses',
            '05' => 'Santa Rosa',
        ), 0 );
        /* Zip Codes from Cartago > Oreamuno */
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-07-and-district-01', 30701, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-07-and-district-02', 30702, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-07-and-district-03', 30703, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-07-and-district-04', 30704, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-07-and-district-05', 30705, 0 );
        /* Districts from Cartago > El Guarco */
        set_transient( 'mojito-shipping-districts-from-province-3-and-canton-08', array(
            '01' => 'Tejar',
            '02' => 'San Isidro',
            '03' => 'Tobosi',
            '04' => 'Patio de Agua',
        ), 0 );
        /* Zip Codes from Cartago > El Guarco */
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-08-and-district-01', 30801, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-08-and-district-02', 30802, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-08-and-district-03', 30803, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-3-and-canton-08-and-district-04', 30804, 0 );
        /**************************/
        /* Cantons from Heredia */
        set_transient( 'mojito-shipping-cantones-from-province-4', array(
            '01' => 'Heredia',
            '02' => 'Barva',
            '03' => 'Santo Domingo',
            '04' => 'Santa Bárbara',
            '05' => 'San Rafael',
            '06' => 'San Isidro',
            '07' => 'Belén',
            '08' => 'San Joaquín de Flores',
            '09' => 'San Pablo',
            '10' => 'Sarapiquí',
        ), 0 );
        /* Districts from Heredia > Heredia */
        set_transient( 'mojito-shipping-districts-from-province-4-and-canton-01', array(
            '01' => 'Heredia',
            '02' => 'Mercedes',
            '03' => 'San Francisco',
            '04' => 'Ulloa',
            '05' => 'Varablanca',
        ), 0 );
        /* Zip Codes from Heredia > Heredia */
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-01-and-district-01', 40101, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-01-and-district-02', 40102, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-01-and-district-03', 40103, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-01-and-district-04', 40104, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-01-and-district-05', 40105, 0 );
        /* Districts from Heredia > Barva */
        set_transient( 'mojito-shipping-districts-from-province-4-and-canton-02', array(
            '01' => 'Barva',
            '02' => 'San Pedro',
            '03' => 'San Pablo',
            '04' => 'San Roque',
            '05' => 'Santa Lucia',
            '06' => 'San Jose de la Montaña',
        ), 0 );
        /* Zip Codes from Heredia > Barva */
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-02-and-district-01', 40201, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-02-and-district-02', 40202, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-02-and-district-03', 40203, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-02-and-district-04', 40204, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-02-and-district-05', 40205, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-02-and-district-06', 40206, 0 );
        /* Districts from Heredia > Santo Domingo */
        set_transient( 'mojito-shipping-districts-from-province-4-and-canton-03', array(
            '01' => 'Santo Domingo',
            '02' => 'San Vicente',
            '03' => 'San Miguel',
            '04' => 'Paracito',
            '05' => 'Santo Tomas',
            '06' => 'Santa Rosa',
            '07' => 'Tures',
            '08' => 'Para',
        ), 0 );
        /* Zip Codes from Heredia > Santo Domingo */
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-03-and-district-01', 40301, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-03-and-district-02', 40302, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-03-and-district-03', 40303, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-03-and-district-04', 40304, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-03-and-district-05', 40305, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-03-and-district-06', 40306, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-03-and-district-07', 40307, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-03-and-district-08', 40308, 0 );
        /* Districts from Heredia > Santa Bárbara */
        set_transient( 'mojito-shipping-districts-from-province-4-and-canton-04', array(
            '01' => 'Santa Barbara',
            '02' => 'San Pedro',
            '03' => 'San Juan',
            '04' => 'Jesus',
            '05' => 'Santo Domingo',
            '06' => 'Puraba',
        ), 0 );
        /* Zip Codes from Heredia > Santa Bárbara */
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-04-and-district-01', 40401, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-04-and-district-02', 40402, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-04-and-district-03', 40403, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-04-and-district-04', 40404, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-04-and-district-05', 40405, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-04-and-district-06', 40406, 0 );
        /* Districts from Heredia > San Rafael */
        set_transient( 'mojito-shipping-districts-from-province-4-and-canton-05', array(
            '01' => 'San Rafael',
            '02' => 'San Josecito',
            '03' => 'Santiago',
            '04' => 'angeles',
            '05' => 'Concepcion',
        ), 0 );
        /* Zip Codes from Heredia > San Rafael */
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-05-and-district-01', 40501, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-05-and-district-02', 40502, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-05-and-district-03', 40503, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-05-and-district-04', 40504, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-05-and-district-05', 40505, 0 );
        /* Districts from Heredia > San Isidro */
        set_transient( 'mojito-shipping-districts-from-province-4-and-canton-06', array(
            '01' => 'San Isidro',
            '02' => 'San Jose',
            '03' => 'Concepcion',
            '04' => 'San Francisco',
        ), 0 );
        /* Zip Codes from Heredia > San Isidro */
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-06-and-district-01', 40601, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-06-and-district-02', 40602, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-06-and-district-03', 40603, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-06-and-district-04', 40604, 0 );
        /* Districts from Heredia > Belén */
        set_transient( 'mojito-shipping-districts-from-province-4-and-canton-07', array(
            '01' => 'San Antonio',
            '02' => 'Ribera',
            '03' => 'Asuncion',
        ), 0 );
        /* Zip Codes from Heredia > Belén */
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-07-and-district-01', 40701, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-07-and-district-02', 40702, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-07-and-district-03', 40703, 0 );
        /* Districts from Heredia > San Joaquín de Flores */
        set_transient( 'mojito-shipping-districts-from-province-4-and-canton-08', array(
            '01' => 'San Joaquin de Flores',
            '02' => 'Barrantes',
            '03' => 'Llorente',
        ), 0 );
        /* Zip Codes from Heredia > San Joaquín de Flores */
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-08-and-district-01', 40801, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-08-and-district-02', 40802, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-08-and-district-03', 40803, 0 );
        /* Districts from Heredia > San Pablo */
        set_transient( 'mojito-shipping-districts-from-province-4-and-canton-09', array(
            '01' => 'San Pablo',
            '02' => 'RINCON DE SABANILLA',
        ), 0 );
        /* Zip Codes from Heredia > San Pablo */
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-09-and-district-01', 40901, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-09-and-district-02', 40902, 0 );
        /* Districts from Heredia > Sarapiquí */
        set_transient( 'mojito-shipping-districts-from-province-4-and-canton-10', array(
            '01' => 'Puerto Viejo',
            '02' => 'La Virgen',
            '03' => 'Horquetas',
            '04' => 'Llanuras del Gaspar',
            '05' => 'Cureña',
        ), 0 );
        /* Zip Codes from Heredia > Sarapiquí */
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-10-and-district-01', 41001, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-10-and-district-02', 41002, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-10-and-district-03', 41003, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-10-and-district-04', 41004, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-4-and-canton-10-and-district-05', 41005, 0 );
        /**************************/
        /* Cantons from Guanacaste */
        set_transient( 'mojito-shipping-cantones-from-province-5', array(
            '01' => 'Liberia',
            '02' => 'Nicoya',
            '03' => 'Santa Cruz',
            '04' => 'Bagaces',
            '05' => 'Carrillo',
            '06' => 'Cañas',
            '07' => 'Abangares',
            '08' => 'Tilarán',
            '09' => 'Nandayure',
            '10' => 'La Cruz',
            '11' => 'Hojancha',
        ), 0 );
        /* Districts from Guanacaste > Liberia */
        set_transient( 'mojito-shipping-districts-from-province-5-and-canton-01', array(
            '01' => 'Liberia',
            '02' => 'Cañas Dulces',
            '03' => 'Mayorga',
            '04' => 'Nacascolo',
            '05' => 'Curubande',
        ), 0 );
        /* Zip Codes from Guanacaste > Liberia */
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-01-and-district-01', 50101, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-01-and-district-02', 50102, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-01-and-district-03', 50103, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-01-and-district-04', 50104, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-01-and-district-05', 50105, 0 );
        /* Districts from Guanacaste > Nicoya */
        set_transient( 'mojito-shipping-districts-from-province-5-and-canton-02', array(
            '01' => 'Nicoya',
            '02' => 'Mansion',
            '03' => 'San Antonio',
            '04' => 'Quebrada Honda',
            '05' => 'Samara',
            '06' => 'Nosara',
            '07' => 'Belen de Nosarita',
        ), 0 );
        /* Zip Codes from Guanacaste > Nicoya */
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-02-and-district-01', 50201, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-02-and-district-02', 50202, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-02-and-district-03', 50203, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-02-and-district-04', 50204, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-02-and-district-05', 50205, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-02-and-district-06', 50206, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-02-and-district-07', 50207, 0 );
        /* Districts from Guanacaste > Santa Cruz */
        set_transient( 'mojito-shipping-districts-from-province-5-and-canton-03', array(
            '01' => 'Santa Cruz',
            '02' => 'Bolson',
            '03' => 'Veintisiete de Abril',
            '04' => 'Tempate',
            '05' => 'Cartagena',
            '06' => 'Cuajiniquil',
            '07' => 'Diria',
            '08' => 'Cabo Velas',
            '09' => 'Tamarindo',
        ), 0 );
        /* Zip Codes from Guanacaste > Santa Cruz */
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-03-and-district-01', 50301, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-03-and-district-02', 50302, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-03-and-district-03', 50303, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-03-and-district-04', 50304, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-03-and-district-05', 50305, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-03-and-district-06', 50306, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-03-and-district-07', 50307, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-03-and-district-08', 50308, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-03-and-district-09', 50309, 0 );
        /* Districts from Guanacaste > Bagaces */
        set_transient( 'mojito-shipping-districts-from-province-5-and-canton-04', array(
            '01' => 'Bagaces',
            '02' => 'Fortuna',
            '03' => 'Mogote',
            '04' => 'Rio Naranjo',
        ), 0 );
        /* Zip Codes from Guanacaste > Bagaces */
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-04-and-district-01', 50401, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-04-and-district-02', 50402, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-04-and-district-03', 50403, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-04-and-district-04', 50404, 0 );
        /* Districts from Guanacaste > Carrillo */
        set_transient( 'mojito-shipping-districts-from-province-5-and-canton-05', array(
            '01' => 'Filadelfia',
            '02' => 'Palmira',
            '03' => 'Sardinal',
            '04' => 'Belen',
        ), 0 );
        /* Zip Codes from Guanacaste > Carrillo */
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-05-and-district-01', 50501, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-05-and-district-02', 50502, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-05-and-district-03', 50503, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-05-and-district-04', 50504, 0 );
        /* Districts from Guanacaste > Cañas */
        set_transient( 'mojito-shipping-districts-from-province-5-and-canton-06', array(
            '01' => 'Cañas',
            '02' => 'Palmira',
            '03' => 'San Miguel',
            '04' => 'Bebedero',
            '05' => 'Porozal',
        ), 0 );
        /* Zip Codes from Guanacaste > Cañas */
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-06-and-district-01', 50601, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-06-and-district-02', 50602, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-06-and-district-03', 50603, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-06-and-district-04', 50604, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-06-and-district-05', 50605, 0 );
        /* Districts from Guanacaste > Abangares */
        set_transient( 'mojito-shipping-districts-from-province-5-and-canton-07', array(
            '01' => 'Juntas',
            '02' => 'Sierra',
            '03' => 'San Juan',
            '04' => 'Colorado',
        ), 0 );
        /* Zip Codes from Guanacaste > Abangares */
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-07-and-district-01', 50701, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-07-and-district-02', 50702, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-07-and-district-03', 50703, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-07-and-district-04', 50704, 0 );
        /* Districts from Guanacaste > Tilarán */
        set_transient( 'mojito-shipping-districts-from-province-5-and-canton-08', array(
            '01' => 'Tilaran',
            '02' => 'Quebrada Grande',
            '03' => 'Tronadora',
            '04' => 'Santa Rosa',
            '05' => 'Libano',
            '06' => 'Tierras Morenas',
            '07' => 'Arenal',
            '08' => 'Cabeceras',
        ), 0 );
        /* Zip Codes from Guanacaste > Tilarán */
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-08-and-district-01', 50801, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-08-and-district-02', 50802, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-08-and-district-03', 50803, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-08-and-district-04', 50804, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-08-and-district-05', 50805, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-08-and-district-06', 50806, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-08-and-district-07', 50807, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-08-and-district-08', 50808, 0 );
        /* Districts from Guanacaste > Nandayure */
        set_transient( 'mojito-shipping-districts-from-province-5-and-canton-09', array(
            '01' => 'Carmona',
            '02' => 'Santa Rita',
            '03' => 'Zapotal',
            '04' => 'San Pablo',
            '05' => 'Porvenir',
            '06' => 'Bejuco',
        ), 0 );
        /* Zip Codes from Guanacaste > Nandayure */
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-09-and-district-01', 50901, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-09-and-district-02', 50902, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-09-and-district-03', 50903, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-09-and-district-04', 50904, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-09-and-district-05', 50905, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-09-and-district-06', 50906, 0 );
        /* Districts from Guanacaste > La Cruz */
        set_transient( 'mojito-shipping-districts-from-province-5-and-canton-10', array(
            '01' => 'La Cruz',
            '02' => 'Santa Cecilia',
            '03' => 'Garita',
            '04' => 'Santa Elena',
        ), 0 );
        /* Zip Codes from Guanacaste > La Cruz */
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-10-and-district-01', 51001, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-10-and-district-02', 51002, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-10-and-district-03', 51003, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-10-and-district-04', 51004, 0 );
        /* Districts from Guanacaste > Hojancha */
        set_transient( 'mojito-shipping-districts-from-province-5-and-canton-11', array(
            '01' => 'Hojancha',
            '02' => 'Monte Romo',
            '03' => 'Puerto Carrillo',
            '04' => 'Huacas',
            '05' => 'Matambu',
        ), 0 );
        /* Zip Codes from Guanacaste > Hojancha */
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-11-and-district-01', 51101, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-11-and-district-02', 51102, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-11-and-district-03', 51103, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-5-and-canton-11-and-district-04', 51104, 0 );
        /**************************/
        /* Cantons from Puntarenas */
        set_transient( 'mojito-shipping-cantones-from-province-6', array(
            '01' => 'Puntarenas',
            '02' => 'Esparza',
            '03' => 'Buenos Aires',
            '04' => 'Montes de Oro',
            '05' => 'Osa',
            '06' => 'Aguirre',
            '07' => 'Golfito',
            '08' => 'Coto Brus',
            '09' => 'Parrita',
            '10' => 'Corredores',
            '11' => 'Garabito',
            '12' => 'Monteverde',
        ), 0 );
        /* Districts from Puntarenas > Puntarenas */
        set_transient( 'mojito-shipping-districts-from-province-6-and-canton-01', array(
            '01' => 'Puntarenas',
            '02' => 'Pitahaya',
            '03' => 'Chomes',
            '04' => 'Lepanto',
            '05' => 'Paquera',
            '06' => 'Manzanillo',
            '07' => 'Guacimal',
            '08' => 'Barranca',
            '09' => 'Monte Verde',
            '10' => 'Isla del Coco',
            '11' => 'Cobano',
            '12' => 'Chacarita',
            '13' => 'Chira',
            '14' => 'Acapulco',
            '15' => 'El Roble',
            '16' => 'Arancibia',
        ), 0 );
        /* Zip Codes from Puntarenas > Puntarenas */
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-01', 60101, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-02', 60102, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-03', 60103, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-04', 60104, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-05', 60105, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-06', 60106, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-07', 60107, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-08', 60108, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-09', 60109, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-10', 60110, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-11', 60111, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-12', 60112, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-13', 60113, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-14', 60114, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-15', 60115, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-16', 60116, 0 );
        /* Districts from Puntarenas > Esparza */
        set_transient( 'mojito-shipping-districts-from-province-6-and-canton-02', array(
            '01' => 'Espiritu Santo',
            '02' => 'San Juan Grande',
            '03' => 'Macacona',
            '04' => 'San Rafael',
            '05' => 'San Jeronimo',
            '06' => 'Caldera',
        ), 0 );
        /* Zip Codes from Puntarenas > Esparza */
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-02-and-district-01', 60201, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-02-and-district-02', 60202, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-02-and-district-03', 60203, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-02-and-district-04', 60204, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-02-and-district-05', 60205, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-02-and-district-06', 60206, 0 );
        /* Districts from Puntarenas > Buenos Aires */
        set_transient( 'mojito-shipping-districts-from-province-6-and-canton-03', array(
            '01' => 'Buenos Aires',
            '02' => 'Volcan',
            '03' => 'Potrero Grande',
            '04' => 'Boruca',
            '05' => 'Pilas',
            '06' => 'Colinas',
            '07' => 'Changena',
            '08' => 'Briolley',
            '09' => 'Brunka',
        ), 0 );
        /* Zip Codes from Puntarenas > Buenos Aires */
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-03-and-district-01', 60301, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-03-and-district-02', 60302, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-03-and-district-03', 60303, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-03-and-district-04', 60304, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-03-and-district-05', 60305, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-03-and-district-06', 60306, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-03-and-district-07', 60307, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-03-and-district-08', 60308, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-03-and-district-09', 60309, 0 );
        /* Districts from Puntarenas > Montes de Oro */
        set_transient( 'mojito-shipping-districts-from-province-6-and-canton-04', array(
            '01' => 'Miramar',
            '02' => 'Union',
            '03' => 'San Isidro',
        ), 0 );
        /* Zip Codes from Puntarenas > Montes de Oro */
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-04-and-district-01', 60401, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-04-and-district-02', 60402, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-04-and-district-03', 60403, 0 );
        /* Districts from Puntarenas > Osa */
        set_transient( 'mojito-shipping-districts-from-province-6-and-canton-05', array(
            '01' => 'Puerto Cortes',
            '02' => 'Palmar',
            '03' => 'Sierpe',
            '04' => 'Bahia Ballena',
            '05' => 'Piedras Blancas',
            '06' => 'BAHIA DRAKE',
        ), 0 );
        /* Zip Codes from Puntarenas > Osa */
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-05-and-district-01', 60501, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-05-and-district-02', 60502, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-05-and-district-03', 60503, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-05-and-district-04', 60504, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-05-and-district-05', 60505, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-05-and-district-06', 60506, 0 );
        /* Districts from Puntarenas > Aguirre */
        set_transient( 'mojito-shipping-districts-from-province-6-and-canton-06', array(
            '01' => 'Quepos',
            '02' => 'Savegre',
            '03' => 'Naranjito',
        ), 0 );
        /* Zip Codes from Puntarenas > Aguirre */
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-06-and-district-01', 60601, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-06-and-district-02', 60602, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-06-and-district-03', 60603, 0 );
        /* Districts from Puntarenas > Golfito */
        set_transient( 'mojito-shipping-districts-from-province-6-and-canton-07', array(
            '01' => 'Golfito',
            '02' => 'Puerto Jimenez',
            '03' => 'Guaycara',
            '04' => 'Pavon',
        ), 0 );
        /* Zip Codes from Puntarenas > Golfito */
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-07-and-district-01', 60701, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-07-and-district-02', 60702, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-07-and-district-03', 60703, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-07-and-district-04', 60704, 0 );
        /* Districts from Puntarenas > Coto Brus */
        set_transient( 'mojito-shipping-districts-from-province-6-and-canton-08', array(
            '01' => 'San Vito',
            '02' => 'Sabalito',
            '03' => 'Aguabuena',
            '04' => 'Limoncito',
            '05' => 'Pittier',
            '06' => 'Gutierrez Brown',
        ), 0 );
        /* Zip Codes from Puntarenas > Coto Brus */
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-08-and-district-01', 60801, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-08-and-district-02', 60802, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-08-and-district-03', 60803, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-08-and-district-04', 60804, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-08-and-district-05', 60805, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-08-and-district-06', 60806, 0 );
        /* Districts from Puntarenas > Parrita */
        set_transient( 'mojito-shipping-districts-from-province-6-and-canton-09', array(
            '01' => 'Parrita',
        ), 0 );
        /* Zip Codes from Puntarenas > Parrita */
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-09-and-district-01', 60901, 0 );
        /* Districts from Puntarenas > Corredores */
        set_transient( 'mojito-shipping-districts-from-province-6-and-canton-10', array(
            '01' => 'Corredor',
            '02' => 'La Cuesta',
            '03' => 'Canoas',
            '04' => 'Laurel',
        ), 0 );
        /* Zip Codes from Puntarenas > Corredores */
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-10-and-district-01', 61001, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-10-and-district-02', 61002, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-10-and-district-03', 61003, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-10-and-district-04', 61004, 0 );
        /* Districts from Puntarenas > Garabito */
        set_transient( 'mojito-shipping-districts-from-province-6-and-canton-11', array(
            '01' => 'Jaco',
            '02' => 'Tarcoles',
        ), 0 );
        /* Zip Codes from Puntarenas > Garabito */
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-11-and-district-01', 61101, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-11-and-district-02', 61102, 0 );
        /* Districts from Puntarenas > Monteverde */
        set_transient( 'mojito-shipping-districts-from-province-6-and-canton-12', array(
            '01' => 'Monteverde',
        ), 0 );
        /* Zip Codes from Puntarenas > Monteverde */
        set_transient( 'mojito-shipping-postcode-from-province-6-and-canton-12-and-district-01', 61201, 0 );
        /**************************/
        /* Cantons from Limón */
        set_transient( 'mojito-shipping-cantones-from-province-7', array(
            '01' => 'Limón',
            '02' => 'Pococí',
            '03' => 'Siquirres',
            '04' => 'Talamanca',
            '05' => 'Matina',
            '06' => 'Guácimo',
        ), 0 );
        /* Districts from Limón > Limón */
        set_transient( 'mojito-shipping-districts-from-province-7-and-canton-01', array(
            '01' => 'Limon',
            '02' => 'Valle La Estrella',
            '03' => 'Rio Blanco',
            '04' => 'Matama',
        ), 0 );
        /* Zip Codes from Limón > Limón */
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-01-and-district-01', 70101, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-01-and-district-02', 70102, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-01-and-district-03', 70103, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-01-and-district-04', 70104, 0 );
        /* Districts from Limón > Pococí */
        set_transient( 'mojito-shipping-districts-from-province-7-and-canton-02', array(
            '01' => 'Guapiles',
            '02' => 'Jimenez',
            '03' => 'Rita',
            '04' => 'Roxana',
            '05' => 'Cariari',
            '06' => 'Colorado',
            '07' => 'LA COLONIA',
        ), 0 );
        /* Zip Codes from Limón > Pococí */
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-02-and-district-01', 70201, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-02-and-district-02', 70202, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-02-and-district-03', 70203, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-02-and-district-04', 70204, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-02-and-district-05', 70205, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-02-and-district-06', 70206, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-02-and-district-07', 70207, 0 );
        /* Districts from Limón > Siquirres */
        set_transient( 'mojito-shipping-districts-from-province-7-and-canton-03', array(
            '01' => 'Siquirres',
            '02' => 'Pacuarito',
            '03' => 'Florida',
            '04' => 'Germania',
            '05' => 'Cairo',
            '06' => 'Alegria',
            '07' => 'Reventazon',
        ), 0 );
        /* Zip Codes from Limón > Siquirres */
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-03-and-district-01', 70301, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-03-and-district-02', 70302, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-03-and-district-03', 70303, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-03-and-district-04', 70304, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-03-and-district-05', 70305, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-03-and-district-06', 70306, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-03-and-district-07', 70307, 0 );
        /* Districts from Limón > Talamanca */
        set_transient( 'mojito-shipping-districts-from-province-7-and-canton-04', array(
            '01' => 'Bratsi',
            '02' => 'Sixaola',
            '03' => 'Cahuita',
            '04' => 'Telire',
        ), 0 );
        /* Zip Codes from Limón > Talamanca */
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-04-and-district-01', 70401, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-04-and-district-02', 70402, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-04-and-district-03', 70403, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-04-and-district-04', 70404, 0 );
        /* Districts from Limón > Matina */
        set_transient( 'mojito-shipping-districts-from-province-7-and-canton-05', array(
            '01' => 'Matina',
            '02' => 'Battan',
            '03' => 'Carrandi',
        ), 0 );
        /* Zip Codes from Limón > Matina */
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-05-and-district-01', 70501, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-05-and-district-02', 70502, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-05-and-district-03', 70503, 0 );
        /* Districts from Limón > Guácimo */
        set_transient( 'mojito-shipping-districts-from-province-7-and-canton-06', array(
            '01' => 'Guacimo',
            '02' => 'Mercedes',
            '03' => 'Pocora',
            '04' => 'Rio Jimenez',
            '05' => 'Duacari',
        ), 0 );
        /* Zip Codes from Limón > Guácimo */
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-06-and-district-01', 70601, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-06-and-district-02', 70602, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-06-and-district-03', 70603, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-06-and-district-04', 70604, 0 );
        set_transient( 'mojito-shipping-postcode-from-province-7-and-canton-06-and-district-05', 70605, 0 );
        \update_option( 'mojito-shipping-pymexpress-preloaded', true );
    }

    public function clear_pymexpress_locations() {
        /**************************/
        /* Cantons from SJ */
        delete_transient( 'mojito-shipping-cantones-from-province-1' );
        /* Districts from SJ > San José */
        delete_transient( 'mojito-shipping-districts-from-province-1-and-canton-01' );
        /* Zip Codes from SJ > San José */
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-01-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-01-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-01-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-01-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-01-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-01-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-01-and-district-07' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-01-and-district-08' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-01-and-district-09' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-01-and-district-10' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-01-and-district-11' );
        /* Districts from SJ > Escazú */
        delete_transient( 'mojito-shipping-districts-from-province-1-and-canton-02' );
        /* Zip Codes from SJ > Escazú */
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-02-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-02-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-02-and-district-03' );
        /* Districts from SJ > Desamparados */
        delete_transient( 'mojito-shipping-districts-from-province-1-and-canton-03' );
        /* Zip Codes from SJ > Desamparados */
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-07' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-08' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-09' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-10' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-11' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-12' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-03-and-district-13' );
        /* Districts from SJ > Puriscal */
        delete_transient( 'mojito-shipping-districts-from-province-1-and-canton-04' );
        /* Zip Codes from SJ > Puriscal */
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-04-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-04-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-04-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-04-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-04-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-04-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-04-and-district-07' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-04-and-district-08' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-04-and-district-09' );
        /* Districts from SJ > Tarrazu */
        delete_transient( 'mojito-shipping-districts-from-province-1-and-canton-05' );
        /* Zip Codes from SJ > Tarrazu */
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-05-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-05-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-05-and-district-03' );
        /* Districts from SJ > Aserrí */
        delete_transient( 'mojito-shipping-districts-from-province-1-and-canton-06' );
        /* Zip Codes from SJ > Aserrí */
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-06-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-06-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-06-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-06-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-06-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-06-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-06-and-district-07' );
        /* Districts from SJ > Mora */
        delete_transient( 'mojito-shipping-districts-from-province-1-and-canton-07' );
        /* Zip Codes from SJ > Mora */
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-07-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-07-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-07-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-07-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-07-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-07-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-07-and-district-07' );
        /* Districts from SJ > Goicoechea */
        delete_transient( 'mojito-shipping-districts-from-province-1-and-canton-08' );
        /* Zip Codes from SJ > Goicoechea */
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-08-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-08-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-08-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-08-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-08-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-08-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-08-and-district-07' );
        /* Districts from SJ > Santa Ana */
        delete_transient( 'mojito-shipping-districts-from-province-1-and-canton-09' );
        /* Zip Codes from SJ > Santa Ana */
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-09-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-09-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-09-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-09-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-09-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-09-and-district-06' );
        /* Districts from SJ > Alajuelita */
        delete_transient( 'mojito-shipping-districts-from-province-1-and-canton-10' );
        /* Zip Codes from SJ > Alajuelita */
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-10-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-10-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-10-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-10-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-10-and-district-05' );
        /* Districts from SJ > Vazquez de Coronado */
        delete_transient( 'mojito-shipping-districts-from-province-1-and-canton-11' );
        /* Zip Codes from SJ > Vazquez de Coronado */
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-11-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-11-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-11-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-11-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-11-and-district-05' );
        /* Districts from SJ > Acosta */
        delete_transient( 'mojito-shipping-districts-from-province-1-and-canton-12' );
        /* Zip Codes from SJ > Acosta */
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-12-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-12-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-12-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-12-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-12-and-district-05' );
        /* Districts from SJ > Tibás */
        delete_transient( 'mojito-shipping-districts-from-province-1-and-canton-13' );
        /* Zip Codes from SJ > Tibás */
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-13-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-13-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-13-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-13-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-13-and-district-05' );
        /* Districts from SJ > Moravia */
        delete_transient( 'mojito-shipping-districts-from-province-1-and-canton-14' );
        /* Zip Codes from SJ > Moravia */
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-14-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-14-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-14-and-district-03' );
        /* Districts from SJ > Montes de Oca */
        delete_transient( 'mojito-shipping-districts-from-province-1-and-canton-15' );
        /* Zip Codes from SJ > Montes de Oca */
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-15-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-15-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-15-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-15-and-district-04' );
        /* Districts from SJ > Turrubares */
        delete_transient( 'mojito-shipping-districts-from-province-1-and-canton-16' );
        /* Zip Codes from SJ > Turrubares */
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-16-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-16-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-16-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-16-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-16-and-district-05' );
        /* Districts from SJ > Dota */
        delete_transient( 'mojito-shipping-districts-from-province-1-and-canton-17' );
        /* Zip Codes from SJ > Dota */
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-17-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-17-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-17-and-district-03' );
        /* Districts from SJ > Curridabat */
        delete_transient( 'mojito-shipping-districts-from-province-1-and-canton-18' );
        /* Zip Codes from SJ > Curridabat */
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-18-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-18-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-18-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-18-and-district-04' );
        /* Districts from SJ > Pérez Zeledón */
        delete_transient( 'mojito-shipping-districts-from-province-1-and-canton-19' );
        /* Zip Codes from SJ > Pérez Zeledón */
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-07' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-08' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-09' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-10' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-11' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-19-and-district-12' );
        /* Districts from SJ > León Cortes */
        delete_transient( 'mojito-shipping-districts-from-province-1-and-canton-20' );
        /* Zip Codes from SJ > León Cortes */
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-20-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-20-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-20-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-20-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-20-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-1-and-canton-20-and-district-06' );
        /* Cantons from AL */
        delete_transient( 'mojito-shipping-cantones-from-province-2' );
        /* Districts from AL > Alajuela */
        delete_transient( 'mojito-shipping-districts-from-province-2-and-canton-01' );
        /* Zip Codes from AL > Alajuela */
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-07' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-08' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-09' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-10' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-11' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-12' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-13' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-01-and-district-14' );
        /* Districts from AL > San Ramon */
        delete_transient( 'mojito-shipping-districts-from-province-2-and-canton-02' );
        /* Zip Codes from AL > San Ramon */
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-07' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-08' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-09' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-10' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-11' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-12' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-02-and-district-13' );
        /* Districts from AL > Grecia */
        delete_transient( 'mojito-shipping-districts-from-province-2-and-canton-03' );
        /* Zip Codes from AL > Grecia */
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-03-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-03-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-03-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-03-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-03-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-03-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-03-and-district-07' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-03-and-district-08' );
        /* Districts from AL > San Mateo */
        delete_transient( 'mojito-shipping-districts-from-province-2-and-canton-04' );
        /* Zip Codes from AL > San Mateo */
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-04-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-04-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-04-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-04-and-district-04' );
        /* Districts from AL > Atenas */
        delete_transient( 'mojito-shipping-districts-from-province-2-and-canton-05' );
        /* Zip Codes from AL > Atenas */
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-05-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-05-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-05-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-05-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-05-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-05-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-05-and-district-07' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-05-and-district-08' );
        /* Districts from AL > Naranjo */
        delete_transient( 'mojito-shipping-districts-from-province-2-and-canton-06' );
        /* Zip Codes from AL > Naranjo */
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-06-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-06-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-06-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-06-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-06-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-06-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-06-and-district-07' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-06-and-district-08' );
        /* Districts from AL > Palmares */
        delete_transient( 'mojito-shipping-districts-from-province-2-and-canton-07' );
        /* Zip Codes from AL > Palmares */
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-07-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-07-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-07-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-07-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-07-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-07-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-07-and-district-07' );
        /* Districts from AL > Poás */
        delete_transient( 'mojito-shipping-districts-from-province-2-and-canton-08' );
        /* Zip Codes from AL > Poás */
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-08-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-08-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-08-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-08-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-08-and-district-05' );
        /* Districts from AL > Orotina */
        delete_transient( 'mojito-shipping-districts-from-province-2-and-canton-09' );
        /* Zip Codes from AL > Orotina */
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-09-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-09-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-09-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-09-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-09-and-district-05' );
        /* Districts from AL > San Carlos */
        delete_transient( 'mojito-shipping-districts-from-province-2-and-canton-10' );
        /* Zip Codes from AL > San Carlos */
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-07' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-08' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-09' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-10' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-11' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-12' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-10-and-district-13' );
        /* Districts from AL > Alfaro Ruiz */
        delete_transient( 'mojito-shipping-districts-from-province-2-and-canton-11' );
        /* Zip Codes from AL > Alfaro Ruiz */
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-11-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-11-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-11-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-11-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-11-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-11-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-11-and-district-07' );
        /* Districts from AL > Valverde Vega */
        delete_transient( 'mojito-shipping-districts-from-province-2-and-canton-12' );
        /* Zip Codes from AL > Valverde Vega */
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-12-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-12-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-12-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-12-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-12-and-district-05' );
        /* Districts from AL > Upala */
        delete_transient( 'mojito-shipping-districts-from-province-2-and-canton-13' );
        /* Zip Codes from AL > Upala */
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-13-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-13-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-13-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-13-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-13-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-13-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-13-and-district-07' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-13-and-district-08' );
        /* Districts from AL > Los Chiles */
        delete_transient( 'mojito-shipping-districts-from-province-2-and-canton-14' );
        /* Zip Codes from AL > Los Chiles */
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-14-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-14-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-14-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-14-and-district-04' );
        /* Districts from AL > Guatuso */
        delete_transient( 'mojito-shipping-districts-from-province-2-and-canton-15' );
        /* Zip Codes from AL > Guatuso */
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-15-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-15-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-15-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-15-and-district-04' );
        /* Districts from AL > Rio Cuarto */
        delete_transient( 'mojito-shipping-districts-from-province-2-and-canton-16' );
        /* Zip Codes from AL > Rio Cuarto */
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-16-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-16-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-2-and-canton-16-and-district-03' );
        /* Cantons from CG */
        delete_transient( 'mojito-shipping-cantones-from-province-3' );
        /* Districts from CG > Cartago */
        delete_transient( 'mojito-shipping-districts-from-province-3-and-canton-01' );
        /* Zip Codes from CG > Cartago */
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-01-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-01-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-01-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-01-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-01-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-01-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-01-and-district-07' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-01-and-district-08' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-01-and-district-09' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-01-and-district-10' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-01-and-district-11' );
        /* Districts from CG > Paraíso */
        delete_transient( 'mojito-shipping-districts-from-province-3-and-canton-02' );
        /* Zip Codes from CG > Paraíso */
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-02-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-02-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-02-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-02-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-02-and-district-05' );
        /* Districts from CG > La Unión */
        delete_transient( 'mojito-shipping-districts-from-province-3-and-canton-03' );
        /* Zip Codes from CG > La Unión */
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-03-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-03-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-03-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-03-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-03-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-03-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-03-and-district-07' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-03-and-district-08' );
        /* Districts from CG > Jiménez */
        delete_transient( 'mojito-shipping-districts-from-province-3-and-canton-04' );
        /* Zip Codes from CG > Jiménez */
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-04-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-04-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-04-and-district-03' );
        /* Districts from CG > Turrialba */
        delete_transient( 'mojito-shipping-districts-from-province-3-and-canton-05' );
        /* Zip Codes from CG > Turrialba */
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-07' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-08' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-09' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-10' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-11' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-05-and-district-12' );
        /* Districts from CG > Alvarado */
        delete_transient( 'mojito-shipping-districts-from-province-3-and-canton-06' );
        /* Zip Codes from CG > Alvarado */
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-06-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-06-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-06-and-district-03' );
        /* Districts from CG > Oreamuno */
        delete_transient( 'mojito-shipping-districts-from-province-3-and-canton-07' );
        /* Zip Codes from CG > Oreamuno */
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-07-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-07-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-07-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-07-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-07-and-district-05' );
        /* Districts from CG > El Guarco */
        delete_transient( 'mojito-shipping-districts-from-province-3-and-canton-08' );
        /* Zip Codes from CG > El Guarco */
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-08-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-08-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-08-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-3-and-canton-08-and-district-04' );
        /* Cantons from HD */
        delete_transient( 'mojito-shipping-cantones-from-province-4' );
        /* Districts from HD > Heredia */
        delete_transient( 'mojito-shipping-districts-from-province-4-and-canton-01' );
        /* Zip Codes from HD > Heredia */
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-01-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-01-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-01-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-01-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-01-and-district-05' );
        /* Districts from HD > Barva */
        delete_transient( 'mojito-shipping-districts-from-province-4-and-canton-02' );
        /* Zip Codes from HD > Barva */
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-02-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-02-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-02-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-02-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-02-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-02-and-district-06' );
        /* Districts from HD > Santo Domingo */
        delete_transient( 'mojito-shipping-districts-from-province-4-and-canton-03' );
        /* Zip Codes from HD > Santo Domingo */
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-03-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-03-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-03-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-03-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-03-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-03-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-03-and-district-07' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-03-and-district-08' );
        /* Districts from HD > Santa Bárbara */
        delete_transient( 'mojito-shipping-districts-from-province-4-and-canton-04' );
        /* Zip Codes from HD > Santa Bárbara */
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-04-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-04-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-04-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-04-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-04-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-04-and-district-06' );
        /* Districts from HD > San Rafael */
        delete_transient( 'mojito-shipping-districts-from-province-4-and-canton-05' );
        /* Zip Codes from HD > San Rafael */
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-05-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-05-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-05-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-05-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-05-and-district-05' );
        /* Districts from HD > San Isidro */
        delete_transient( 'mojito-shipping-districts-from-province-4-and-canton-06' );
        /* Zip Codes from HD > San Isidro */
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-06-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-06-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-06-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-06-and-district-04' );
        /* Districts from HD > Belén */
        delete_transient( 'mojito-shipping-districts-from-province-4-and-canton-07' );
        /* Zip Codes from HD > Belén */
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-07-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-07-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-07-and-district-03' );
        /* Districts from HD > San Joaquín de Flores */
        delete_transient( 'mojito-shipping-districts-from-province-4-and-canton-08' );
        /* Zip Codes from HD > San Joaquín de Flores */
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-08-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-08-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-08-and-district-03' );
        /* Districts from HD > San Pablo */
        delete_transient( 'mojito-shipping-districts-from-province-4-and-canton-09' );
        /* Zip Codes from HD > San Pablo */
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-09-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-09-and-district-02' );
        /* Districts from HD > Sarapiquí */
        delete_transient( 'mojito-shipping-districts-from-province-4-and-canton-10' );
        /* Zip Codes from HD > Sarapiquí */
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-10-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-10-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-10-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-10-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-4-and-canton-10-and-district-05' );
        /* Cantons from GT */
        delete_transient( 'mojito-shipping-cantones-from-province-5' );
        /* Districts from GT > Liberia */
        delete_transient( 'mojito-shipping-districts-from-province-5-and-canton-01' );
        /* Zip Codes from GT > Liberia */
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-01-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-01-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-01-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-01-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-01-and-district-05' );
        /* Districts from GT > Nicoya */
        delete_transient( 'mojito-shipping-districts-from-province-5-and-canton-02' );
        /* Zip Codes from GT > Nicoya */
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-02-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-02-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-02-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-02-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-02-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-02-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-02-and-district-07' );
        /* Districts from GT > Santa Cruz */
        delete_transient( 'mojito-shipping-districts-from-province-5-and-canton-03' );
        /* Zip Codes from GT > Santa Cruz */
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-03-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-03-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-03-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-03-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-03-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-03-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-03-and-district-07' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-03-and-district-08' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-03-and-district-09' );
        /* Districts from GT > Bagaces */
        delete_transient( 'mojito-shipping-districts-from-province-5-and-canton-04' );
        /* Zip Codes from GT > Bagaces */
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-04-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-04-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-04-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-04-and-district-04' );
        /* Districts from GT > Carrillo */
        delete_transient( 'mojito-shipping-districts-from-province-5-and-canton-05' );
        /* Zip Codes from GT > Carrillo */
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-05-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-05-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-05-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-05-and-district-04' );
        /* Districts from GT > Cañas */
        delete_transient( 'mojito-shipping-districts-from-province-5-and-canton-06' );
        /* Zip Codes from GT > Cañas */
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-06-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-06-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-06-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-06-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-06-and-district-05' );
        /* Districts from GT > Abangares */
        delete_transient( 'mojito-shipping-districts-from-province-5-and-canton-07' );
        /* Zip Codes from GT > Abangares */
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-07-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-07-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-07-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-07-and-district-04' );
        /* Districts from GT > Tilarán */
        delete_transient( 'mojito-shipping-districts-from-province-5-and-canton-08' );
        /* Zip Codes from GT > Tilarán */
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-08-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-08-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-08-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-08-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-08-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-08-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-08-and-district-07' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-08-and-district-08' );
        /* Districts from GT > Nandayure */
        delete_transient( 'mojito-shipping-districts-from-province-5-and-canton-09' );
        /* Zip Codes from GT > Nandayure */
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-09-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-09-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-09-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-09-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-09-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-09-and-district-06' );
        /* Districts from GT > La Cruz */
        delete_transient( 'mojito-shipping-districts-from-province-5-and-canton-10' );
        /* Zip Codes from GT > La Cruz */
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-10-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-10-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-10-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-10-and-district-04' );
        /* Districts from GT > Hojancha */
        delete_transient( 'mojito-shipping-districts-from-province-5-and-canton-11' );
        /* Zip Codes from GT > Hojancha */
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-11-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-11-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-11-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-11-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-5-and-canton-11-and-district-05' );
        /* Cantons from PT */
        delete_transient( 'mojito-shipping-cantones-from-province-6' );
        /* Districts from PT > Puntarenas */
        delete_transient( 'mojito-shipping-districts-from-province-6-and-canton-01' );
        /* Zip Codes from PT > Puntarenas */
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-07' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-08' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-09' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-10' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-11' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-12' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-13' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-14' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-15' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-01-and-district-16' );
        /* Districts from PT > Esparza */
        delete_transient( 'mojito-shipping-districts-from-province-6-and-canton-02' );
        /* Zip Codes from PT > Esparza */
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-02-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-02-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-02-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-02-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-02-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-02-and-district-06' );
        /* Districts from PT > Buenos Aires */
        delete_transient( 'mojito-shipping-districts-from-province-6-and-canton-03' );
        /* Zip Codes from PT > Buenos Aires */
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-03-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-03-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-03-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-03-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-03-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-03-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-03-and-district-07' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-03-and-district-08' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-03-and-district-09' );
        /* Districts from PT > Montes de Oro */
        delete_transient( 'mojito-shipping-districts-from-province-6-and-canton-04' );
        /* Zip Codes from PT > Montes de Oro */
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-04-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-04-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-04-and-district-03' );
        /* Districts from PT > Osa */
        delete_transient( 'mojito-shipping-districts-from-province-6-and-canton-05' );
        /* Zip Codes from PT > Osa */
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-05-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-05-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-05-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-05-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-05-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-05-and-district-06' );
        /* Districts from PT > Aguirre */
        delete_transient( 'mojito-shipping-districts-from-province-6-and-canton-06' );
        /* Zip Codes from PT > Aguirre */
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-06-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-06-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-06-and-district-03' );
        /* Districts from PT > Golfito */
        delete_transient( 'mojito-shipping-districts-from-province-6-and-canton-07' );
        /* Zip Codes from PT > Golfito */
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-07-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-07-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-07-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-07-and-district-04' );
        /* Districts from PT > Coto Brus */
        delete_transient( 'mojito-shipping-districts-from-province-6-and-canton-08' );
        /* Zip Codes from PT > Coto Brus */
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-08-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-08-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-08-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-08-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-08-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-08-and-district-06' );
        /* Districts from PT > Parrita */
        delete_transient( 'mojito-shipping-districts-from-province-6-and-canton-09' );
        /* Zip Codes from PT > Parrita */
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-09-and-district-01' );
        /* Districts from PT > Corredores */
        delete_transient( 'mojito-shipping-districts-from-province-6-and-canton-10' );
        /* Zip Codes from PT > Corredores */
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-10-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-10-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-10-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-10-and-district-04' );
        /* Districts from PT > Garabito */
        delete_transient( 'mojito-shipping-districts-from-province-6-and-canton-11' );
        /* Zip Codes from PT > Garabito */
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-11-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-11-and-district-02' );
        /* Districts from PT > Monteverde */
        delete_transient( 'mojito-shipping-districts-from-province-6-and-canton-12' );
        /* Zip Codes from PT > Monteverde */
        delete_transient( 'mojito-shipping-postcode-from-province-6-and-canton-12-and-district-01' );
        /* Cantons from LM */
        delete_transient( 'mojito-shipping-cantones-from-province-7' );
        /* Districts from LM > Limón */
        delete_transient( 'mojito-shipping-districts-from-province-7-and-canton-01' );
        /* Zip Codes from LM > Limón */
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-01-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-01-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-01-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-01-and-district-04' );
        /* Districts from LM > Pococí */
        delete_transient( 'mojito-shipping-districts-from-province-7-and-canton-02' );
        /* Zip Codes from LM > Pococí */
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-02-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-02-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-02-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-02-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-02-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-02-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-02-and-district-07' );
        /* Districts from LM > Siquirres */
        delete_transient( 'mojito-shipping-districts-from-province-7-and-canton-03' );
        /* Zip Codes from LM > Siquirres */
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-03-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-03-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-03-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-03-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-03-and-district-05' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-03-and-district-06' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-03-and-district-07' );
        /* Districts from LM > Talamanca */
        delete_transient( 'mojito-shipping-districts-from-province-7-and-canton-04' );
        /* Zip Codes from LM > Talamanca */
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-04-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-04-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-04-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-04-and-district-04' );
        /* Districts from LM > Matina */
        delete_transient( 'mojito-shipping-districts-from-province-7-and-canton-05' );
        /* Zip Codes from LM > Matina */
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-05-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-05-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-05-and-district-03' );
        /* Districts from LM > Guácimo */
        delete_transient( 'mojito-shipping-districts-from-province-7-and-canton-06' );
        /* Zip Codes from LM > Guácimo */
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-06-and-district-01' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-06-and-district-02' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-06-and-district-03' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-06-and-district-04' );
        delete_transient( 'mojito-shipping-postcode-from-province-7-and-canton-06-and-district-05' );
    }

    public function pymexpress_locations( $postcode ) {
        $data = [
            '10101' => [
                'province' => '1',
                'canton'   => '01',
                'district' => '01',
            ],
            '10102' => [
                'province' => '1',
                'canton'   => '01',
                'district' => '02',
            ],
            '10103' => [
                'province' => '1',
                'canton'   => '01',
                'district' => '03',
            ],
            '10104' => [
                'province' => '1',
                'canton'   => '01',
                'district' => '04',
            ],
            '10105' => [
                'province' => '1',
                'canton'   => '01',
                'district' => '05',
            ],
            '10106' => [
                'province' => '1',
                'canton'   => '01',
                'district' => '06',
            ],
            '10107' => [
                'province' => '1',
                'canton'   => '01',
                'district' => '07',
            ],
            '10108' => [
                'province' => '1',
                'canton'   => '01',
                'district' => '08',
            ],
            '10109' => [
                'province' => '1',
                'canton'   => '01',
                'district' => '09',
            ],
            '10110' => [
                'province' => '1',
                'canton'   => '01',
                'district' => '10',
            ],
            '10111' => [
                'province' => '1',
                'canton'   => '01',
                'district' => '11',
            ],
            '10201' => [
                'province' => '1',
                'canton'   => '02',
                'district' => '01',
            ],
            '10202' => [
                'province' => '1',
                'canton'   => '02',
                'district' => '02',
            ],
            '10203' => [
                'province' => '1',
                'canton'   => '02',
                'district' => '03',
            ],
            '10301' => [
                'province' => '1',
                'canton'   => '03',
                'district' => '01',
            ],
            '10302' => [
                'province' => '1',
                'canton'   => '03',
                'district' => '02',
            ],
            '10303' => [
                'province' => '1',
                'canton'   => '03',
                'district' => '03',
            ],
            '10304' => [
                'province' => '1',
                'canton'   => '03',
                'district' => '04',
            ],
            '10305' => [
                'province' => '1',
                'canton'   => '03',
                'district' => '05',
            ],
            '10306' => [
                'province' => '1',
                'canton'   => '03',
                'district' => '06',
            ],
            '10307' => [
                'province' => '1',
                'canton'   => '03',
                'district' => '07',
            ],
            '10308' => [
                'province' => '1',
                'canton'   => '03',
                'district' => '08',
            ],
            '10309' => [
                'province' => '1',
                'canton'   => '03',
                'district' => '09',
            ],
            '10310' => [
                'province' => '1',
                'canton'   => '03',
                'district' => '10',
            ],
            '10311' => [
                'province' => '1',
                'canton'   => '03',
                'district' => '11',
            ],
            '10312' => [
                'province' => '1',
                'canton'   => '03',
                'district' => '12',
            ],
            '10313' => [
                'province' => '1',
                'canton'   => '03',
                'district' => '13',
            ],
            '10401' => [
                'province' => '1',
                'canton'   => '04',
                'district' => '01',
            ],
            '10402' => [
                'province' => '1',
                'canton'   => '04',
                'district' => '02',
            ],
            '10403' => [
                'province' => '1',
                'canton'   => '04',
                'district' => '03',
            ],
            '10404' => [
                'province' => '1',
                'canton'   => '04',
                'district' => '04',
            ],
            '10405' => [
                'province' => '1',
                'canton'   => '04',
                'district' => '05',
            ],
            '10406' => [
                'province' => '1',
                'canton'   => '04',
                'district' => '06',
            ],
            '10407' => [
                'province' => '1',
                'canton'   => '04',
                'district' => '07',
            ],
            '10408' => [
                'province' => '1',
                'canton'   => '04',
                'district' => '08',
            ],
            '10409' => [
                'province' => '1',
                'canton'   => '04',
                'district' => '09',
            ],
            '10501' => [
                'province' => '1',
                'canton'   => '05',
                'district' => '01',
            ],
            '10502' => [
                'province' => '1',
                'canton'   => '05',
                'district' => '02',
            ],
            '10503' => [
                'province' => '1',
                'canton'   => '05',
                'district' => '03',
            ],
            '10601' => [
                'province' => '1',
                'canton'   => '06',
                'district' => '01',
            ],
            '10602' => [
                'province' => '1',
                'canton'   => '06',
                'district' => '02',
            ],
            '10603' => [
                'province' => '1',
                'canton'   => '06',
                'district' => '03',
            ],
            '10604' => [
                'province' => '1',
                'canton'   => '06',
                'district' => '04',
            ],
            '10605' => [
                'province' => '1',
                'canton'   => '06',
                'district' => '05',
            ],
            '10606' => [
                'province' => '1',
                'canton'   => '06',
                'district' => '06',
            ],
            '10607' => [
                'province' => '1',
                'canton'   => '06',
                'district' => '07',
            ],
            '10701' => [
                'province' => '1',
                'canton'   => '07',
                'district' => '01',
            ],
            '10702' => [
                'province' => '1',
                'canton'   => '07',
                'district' => '02',
            ],
            '10703' => [
                'province' => '1',
                'canton'   => '07',
                'district' => '03',
            ],
            '10704' => [
                'province' => '1',
                'canton'   => '07',
                'district' => '04',
            ],
            '10705' => [
                'province' => '1',
                'canton'   => '07',
                'district' => '05',
            ],
            '10706' => [
                'province' => '1',
                'canton'   => '07',
                'district' => '06',
            ],
            '10707' => [
                'province' => '1',
                'canton'   => '07',
                'district' => '07',
            ],
            '10801' => [
                'province' => '1',
                'canton'   => '08',
                'district' => '01',
            ],
            '10802' => [
                'province' => '1',
                'canton'   => '08',
                'district' => '02',
            ],
            '10803' => [
                'province' => '1',
                'canton'   => '08',
                'district' => '03',
            ],
            '10804' => [
                'province' => '1',
                'canton'   => '08',
                'district' => '04',
            ],
            '10805' => [
                'province' => '1',
                'canton'   => '08',
                'district' => '05',
            ],
            '10806' => [
                'province' => '1',
                'canton'   => '08',
                'district' => '06',
            ],
            '10807' => [
                'province' => '1',
                'canton'   => '08',
                'district' => '07',
            ],
            '10901' => [
                'province' => '1',
                'canton'   => '09',
                'district' => '01',
            ],
            '10902' => [
                'province' => '1',
                'canton'   => '09',
                'district' => '02',
            ],
            '10903' => [
                'province' => '1',
                'canton'   => '09',
                'district' => '03',
            ],
            '10904' => [
                'province' => '1',
                'canton'   => '09',
                'district' => '04',
            ],
            '10905' => [
                'province' => '1',
                'canton'   => '09',
                'district' => '05',
            ],
            '10906' => [
                'province' => '1',
                'canton'   => '09',
                'district' => '06',
            ],
            '11001' => [
                'province' => '1',
                'canton'   => '10',
                'district' => '01',
            ],
            '11002' => [
                'province' => '1',
                'canton'   => '10',
                'district' => '02',
            ],
            '11003' => [
                'province' => '1',
                'canton'   => '10',
                'district' => '03',
            ],
            '11004' => [
                'province' => '1',
                'canton'   => '10',
                'district' => '04',
            ],
            '11005' => [
                'province' => '1',
                'canton'   => '10',
                'district' => '05',
            ],
            '11101' => [
                'province' => '1',
                'canton'   => '11',
                'district' => '01',
            ],
            '11102' => [
                'province' => '1',
                'canton'   => '11',
                'district' => '02',
            ],
            '11103' => [
                'province' => '1',
                'canton'   => '11',
                'district' => '03',
            ],
            '11104' => [
                'province' => '1',
                'canton'   => '11',
                'district' => '04',
            ],
            '11105' => [
                'province' => '1',
                'canton'   => '11',
                'district' => '05',
            ],
            '11201' => [
                'province' => '1',
                'canton'   => '12',
                'district' => '01',
            ],
            '11202' => [
                'province' => '1',
                'canton'   => '12',
                'district' => '02',
            ],
            '11203' => [
                'province' => '1',
                'canton'   => '12',
                'district' => '03',
            ],
            '11204' => [
                'province' => '1',
                'canton'   => '12',
                'district' => '04',
            ],
            '11205' => [
                'province' => '1',
                'canton'   => '12',
                'district' => '05',
            ],
            '11301' => [
                'province' => '1',
                'canton'   => '13',
                'district' => '01',
            ],
            '11302' => [
                'province' => '1',
                'canton'   => '13',
                'district' => '02',
            ],
            '11303' => [
                'province' => '1',
                'canton'   => '13',
                'district' => '03',
            ],
            '11304' => [
                'province' => '1',
                'canton'   => '13',
                'district' => '04',
            ],
            '11305' => [
                'province' => '1',
                'canton'   => '13',
                'district' => '05',
            ],
            '11401' => [
                'province' => '1',
                'canton'   => '14',
                'district' => '01',
            ],
            '11402' => [
                'province' => '1',
                'canton'   => '14',
                'district' => '02',
            ],
            '11403' => [
                'province' => '1',
                'canton'   => '14',
                'district' => '03',
            ],
            '11501' => [
                'province' => '1',
                'canton'   => '15',
                'district' => '01',
            ],
            '11502' => [
                'province' => '1',
                'canton'   => '15',
                'district' => '02',
            ],
            '11503' => [
                'province' => '1',
                'canton'   => '15',
                'district' => '03',
            ],
            '11504' => [
                'province' => '1',
                'canton'   => '15',
                'district' => '04',
            ],
            '11601' => [
                'province' => '1',
                'canton'   => '16',
                'district' => '01',
            ],
            '11602' => [
                'province' => '1',
                'canton'   => '16',
                'district' => '02',
            ],
            '11603' => [
                'province' => '1',
                'canton'   => '16',
                'district' => '03',
            ],
            '11604' => [
                'province' => '1',
                'canton'   => '16',
                'district' => '04',
            ],
            '11605' => [
                'province' => '1',
                'canton'   => '16',
                'district' => '05',
            ],
            '11701' => [
                'province' => '1',
                'canton'   => '17',
                'district' => '01',
            ],
            '11702' => [
                'province' => '1',
                'canton'   => '17',
                'district' => '02',
            ],
            '11703' => [
                'province' => '1',
                'canton'   => '17',
                'district' => '03',
            ],
            '11801' => [
                'province' => '1',
                'canton'   => '18',
                'district' => '01',
            ],
            '11802' => [
                'province' => '1',
                'canton'   => '18',
                'district' => '02',
            ],
            '11803' => [
                'province' => '1',
                'canton'   => '18',
                'district' => '03',
            ],
            '11804' => [
                'province' => '1',
                'canton'   => '18',
                'district' => '04',
            ],
            '11901' => [
                'province' => '1',
                'canton'   => '19',
                'district' => '01',
            ],
            '11902' => [
                'province' => '1',
                'canton'   => '19',
                'district' => '02',
            ],
            '11903' => [
                'province' => '1',
                'canton'   => '19',
                'district' => '03',
            ],
            '11904' => [
                'province' => '1',
                'canton'   => '19',
                'district' => '04',
            ],
            '11905' => [
                'province' => '1',
                'canton'   => '19',
                'district' => '05',
            ],
            '11906' => [
                'province' => '1',
                'canton'   => '19',
                'district' => '06',
            ],
            '11907' => [
                'province' => '1',
                'canton'   => '19',
                'district' => '07',
            ],
            '11908' => [
                'province' => '1',
                'canton'   => '19',
                'district' => '08',
            ],
            '11909' => [
                'province' => '1',
                'canton'   => '19',
                'district' => '09',
            ],
            '11910' => [
                'province' => '1',
                'canton'   => '19',
                'district' => '10',
            ],
            '11911' => [
                'province' => '1',
                'canton'   => '19',
                'district' => '11',
            ],
            '11912' => [
                'province' => '1',
                'canton'   => '19',
                'district' => '12',
            ],
            '12001' => [
                'province' => '1',
                'canton'   => '20',
                'district' => '01',
            ],
            '12002' => [
                'province' => '1',
                'canton'   => '20',
                'district' => '02',
            ],
            '12003' => [
                'province' => '1',
                'canton'   => '20',
                'district' => '03',
            ],
            '12004' => [
                'province' => '1',
                'canton'   => '20',
                'district' => '04',
            ],
            '12005' => [
                'province' => '1',
                'canton'   => '20',
                'district' => '05',
            ],
            '12006' => [
                'province' => '1',
                'canton'   => '20',
                'district' => '06',
            ],
            '20101' => [
                'province' => '2',
                'canton'   => '01',
                'district' => '01',
            ],
            '20102' => [
                'province' => '2',
                'canton'   => '01',
                'district' => '02',
            ],
            '20103' => [
                'province' => '2',
                'canton'   => '01',
                'district' => '03',
            ],
            '20104' => [
                'province' => '2',
                'canton'   => '01',
                'district' => '04',
            ],
            '20105' => [
                'province' => '2',
                'canton'   => '01',
                'district' => '05',
            ],
            '20106' => [
                'province' => '2',
                'canton'   => '01',
                'district' => '06',
            ],
            '20107' => [
                'province' => '2',
                'canton'   => '01',
                'district' => '07',
            ],
            '20108' => [
                'province' => '2',
                'canton'   => '01',
                'district' => '08',
            ],
            '20109' => [
                'province' => '2',
                'canton'   => '01',
                'district' => '09',
            ],
            '20110' => [
                'province' => '2',
                'canton'   => '01',
                'district' => '10',
            ],
            '20111' => [
                'province' => '2',
                'canton'   => '01',
                'district' => '11',
            ],
            '20112' => [
                'province' => '2',
                'canton'   => '01',
                'district' => '12',
            ],
            '20113' => [
                'province' => '2',
                'canton'   => '01',
                'district' => '13',
            ],
            '20114' => [
                'province' => '2',
                'canton'   => '01',
                'district' => '14',
            ],
            '20201' => [
                'province' => '2',
                'canton'   => '02',
                'district' => '01',
            ],
            '20202' => [
                'province' => '2',
                'canton'   => '02',
                'district' => '02',
            ],
            '20203' => [
                'province' => '2',
                'canton'   => '02',
                'district' => '03',
            ],
            '20204' => [
                'province' => '2',
                'canton'   => '02',
                'district' => '04',
            ],
            '20205' => [
                'province' => '2',
                'canton'   => '02',
                'district' => '05',
            ],
            '20206' => [
                'province' => '2',
                'canton'   => '02',
                'district' => '06',
            ],
            '20207' => [
                'province' => '2',
                'canton'   => '02',
                'district' => '07',
            ],
            '20208' => [
                'province' => '2',
                'canton'   => '02',
                'district' => '08',
            ],
            '20209' => [
                'province' => '2',
                'canton'   => '02',
                'district' => '09',
            ],
            '20210' => [
                'province' => '2',
                'canton'   => '02',
                'district' => '10',
            ],
            '20211' => [
                'province' => '2',
                'canton'   => '02',
                'district' => '11',
            ],
            '20212' => [
                'province' => '2',
                'canton'   => '02',
                'district' => '12',
            ],
            '20213' => [
                'province' => '2',
                'canton'   => '02',
                'district' => '13',
            ],
            '20301' => [
                'province' => '2',
                'canton'   => '03',
                'district' => '01',
            ],
            '20302' => [
                'province' => '2',
                'canton'   => '03',
                'district' => '02',
            ],
            '20303' => [
                'province' => '2',
                'canton'   => '03',
                'district' => '03',
            ],
            '20304' => [
                'province' => '2',
                'canton'   => '03',
                'district' => '04',
            ],
            '20305' => [
                'province' => '2',
                'canton'   => '03',
                'district' => '05',
            ],
            '20306' => [
                'province' => '2',
                'canton'   => '03',
                'district' => '06',
            ],
            '20307' => [
                'province' => '2',
                'canton'   => '03',
                'district' => '07',
            ],
            '20308' => [
                'province' => '2',
                'canton'   => '03',
                'district' => '08',
            ],
            '20401' => [
                'province' => '2',
                'canton'   => '04',
                'district' => '01',
            ],
            '20402' => [
                'province' => '2',
                'canton'   => '04',
                'district' => '02',
            ],
            '20403' => [
                'province' => '2',
                'canton'   => '04',
                'district' => '03',
            ],
            '20404' => [
                'province' => '2',
                'canton'   => '04',
                'district' => '04',
            ],
            '20501' => [
                'province' => '2',
                'canton'   => '05',
                'district' => '01',
            ],
            '20502' => [
                'province' => '2',
                'canton'   => '05',
                'district' => '02',
            ],
            '20503' => [
                'province' => '2',
                'canton'   => '05',
                'district' => '03',
            ],
            '20504' => [
                'province' => '2',
                'canton'   => '05',
                'district' => '04',
            ],
            '20505' => [
                'province' => '2',
                'canton'   => '05',
                'district' => '05',
            ],
            '20506' => [
                'province' => '2',
                'canton'   => '05',
                'district' => '06',
            ],
            '20507' => [
                'province' => '2',
                'canton'   => '05',
                'district' => '07',
            ],
            '20508' => [
                'province' => '2',
                'canton'   => '05',
                'district' => '08',
            ],
            '20601' => [
                'province' => '2',
                'canton'   => '06',
                'district' => '01',
            ],
            '20602' => [
                'province' => '2',
                'canton'   => '06',
                'district' => '02',
            ],
            '20603' => [
                'province' => '2',
                'canton'   => '06',
                'district' => '03',
            ],
            '20604' => [
                'province' => '2',
                'canton'   => '06',
                'district' => '04',
            ],
            '20605' => [
                'province' => '2',
                'canton'   => '06',
                'district' => '05',
            ],
            '20606' => [
                'province' => '2',
                'canton'   => '06',
                'district' => '06',
            ],
            '20607' => [
                'province' => '2',
                'canton'   => '06',
                'district' => '07',
            ],
            '20608' => [
                'province' => '2',
                'canton'   => '06',
                'district' => '08',
            ],
            '20701' => [
                'province' => '2',
                'canton'   => '07',
                'district' => '01',
            ],
            '20702' => [
                'province' => '2',
                'canton'   => '07',
                'district' => '02',
            ],
            '20703' => [
                'province' => '2',
                'canton'   => '07',
                'district' => '03',
            ],
            '20704' => [
                'province' => '2',
                'canton'   => '07',
                'district' => '04',
            ],
            '20705' => [
                'province' => '2',
                'canton'   => '07',
                'district' => '05',
            ],
            '20706' => [
                'province' => '2',
                'canton'   => '07',
                'district' => '06',
            ],
            '20707' => [
                'province' => '2',
                'canton'   => '07',
                'district' => '07',
            ],
            '20801' => [
                'province' => '2',
                'canton'   => '08',
                'district' => '01',
            ],
            '20802' => [
                'province' => '2',
                'canton'   => '08',
                'district' => '02',
            ],
            '20803' => [
                'province' => '2',
                'canton'   => '08',
                'district' => '03',
            ],
            '20804' => [
                'province' => '2',
                'canton'   => '08',
                'district' => '04',
            ],
            '20805' => [
                'province' => '2',
                'canton'   => '08',
                'district' => '05',
            ],
            '20901' => [
                'province' => '2',
                'canton'   => '09',
                'district' => '01',
            ],
            '20902' => [
                'province' => '2',
                'canton'   => '09',
                'district' => '02',
            ],
            '20903' => [
                'province' => '2',
                'canton'   => '09',
                'district' => '03',
            ],
            '20904' => [
                'province' => '2',
                'canton'   => '09',
                'district' => '04',
            ],
            '20905' => [
                'province' => '2',
                'canton'   => '09',
                'district' => '05',
            ],
            '21001' => [
                'province' => '2',
                'canton'   => '10',
                'district' => '01',
            ],
            '21002' => [
                'province' => '2',
                'canton'   => '10',
                'district' => '02',
            ],
            '21003' => [
                'province' => '2',
                'canton'   => '10',
                'district' => '03',
            ],
            '21004' => [
                'province' => '2',
                'canton'   => '10',
                'district' => '04',
            ],
            '21005' => [
                'province' => '2',
                'canton'   => '10',
                'district' => '05',
            ],
            '21006' => [
                'province' => '2',
                'canton'   => '10',
                'district' => '06',
            ],
            '21007' => [
                'province' => '2',
                'canton'   => '10',
                'district' => '07',
            ],
            '21008' => [
                'province' => '2',
                'canton'   => '10',
                'district' => '08',
            ],
            '21009' => [
                'province' => '2',
                'canton'   => '10',
                'district' => '09',
            ],
            '21010' => [
                'province' => '2',
                'canton'   => '10',
                'district' => '10',
            ],
            '21011' => [
                'province' => '2',
                'canton'   => '10',
                'district' => '11',
            ],
            '21012' => [
                'province' => '2',
                'canton'   => '10',
                'district' => '12',
            ],
            '21013' => [
                'province' => '2',
                'canton'   => '10',
                'district' => '13',
            ],
            '21101' => [
                'province' => '2',
                'canton'   => '11',
                'district' => '01',
            ],
            '21102' => [
                'province' => '2',
                'canton'   => '11',
                'district' => '02',
            ],
            '21103' => [
                'province' => '2',
                'canton'   => '11',
                'district' => '03',
            ],
            '21104' => [
                'province' => '2',
                'canton'   => '11',
                'district' => '04',
            ],
            '21105' => [
                'province' => '2',
                'canton'   => '11',
                'district' => '05',
            ],
            '21106' => [
                'province' => '2',
                'canton'   => '11',
                'district' => '06',
            ],
            '21107' => [
                'province' => '2',
                'canton'   => '11',
                'district' => '07',
            ],
            '21201' => [
                'province' => '2',
                'canton'   => '12',
                'district' => '01',
            ],
            '21202' => [
                'province' => '2',
                'canton'   => '12',
                'district' => '02',
            ],
            '21203' => [
                'province' => '2',
                'canton'   => '12',
                'district' => '03',
            ],
            '21204' => [
                'province' => '2',
                'canton'   => '12',
                'district' => '04',
            ],
            '21205' => [
                'province' => '2',
                'canton'   => '12',
                'district' => '05',
            ],
            '21301' => [
                'province' => '2',
                'canton'   => '13',
                'district' => '01',
            ],
            '21302' => [
                'province' => '2',
                'canton'   => '13',
                'district' => '02',
            ],
            '21303' => [
                'province' => '2',
                'canton'   => '13',
                'district' => '03',
            ],
            '21304' => [
                'province' => '2',
                'canton'   => '13',
                'district' => '04',
            ],
            '21305' => [
                'province' => '2',
                'canton'   => '13',
                'district' => '05',
            ],
            '21306' => [
                'province' => '2',
                'canton'   => '13',
                'district' => '06',
            ],
            '21307' => [
                'province' => '2',
                'canton'   => '13',
                'district' => '07',
            ],
            '21308' => [
                'province' => '2',
                'canton'   => '13',
                'district' => '08',
            ],
            '21401' => [
                'province' => '2',
                'canton'   => '14',
                'district' => '01',
            ],
            '21402' => [
                'province' => '2',
                'canton'   => '14',
                'district' => '02',
            ],
            '21403' => [
                'province' => '2',
                'canton'   => '14',
                'district' => '03',
            ],
            '21404' => [
                'province' => '2',
                'canton'   => '14',
                'district' => '04',
            ],
            '21501' => [
                'province' => '2',
                'canton'   => '15',
                'district' => '01',
            ],
            '21502' => [
                'province' => '2',
                'canton'   => '15',
                'district' => '02',
            ],
            '21503' => [
                'province' => '2',
                'canton'   => '15',
                'district' => '03',
            ],
            '21504' => [
                'province' => '2',
                'canton'   => '15',
                'district' => '04',
            ],
            '21601' => [
                'province' => '2',
                'canton'   => '16',
                'district' => '01',
            ],
            '21602' => [
                'province' => '2',
                'canton'   => '16',
                'district' => '02',
            ],
            '21603' => [
                'province' => '2',
                'canton'   => '16',
                'district' => '03',
            ],
            '30101' => [
                'province' => '3',
                'canton'   => '01',
                'district' => '01',
            ],
            '30102' => [
                'province' => '3',
                'canton'   => '01',
                'district' => '02',
            ],
            '30103' => [
                'province' => '3',
                'canton'   => '01',
                'district' => '03',
            ],
            '30104' => [
                'province' => '3',
                'canton'   => '01',
                'district' => '04',
            ],
            '30105' => [
                'province' => '3',
                'canton'   => '01',
                'district' => '05',
            ],
            '30106' => [
                'province' => '3',
                'canton'   => '01',
                'district' => '06',
            ],
            '30107' => [
                'province' => '3',
                'canton'   => '01',
                'district' => '07',
            ],
            '30108' => [
                'province' => '3',
                'canton'   => '01',
                'district' => '08',
            ],
            '30109' => [
                'province' => '3',
                'canton'   => '01',
                'district' => '09',
            ],
            '30110' => [
                'province' => '3',
                'canton'   => '01',
                'district' => '10',
            ],
            '30111' => [
                'province' => '3',
                'canton'   => '01',
                'district' => '11',
            ],
            '30201' => [
                'province' => '3',
                'canton'   => '02',
                'district' => '01',
            ],
            '30202' => [
                'province' => '3',
                'canton'   => '02',
                'district' => '02',
            ],
            '30203' => [
                'province' => '3',
                'canton'   => '02',
                'district' => '03',
            ],
            '30204' => [
                'province' => '3',
                'canton'   => '02',
                'district' => '04',
            ],
            '30205' => [
                'province' => '3',
                'canton'   => '02',
                'district' => '05',
            ],
            '30301' => [
                'province' => '3',
                'canton'   => '03',
                'district' => '01',
            ],
            '30302' => [
                'province' => '3',
                'canton'   => '03',
                'district' => '02',
            ],
            '30303' => [
                'province' => '3',
                'canton'   => '03',
                'district' => '03',
            ],
            '30304' => [
                'province' => '3',
                'canton'   => '03',
                'district' => '04',
            ],
            '30305' => [
                'province' => '3',
                'canton'   => '03',
                'district' => '05',
            ],
            '30306' => [
                'province' => '3',
                'canton'   => '03',
                'district' => '06',
            ],
            '30307' => [
                'province' => '3',
                'canton'   => '03',
                'district' => '07',
            ],
            '30308' => [
                'province' => '3',
                'canton'   => '03',
                'district' => '08',
            ],
            '30401' => [
                'province' => '3',
                'canton'   => '04',
                'district' => '01',
            ],
            '30402' => [
                'province' => '3',
                'canton'   => '04',
                'district' => '02',
            ],
            '30403' => [
                'province' => '3',
                'canton'   => '04',
                'district' => '03',
            ],
            '30501' => [
                'province' => '3',
                'canton'   => '05',
                'district' => '01',
            ],
            '30502' => [
                'province' => '3',
                'canton'   => '05',
                'district' => '02',
            ],
            '30503' => [
                'province' => '3',
                'canton'   => '05',
                'district' => '03',
            ],
            '30504' => [
                'province' => '3',
                'canton'   => '05',
                'district' => '04',
            ],
            '30505' => [
                'province' => '3',
                'canton'   => '05',
                'district' => '05',
            ],
            '30506' => [
                'province' => '3',
                'canton'   => '05',
                'district' => '06',
            ],
            '30507' => [
                'province' => '3',
                'canton'   => '05',
                'district' => '07',
            ],
            '30508' => [
                'province' => '3',
                'canton'   => '05',
                'district' => '08',
            ],
            '30509' => [
                'province' => '3',
                'canton'   => '05',
                'district' => '09',
            ],
            '30510' => [
                'province' => '3',
                'canton'   => '05',
                'district' => '10',
            ],
            '30511' => [
                'province' => '3',
                'canton'   => '05',
                'district' => '11',
            ],
            '30512' => [
                'province' => '3',
                'canton'   => '05',
                'district' => '12',
            ],
            '30601' => [
                'province' => '3',
                'canton'   => '06',
                'district' => '01',
            ],
            '30602' => [
                'province' => '3',
                'canton'   => '06',
                'district' => '02',
            ],
            '30603' => [
                'province' => '3',
                'canton'   => '06',
                'district' => '03',
            ],
            '30701' => [
                'province' => '3',
                'canton'   => '07',
                'district' => '01',
            ],
            '30702' => [
                'province' => '3',
                'canton'   => '07',
                'district' => '02',
            ],
            '30703' => [
                'province' => '3',
                'canton'   => '07',
                'district' => '03',
            ],
            '30704' => [
                'province' => '3',
                'canton'   => '07',
                'district' => '04',
            ],
            '30705' => [
                'province' => '3',
                'canton'   => '07',
                'district' => '05',
            ],
            '30801' => [
                'province' => '3',
                'canton'   => '08',
                'district' => '01',
            ],
            '30802' => [
                'province' => '3',
                'canton'   => '08',
                'district' => '02',
            ],
            '30803' => [
                'province' => '3',
                'canton'   => '08',
                'district' => '03',
            ],
            '30804' => [
                'province' => '3',
                'canton'   => '08',
                'district' => '04',
            ],
            '40101' => [
                'province' => '4',
                'canton'   => '01',
                'district' => '01',
            ],
            '40102' => [
                'province' => '4',
                'canton'   => '01',
                'district' => '02',
            ],
            '40103' => [
                'province' => '4',
                'canton'   => '01',
                'district' => '03',
            ],
            '40104' => [
                'province' => '4',
                'canton'   => '01',
                'district' => '04',
            ],
            '40105' => [
                'province' => '4',
                'canton'   => '01',
                'district' => '05',
            ],
            '40201' => [
                'province' => '4',
                'canton'   => '02',
                'district' => '01',
            ],
            '40202' => [
                'province' => '4',
                'canton'   => '02',
                'district' => '02',
            ],
            '40203' => [
                'province' => '4',
                'canton'   => '02',
                'district' => '03',
            ],
            '40204' => [
                'province' => '4',
                'canton'   => '02',
                'district' => '04',
            ],
            '40205' => [
                'province' => '4',
                'canton'   => '02',
                'district' => '05',
            ],
            '40206' => [
                'province' => '4',
                'canton'   => '02',
                'district' => '06',
            ],
            '40301' => [
                'province' => '4',
                'canton'   => '03',
                'district' => '01',
            ],
            '40302' => [
                'province' => '4',
                'canton'   => '03',
                'district' => '02',
            ],
            '40303' => [
                'province' => '4',
                'canton'   => '03',
                'district' => '03',
            ],
            '40304' => [
                'province' => '4',
                'canton'   => '03',
                'district' => '04',
            ],
            '40305' => [
                'province' => '4',
                'canton'   => '03',
                'district' => '05',
            ],
            '40306' => [
                'province' => '4',
                'canton'   => '03',
                'district' => '06',
            ],
            '40307' => [
                'province' => '4',
                'canton'   => '03',
                'district' => '07',
            ],
            '40308' => [
                'province' => '4',
                'canton'   => '03',
                'district' => '08',
            ],
            '40401' => [
                'province' => '4',
                'canton'   => '04',
                'district' => '01',
            ],
            '40402' => [
                'province' => '4',
                'canton'   => '04',
                'district' => '02',
            ],
            '40403' => [
                'province' => '4',
                'canton'   => '04',
                'district' => '03',
            ],
            '40404' => [
                'province' => '4',
                'canton'   => '04',
                'district' => '04',
            ],
            '40405' => [
                'province' => '4',
                'canton'   => '04',
                'district' => '05',
            ],
            '40406' => [
                'province' => '4',
                'canton'   => '04',
                'district' => '06',
            ],
            '40501' => [
                'province' => '4',
                'canton'   => '05',
                'district' => '01',
            ],
            '40502' => [
                'province' => '4',
                'canton'   => '05',
                'district' => '02',
            ],
            '40503' => [
                'province' => '4',
                'canton'   => '05',
                'district' => '03',
            ],
            '40504' => [
                'province' => '4',
                'canton'   => '05',
                'district' => '04',
            ],
            '40505' => [
                'province' => '4',
                'canton'   => '05',
                'district' => '05',
            ],
            '40601' => [
                'province' => '4',
                'canton'   => '06',
                'district' => '01',
            ],
            '40602' => [
                'province' => '4',
                'canton'   => '06',
                'district' => '02',
            ],
            '40603' => [
                'province' => '4',
                'canton'   => '06',
                'district' => '03',
            ],
            '40604' => [
                'province' => '4',
                'canton'   => '06',
                'district' => '04',
            ],
            '40701' => [
                'province' => '4',
                'canton'   => '07',
                'district' => '01',
            ],
            '40702' => [
                'province' => '4',
                'canton'   => '07',
                'district' => '02',
            ],
            '40703' => [
                'province' => '4',
                'canton'   => '07',
                'district' => '03',
            ],
            '40801' => [
                'province' => '4',
                'canton'   => '08',
                'district' => '01',
            ],
            '40802' => [
                'province' => '4',
                'canton'   => '08',
                'district' => '02',
            ],
            '40803' => [
                'province' => '4',
                'canton'   => '08',
                'district' => '03',
            ],
            '40901' => [
                'province' => '4',
                'canton'   => '09',
                'district' => '01',
            ],
            '40902' => [
                'province' => '4',
                'canton'   => '09',
                'district' => '02',
            ],
            '41001' => [
                'province' => '4',
                'canton'   => '10',
                'district' => '01',
            ],
            '41002' => [
                'province' => '4',
                'canton'   => '10',
                'district' => '02',
            ],
            '41003' => [
                'province' => '4',
                'canton'   => '10',
                'district' => '03',
            ],
            '41004' => [
                'province' => '4',
                'canton'   => '10',
                'district' => '04',
            ],
            '41005' => [
                'province' => '4',
                'canton'   => '10',
                'district' => '05',
            ],
            '50101' => [
                'province' => '5',
                'canton'   => '01',
                'district' => '01',
            ],
            '50102' => [
                'province' => '5',
                'canton'   => '01',
                'district' => '02',
            ],
            '50103' => [
                'province' => '5',
                'canton'   => '01',
                'district' => '03',
            ],
            '50104' => [
                'province' => '5',
                'canton'   => '01',
                'district' => '04',
            ],
            '50105' => [
                'province' => '5',
                'canton'   => '01',
                'district' => '05',
            ],
            '50201' => [
                'province' => '5',
                'canton'   => '02',
                'district' => '01',
            ],
            '50202' => [
                'province' => '5',
                'canton'   => '02',
                'district' => '02',
            ],
            '50203' => [
                'province' => '5',
                'canton'   => '02',
                'district' => '03',
            ],
            '50204' => [
                'province' => '5',
                'canton'   => '02',
                'district' => '04',
            ],
            '50205' => [
                'province' => '5',
                'canton'   => '02',
                'district' => '05',
            ],
            '50206' => [
                'province' => '5',
                'canton'   => '02',
                'district' => '06',
            ],
            '50207' => [
                'province' => '5',
                'canton'   => '02',
                'district' => '07',
            ],
            '50301' => [
                'province' => '5',
                'canton'   => '03',
                'district' => '01',
            ],
            '50302' => [
                'province' => '5',
                'canton'   => '03',
                'district' => '02',
            ],
            '50303' => [
                'province' => '5',
                'canton'   => '03',
                'district' => '03',
            ],
            '50304' => [
                'province' => '5',
                'canton'   => '03',
                'district' => '04',
            ],
            '50305' => [
                'province' => '5',
                'canton'   => '03',
                'district' => '05',
            ],
            '50306' => [
                'province' => '5',
                'canton'   => '03',
                'district' => '06',
            ],
            '50307' => [
                'province' => '5',
                'canton'   => '03',
                'district' => '07',
            ],
            '50308' => [
                'province' => '5',
                'canton'   => '03',
                'district' => '08',
            ],
            '50309' => [
                'province' => '5',
                'canton'   => '03',
                'district' => '09',
            ],
            '50401' => [
                'province' => '5',
                'canton'   => '04',
                'district' => '01',
            ],
            '50402' => [
                'province' => '5',
                'canton'   => '04',
                'district' => '02',
            ],
            '50403' => [
                'province' => '5',
                'canton'   => '04',
                'district' => '03',
            ],
            '50404' => [
                'province' => '5',
                'canton'   => '04',
                'district' => '04',
            ],
            '50501' => [
                'province' => '5',
                'canton'   => '05',
                'district' => '01',
            ],
            '50502' => [
                'province' => '5',
                'canton'   => '05',
                'district' => '02',
            ],
            '50503' => [
                'province' => '5',
                'canton'   => '05',
                'district' => '03',
            ],
            '50504' => [
                'province' => '5',
                'canton'   => '05',
                'district' => '04',
            ],
            '50601' => [
                'province' => '5',
                'canton'   => '06',
                'district' => '01',
            ],
            '50602' => [
                'province' => '5',
                'canton'   => '06',
                'district' => '02',
            ],
            '50603' => [
                'province' => '5',
                'canton'   => '06',
                'district' => '03',
            ],
            '50604' => [
                'province' => '5',
                'canton'   => '06',
                'district' => '04',
            ],
            '50605' => [
                'province' => '5',
                'canton'   => '06',
                'district' => '05',
            ],
            '50701' => [
                'province' => '5',
                'canton'   => '07',
                'district' => '01',
            ],
            '50702' => [
                'province' => '5',
                'canton'   => '07',
                'district' => '02',
            ],
            '50703' => [
                'province' => '5',
                'canton'   => '07',
                'district' => '03',
            ],
            '50704' => [
                'province' => '5',
                'canton'   => '07',
                'district' => '04',
            ],
            '50801' => [
                'province' => '5',
                'canton'   => '08',
                'district' => '01',
            ],
            '50802' => [
                'province' => '5',
                'canton'   => '08',
                'district' => '02',
            ],
            '50803' => [
                'province' => '5',
                'canton'   => '08',
                'district' => '03',
            ],
            '50804' => [
                'province' => '5',
                'canton'   => '08',
                'district' => '04',
            ],
            '50805' => [
                'province' => '5',
                'canton'   => '08',
                'district' => '05',
            ],
            '50806' => [
                'province' => '5',
                'canton'   => '08',
                'district' => '06',
            ],
            '50807' => [
                'province' => '5',
                'canton'   => '08',
                'district' => '07',
            ],
            '50808' => [
                'province' => '5',
                'canton'   => '08',
                'district' => '08',
            ],
            '50901' => [
                'province' => '5',
                'canton'   => '09',
                'district' => '01',
            ],
            '50902' => [
                'province' => '5',
                'canton'   => '09',
                'district' => '02',
            ],
            '50903' => [
                'province' => '5',
                'canton'   => '09',
                'district' => '03',
            ],
            '50904' => [
                'province' => '5',
                'canton'   => '09',
                'district' => '04',
            ],
            '50905' => [
                'province' => '5',
                'canton'   => '09',
                'district' => '05',
            ],
            '50906' => [
                'province' => '5',
                'canton'   => '09',
                'district' => '06',
            ],
            '51001' => [
                'province' => '5',
                'canton'   => '10',
                'district' => '01',
            ],
            '51002' => [
                'province' => '5',
                'canton'   => '10',
                'district' => '02',
            ],
            '51003' => [
                'province' => '5',
                'canton'   => '10',
                'district' => '03',
            ],
            '51004' => [
                'province' => '5',
                'canton'   => '10',
                'district' => '04',
            ],
            '51101' => [
                'province' => '5',
                'canton'   => '11',
                'district' => '01',
            ],
            '51102' => [
                'province' => '5',
                'canton'   => '11',
                'district' => '02',
            ],
            '51103' => [
                'province' => '5',
                'canton'   => '11',
                'district' => '03',
            ],
            '51104' => [
                'province' => '5',
                'canton'   => '11',
                'district' => '04',
            ],
            '60101' => [
                'province' => '6',
                'canton'   => '01',
                'district' => '01',
            ],
            '60102' => [
                'province' => '6',
                'canton'   => '01',
                'district' => '02',
            ],
            '60103' => [
                'province' => '6',
                'canton'   => '01',
                'district' => '03',
            ],
            '60104' => [
                'province' => '6',
                'canton'   => '01',
                'district' => '04',
            ],
            '60105' => [
                'province' => '6',
                'canton'   => '01',
                'district' => '05',
            ],
            '60106' => [
                'province' => '6',
                'canton'   => '01',
                'district' => '06',
            ],
            '60107' => [
                'province' => '6',
                'canton'   => '01',
                'district' => '07',
            ],
            '60108' => [
                'province' => '6',
                'canton'   => '01',
                'district' => '08',
            ],
            '60109' => [
                'province' => '6',
                'canton'   => '01',
                'district' => '09',
            ],
            '60110' => [
                'province' => '6',
                'canton'   => '01',
                'district' => '10',
            ],
            '60111' => [
                'province' => '6',
                'canton'   => '01',
                'district' => '11',
            ],
            '60112' => [
                'province' => '6',
                'canton'   => '01',
                'district' => '12',
            ],
            '60113' => [
                'province' => '6',
                'canton'   => '01',
                'district' => '13',
            ],
            '60114' => [
                'province' => '6',
                'canton'   => '01',
                'district' => '14',
            ],
            '60115' => [
                'province' => '6',
                'canton'   => '01',
                'district' => '15',
            ],
            '60116' => [
                'province' => '6',
                'canton'   => '01',
                'district' => '16',
            ],
            '60201' => [
                'province' => '6',
                'canton'   => '02',
                'district' => '01',
            ],
            '60202' => [
                'province' => '6',
                'canton'   => '02',
                'district' => '02',
            ],
            '60203' => [
                'province' => '6',
                'canton'   => '02',
                'district' => '03',
            ],
            '60204' => [
                'province' => '6',
                'canton'   => '02',
                'district' => '04',
            ],
            '60205' => [
                'province' => '6',
                'canton'   => '02',
                'district' => '05',
            ],
            '60206' => [
                'province' => '6',
                'canton'   => '02',
                'district' => '06',
            ],
            '60301' => [
                'province' => '6',
                'canton'   => '03',
                'district' => '01',
            ],
            '60302' => [
                'province' => '6',
                'canton'   => '03',
                'district' => '02',
            ],
            '60303' => [
                'province' => '6',
                'canton'   => '03',
                'district' => '03',
            ],
            '60304' => [
                'province' => '6',
                'canton'   => '03',
                'district' => '04',
            ],
            '60305' => [
                'province' => '6',
                'canton'   => '03',
                'district' => '05',
            ],
            '60306' => [
                'province' => '6',
                'canton'   => '03',
                'district' => '06',
            ],
            '60307' => [
                'province' => '6',
                'canton'   => '03',
                'district' => '07',
            ],
            '60308' => [
                'province' => '6',
                'canton'   => '03',
                'district' => '08',
            ],
            '60309' => [
                'province' => '6',
                'canton'   => '03',
                'district' => '09',
            ],
            '60401' => [
                'province' => '6',
                'canton'   => '04',
                'district' => '01',
            ],
            '60402' => [
                'province' => '6',
                'canton'   => '04',
                'district' => '02',
            ],
            '60403' => [
                'province' => '6',
                'canton'   => '04',
                'district' => '03',
            ],
            '60501' => [
                'province' => '6',
                'canton'   => '05',
                'district' => '01',
            ],
            '60502' => [
                'province' => '6',
                'canton'   => '05',
                'district' => '02',
            ],
            '60503' => [
                'province' => '6',
                'canton'   => '05',
                'district' => '03',
            ],
            '60504' => [
                'province' => '6',
                'canton'   => '05',
                'district' => '04',
            ],
            '60505' => [
                'province' => '6',
                'canton'   => '05',
                'district' => '05',
            ],
            '60506' => [
                'province' => '6',
                'canton'   => '05',
                'district' => '06',
            ],
            '60601' => [
                'province' => '6',
                'canton'   => '06',
                'district' => '01',
            ],
            '60602' => [
                'province' => '6',
                'canton'   => '06',
                'district' => '02',
            ],
            '60603' => [
                'province' => '6',
                'canton'   => '06',
                'district' => '03',
            ],
            '60701' => [
                'province' => '6',
                'canton'   => '07',
                'district' => '01',
            ],
            '60702' => [
                'province' => '6',
                'canton'   => '07',
                'district' => '02',
            ],
            '60703' => [
                'province' => '6',
                'canton'   => '07',
                'district' => '03',
            ],
            '60704' => [
                'province' => '6',
                'canton'   => '07',
                'district' => '04',
            ],
            '60801' => [
                'province' => '6',
                'canton'   => '08',
                'district' => '01',
            ],
            '60802' => [
                'province' => '6',
                'canton'   => '08',
                'district' => '02',
            ],
            '60803' => [
                'province' => '6',
                'canton'   => '08',
                'district' => '03',
            ],
            '60804' => [
                'province' => '6',
                'canton'   => '08',
                'district' => '04',
            ],
            '60805' => [
                'province' => '6',
                'canton'   => '08',
                'district' => '05',
            ],
            '60806' => [
                'province' => '6',
                'canton'   => '08',
                'district' => '06',
            ],
            '60901' => [
                'province' => '6',
                'canton'   => '09',
                'district' => '01',
            ],
            '61001' => [
                'province' => '6',
                'canton'   => '10',
                'district' => '01',
            ],
            '61002' => [
                'province' => '6',
                'canton'   => '10',
                'district' => '02',
            ],
            '61003' => [
                'province' => '6',
                'canton'   => '10',
                'district' => '03',
            ],
            '61004' => [
                'province' => '6',
                'canton'   => '10',
                'district' => '04',
            ],
            '61101' => [
                'province' => '6',
                'canton'   => '11',
                'district' => '01',
            ],
            '61102' => [
                'province' => '6',
                'canton'   => '11',
                'district' => '02',
            ],
            '61201' => [
                'province' => '6',
                'canton'   => '12',
                'district' => '01',
            ],
            '70101' => [
                'province' => '7',
                'canton'   => '01',
                'district' => '01',
            ],
            '70102' => [
                'province' => '7',
                'canton'   => '01',
                'district' => '02',
            ],
            '70103' => [
                'province' => '7',
                'canton'   => '01',
                'district' => '03',
            ],
            '70104' => [
                'province' => '7',
                'canton'   => '01',
                'district' => '04',
            ],
            '70201' => [
                'province' => '7',
                'canton'   => '02',
                'district' => '01',
            ],
            '70202' => [
                'province' => '7',
                'canton'   => '02',
                'district' => '02',
            ],
            '70203' => [
                'province' => '7',
                'canton'   => '02',
                'district' => '03',
            ],
            '70204' => [
                'province' => '7',
                'canton'   => '02',
                'district' => '04',
            ],
            '70205' => [
                'province' => '7',
                'canton'   => '02',
                'district' => '05',
            ],
            '70206' => [
                'province' => '7',
                'canton'   => '02',
                'district' => '06',
            ],
            '70207' => [
                'province' => '7',
                'canton'   => '02',
                'district' => '07',
            ],
            '70301' => [
                'province' => '7',
                'canton'   => '03',
                'district' => '01',
            ],
            '70302' => [
                'province' => '7',
                'canton'   => '03',
                'district' => '02',
            ],
            '70303' => [
                'province' => '7',
                'canton'   => '03',
                'district' => '03',
            ],
            '70304' => [
                'province' => '7',
                'canton'   => '03',
                'district' => '04',
            ],
            '70305' => [
                'province' => '7',
                'canton'   => '03',
                'district' => '05',
            ],
            '70306' => [
                'province' => '7',
                'canton'   => '03',
                'district' => '06',
            ],
            '70307' => [
                'province' => '7',
                'canton'   => '03',
                'district' => '07',
            ],
            '70401' => [
                'province' => '7',
                'canton'   => '04',
                'district' => '01',
            ],
            '70402' => [
                'province' => '7',
                'canton'   => '04',
                'district' => '02',
            ],
            '70403' => [
                'province' => '7',
                'canton'   => '04',
                'district' => '03',
            ],
            '70404' => [
                'province' => '7',
                'canton'   => '04',
                'district' => '04',
            ],
            '70501' => [
                'province' => '7',
                'canton'   => '05',
                'district' => '01',
            ],
            '70502' => [
                'province' => '7',
                'canton'   => '05',
                'district' => '02',
            ],
            '70503' => [
                'province' => '7',
                'canton'   => '05',
                'district' => '03',
            ],
            '70601' => [
                'province' => '7',
                'canton'   => '06',
                'district' => '01',
            ],
            '70602' => [
                'province' => '7',
                'canton'   => '06',
                'district' => '02',
            ],
            '70603' => [
                'province' => '7',
                'canton'   => '06',
                'district' => '03',
            ],
            '70604' => [
                'province' => '7',
                'canton'   => '06',
                'district' => '04',
            ],
            '70605' => [
                'province' => '7',
                'canton'   => '06',
                'district' => '05',
            ],
        ];
        if ( !empty( $data[$postcode] ) ) {
            return $data[$postcode];
        }
        return [];
    }

}

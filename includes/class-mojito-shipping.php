<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://mojitowp.com
 * @since      1.0.0
 *
 * @package    Mojito_Shipping
 * @subpackage Mojito_Shipping/includes
 *
 *
 * Los curiosos deben leer el constructor: __construct()
 */
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
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
class Mojito_Shipping {
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Mojito_Shipping_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * CCR legacy WS client
     *
     * @var Mojito_Shipping_Method_CCR_WSC
     */
    protected $ccr_ws_client;

    /**
     * Pymexpress WS Client
     *
     * @var Mojito_Shipping_Method_Pymexpress_WSC
     */
    protected $pymexpress_ws_client;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if ( defined( 'MOJITO_SHIPPING_VERSION' ) ) {
            $this->version = MOJITO_SHIPPING_VERSION;
        } else {
            $this->version = '1.5.6';
        }
        $this->plugin_name = 'mojito-shipping';
        /**
         * Define plugin name as constant.
         */
        define( 'MOJITO_SHIPPING_SLUG', $this->plugin_name );
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        /**
         * Add address support
         */
        if ( class_exists( 'Mojito_Shipping\\Mojito_Shipping_Address' ) ) {
            $mojito_address = new Mojito_Shipping_Address();
        }
        add_action( 'init', array($this, 'start_woocommerce_shipping') );
        /**
         * Cron jobs
         */
        add_action( 'mojito-shippping-cron-ccr', array($this, 'cron_ccr') );
        add_action( 'mojito-shippping-cron-pymexpress', array($this, 'cron_pymexpress') );
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Mojito_Shipping_Loader. Orchestrates the hooks of the plugin.
     * - Mojito_Shipping_I18n. Defines internationalization functionality.
     * - Mojito_Shipping_Admin. Defines all hooks for the admin area.
     * - Mojito_Shipping_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        if ( !class_exists( 'Mojito_Shipping_Loader' ) ) {
            require_once MOJITO_SHIPPING_DIR . 'includes/class-mojito-shipping-loader.php';
        }
        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        if ( !class_exists( 'Mojito_Shipping_I18n' ) ) {
            require_once MOJITO_SHIPPING_DIR . 'includes/class-mojito-shipping-i18n.php';
        }
        /**
         * The class responsible for generate settings controls ans inputs
         */
        if ( !class_exists( 'Mojito_Settings' ) ) {
            require_once MOJITO_SHIPPING_DIR . 'includes/class-mojito-settings.php';
        }
        /**
         * The class responsible for exchange rates.
         */
        if ( !class_exists( 'Mojito_Shipping_Exchange_Rate' ) ) {
            require_once MOJITO_SHIPPING_DIR . 'includes/class-mojito-shipping-exchange-rate.php';
        }
        /**
         * Load Address
         */
        require_once MOJITO_SHIPPING_DIR . 'includes/class-mojito-shipping-address.php';
        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        if ( !class_exists( 'Mojito_Shipping\\Mojito_Shipping_Admin' ) ) {
            require_once MOJITO_SHIPPING_DIR . 'admin/class-mojito-shipping-admin.php';
        }
        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once MOJITO_SHIPPING_DIR . 'public/class-mojito-shipping-public.php';
        $this->loader = new Mojito_Shipping_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Mojito_Shipping_I18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Mojito_Shipping_I18n();
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Mojito_Shipping_Admin();
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'menu_options' );
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Mojito_Shipping_Public($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $carrier_provider = get_option( 'mojito-shipping-carrier-provider' );
        if ( is_array( $carrier_provider ) ) {
            if ( in_array( 'ccr', $carrier_provider, true ) ) {
                // Init web service client.
                if ( !class_exists( 'Mojito_Shipping_Method_CCR_WSC' ) ) {
                    require_once MOJITO_SHIPPING_DIR . 'includes/class-mojito-shipping-method-ccr-webservice-client.php';
                }
                $this->ccr_ws_client = new Mojito_Shipping_Method_CCR_WSC();
                add_action( 'woocommerce_checkout_after_customer_details', array($this, 'ccr_checkout_after_customer_details') );
                add_action( 'woocommerce_checkout_update_order_meta', array($this, 'ccr_checkout_update_order_meta_save_guide_number') );
                add_action( 'woocommerce_thankyou', array($this, 'ccr_thankyou_log_send') );
                add_action(
                    'woocommerce_order_details_after_order_table_items',
                    array($this, 'ccr_order_items_table_success_meta'),
                    30,
                    1
                );
                add_action(
                    'woocommerce_admin_order_data_after_billing_address',
                    array($this, 'ccr_admin_order_data_after_billing_address'),
                    40,
                    1
                );
                add_action(
                    'load-post.php',
                    array($this, 'ccr_admin_order_tracking_data'),
                    40,
                    1
                );
                add_action(
                    'woocommerce_email_after_order_table',
                    array($this, 'ccr_email_after_order_table_add_guide_number'),
                    12,
                    2
                );
                /**
                 * Manual Guide number request
                 */
                add_action( 'wp_ajax_mojito_shipping_ccr_manual_request_guide_number', array($this, 'ccr_manual_request') );
                /**
                 * Manual register Guide number
                 */
                add_action( 'wp_ajax_mojito_shipping_ccr_manual_register_guide_number', array($this, 'ccr_manual_register') );
                /**
                 * Download PDF from admin
                 */
                add_action( 'wp_ajax_mojito_shipping_ccr_download_pdf', array($this, 'ccr_pdf_download') );
                /**
                 * Download PDF from customer order details
                 */
                if ( 'yes' === get_option( 'mojito-shipping-ccr-pdf-export-in-customer-order' ) ) {
                    add_action( 'wp_ajax_mojito_shipping_ccr_download_pdf_customer', array($this, 'ccr_pdf_download') );
                }
            }
            if ( in_array( 'pymexpress', $carrier_provider, true ) ) {
                // Init web service client.
                if ( !class_exists( 'Mojito_Shipping_Method_Pymexpress_WSC' ) ) {
                    require_once MOJITO_SHIPPING_DIR . 'includes/class-mojito-shipping-method-pymexpress-webservice-client.php';
                }
                $this->pymexpress_ws_client = new Mojito_Shipping_Method_Pymexpress_WSC();
                add_action( 'woocommerce_checkout_after_customer_details', array($this, 'pymexpress_checkout_after_customer_details') );
                add_action( 'woocommerce_checkout_update_order_meta', array($this, 'pymexpress_checkout_update_order_meta_save_guide_number') );
                add_action( 'woocommerce_order_status_changed', array($this, 'pymexpress_order_is_completed') );
                add_action(
                    'woocommerce_order_details_after_order_table_items',
                    array($this, 'pymexpress_order_items_table_success_meta'),
                    30,
                    1
                );
                add_action(
                    'woocommerce_admin_order_data_after_billing_address',
                    array($this, 'pymexpress_admin_order_data_after_billing_address'),
                    40,
                    1
                );
                add_action(
                    'load-post.php',
                    array($this, 'pymexpress_admin_order_tracking_data'),
                    40,
                    1
                );
                add_action(
                    'woocommerce_email_after_order_table',
                    array($this, 'pymexpress_email_after_order_table_add_guide_number'),
                    12,
                    2
                );
                /**
                 * Manual Guide number request
                 */
                add_action( 'wp_ajax_mojito_shipping_pymexpress_manual_request_guide_number', array($this, 'pymexpress_manual_request') );
                /**
                 * Manual register Guide number
                 */
                add_action( 'wp_ajax_mojito_shipping_pymexpress_manual_register_guide_number', array($this, 'pymexpress_manual_register') );
                /**
                 * Download PDF from admin
                 */
                add_action( 'wp_ajax_mojito_shipping_pymexpress_download_pdf', array($this, 'pymexpress_pdf_download') );
                /**
                 * Download PDF from customer order details
                 */
                if ( 'yes' === get_option( 'mojito-shipping-pymexpress-pdf-export-in-customer-order' ) ) {
                    add_action( 'wp_ajax_mojito_shipping_pymexpress_download_pdf_customer', array($this, 'pymexpress_pdf_download') );
                }
                /**
                 * Pymexpress location list
                 */
                $mojito_address = new Mojito_Shipping_Address();
                add_action( 'wp_ajax_mojito_shipping_pymexpress_get_provinces_list', array($mojito_address, 'get_pymexpress_provinces_list_ajax') );
                add_action( 'wp_ajax_mojito_shipping_pymexpress_get_cantons_list', array($mojito_address, 'get_pymexpress_cantons_list_ajax') );
                add_action( 'wp_ajax_mojito_shipping_pymexpress_get_district_list', array($mojito_address, 'get_pymexpress_districts_list_ajax') );
                add_action( 'wp_ajax_mojito_shipping_pymexpress_get_cities_list', array($mojito_address, 'get_pymexpress_cities_list_ajax') );
                if ( !get_option( 'mojito-shipping-pymexpress-preloaded' ) ) {
                    $mojito_address->pre_load_pymexpress_locations();
                }
            }
        }
        $this->loader->run();
    }

    /**
     * Add CCR guide number after customer details
     *
     * @return void
     */
    public function ccr_checkout_after_customer_details() {
        woocommerce_form_field( 'mojito_shipping_ccr_guide_number', array(
            'type'              => 'text',
            'class'             => array('hidden'),
            'default'           => $this->ccr_ws_client->ccr_get_guide_number(),
            'custom_attributes' => array(
                'readonly' => 'readonly',
            ),
        ) );
    }

    /**
     * Update meta, save guide number.
     *
     * @param Int $order_id Order ID.
     * @return void
     */
    public function ccr_checkout_update_order_meta_save_guide_number( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( !$order->has_shipping_method( 'mojito_shipping_ccr' ) ) {
            return;
        }
        if ( !empty( $_POST['mojito_shipping_ccr_guide_number'] ) && $order->has_shipping_method( 'mojito_shipping_ccr' ) ) {
            $order->update_meta_data( 'mojito_shipping_ccr_guide_number', sanitize_text_field( $_POST['mojito_shipping_ccr_guide_number'] ) );
            $order->save();
        }
    }

    /**
     * Thank you page
     *
     * @param Int     $order_id Order id.
     * @param boolean $manual_request is a manual request?.
     * @return void
     */
    public function ccr_thankyou_log_send( $order_id, $manual_request = false ) {
        $order = wc_get_order( $order_id );
        $log_send = $order->get_meta( 'mojito_ccr_shipping_log', true );
        $details = '';
        /**
         * Is a valid order?
         */
        if ( is_bool( $order ) ) {
            return;
        }
        $items = array();
        $weight_unit = get_option( 'woocommerce_weight_unit' );
        if ( ($manual_request || empty( $log_send )) && $order->has_shipping_method( 'mojito_shipping_ccr' ) ) {
            $full_name = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
            if ( 0 === strlen( trim( $full_name ) ) ) {
                $full_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            }
            $address = $order->get_shipping_address_1();
            if ( !empty( $order->get_shipping_address_2() ) ) {
                $address .= ' ' . $order->get_shipping_address_2();
            }
            foreach ( $order->get_items() as $key => $order_values ) {
                $product = $order_values->get_product();
                $product_order_data = $order_values->get_data();
                $items[] = array(
                    'quantity' => $product_order_data['quantity'],
                    'weight'   => $product->get_weight(),
                );
                if ( empty( $details ) ) {
                    $details = $product->get_title();
                } elseif ( $details !== $product->get_title() ) {
                    $details .= ', ' . $product->get_title();
                }
            }
            $shipping_weight = 0;
            foreach ( $items as $id => $data ) {
                $weight = $data['weight'];
                if ( !is_numeric( $weight ) ) {
                    /**
                     * 1 kg default weight
                     */
                    $weight = 1000;
                }
                $product_weight = $data['quantity'] * $weight;
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
             * If $order->get_shipping_postcode() is empty, then use get_billing_postcode()
             */
            $post_code = $order->get_shipping_postcode();
            if ( empty( $post_code ) ) {
                $post_code = $order->get_billing_postcode();
            }
            $dest_phone_number = $order->get_shipping_phone();
            $dest_phone_number = str_replace( ' ', '', $dest_phone_number );
            $dest_phone_number = str_replace( '-', '', $dest_phone_number );
            if ( empty( $dest_phone_number ) ) {
                $dest_phone_number = $order->get_billing_phone();
                $dest_phone_number = str_replace( ' ', '', $dest_phone_number );
                $dest_phone_number = str_replace( '-', '', $dest_phone_number );
            }
            if ( strpos( $dest_phone_number, '/' ) !== false ) {
                $dest_phone_number = explode( '/', $dest_phone_number );
                $dest_phone_number = $dest_phone_number[0];
            }
            return $order->add_order_note( __( 'Correos de Costa Rica Answer: ', 'mojito-shipping' ) . $this->ccr_ws_client->ccr_register_sending(
                $order_id,
                $order->get_meta( 'mojito_shipping_ccr_guide_number', true ),
                $details,
                $order->get_shipping_total(),
                $full_name,
                $address,
                $dest_phone_number,
                $post_code,
                $shipping_weight
            ) );
        }
    }

    /**
     * Add info to order data.
     *
     * @param Order $order Order object.
     * @return void
     */
    public function ccr_order_items_table_success_meta( $order ) {
        if ( !$order->has_shipping_method( 'mojito_shipping_ccr' ) ) {
            return;
        }
        $order_id = $order->get_id();
        $guide_number = $order->get_meta( 'mojito_shipping_ccr_guide_number', true );
        $ccr_msj_email_label = get_option( 'mojito-shipping-ccr-mail-orders-name' );
        $ccr_msj_email_content = get_option( 'mojito-shipping-ccr-mail-orders-message' );
        if ( !empty( $guide_number ) ) {
            $ccr_msj_email_label = ( !empty( $ccr_msj_email_label ) ? $ccr_msj_email_label : __( 'Correos de Costa Rica Tracking code', 'mojito-shipping' ) );
            $ccr_msj_email_content = ( !empty( $ccr_msj_email_content ) ? $ccr_msj_email_content : __( 'Guide number for packages tracking', 'mojito-shipping' ) );
            $html = '';
            $html .= '<tr class="mojito-ccr-ws order_item">';
            $html .= '<td scope="row">';
            $html .= '<strong class="guide-number-title">' . $ccr_msj_email_label . ':</strong>';
            $html .= '</td>';
            $html .= '<td>';
            $html .= '<strong><span class="guide-number">' . $guide_number . '</span></strong>';
            $html .= '<small>  ' . $ccr_msj_email_content . '</small>';
            $html .= '</td>';
            $html .= '</tr>';
            echo $html;
        }
    }

    /**
     * Data after billing address.
     *
     * @param object $order Order id.
     * @return void
     */
    public function ccr_admin_order_data_after_billing_address( $order ) {
        if ( !$order->has_shipping_method( 'mojito_shipping_ccr' ) ) {
            return;
        }
        $order_id = $order->get_id();
        $guide_number = $order->get_meta( 'mojito_shipping_ccr_guide_number', true );
        $reponse_code = $order->get_meta( 'mojito_shipping_ccr_ccrRegistroEnvio_response_code', true );
        $html = '';
        $html .= '<tr class="mojito-ccr-ws">';
        $html .= '<td colspan="2">';
        $html .= '<div class="mojito-ccr-ws">';
        $html .= '<span class="title" style="">' . __( 'Guide number from Correos de Costa Rica: ', 'mojito-shipping' ) . '</span>';
        if ( empty( $guide_number ) ) {
            $html .= '<a id="' . $order_id . '" class="mojito-shipping-ccr-manual-request"> ' . __( 'Click to request. ', 'mojito-shipping' ) . '</a>';
        } elseif ( '00' !== $reponse_code && '36' !== $reponse_code ) {
            $html .= '<a id="' . $order_id . '" class="mojito-shipping-ccr-manual-register"> ' . __( 'Click to request. ', 'mojito-shipping' ) . '</a>';
        } else {
            $html .= '<p> ' . $guide_number . ' </p>';
            $html .= '<a id="' . $order_id . '" class="download mojito-shipping-ccr-download-pdf">';
            $html .= '<img src="' . plugin_dir_url( __DIR__ ) . 'admin/img/download.svg">';
            $html .= '</a>';
        }
        $html .= '</div>';
        $html .= '</td>';
        $html .= '</tr>';
        echo $html;
    }

    /**
     * Add tracking to admin
     *
     * @return void
     */
    public function ccr_admin_order_tracking_data() {
        if ( !isset( $_GET['post'] ) ) {
            return;
        }
        $post_id = sanitize_text_field( $_GET['post'] );
        if ( !is_numeric( $post_id ) ) {
            return;
        }
        $order = wc_get_order( $post_id );
        if ( is_bool( $order ) ) {
            return;
        }
        if ( !$order->has_shipping_method( 'mojito_shipping_ccr' ) ) {
            return;
        }
    }

    /**
     * Add data to tabmel in email-
     *
     * @param Object $order Order.
     * @param bool   $sent_to_admin Send to admin.
     * @return void
     */
    public function ccr_email_after_order_table_add_guide_number( $order, $sent_to_admin ) {
        if ( !$order->has_shipping_method( 'mojito_shipping_ccr' ) ) {
            return;
        }
        /**
         * If order is Failed or Canceled, then return.
         */
        if ( in_array( $order->get_status(), array('failed', 'canceled'), true ) ) {
            return;
        }
        $order_id = $order->get_id();
        $guide_number = $order->get_meta( 'mojito_shipping_ccr_guide_number', true );
        $ccr_msj_email_label = get_option( 'mojito-shipping-ccr-mail-orders-name' );
        $ccr_msj_email_content = get_option( 'mojito-shipping-ccr-mail-orders-message' );
        if ( !empty( $guide_number ) ) {
            $ccr_msj_email_label = ( !empty( $ccr_msj_email_label ) ? $ccr_msj_email_label : '' );
            $ccr_msj_email_content = ( !empty( $ccr_msj_email_content ) ? $ccr_msj_email_content : '' );
            $ccr_tracking_url = apply_filters( 'mojito_shipping_ccr_tracking_url', 'https://correos.go.cr/rastreo/' );
            $html = '';
            $html .= '<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;" border="1">';
            $html .= '<tr class="mojito-shipping-ccr-payment-gateway">';
            $html .= '<td class="td" scope="row" colspan="2">';
            $html .= '<strong class="title" style="display: block;">' . $ccr_msj_email_label . '</strong>';
            $html .= '<span style="font-size: 12px;">' . $guide_number . '</span>';
            $html .= '<span style="font-size: 12px; display: block;"><a href="' . $ccr_tracking_url . '">' . $ccr_msj_email_content . '</a></span>';
            $html .= '</td>';
            $html .= '</tr>';
            $html .= '</table>';
            echo $html;
        }
    }

    /**
     * Manual request
     * Useful when guide number request fail during the shopping
     */
    public function ccr_manual_request() {
        if ( isset( $_POST['order_id'] ) ) {
            $order_id = sanitize_text_field( $_POST['order_id'] );
            if ( !is_numeric( $order_id ) ) {
                echo wp_json_encode( false );
                die;
            }
            $guide_number = $this->ccr_ws_client->ccr_get_guide_number();
            $order = wc_get_order( $order_id );
            $order->update_meta_data( 'mojito_shipping_ccr_guide_number', sanitize_text_field( $guide_number ) );
            $order->save();
            $comment_id = $this->ccr_thankyou_log_send( $order_id, true );
            if ( is_numeric( $comment_id ) ) {
                echo wp_json_encode( true );
                die;
            } else {
                echo wp_json_encode( false );
                die;
            }
        }
        die;
    }

    /**
     * Manual register
     * Useful when register process fail during the shopping
     */
    public function ccr_manual_register() {
        if ( isset( $_POST['order_id'] ) ) {
            $order_id = sanitize_text_field( $_POST['order_id'] );
            if ( !is_numeric( $order_id ) ) {
                echo wp_json_encode( false );
                die;
            }
            $comment_id = $this->ccr_thankyou_log_send( $order_id, true );
            if ( is_numeric( $comment_id ) ) {
                echo wp_json_encode( true );
                die;
            } else {
                echo wp_json_encode( false );
                die;
            }
        }
        die;
    }

    /**
     * Download PDF
     * Download generated pdf with the guide number
     */
    public function ccr_pdf_download() {
        if ( empty( $_POST['order_id'] ) ) {
            die;
        }
        $order_id = sanitize_text_field( $_POST['order_id'] );
        if ( !is_numeric( $order_id ) ) {
            echo wp_json_encode( false );
            die;
        }
        /**
         * Validate order
         */
        $order = \wc_get_order( $order_id );
        /**
         * Is a valid order?
         */
        if ( is_bool( $order ) ) {
            return;
        }
        /**
         * TMP folder
         */
        $tmp_dir = '';
        $upload_dir = wp_upload_dir();
        if ( !empty( $upload_dir['error'] ) ) {
            $tmp_dir = get_temp_dir();
        } else {
            $tmp_dir = $upload_dir['basedir'];
        }
        $tmp_dir .= '/mojito-shipping-tmp/';
        /**
         * Check if folder exists
         */
        if ( !is_dir( $tmp_dir ) ) {
            // dir doesn't exist, make it.
            mkdir( $tmp_dir );
        }
        /**
         * Guide number
         */
        $guide_number = $order->get_meta( 'mojito_shipping_ccr_guide_number', true );
        /**
         * Filename
         */
        $file = $tmp_dir . $guide_number . ' .pdf';
        /**
         * Create PDF
         */
        $pdf = new \TCPDF(
            PDF_PAGE_ORIENTATION,
            PDF_UNIT,
            PDF_PAGE_FORMAT,
            true,
            'UTF-8',
            false
        );
        // remove default header/footer.
        $pdf->setPrintHeader( false );
        $pdf->setPrintFooter( false );
        // add a page.
        $pdf->AddPage();
        /**
         * Filter: Custom header.
         */
        $custom_content = apply_filters( 'mojito_shipping_ccr_pdf_custom_header', array(
            'content'  => '',
            'position' => 'L',
        ) );
        if ( 'L' !== $custom_content['position'] && 'C' !== $custom_content['position'] && 'R' !== $custom_content['position'] ) {
            $custom_content['position'] = 'L';
        }
        if ( !empty( $custom_content['content'] ) ) {
            $pdf->writeHTML(
                $custom_content['content'],
                true,
                false,
                false,
                false,
                $custom_content['position']
            );
        }
        /**
         * Barcode
         */
        $style = array();
        $pdf->write1DBarcode(
            $guide_number,
            'C39',
            '',
            '',
            '',
            16,
            0.6,
            $style,
            'N'
        );
        $pdf->writeHTML(
            $guide_number,
            true,
            false,
            true,
            false,
            'C'
        );
        $pdf->writeHTML(
            '<br>',
            true,
            false,
            false,
            false,
            'C'
        );
        if ( 'full' === get_option( 'mojito-shipping-ccr-pdf-export-content' ) ) {
            // translators: date.
            $pdf->writeHTML(
                sprintf( __( 'Created: %s', 'mojito-shipping' ), gmdate( 'd-m-Y' ) ),
                true,
                false,
                true,
                false,
                'R'
            );
        }
        /**
         * Filter: After barcode
         */
        $custom_content = apply_filters( 'mojito_shipping_ccr_pdf_after_barcode', array(
            'content'  => '',
            'position' => 'L',
        ) );
        if ( 'L' !== $custom_content['position'] && 'C' !== $custom_content['position'] && 'R' !== $custom_content['position'] ) {
            $custom_content['position'] = 'L';
        }
        if ( !empty( $custom_content['content'] ) ) {
            $pdf->writeHTML(
                $custom_content['content'],
                true,
                false,
                false,
                false,
                $custom_content['position']
            );
        }
        if ( 'full' === get_option( 'mojito-shipping-ccr-pdf-export-content' ) ) {
            /**
             * Sender data
             */
            $pdf->writeHTML(
                '<br>',
                true,
                false,
                false,
                false,
                'C'
            );
            $pdf->writeHTML(
                '<h2>' . __( 'Sender details', 'mojito-shipping' ) . '</h2>',
                true,
                false,
                false,
                false,
                'C'
            );
            $pdf->writeHTML(
                '<hr>',
                true,
                false,
                false,
                false,
                'C'
            );
            $pdf->writeHTML( '<b>' . __( 'Sender name:', 'mojito-shipping' ) . '</b> ' . get_option( 'mojito-shipping-ccr-sender-name' ) );
            $pdf->writeHTML( '<b>' . __( 'Address:', 'mojito-shipping' ) . '</b> ' . get_option( 'mojito-shipping-ccr-sender-address' ) );
            $pdf->writeHTML( '<b>' . __( 'Zip-code:', 'mojito-shipping' ) . '</b> ' . get_option( 'mojito-shipping-ccr-sender-zip-code' ) );
            $pdf->writeHTML( '<b>' . __( 'Phone:', 'mojito-shipping' ) . '</b> ' . get_option( 'mojito-shipping-ccr-sender-phone' ) );
            $pdf->writeHTML( '<b>' . __( 'Email:', 'mojito-shipping' ) . '</b> ' . get_option( 'mojito-shipping-ccr-sender-email' ) );
            /**
             * Filter: After Sender data
             */
            $custom_content = apply_filters( 'mojito_shipping_ccr_pdf_after_sender_data', array(
                'content'  => '',
                'position' => 'L',
            ) );
            if ( 'L' !== $custom_content['position'] && 'C' !== $custom_content['position'] && 'R' !== $custom_content['position'] ) {
                $custom_content['position'] = 'L';
            }
            if ( !empty( $custom_content['content'] ) ) {
                $pdf->writeHTML(
                    $custom_content['content'],
                    true,
                    false,
                    false,
                    false,
                    $custom_content['position']
                );
            }
            /**
             * Recipient details
             */
            // Full Name.
            $full_name = '';
            if ( !empty( $order->get_shipping_first_name() ) && !empty( $order->get_shipping_last_name() ) ) {
                $full_name = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
            } else {
                $full_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            }
            // Company.
            $company = '';
            if ( !empty( $order->get_shipping_company() ) ) {
                $company = $order->get_shipping_company();
            } elseif ( !empty( $order->get_billing_company() ) ) {
                $company = $order->get_billing_company();
            } else {
                $company = false;
            }
            // Address.
            $address = $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2();
            if ( empty( $address ) ) {
                $address = $order->get_billing_address_1() . ' ' . $order->get_billing_address_2();
            }
            // City, State.
            $city = '';
            $state = '';
            $canton = '';
            $distric = '';
            $states = array(
                'SJ' => 'San José',
                'AL' => 'Alajuela',
                'CG' => 'Cartago',
                'HD' => 'Heredia',
                'GT' => 'Guanacaste',
                'PT' => 'Puntarenas',
                'LM' => 'Limón',
            );
            if ( !empty( $order->get_shipping_city() ) ) {
                $city = $order->get_shipping_city();
                $canton = trim( explode( ',', $city )[0] );
                $distric = trim( explode( ',', $city )[1] );
            } elseif ( !empty( $order->get_billing_city() ) ) {
                $city = $order->get_billing_city();
                $canton = trim( explode( ',', $city )[0] );
                $distric = trim( explode( ',', $city )[1] );
            } else {
                $city = false;
            }
            if ( !empty( $order->get_shipping_state() ) ) {
                $state = $order->get_shipping_state();
            } elseif ( !empty( $order->get_billing_state() ) ) {
                $state = $order->get_billing_state();
            } else {
                $state = false;
            }
            if ( array_key_exists( $state, $states ) ) {
                $state = $states[$state];
            }
            // Post code.
            $post_code = $order->get_shipping_postcode();
            if ( empty( $post_code ) ) {
                $post_code = $order->get_billing_postcode();
            }
            // HTML.
            $pdf->writeHTML(
                '<br>',
                true,
                false,
                false,
                false,
                'C'
            );
            $pdf->writeHTML(
                '<h2>' . __( 'Recipient details', 'mojito-shipping' ) . '</h2>',
                true,
                false,
                false,
                false,
                'C'
            );
            $pdf->writeHTML(
                '<hr>',
                true,
                false,
                false,
                false,
                'C'
            );
            $pdf->writeHTML( '<b>' . __( 'Recipient name:', 'mojito-shipping' ) . '</b> ' . $full_name );
            if ( false !== $company && !empty( $company ) ) {
                $pdf->writeHTML( '<b>' . __( 'Company:', 'mojito-shipping' ) . '</b> ' . $company );
            }
            if ( false !== $state && !empty( $state ) ) {
                $pdf->writeHTML( '<b>' . __( 'State:', 'mojito-shipping' ) . '</b> ' . $state );
            }
            if ( false !== $city && !empty( $city ) ) {
                $pdf->writeHTML( '<b>' . __( 'Canton:', 'mojito-shipping' ) . '</b> ' . $canton );
                $pdf->writeHTML( '<b>' . __( 'Distric:', 'mojito-shipping' ) . '</b> ' . $distric );
            }
            $pdf->writeHTML( '<b>' . __( 'Address:', 'mojito-shipping' ) . '</b> ' . $address );
            $pdf->writeHTML( '<b>' . __( 'Zip-code:', 'mojito-shipping' ) . '</b> ' . $post_code );
            $dest_phone_number = $order->get_shipping_phone();
            $dest_phone_number = str_replace( ' ', '', $dest_phone_number );
            $dest_phone_number = str_replace( '-', '', $dest_phone_number );
            if ( empty( $dest_phone_number ) ) {
                $dest_phone_number = $order->get_billing_phone();
                $dest_phone_number = str_replace( ' ', '', $dest_phone_number );
                $dest_phone_number = str_replace( '-', '', $dest_phone_number );
            }
            $pdf->writeHTML( '<b>' . __( 'Phone:', 'mojito-shipping' ) . '</b> ' . $dest_phone_number );
            /**
             * Filter: After Recipient data
             */
            $custom_content = apply_filters( 'mojito_shipping_ccr_pdf_after_recipient_data', array(
                'content'  => '',
                'position' => 'L',
            ) );
            if ( 'L' !== $custom_content['position'] && 'C' !== $custom_content['position'] && 'R' !== $custom_content['position'] ) {
                $custom_content['position'] = 'L';
            }
            if ( !empty( $custom_content['content'] ) ) {
                $pdf->writeHTML(
                    $custom_content['content'],
                    true,
                    false,
                    false,
                    false,
                    $custom_content['position']
                );
            }
            /**
             * Package content details
             */
            if ( 'yes' === get_option( 'mojito-shipping-ccr-pdf-export-order-content', 'no' ) ) {
                $package_items = '';
                foreach ( $order->get_items() as $item_id => $item ) {
                    $line = $item->get_name();
                    $line .= ' ( ';
                    $line .= $item->get_quantity();
                    $line .= ' ), ';
                    $package_items .= $line;
                }
                $package_items = rtrim( $package_items, ', ' );
                $pdf->writeHTML(
                    '<br>',
                    true,
                    false,
                    false,
                    false,
                    'C'
                );
                $pdf->writeHTML(
                    '<h2>' . __( 'Package details', 'mojito-shipping' ) . '</h2>',
                    true,
                    false,
                    false,
                    false,
                    'C'
                );
                $pdf->writeHTML(
                    '<hr>',
                    true,
                    false,
                    false,
                    false,
                    'C'
                );
                $pdf->writeHTML( '<b>' . __( 'Details:', 'mojito-shipping' ) . '</b> ' . $package_items );
                /**
                 * Filter: After package content details
                 */
                $custom_content = apply_filters( 'mojito_shipping_ccr_pdf_after_package_content', array(
                    'content'  => '',
                    'position' => 'L',
                ) );
                if ( 'L' !== $custom_content['position'] && 'C' !== $custom_content['position'] && 'R' !== $custom_content['position'] ) {
                    $custom_content['position'] = 'L';
                }
                if ( !empty( $custom_content['content'] ) ) {
                    $pdf->writeHTML(
                        $custom_content['content'],
                        true,
                        false,
                        false,
                        false,
                        $custom_content['position']
                    );
                }
            }
            /**
             * Client notes
             */
            $pdf->writeHTML(
                '<br>',
                true,
                false,
                false,
                false,
                'C'
            );
            $pdf->writeHTML(
                '<h2>' . __( 'Client notes', 'mojito-shipping' ) . '</h2>',
                true,
                false,
                false,
                false,
                'C'
            );
            $pdf->writeHTML(
                '<hr>',
                true,
                false,
                false,
                false,
                'C'
            );
            $pdf->writeHTML( '<b>' . __( 'Client note:', 'mojito-shipping' ) . '</b> ' . $order->get_customer_note() );
            /**
             * Filter: After Client notes
             */
            $custom_content = apply_filters( 'mojito_shipping_ccr_pdf_after_client_notes', array(
                'content'  => '',
                'position' => 'L',
            ) );
            // Backguards compatibility.
            $custom_content = apply_filters( 'mojito_shipping_ccr_pdf_after_package_data', $custom_content );
            if ( 'L' !== $custom_content['position'] && 'C' !== $custom_content['position'] && 'R' !== $custom_content['position'] ) {
                $custom_content['position'] = 'L';
            }
            if ( !empty( $custom_content['content'] ) ) {
                $pdf->writeHTML(
                    $custom_content['content'],
                    true,
                    false,
                    false,
                    false,
                    $custom_content['position']
                );
            }
            /**
             * Logos
             */
            $logos_html = '<table><tr>';
            if ( 'yes' === get_option( 'mojito-shipping-ccr-pdf-export-ccr-logo' ) ) {
                $logo_file = 'logo-correos-de-costa-rica.jpg';
                $logo_url = apply_filters( 'mojito_shipping_ccr_pdf_ccr_logo_src', plugin_dir_url( __DIR__ ) . 'public/img/' . $logo_file );
                $img = '<img style="width:200px;display:inline-block;" src="' . $logo_url . '">';
                $logos_html .= '<td>' . $img . '</td>';
            }
            if ( 'yes' === get_option( 'mojito-shipping-ccr-pdf-export-site-logo' ) ) {
                $blog_id = ( is_multisite() ? get_current_blog_id() : 0 );
                if ( has_custom_logo( $blog_id ) ) {
                    $custom_logo_id = get_theme_mod( 'custom_logo' );
                    $image = wp_get_attachment_image_src( $custom_logo_id, 'full' );
                    add_filter(
                        'get_custom_logo',
                        function ( $html, $blog_id ) {
                            return str_replace( 'itemprop="logo"', '', $html );
                        },
                        10,
                        2
                    );
                    $logo_url = apply_filters( 'mojito_shipping_ccr_pdf_site_logo_src', $image[0] );
                    $img = '<img style="width:200px;display:inline-block;" src="' . $logo_url . '">';
                    $logos_html .= '<td>' . $img . '</td>';
                } else {
                    $logo_url = apply_filters( 'mojito_shipping_ccr_pdf_site_logo_src', '' );
                    $img = '<img style="width:200px;display:inline-block;" src="' . $logo_url . '">';
                    $logos_html .= '<td>' . $img . '</td>';
                }
            }
            $logos_html .= '</tr></table>';
            $pdf->writeHTML(
                '<div>' . $logos_html . '</div>',
                true,
                false,
                true,
                false,
                'C'
            );
        }
        /**
         * Save file
         */
        $output = $pdf->Output( $file, 'S' );
        file_put_contents( $file, $output );
        $content = base64_encode( file_get_contents( $file ) );
        $url = $upload_dir['baseurl'] . '/mojito-shipping-tmp/' . $guide_number . ' .pdf';
        echo wp_json_encode( array(
            'url'          => $url,
            'content'      => $content,
            'guide_number' => $guide_number,
        ) );
        wp_delete_file( $file );
        die;
    }

    /**
     * Add CCR Pymexpress guide number after customer details
     *
     * @return void
     */
    public function pymexpress_checkout_after_customer_details() {
        woocommerce_form_field( 'mojito_shipping_pymexpress_guide_number', array(
            'type'              => 'text',
            'class'             => array('hidden'),
            'default'           => $this->pymexpress_ws_client->generar_guia(),
            'custom_attributes' => array(
                'readonly' => 'readonly',
            ),
        ) );
    }

    /**
     * Update meta, save guide number.
     *
     * @param Int $order_id Order ID.
     * @return void
     */
    public function pymexpress_checkout_update_order_meta_save_guide_number( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( !$order->has_shipping_method( 'mojito_shipping_pymexpress' ) ) {
            return;
        }
        if ( !empty( $_POST['mojito_shipping_pymexpress_guide_number'] ) && $order->has_shipping_method( 'mojito_shipping_pymexpress' ) ) {
            $order->update_meta_data( 'mojito_shipping_pymexpress_guide_number', sanitize_text_field( $_POST['mojito_shipping_pymexpress_guide_number'] ) );
            $order->save();
        }
    }

    /**
     * Thank you page
     *
     * @param Int     $order_id Order id.
     * @param boolean $manual_request is a manual request?.
     * @return void
     */
    public function pymexpress_thankyou_log_send( $order_id, $manual_request = false ) {
        $order = wc_get_order( $order_id );
        $log_send = $order->get_meta( 'mojito_pymexpress_shipping_log', true );
        $details = '';
        /**
         * Is a valid order?
         */
        if ( is_bool( $order ) ) {
            return;
        }
        $items = array();
        $weight_unit = get_option( 'woocommerce_weight_unit' );
        if ( ($manual_request || empty( $log_send )) && $order->has_shipping_method( 'mojito_shipping_pymexpress' ) ) {
            $full_name = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
            if ( 0 === strlen( trim( $full_name ) ) ) {
                $full_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            }
            $address = $order->get_shipping_address_1();
            if ( !empty( $order->get_shipping_address_2() ) ) {
                $address .= ' ' . $order->get_shipping_address_2();
            }
            foreach ( $order->get_items() as $key => $order_values ) {
                $product = $order_values->get_product();
                $product_order_data = $order_values->get_data();
                $items[] = array(
                    'quantity' => $product_order_data['quantity'],
                    'weight'   => $product->get_weight(),
                );
                if ( empty( $details ) ) {
                    $details = $product->get_title();
                } elseif ( $details !== $product->get_title() ) {
                    $details .= ', ' . $product->get_title();
                }
            }
            $shipping_weight = 0;
            foreach ( $items as $id => $data ) {
                $weight = $data['weight'];
                if ( !is_numeric( $weight ) ) {
                    /**
                     * 1 kg default weight
                     */
                    $weight = 1000;
                }
                $product_weight = $data['quantity'] * $weight;
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
             * If $order->get_shipping_postcode() is empty, then use get_billing_postcode()
             */
            $post_code = $order->get_shipping_postcode();
            if ( empty( $post_code ) ) {
                $post_code = $order->get_billing_postcode();
            }
            if ( empty( $post_code ) ) {
                $find_address = new Mojito_Shipping_Address();
                $post_code = $find_address->find_postcode_legacy( $order->get_shipping_state(), $order->get_shipping_city() );
                if ( empty( $post_code ) ) {
                    $post_code = $find_address->find_postcode_legacy( $order->get_billing_state(), $order->get_billing_city() );
                }
            }
            /**	
             * Remove letters from postcode
             */
            $post_code = preg_replace( '/[^0-9]/', '', $post_code );
            $shipping_total = $order->get_shipping_total();
            /**
             * Exchange rates
             */
            if ( 'enable' === get_option( 'mojito-shipping-pymexpress-exchange-rate-enable', 'disabled' ) ) {
                $origin = get_option( 'mojito-shipping-pymexpress-exchange-rate-origin', 'manual' );
                $exchange_rate = get_option( 'mojito-shipping-pymexpress-exchange-rate-rate', 620 );
                if ( $exchange_rate <= 0 ) {
                    $exchange_rate = 1;
                }
                $shipping_total = $shipping_total * $exchange_rate;
            }
            if ( empty( $shipping_total ) || 0 === $shipping_total ) {
                $shipping_total = 2000;
            }
            /**
             * Check destination phone number
             */
            $dest_phone_number = $order->get_shipping_phone();
            $dest_phone_number = str_replace( ' ', '', $dest_phone_number );
            $dest_phone_number = str_replace( '-', '', $dest_phone_number );
            if ( empty( $dest_phone_number ) ) {
                $dest_phone_number = $order->get_billing_phone();
                $dest_phone_number = str_replace( ' ', '', $dest_phone_number );
                $dest_phone_number = str_replace( '-', '', $dest_phone_number );
            }
            if ( strpos( $dest_phone_number, '/' ) !== false ) {
                $dest_phone_number = explode( '/', $dest_phone_number );
                $dest_phone_number = $dest_phone_number[0];
            }
            if ( empty( $post_code ) ) {
                $order->add_order_note( __( 'DEST_APARTADO is empty', 'mojito-shipping' ) );
            }
            if ( empty( $address ) ) {
                $order->add_order_note( __( 'DEST_DIRECCION is empty', 'mojito-shipping' ) );
            }
            if ( empty( $full_name ) ) {
                $order->add_order_note( __( 'DEST_NOMBRE is empty', 'mojito-shipping' ) );
            }
            if ( empty( $dest_phone_number ) ) {
                $order->add_order_note( __( 'DEST_TELEFONO is empty', 'mojito-shipping' ) );
            }
            if ( empty( $post_code ) ) {
                $order->add_order_note( __( 'DEST_ZIP is empty', 'mojito-shipping' ) );
            }
            if ( empty( $shipping_total ) ) {
                $order->add_order_note( __( 'MONTO_FLETE is empty', 'mojito-shipping' ) );
            }
            if ( empty( $details ) ) {
                $order->add_order_note( __( 'OBSERVACIONES is empty', 'mojito-shipping' ) );
            }
            if ( empty( $shipping_weight ) ) {
                $order->add_order_note( __( 'PESO is empty', 'mojito-shipping' ) );
            }
            /**
             * Filter to change address based on postcode
             */
            $address = apply_filters( 'mojito_shipping_pymexpress_address', $address, $post_code );
            $comment_id = $order->add_order_note( __( 'Correos de Costa Rica Answer: ', 'mojito-shipping' ) . $this->pymexpress_ws_client->registro_envio( $order_id, array(
                'DEST_APARTADO'  => $post_code,
                'DEST_DIRECCION' => $address,
                'DEST_NOMBRE'    => $full_name,
                'DEST_TELEFONO'  => $dest_phone_number,
                'DEST_ZIP'       => $post_code,
                'ENVIO_ID'       => $order->get_meta( 'mojito_shipping_pymexpress_guide_number', true ),
                'MONTO_FLETE'    => $shipping_total,
                'OBSERVACIONES'  => $details,
                'PESO'           => $shipping_weight,
            ) ) );
            /**
             * Try to send emails
             */
            if ( !empty( get_option( 'mojito-shipping-pymexpress-pdf-export-send-to-emails' ) ) ) {
                $status = $order->get_meta( 'mojito_pymexpress_shipping_order_notificated', 0 );
                if ( $status == 1 ) {
                    return $comment_id;
                }
                $emails = str_replace( ' ', '', get_option( 'mojito-shipping-pymexpress-pdf-export-send-to-emails' ) );
                $subject = sprintf( __( 'New Pymexpress guide for order: %s', 'mojito-shipping' ), $order_id );
                $body = sprintf( __( 'Please find attached the PDF Guide for the order %s', 'mojito-shipping' ), $order_id );
                $headers = array('Content-Type: text/html; charset=UTF-8');
                $file = $this->pymexpress_pdf_download( $order_id )['path'];
                wp_mail(
                    $emails,
                    $subject,
                    $body,
                    $headers,
                    $file
                );
                wp_delete_file( $file );
                $order->update_meta_data( 'mojito_pymexpress_shipping_order_notificated', 1 );
            }
            return $comment_id;
        }
    }

    public function pymexpress_order_is_completed(
        $order_id,
        $old_status = '',
        $new_status = '',
        $order = ''
    ) {
        $order = wc_get_order( $order_id );
        if ( !$order->has_shipping_method( 'mojito_shipping_pymexpress' ) ) {
            return;
        }
        $reponse_code = $order->get_meta( 'mojito_shipping_pymexpress_ccrRegistroEnvio_response_code', true );
        if ( $reponse_code == '00' ) {
            return;
        }
        if ( in_array( $order->get_status(), array('completed', 'on-hold') ) ) {
            $this->pymexpress_thankyou_log_send( $order_id );
        }
    }

    /**
     * Add info to order data.
     *
     * @param Order $order Order object.
     * @return void
     */
    public function pymexpress_order_items_table_success_meta( $order ) {
        if ( !$order->has_shipping_method( 'mojito_shipping_pymexpress' ) ) {
            return;
        }
        $order_id = $order->get_id();
        $guide_number = $order->get_meta( 'mojito_shipping_pymexpress_guide_number', true );
        $ccr_msj_email_label = get_option( 'mojito-shipping-pymexpress-mail-orders-name' );
        $ccr_msj_email_content = get_option( 'mojito-shipping-pymexpress-mail-orders-message' );
        if ( !empty( $guide_number ) ) {
            $ccr_msj_email_label = ( !empty( $ccr_msj_email_label ) ? $ccr_msj_email_label : __( 'Correos de Costa Rica Tracking code', 'mojito-shipping' ) );
            $ccr_msj_email_content = ( !empty( $ccr_msj_email_content ) ? $ccr_msj_email_content : __( 'Guide number for packages tracking', 'mojito-shipping' ) );
            $html = '';
            $html .= '<tr class="mojito-pymexpress-ws order_item">';
            $html .= '<td scope="row">';
            $html .= '<strong class="guide-number-title">' . $ccr_msj_email_label . ':</strong>';
            $html .= '</td>';
            $html .= '<td>';
            $html .= '<strong><span class="guide-number">' . $guide_number . '</span></strong>';
            $html .= '<small>  ' . $ccr_msj_email_content . '</small>';
            $html .= '</td>';
            $html .= '</tr>';
            echo $html;
        }
    }

    /**
     * Data after billing address.
     *
     * @param object $order Order id.
     * @return void
     */
    public function pymexpress_admin_order_data_after_billing_address( $order ) {
        if ( !$order->has_shipping_method( 'mojito_shipping_pymexpress' ) ) {
            return;
        }
        $order_id = $order->get_id();
        $guide_number = $order->get_meta( 'mojito_shipping_pymexpress_guide_number', true );
        $reponse_code = $order->get_meta( 'mojito_shipping_pymexpress_ccrRegistroEnvio_response_code', true );
        $html = '';
        $html .= '<tr class="mojito-pymexpress-ws">';
        $html .= '<td colspan="2">';
        $html .= '<div class="mojito-pymexpress-ws">';
        $html .= '<span class="title" style="">' . __( 'Guide number from Correos de Costa Rica: ', 'mojito-shipping' ) . '</span>';
        if ( empty( $guide_number ) ) {
            $html .= '<a id="' . $order_id . '" class="mojito-shipping-pymexpress-manual-request"> ' . __( 'Click to request. ', 'mojito-shipping' ) . '</a>';
        } elseif ( !empty( $reponse_code ) && ('00' !== $reponse_code && '36' !== $reponse_code) ) {
            $html .= '<a id="' . $order_id . '" class="mojito-shipping-pymexpress-manual-register"> ' . __( 'Click to request. ', 'mojito-shipping' ) . '</a>';
        } else {
            $html .= '<p> ' . $guide_number . ' </p>';
            $html .= '<a id="' . $order_id . '" class="download mojito-shipping-pymexpress-download-pdf">';
            $html .= '<img src="' . plugin_dir_url( __DIR__ ) . 'admin/img/download.svg">';
            $html .= '</a>';
        }
        $html .= '</div>';
        $html .= '</td>';
        $html .= '</tr>';
        echo $html;
    }

    /**
     * Add tracking to admin
     *
     * @return void
     */
    public function pymexpress_admin_order_tracking_data() {
        if ( !isset( $_GET['post'] ) ) {
            return;
        }
        $post_id = sanitize_text_field( $_GET['post'] );
        if ( !is_numeric( $post_id ) ) {
            return;
        }
        $order = wc_get_order( $post_id );
        if ( is_bool( $order ) ) {
            return;
        }
        if ( !$order->has_shipping_method( 'mojito_shipping_pymexpress' ) ) {
            return;
        }
    }

    /**
     * Add data to tabmel in email-
     *
     * @param Object $order Order.
     * @param bool   $sent_to_admin Send to admin.
     * @return void
     */
    public function pymexpress_email_after_order_table_add_guide_number( $order, $sent_to_admin ) {
        if ( !$order->has_shipping_method( 'mojito_shipping_pymexpress' ) ) {
            return;
        }
        /**
         * If order is Failed or Canceled, then return.
         */
        if ( in_array( $order->get_status(), array('failed', 'canceled'), true ) ) {
            return;
        }
        $order_id = $order->get_id();
        $guide_number = $order->get_meta( 'mojito_shipping_pymexpress_guide_number', true );
        $ccr_msj_email_label = get_option( 'mojito-shipping-pymexpress-mail-orders-name' );
        $ccr_msj_email_content = get_option( 'mojito-shipping-pymexpress-mail-orders-message' );
        if ( !empty( $guide_number ) ) {
            $ccr_msj_email_label = ( !empty( $ccr_msj_email_label ) ? $ccr_msj_email_label : '' );
            $ccr_msj_email_content = ( !empty( $ccr_msj_email_content ) ? $ccr_msj_email_content : '' );
            $ccr_tracking_url = apply_filters( 'mojito_shipping_pymexpress_tracking_url', 'https://correos.go.cr/rastreo/' );
            $html = '';
            $html .= '<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;" border="1">';
            $html .= '<tr class="mojito-shipping-pymexpress-payment-gateway">';
            $html .= '<td class="td" scope="row" colspan="2">';
            $html .= '<strong class="title" style="display: block;">' . $ccr_msj_email_label . '</strong>';
            $html .= '<span style="font-size: 12px;">' . $guide_number . '</span>';
            $html .= '<span style="font-size: 12px; display: block;"><a href="' . $ccr_tracking_url . '">' . $ccr_msj_email_content . '</a></span>';
            $html .= '</td>';
            $html .= '</tr>';
            $html .= '</table>';
            echo $html;
        }
    }

    /**
     * Manual request
     * Useful when guide number request fail during the shopping
     */
    public function pymexpress_manual_request() {
        if ( isset( $_POST['order_id'] ) ) {
            $order_id = sanitize_text_field( $_POST['order_id'] );
            if ( !is_numeric( $order_id ) ) {
                echo wp_json_encode( false );
                die;
            }
            $guide_number = $this->pymexpress_ws_client->generar_guia();
            $order = wc_get_order( $order_id );
            $order->update_meta_data( 'mojito_shipping_pymexpress_guide_number', sanitize_text_field( $guide_number ) );
            $order->save();
            $comment_id = $this->pymexpress_thankyou_log_send( $order_id, true );
            if ( is_numeric( $comment_id ) ) {
                echo wp_json_encode( true );
                die;
            } else {
                echo wp_json_encode( false );
                die;
            }
        }
        die;
    }

    /**
     * Manual register
     * Useful when register process fail during the shopping
     */
    public function pymexpress_manual_register() {
        if ( isset( $_POST['order_id'] ) ) {
            $order_id = sanitize_text_field( $_POST['order_id'] );
            if ( !is_numeric( $order_id ) ) {
                echo wp_json_encode( false );
                die;
            }
            $comment_id = $this->pymexpress_thankyou_log_send( $order_id, true );
            if ( is_numeric( $comment_id ) ) {
                echo wp_json_encode( true );
                die;
            } else {
                echo wp_json_encode( false );
                die;
            }
        }
        die;
    }

    /**
     * Download PDF
     * Download generated pdf with the guide number
     */
    public function pymexpress_pdf_download( $order_id = '' ) {
        $ajax_call = false;
        if ( empty( $order_id ) ) {
            $ajax_call = true;
            if ( empty( $_POST['order_id'] ) ) {
                die;
            }
            $order_id = sanitize_text_field( $_POST['order_id'] );
        }
        if ( !is_numeric( $order_id ) ) {
            echo wp_json_encode( false );
            die;
        }
        /**
         * Validate order
         */
        $order = \wc_get_order( $order_id );
        /**
         * Is a valid order?
         */
        if ( is_bool( $order ) ) {
            return;
        }
        /**
         * TMP folder
         */
        $tmp_dir = '';
        $upload_dir = wp_upload_dir();
        if ( !empty( $upload_dir['error'] ) ) {
            $tmp_dir = get_temp_dir();
        } else {
            $tmp_dir = $upload_dir['basedir'];
        }
        $tmp_dir .= '/mojito-shipping-tmp/';
        /**
         * Guide number
         */
        $guide_number = $order->get_meta( 'mojito_shipping_pymexpress_guide_number', true );
        /**
         * Filename
         */
        $file = $tmp_dir . $guide_number . '.pdf';
        /**
         * PDF origin is pymexpress?
         */
        if ( 'mojito' === get_option( 'mojito-shipping-pymexpress-pdf-export-origin', 'mojito' ) ) {
            /**
             * Check if folder exists
             */
            if ( !is_dir( $tmp_dir ) ) {
                // dir doesn't exist, make it.
                mkdir( $tmp_dir );
            }
            /**
             * Check index for tmp folder
             */
            if ( !file_exists( $tmp_dir . 'index.php' ) ) {
                file_put_contents( $tmp_dir . 'index.php', "<?php \n" );
            }
            /**
             * Create PDF
             */
            $pdf = new \TCPDF(
                PDF_PAGE_ORIENTATION,
                PDF_UNIT,
                PDF_PAGE_FORMAT,
                true,
                'UTF-8',
                false
            );
            // remove default header/footer.
            $pdf->setPrintHeader( false );
            $pdf->setPrintFooter( false );
            // add a page.
            $pdf->AddPage();
            /**
             * Filter: Custom header.
             */
            $custom_content = apply_filters( 'mojito_shipping_pymexpress_pdf_custom_header', array(
                'content'  => '',
                'position' => 'L',
            ) );
            if ( 'L' !== $custom_content['position'] && 'C' !== $custom_content['position'] && 'R' !== $custom_content['position'] ) {
                $custom_content['position'] = 'L';
            }
            if ( !empty( $custom_content['content'] ) ) {
                $pdf->writeHTML(
                    $custom_content['content'],
                    true,
                    false,
                    false,
                    false,
                    $custom_content['position']
                );
            }
            /**
             * Barcode
             */
            $style = array();
            $pdf->write1DBarcode(
                $guide_number,
                'C39',
                '',
                '',
                '',
                16,
                0.6,
                $style,
                'N'
            );
            $pdf->writeHTML(
                $guide_number,
                true,
                false,
                true,
                false,
                'C'
            );
            $pdf->writeHTML(
                '<br>',
                true,
                false,
                false,
                false,
                'C'
            );
            if ( 'full' === get_option( 'mojito-shipping-pymexpress-pdf-export-content' ) ) {
                // translators: date.
                $pdf->writeHTML(
                    sprintf( __( 'Created: %s', 'mojito-shipping' ), gmdate( 'd-m-Y' ) ),
                    true,
                    false,
                    true,
                    false,
                    'R'
                );
            }
            /**
             * Filter: After barcode
             */
            $custom_content = apply_filters( 'mojito_shipping_pymexpress_pdf_after_barcode', array(
                'content'  => '',
                'position' => 'L',
            ) );
            if ( 'L' !== $custom_content['position'] && 'C' !== $custom_content['position'] && 'R' !== $custom_content['position'] ) {
                $custom_content['position'] = 'L';
            }
            if ( !empty( $custom_content['content'] ) ) {
                $pdf->writeHTML(
                    $custom_content['content'],
                    true,
                    false,
                    false,
                    false,
                    $custom_content['position']
                );
            }
            if ( 'full' === get_option( 'mojito-shipping-pymexpress-pdf-export-content' ) ) {
                /**
                 * Sender data
                 */
                $pdf->writeHTML(
                    '<br>',
                    true,
                    false,
                    false,
                    false,
                    'C'
                );
                $pdf->writeHTML(
                    '<h2>' . __( 'Sender details', 'mojito-shipping' ) . '</h2>',
                    true,
                    false,
                    false,
                    false,
                    'C'
                );
                $pdf->writeHTML(
                    '<hr>',
                    true,
                    false,
                    false,
                    false,
                    'C'
                );
                $pdf->writeHTML( '<b>' . __( 'Sender name:', 'mojito-shipping' ) . '</b> ' . get_option( 'mojito-shipping-pymexpress-sender-name' ) );
                $pdf->writeHTML( '<b>' . __( 'Address:', 'mojito-shipping' ) . '</b> ' . get_option( 'mojito-shipping-pymexpress-sender-address' ) );
                $pdf->writeHTML( '<b>' . __( 'Zip-code:', 'mojito-shipping' ) . '</b> ' . get_option( 'mojito-shipping-pymexpress-sender-zip-code' ) );
                $pdf->writeHTML( '<b>' . __( 'Phone:', 'mojito-shipping' ) . '</b> ' . get_option( 'mojito-shipping-pymexpress-sender-phone' ) );
                $pdf->writeHTML( '<b>' . __( 'Email:', 'mojito-shipping' ) . '</b> ' . get_option( 'mojito-shipping-pymexpress-sender-email' ) );
                /**
                 * Filter: After Sender data
                 */
                $custom_content = apply_filters( 'mojito_shipping_pymexpress_pdf_after_sender_data', array(
                    'content'  => '',
                    'position' => 'L',
                ) );
                if ( 'L' !== $custom_content['position'] && 'C' !== $custom_content['position'] && 'R' !== $custom_content['position'] ) {
                    $custom_content['position'] = 'L';
                }
                if ( !empty( $custom_content['content'] ) ) {
                    $pdf->writeHTML(
                        $custom_content['content'],
                        true,
                        false,
                        false,
                        false,
                        $custom_content['position']
                    );
                }
                /**
                 * Recipient details
                 */
                // Full Name.
                $full_name = '';
                if ( !empty( $order->get_shipping_first_name() ) && !empty( $order->get_shipping_last_name() ) ) {
                    $full_name = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
                } else {
                    $full_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                }
                // Company.
                $company = '';
                if ( !empty( $order->get_shipping_company() ) ) {
                    $company = $order->get_shipping_company();
                } elseif ( !empty( $order->get_billing_company() ) ) {
                    $company = $order->get_billing_company();
                } else {
                    $company = false;
                }
                // Address.
                $address = $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2();
                if ( empty( $address ) ) {
                    $address = $order->get_billing_address_1() . ' ' . $order->get_billing_address_2();
                }
                // City, State.
                $city = '';
                $state = '';
                $canton = '';
                $distric = '';
                $states = array(
                    'SJ' => 'San José',
                    'AL' => 'Alajuela',
                    'CG' => 'Cartago',
                    'HD' => 'Heredia',
                    'GT' => 'Guanacaste',
                    'PT' => 'Puntarenas',
                    'LM' => 'Limón',
                );
                if ( !empty( $order->get_shipping_city() ) ) {
                    $city = $order->get_shipping_city();
                    $canton = trim( explode( ',', $city )[0] );
                    $distric = trim( explode( ',', $city )[1] );
                } elseif ( !empty( $order->get_billing_city() ) ) {
                    $city = $order->get_billing_city();
                    $canton = trim( explode( ',', $city )[0] );
                    $distric = trim( explode( ',', $city )[1] );
                } else {
                    $city = false;
                }
                if ( !empty( $order->get_shipping_state() ) ) {
                    $state = $order->get_shipping_state();
                } elseif ( !empty( $order->get_billing_state() ) ) {
                    $state = $order->get_billing_state();
                } else {
                    $state = false;
                }
                if ( array_key_exists( $state, $states ) ) {
                    $state = $states[$state];
                }
                // Post code.
                $post_code = (int) $order->get_shipping_postcode();
                if ( empty( $post_code ) ) {
                    $post_code = (int) $order->get_billing_postcode();
                }
                // HTML.
                $pdf->writeHTML(
                    '<br>',
                    true,
                    false,
                    false,
                    false,
                    'C'
                );
                $pdf->writeHTML(
                    '<h2>' . __( 'Recipient details', 'mojito-shipping' ) . '</h2>',
                    true,
                    false,
                    false,
                    false,
                    'C'
                );
                $pdf->writeHTML(
                    '<hr>',
                    true,
                    false,
                    false,
                    false,
                    'C'
                );
                $pdf->writeHTML( '<b>' . __( 'Recipient name:', 'mojito-shipping' ) . '</b> ' . $full_name );
                if ( false !== $company && !empty( $company ) ) {
                    $pdf->writeHTML( '<b>' . __( 'Company:', 'mojito-shipping' ) . '</b> ' . $company );
                }
                if ( false !== $state && !empty( $state ) ) {
                    $pdf->writeHTML( '<b>' . __( 'State:', 'mojito-shipping' ) . '</b> ' . $state );
                }
                if ( false !== $city && !empty( $city ) ) {
                    $pdf->writeHTML( '<b>' . __( 'Canton:', 'mojito-shipping' ) . '</b> ' . $canton );
                    $pdf->writeHTML( '<b>' . __( 'Distric:', 'mojito-shipping' ) . '</b> ' . $distric );
                }
                $pdf->writeHTML( '<b>' . __( 'Address:', 'mojito-shipping' ) . '</b> ' . $address );
                $pdf->writeHTML( '<b>' . __( 'Zip-code:', 'mojito-shipping' ) . '</b> ' . $post_code );
                $dest_phone_number = $order->get_shipping_phone();
                $dest_phone_number = str_replace( ' ', '', $dest_phone_number );
                $dest_phone_number = str_replace( '-', '', $dest_phone_number );
                if ( empty( $dest_phone_number ) ) {
                    $dest_phone_number = $order->get_billing_phone();
                    $dest_phone_number = str_replace( ' ', '', $dest_phone_number );
                    $dest_phone_number = str_replace( '-', '', $dest_phone_number );
                }
                $pdf->writeHTML( '<b>' . __( 'Phone:', 'mojito-shipping' ) . '</b> ' . $dest_phone_number );
                /**
                 * Filter: After Recipient data
                 */
                $custom_content = apply_filters( 'mojito_shipping_pymexpress_pdf_after_recipient_data', array(
                    'content'  => '',
                    'position' => 'L',
                ) );
                if ( 'L' !== $custom_content['position'] && 'C' !== $custom_content['position'] && 'R' !== $custom_content['position'] ) {
                    $custom_content['position'] = 'L';
                }
                if ( !empty( $custom_content['content'] ) ) {
                    $pdf->writeHTML(
                        $custom_content['content'],
                        true,
                        false,
                        false,
                        false,
                        $custom_content['position']
                    );
                }
                /**
                 * Package content details
                 */
                if ( 'yes' === get_option( 'mojito-shipping-pymexpress-pdf-export-order-content', 'no' ) ) {
                    $package_items = '';
                    foreach ( $order->get_items() as $item_id => $item ) {
                        $line = $item->get_name();
                        $line .= ' ( ';
                        $line .= $item->get_quantity();
                        $line .= ' ), ';
                        $package_items .= $line;
                    }
                    $package_items = rtrim( $package_items, ', ' );
                    $pdf->writeHTML(
                        '<br>',
                        true,
                        false,
                        false,
                        false,
                        'C'
                    );
                    $pdf->writeHTML(
                        '<h2>' . __( 'Package details', 'mojito-shipping' ) . '</h2>',
                        true,
                        false,
                        false,
                        false,
                        'C'
                    );
                    $pdf->writeHTML(
                        '<hr>',
                        true,
                        false,
                        false,
                        false,
                        'C'
                    );
                    $pdf->writeHTML( '<b>' . __( 'Details:', 'mojito-shipping' ) . '</b> ' . $package_items );
                    /**
                     * Filter: After package content details
                     */
                    $custom_content = apply_filters( 'mojito_shipping_pymexpress_pdf_after_package_content', array(
                        'content'  => '',
                        'position' => 'L',
                    ) );
                    if ( 'L' !== $custom_content['position'] && 'C' !== $custom_content['position'] && 'R' !== $custom_content['position'] ) {
                        $custom_content['position'] = 'L';
                    }
                    if ( !empty( $custom_content['content'] ) ) {
                        $pdf->writeHTML(
                            $custom_content['content'],
                            true,
                            false,
                            false,
                            false,
                            $custom_content['position']
                        );
                    }
                }
                /**
                 * Client notes
                 */
                if ( 'yes' === get_option( 'mojito-shipping-pymexpress-pdf-export-client-note', 'no' ) ) {
                    $pdf->writeHTML(
                        '<br>',
                        true,
                        false,
                        false,
                        false,
                        'C'
                    );
                    $pdf->writeHTML(
                        '<h2>' . __( 'Client notes', 'mojito-shipping' ) . '</h2>',
                        true,
                        false,
                        false,
                        false,
                        'C'
                    );
                    $pdf->writeHTML(
                        '<hr>',
                        true,
                        false,
                        false,
                        false,
                        'C'
                    );
                    $pdf->writeHTML( '<b>' . __( 'Client note:', 'mojito-shipping' ) . '</b> ' . $order->get_customer_note() );
                }
                /**
                 * Filter: After Client notes
                 */
                $custom_content = apply_filters( 'mojito_shipping_pymexpress_pdf_after_client_notes', array(
                    'content'  => '',
                    'position' => 'L',
                ) );
                // Backguards compatibility.
                $custom_content = apply_filters( 'mojito_shipping_pymexpress_pdf_after_package_data', $custom_content );
                if ( 'L' !== $custom_content['position'] && 'C' !== $custom_content['position'] && 'R' !== $custom_content['position'] ) {
                    $custom_content['position'] = 'L';
                }
                if ( !empty( $custom_content['content'] ) ) {
                    $pdf->writeHTML(
                        $custom_content['content'],
                        true,
                        false,
                        false,
                        false,
                        $custom_content['position']
                    );
                }
                /**
                 * Logos
                 */
                $logos_html = '<table><tr>';
                if ( 'yes' === get_option( 'mojito-shipping-pymexpress-pdf-export-ccr-logo' ) ) {
                    $logo_file = 'logo-correos-de-costa-rica.jpg';
                    $logo_url = apply_filters( 'mojito_shipping_pymexpress_pdf_ccr_logo_src', plugin_dir_url( __DIR__ ) . 'public/img/' . $logo_file );
                    $img = '<img style="width:200px;display:inline-block;" src="' . $logo_url . '">';
                    $logos_html .= '<td>' . $img . '</td>';
                }
                if ( 'yes' === get_option( 'mojito-shipping-pymexpress-pdf-export-site-logo' ) ) {
                    $blog_id = ( is_multisite() ? get_current_blog_id() : 0 );
                    if ( has_custom_logo( $blog_id ) ) {
                        $custom_logo_id = get_theme_mod( 'custom_logo' );
                        $image = wp_get_attachment_image_src( $custom_logo_id, 'full' );
                        add_filter(
                            'get_custom_logo',
                            function ( $html, $blog_id ) {
                                return str_replace( 'itemprop="logo"', '', $html );
                            },
                            10,
                            2
                        );
                        $logo_url = apply_filters( 'mojito_shipping_pymexpress_pdf_site_logo_src', $image[0] );
                        $img = '<img style="width:200px;display:inline-block;" src="' . $logo_url . '">';
                        $logos_html .= '<td>' . $img . '</td>';
                    } else {
                        $logo_url = apply_filters( 'mojito_shipping_pymexpress_pdf_site_logo_src', '' );
                        $img = '<img style="width:200px;display:inline-block;" src="' . $logo_url . '">';
                        $logos_html .= '<td>' . $img . '</td>';
                    }
                }
                $logos_html .= '</tr></table>';
                $pdf->writeHTML(
                    '<div>' . $logos_html . '</div>',
                    true,
                    false,
                    true,
                    false,
                    'C'
                );
            }
            /**
             * Save file
             */
            $output = $pdf->Output( $file, 'S' );
            file_put_contents( $file, $output );
            $content = base64_encode( file_get_contents( $file ) );
        } elseif ( 'pymexpress' === get_option( 'mojito-shipping-pymexpress-pdf-export-origin', 'mojito' ) ) {
            $content = $order->get_meta( 'mojito_shipping_pymexpress_pdf', true );
            file_put_contents( $file, base64_decode( $content ) );
        }
        $url = $upload_dir['baseurl'] . '/mojito-shipping-tmp/' . $guide_number . '.pdf';
        $response = array(
            'url'          => $url,
            'content'      => $content,
            'guide_number' => $guide_number,
        );
        if ( $ajax_call ) {
            echo wp_json_encode( $response );
            wp_delete_file( $file );
            die;
        } else {
            $response['path'] = $file;
            return $response;
        }
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Mojito_Shipping_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * ¡Vaya que eres curioso!
     */
    public function soy_curiso() {
        /**
         * Este método no hace nada más que darte un 25% de descuento con el cupón SOYCURIOSO
         * Ya quedan pocos, ¡póngale!
         */
    }

    /**
     * Start WooCommerce Shipping method
     */
    public function start_woocommerce_shipping() {
        $carrier_provider = get_option( 'mojito-shipping-carrier-provider' );
        if ( is_array( $carrier_provider ) ) {
            if ( in_array( 'ccr', $carrier_provider, true ) ) {
                add_action( 'woocommerce_shipping_init', function () {
                    require_once MOJITO_SHIPPING_DIR . 'includes/class-mojito-shipping-method-ccr.php';
                    global $instance_ccr_shipping_method;
                    if ( !isset( $instance_ccr_shipping_method ) ) {
                        $instance_ccr_shipping_method = new Mojito_Shipping_Method_CCR();
                    }
                } );
                /**
                 * Register shipping method
                 */
                add_filter( 'woocommerce_shipping_methods', function ( $methods ) {
                    $methods['mojito_shipping_ccr'] = 'Mojito_Shipping\\Mojito_Shipping_Method_CCR';
                    return $methods;
                } );
            }
            if ( in_array( 'pymexpress', $carrier_provider, true ) ) {
                add_action( 'woocommerce_shipping_init', function () {
                    require_once MOJITO_SHIPPING_DIR . 'includes/class-mojito-shipping-method-pymexpress.php';
                    global $instance_pymexpress_shipping_method;
                    if ( !isset( $instance_pymexpress_shipping_method ) ) {
                        $instance_pymexpress_shipping_method = new Mojito_Shipping_Method_Pymexpress();
                    }
                } );
                /**
                 * Register shipping method
                 */
                add_filter( 'woocommerce_shipping_methods', function ( $methods ) {
                    $methods['mojito_shipping_pymexpress'] = 'Mojito_Shipping\\Mojito_Shipping_Method_Pymexpress';
                    return $methods;
                } );
            }
            if ( in_array( 'ccr-simple', $carrier_provider, true ) ) {
                add_action( 'woocommerce_shipping_init', function () {
                    require_once MOJITO_SHIPPING_DIR . 'includes/class-mojito-shipping-method-ccr.php';
                    require_once MOJITO_SHIPPING_DIR . 'includes/class-mojito-shipping-method-ccr-simple.php';
                    global $instance_ccr_simple_shipping_method;
                    if ( !isset( $instance_ccr_simple_shipping_method ) ) {
                        $instance_ccr_simple_shipping_method = new Mojito_Shipping_Method_CCR_Simple();
                    }
                } );
                /**
                 * Register shipping method
                 */
                add_filter( 'woocommerce_shipping_methods', function ( $methods ) {
                    $methods['mojito_shipping_correos_simple'] = 'Mojito_Shipping\\Mojito_Shipping_Method_CCR_Simple';
                    return $methods;
                } );
            }
            if ( in_array( 'simple', $carrier_provider, true ) ) {
                add_action( 'woocommerce_shipping_init', function () {
                    require_once MOJITO_SHIPPING_DIR . 'includes/class-mojito-shipping-method-simple.php';
                    global $instance_simple_shipping_method;
                    if ( !isset( $instance_simple_shipping_method ) ) {
                        $instance_simple_shipping_method = new Mojito_Shipping_Method_Simple();
                    }
                } );
                /**
                 * Register shipping method
                 */
                add_filter( 'woocommerce_shipping_methods', function ( $methods ) {
                    $methods['mojito_shipping_simple'] = 'Mojito_Shipping\\Mojito_Shipping_Method_Simple';
                    return $methods;
                } );
            }
        }
    }

    /**
     * Cron jobs
     * https://wordpress.org/support/topic/no-se-genera-guia-si-el-pago-es-con-tarjeta/
     *
     * @return void
     */
    public function cron_ccr() {
        $orders_to_check = array();
        /**
         * Get orders without guide number
         * Legacy System
         */
        $orders = wc_get_orders( array(
            'meta_key'     => 'mojito_shipping_ccr_guide_number',
            'meta_compare' => 'NOT EXISTS',
            'return'       => 'ids',
        ) );
        $orders_to_check = array_merge( $orders_to_check, $orders );
        /**
         * Get orders with empty guide number
         * Legacy System
         */
        $orders = wc_get_orders( array(
            'meta_key'     => 'mojito_shipping_ccr_guide_number',
            'meta_compare' => 'IN',
            'meta_value'   => array(''),
            'return'       => 'ids',
        ) );
        $orders_to_check = array_merge( $orders_to_check, $orders );
        if ( !$this->ccr_ws_client instanceof Mojito_Shipping_Method_CCR_WSC ) {
            // Init web service client.
            if ( !class_exists( 'Mojito_Shipping_Method_CCR_WSC' ) ) {
                require_once MOJITO_SHIPPING_DIR . 'includes/class-mojito-shipping-method-ccr-webservice-client.php';
            }
            $this->ccr_ws_client = new Mojito_Shipping_Method_CCR_WSC();
        }
        foreach ( $orders_to_check as $key => $order_id ) {
            $order = wc_get_order( $order_id );
            // Legacy System.
            if ( $order->has_shipping_method( 'mojito_shipping_ccr' ) ) {
                $guide_number = $this->ccr_ws_client->ccr_get_guide_number();
                $order->update_meta_data( 'mojito_shipping_ccr_guide_number', sanitize_text_field( $guide_number ) );
                $order->save();
                $this->ccr_thankyou_log_send( $order_id, true );
            }
        }
    }

    /**
     * Cron jobs
     * https://wordpress.org/support/topic/no-se-genera-guia-si-el-pago-es-con-tarjeta/
     *
     * @return void
     */
    public function cron_pymexpress() {
        $allow = get_option( 'mojito-shipping-pymexpress-cron-control-allow-to-run', 'yes' );
        if ( 'yes' !== $allow ) {
            return;
        }
        $orders_to_check = array();
        /**
         * Get orders without guide number
         * New Pymexpress System
         */
        $orders = wc_get_orders( array(
            'meta_key'     => 'mojito_shipping_pymexpress_guide_number',
            'meta_compare' => 'NOT EXISTS',
            'return'       => 'ids',
        ) );
        $orders_to_check = array_merge( $orders_to_check, $orders );
        /**
         * Get orders with empty guide number
         * New Pymexpress System
         */
        $orders = wc_get_orders( array(
            'meta_key'     => 'mojito_shipping_pymexpress_guide_number',
            'meta_compare' => 'IN',
            'meta_value'   => array(''),
            'return'       => 'ids',
        ) );
        $orders_to_check = array_merge( $orders_to_check, $orders );
        if ( !$this->pymexpress_ws_client instanceof Mojito_Shipping_Method_Pymexpress_WSC ) {
            // Init web service client.
            if ( !class_exists( 'Mojito_Shipping_Method_Pymexpress_WSC' ) ) {
                require_once MOJITO_SHIPPING_DIR . 'includes/class-mojito-shipping-method-pymexpress-webservice-client.php';
            }
            $this->pymexpress_ws_client = new Mojito_Shipping_Method_Pymexpress_WSC();
        }
        foreach ( $orders_to_check as $key => $order_id ) {
            $order = wc_get_order( $order_id );
            // New System.
            if ( $order->has_shipping_method( 'mojito_shipping_pymexpress' ) ) {
                $guide_number = $this->pymexpress_ws_client->generar_guia();
                $order->update_meta_data( 'mojito_shipping_pymexpress_guide_number', sanitize_text_field( $guide_number ) );
                $order->save();
                $this->pymexpress_thankyou_log_send( $order_id, true );
            }
        }
        /**
         * Now, orders with guide number but not registered in Correos de Costa Rica
         */
        $orders_to_check = array();
        $orders = wc_get_orders( array(
            'meta_key'     => 'mojito_shipping_pymexpress_ccrRegistroEnvio_response_code',
            'meta_compare' => 'NOT EXISTS',
            'return'       => 'ids',
        ) );
        $orders_to_check = array_merge( $orders_to_check, $orders );
        foreach ( $orders_to_check as $key => $order_id ) {
            $this->pymexpress_thankyou_log_send( $order_id, true );
        }
        /***
         * Update exchange rate
         */
        $exchange_object = new Mojito_Shipping_Exchange_Rate();
        $exchange_rate = $exchange_object->get_exchange_rate_crc_usd();
    }

    /**
     * Revisar IP contra el firewall de Mojito Proxy
     */
    public function cron_mojito_proxy_ip_check() {
    }

}

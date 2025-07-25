<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://mojitowp.com
 * @since      1.0.0
 *
 * @package    Mojito_Shipping
 * @subpackage Mojito_Shipping/admin
 */
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Mojito_Shipping
 * @subpackage Mojito_Shipping/admin
 * @author     Mojito Team <support@mojitowp.com>
 */
namespace Mojito_Shipping;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Admin class for plugin.
 */
class Mojito_Shipping_Admin extends Mojito_Settings {
    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Mojito_Shipping_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Mojito_Shipping_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_style(
            MOJITO_SHIPPING_SLUG,
            plugin_dir_url( __FILE__ ) . 'css/mojito-shipping-admin.css',
            array(),
            MOJITO_SHIPPING_VERSION,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Mojito_Shipping_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Mojito_Shipping_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_script(
            MOJITO_SHIPPING_SLUG,
            plugin_dir_url( __FILE__ ) . 'js/mojito-shipping-admin.js',
            array('jquery'),
            MOJITO_SHIPPING_VERSION,
            false
        );

        wp_localize_script(
            MOJITO_SHIPPING_SLUG,
            'mojito_shipping_admin_ajax',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'mojito_shipping_admin_nonce' ),
            )
        );
    }

    /**
     * Add menu pages
     */
    public function menu_options() {
        add_submenu_page(
            'options-general.php',
            'Mojito Shipping',
            'Mojito Shipping',
            'manage_options',
            'mojito-shipping',
            array($this, 'menu_page')
        );
    }

    /**
     *
     * Show options page.
     */
    public function menu_page() {
        require_once MOJITO_SHIPPING_DIR . '/admin/partials/mojito-shipping-admin-display.php';
    }

    /**
     * Set Settings
     */
    public function set_settings() {
        /**
         * Help links
         * box-id => link
         */
        $this->help_links = array(
            'simple-label'                                      => 'https://mojitowp.com/documentacion/sistema-saliente/#3.15',
            'ccr-store'                                         => 'https://mojitowp.com/documentacion/sistema-saliente/#3.2',
            'ccr-web-service'                                   => 'https://mojitowp.com/documentacion/sistema-saliente/#3.3',
            'ccr-sender'                                        => 'https://mojitowp.com/documentacion/sistema-saliente/#3.4',
            'ccr-mail-orders'                                   => 'https://mojitowp.com/documentacion/sistema-saliente/#3.5',
            'ccr-minimal'                                       => 'https://mojitowp.com/documentacion/sistema-saliente/#3.6',
            'ccr-fixed-rates'                                   => 'https://mojitowp.com/documentacion/sistema-saliente/#3.7',
            'ccr-exchange-rate'                                 => 'https://mojitowp.com/documentacion/sistema-saliente/#3.8',
            'ccr-max-weight'                                    => 'https://mojitowp.com/documentacion/sistema-saliente/#3.9',
            'ccr-proxy'                                         => 'https://mojitowp.com/documentacion/sistema-saliente/#3.10',
            'ccr-label'                                         => 'https://mojitowp.com/documentacion/sistema-saliente/#3.15',
            'ccr-simple-store'                                  => 'https://mojitowp.com/documentacion/sistema-saliente/#3.2',
            'ccr-simple-minimal'                                => 'https://mojitowp.com/documentacion/sistema-saliente/#3.6',
            'ccr-simple-fixed-rates'                            => 'https://mojitowp.com/documentacion/sistema-saliente/#3.7',
            'ccr-simple-exchange-rate'                          => 'https://mojitowp.com/documentacion/sistema-saliente/#3.8',
            'ccr-simple-max-weight'                             => 'https://mojitowp.com/documentacion/sistema-saliente/#3.9',
            'ccr-simple-label'                                  => 'https://mojitowp.com/documentacion/sistema-saliente/#3.15',
            'pymexpress-store'                                  => 'https://mojitowp.com/documentacion/pymexpress/#3.2',
            'pymexpress-web-service'                            => 'https://mojitowp.com/documentacion/pymexpress/#3.3',
            'pymexpress-sender'                                 => 'https://mojitowp.com/documentacion/pymexpress/#3.4',
            'pymexpress-mail-orders'                            => 'https://mojitowp.com/documentacion/pymexpress/#3.5',
            'pymexpress-fixed-rates'                            => 'https://mojitowp.com/documentacion/pymexpress/#3.7',
            'pymexpress-exchange-rate'                          => 'https://mojitowp.com/documentacion/pymexpress/#3.8',
            'pymexpress-max-weight'                             => 'https://mojitowp.com/documentacion/pymexpress/#3.9',
            'pymexpress-proxy'                                  => 'https://mojitowp.com/documentacion/pymexpress/#3.10',
            'pymexpress-label'                                  => 'https://mojitowp.com/documentacion/pymexpress/#3.15',
            'pymexpress-cart-and-checkout-address-preselection' => 'https://mojitowp.com/documentacion/pymexpress/#3.19',
            'settings'                                          => 'https://mojitowp.com/documentacion/pymexpress/#5.1',
        );
        /**
         * Create tabs
         */
        $this->tabs = array(
            'general'     => __( 'General', 'mojito-shipping' ),
            'ccr'         => __( 'Correos de Costa Rica', 'mojito-shipping' ),
            'pymexpress'  => __( 'Correos de Costa Rica - Pymexpress', 'mojito-shipping' ),
            'ccr-simple'  => __( 'Correos de Costa Rica without integration', 'mojito-shipping' ),
            'simple'      => __( 'Simple weight-based', 'mojito-shipping' ),
            'information' => __( 'Information', 'mojito-shipping' ),
            'settings'    => __( 'Other settings', 'mojito-shipping' ),
        );
        /**
         * General settings
         */
        $this->settings['carrier'] = array(
            'title'       => __( 'Carrier settings', 'mojito-shipping' ),
            'description' => __( 'Select your Carrier', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'      => 'select',
                'label'     => __( 'Carrier or Method', 'mojito-shipping' ),
                'name'      => 'provider',
                'options'   => array(
                    ''           => __( 'Select one', 'mojito-shipping' ),
                    'simple'     => __( 'Simple weight-based rates', 'mojito-shipping' ),
                    'pymexpress' => __( 'Correos de Costa Rica - Pymexpress', 'mojito-shipping' ),
                    'ccr-simple' => __( 'Correos de Costa Rica without integration', 'mojito-shipping' ),
                ),
                'tooltip'   => __( 'Select your service provider', 'mojito-shipping' ),
                'data-type' => 'array',
            )),
            'box-id'      => 'carrier',
            'tab-id'      => 'general',
        );
        $this->settings['notices'] = array(
            'title'    => __( 'Do you like Mojito Shipping?', 'mojito-shipping' ),
            'callback' => array($this, 'notices'),
            'box-id'   => 'notices',
            'tab-id'   => 'general',
        );
        /**
         * Method / Carrier
         * Correos de Costa Rica Settings
         */
        /**
         * Store and sendding settings
         */
        $this->settings['ccr-store'] = array(
            'title'       => __( 'Your business settings', 'mojito-shipping' ),
            'description' => __( 'Set settings for your store.', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'select',
                'label'   => __( 'Store location', 'mojito-shipping' ),
                'name'    => 'location',
                'options' => array(
                    ''            => '',
                    'inside-gam'  => __( 'Inside GAM', 'mojito-shipping' ),
                    'outside-gam' => __( 'Outsite GAM', 'mojito-shipping' ),
                ),
                'tooltip' => __( 'Indicate if your store is located inside or outside the GAM', 'mojito-shipping' ),
            ), array(
                'type'    => 'select',
                'label'   => __( 'Service for local shipping', 'mojito-shipping' ),
                'name'    => 'local-shipping',
                'options' => array(
                    'disabled'    => '',
                    'pymexpress'  => __( 'Pymexpress', 'mojito-shipping' ),
                    'ems-courier' => __( 'EMS Courier', 'mojito-shipping' ),
                ),
                'tooltip' => __( 'Select the service to deliver the packages inside Costa Rica', 'mojito-shipping' ),
            )),
            'box-id'      => 'ccr-store',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr',
        );
        /**
         * Web Service Settings
         */
        $this->settings['ccr-web-service'] = array(
            'title'       => __( 'Web Service Settings', 'mojito-shipping' ),
            'description' => __( 'Configuration to consume the Correos de Costa Rica web service.', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'text',
                    'label'   => __( 'URL Web Service', 'mojito-shipping' ),
                    'name'    => 'url',
                    'class'   => 'long',
                    'value'   => __( 'Some like http://amistad.correos.go.cr:82/wserPruebas/wsAppCorreos.wsAppCorreos.svc?WSDL', 'mojito-shippping' ),
                    'tooltip' => __( 'Be sure the URL ends with "?WSDL", example: http://amistad.correos.go.cr:82/wserPruebas/wsAppCorreos.wsAppCorreos.svc?WSDL', 'mojito-shipping' ),
                ),
                array(
                    'type'  => 'text',
                    'label' => __( 'User', 'mojito-shipping' ),
                    'name'  => 'username',
                ),
                array(
                    'type'  => 'password',
                    'label' => __( 'Pass', 'mojito-shipping' ),
                    'name'  => 'password',
                ),
                array(
                    'type'  => 'text',
                    'label' => __( 'User ID', 'mojito-shipping' ),
                    'name'  => 'user-id',
                ),
                array(
                    'type'  => 'text',
                    'label' => __( 'Client Type', 'mojito-shipping' ),
                    'name'  => 'client-type',
                ),
                array(
                    'type'  => 'text',
                    'label' => __( 'Service ID', 'mojito-shipping' ),
                    'name'  => 'service-id',
                ),
                array(
                    'type'  => 'text',
                    'label' => __( 'Client code', 'mojito-shipping' ),
                    'name'  => 'client-code',
                )
            ),
            'box-id'      => 'ccr-web-service',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr',
        );
        /**
         * Sender Settings
         */
        $this->settings['ccr-sender'] = array(
            'title'       => __( 'Sender Settings', 'mojito-shipping' ),
            'description' => __( 'Your company or store information.', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'text',
                    'label'   => __( 'Name', 'mojito-shipping' ),
                    'name'    => 'name',
                    'tooltip' => __( 'Sender Name', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'text',
                    'label'   => __( 'Address', 'mojito-shipping' ),
                    'name'    => 'address',
                    'tooltip' => __( 'Sender physical address', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'text',
                    'label'   => __( 'Zip Code', 'mojito-shipping' ),
                    'name'    => 'zip-code',
                    'tooltip' => __( 'Sender zip code', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'text',
                    'label'   => __( 'Phone', 'mojito-shipping' ),
                    'name'    => 'phone',
                    'tooltip' => __( 'Sender\'s phone number', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'email',
                    'label'   => __( 'Email', 'mojito-shipping' ),
                    'name'    => 'email',
                    'tooltip' => __( 'Sender\'s email', 'mojito-shipping' ),
                )
            ),
            'box-id'      => 'ccr-sender',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr',
        );
        /**
         * Message In Mail Orders Settings
         */
        $this->settings['ccr-mail-orders'] = array(
            'title'       => __( 'Message In Mail Orders Settings', 'mojito-shipping' ),
            'description' => __( 'Custom message to show it on orders mail.', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'  => 'text',
                'label' => __( 'Label', 'mojito-shipping' ),
                'name'  => 'name',
                'value' => __( 'Correos de Costa Rica Tracking code', 'mojito-shipping' ),
            ), array(
                'type'  => 'textarea',
                'label' => __( 'Message', 'mojito-shipping' ),
                'name'  => 'message',
                'class' => 'long',
                'value' => __( 'Guide number for packages tracking', 'mojito-shipping' ),
            )),
            'box-id'      => 'ccr-mail-orders',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr',
        );
        /**
         * Correos de Costa Rica Fixed rates
         */
        $this->settings['ccr-fixed-rates'] = array(
            'title'       => __( 'Fixed rates', 'mojito-shipping' ),
            'description' => __( 'Set fixed rates. <span class="error">Caution: This will overwrite any other shipping rates calculation.</span>', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'select',
                'label'   => __( 'Enable fixed rates', 'mojito-shipping' ),
                'name'    => 'enable',
                'options' => array(
                    'disabled' => __( 'Disable fixed rates', 'mojito-shipping' ),
                    'enable'   => __( 'Enable fixed rates', 'mojito-shipping' ),
                ),
                'value'   => 'disabled',
                'tooltip' => __( 'This setting allows you to ensure fixed rates to GAM and Non-GAM shipping.', 'mojito-shipping' ),
            ), array(
                'type'    => 'number',
                'label'   => __( 'Rate for GAM', 'mojito-shipping' ),
                'name'    => 'gam-rate',
                'tooltip' => __( 'Set a fixed rate to GAM.', 'mojito-shipping' ),
                'value'   => 2000,
            ), array(
                'type'    => 'number',
                'label'   => __( 'Rate for non-GAM', 'mojito-shipping' ),
                'name'    => 'no-gam-rate',
                'tooltip' => __( 'Set a fixed rate to non-GAM.', 'mojito-shipping' ),
                'value'   => 3000,
            )),
            'box-id'      => 'ccr-fixed-rates',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr',
        );
        /**
         * Correos de Costa Rica Minimal amounts
         */
        $this->settings['ccr-minimal'] = array(
            'title'       => __( 'Minimal amounts', 'mojito-shipping' ),
            'description' => __( 'Set the minimal amount to charge.', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'select',
                'label'   => __( 'Enable minimal amounts', 'mojito-shipping' ),
                'name'    => 'enable',
                'options' => array(
                    'disabled' => __( 'Disable the minimal amounts', 'mojito-shipping' ),
                    'enable'   => __( 'Enable the minimal amounts', 'mojito-shipping' ),
                ),
                'value'   => 'disabled',
                'tooltip' => __( 'This setting allows you to ensure a minimum shipping charge.', 'mojito-shipping' ),
            ), array(
                'type'    => 'number',
                'label'   => __( 'General minimal amount', 'mojito-shipping' ),
                'name'    => 'amount-general',
                'tooltip' => __( 'Set a minimum charge.', 'mojito-shipping' ),
                'value'   => 2000,
            )),
            'box-id'      => 'ccr-minimal',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr',
        );
        /**
         * Correos de Costa Rica Exchange rate
         */
        $this->settings['ccr-exchange-rate'] = array(
            'title'       => __( 'Exchange Rate', 'mojito-shipping' ),
            'description' => __( 'Due Correos de Costa Rica uses colones for its rates, this option allows you to sell in another currency and convert the final shipping rate to the currency established in your store, according to the exchange rate settings.', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'select',
                'label'   => __( 'Enable exchange rate', 'mojito-shipping' ),
                'name'    => 'enable',
                'options' => array(
                    'disabled' => __( 'Disable exchange rate', 'mojito-shipping' ),
                    'enable'   => __( 'Enable exchange rate', 'mojito-shipping' ),
                ),
                'value'   => 'disabled',
                'tooltip' => __( 'This option allows you to enable the exchange rate.', 'mojito-shipping' ),
            ), array(
                'type'    => 'number',
                'label'   => __( 'Exchange rate', 'mojito-shipping' ),
                'name'    => 'rate',
                'tooltip' => __( 'Please set up the dollar price in colones.', 'mojito-shipping' ),
                'value'   => 610,
            )),
            'box-id'      => 'ccr-exchange-rate',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr',
        );
        /**
         * Correos de Costa Rica Max Weight
         */
        $this->settings['ccr-max-weight'] = array(
            'title'       => __( 'Max Weight settings', 'mojito-shipping' ),
            'description' => __( 'The maximum weight allowed by Correos de Costa Rica is 30,000 grams (30 kg), you can disable this shipping method when the maximum package weight is reached.', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'select',
                'label'   => __( 'Max Weight Control', 'mojito-shipping' ),
                'name'    => 'enable',
                'options' => array(
                    'disabled' => __( 'Disable Correos de Costa Rica when order total weight is over 30 kg', 'mojito-shipping' ),
                    'enable'   => __( 'Keep Correos de Costa Rica ENABLED when order total weight is over 30 kg', 'mojito-shipping' ),
                ),
                'value'   => 'disabled',
                'tooltip' => __( 'This option allows you to disable Correos de Costa Rica when order total weight is over 30000 g (30 kg).', 'mojito-shipping' ),
            )),
            'box-id'      => 'ccr-max-weight',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr',
        );
        /**
         * Proxy Connection Settings
         */
        $this->settings['ccr-proxy'] = array(
            'title'       => __( 'Proxy Connection Settings', 'mojito-shipping' ),
            'description' => __( 'Connection Settings.', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'select',
                    'label'   => __( 'Enable Proxy Connection', 'mojito-shipping' ),
                    'name'    => 'enable',
                    'options' => array(
                        'false' => __( 'Disabled', 'mojito-shipping' ),
                        'true'  => __( 'Enabled', 'mojito-shipping' ),
                    ),
                ),
                array(
                    'type'  => 'text',
                    'label' => __( 'Username', 'mojito-shipping' ),
                    'name'  => 'username',
                ),
                array(
                    'type'  => 'password',
                    'label' => __( 'Password', 'mojito-shipping' ),
                    'name'  => 'password',
                ),
                array(
                    'type'  => 'text',
                    'label' => __( 'Proxy IP', 'mojito-shipping' ),
                    'name'  => 'ip',
                ),
                array(
                    'type'  => 'text',
                    'label' => __( 'Proxy Port', 'mojito-shipping' ),
                    'name'  => 'port',
                )
            ),
            'box-id'      => 'ccr-proxy',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr',
        );
        /**
         * Correos de Costa Rica Label
         */
        $this->settings['ccr-label'] = array(
            'title'       => __( 'Label settings', 'mojito-shipping' ),
            'description' => __( 'This option allows you set a custom label for Cart and Checkout.', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'text',
                'label'   => __( 'Custom label', 'mojito-shipping' ),
                'name'    => 'label',
                'value'   => '',
                'tooltip' => __( 'Dynamic allowed values: <br>%rate% for shipping rate <br>%country% for destination country <br>%weight% for package weight <br>%weight-ccr% for package weight allowed by Correos de Costa Rica', 'mojito-shipping' ),
            )),
            'box-id'      => 'ccr-label',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr',
        );
        /**
         * Correos de Costa Rica Pymexpress - New System
         */
        /**
         * Web Service Settings
         */
        $this->settings['pymexpress-web-service'] = array(
            'title'       => __( 'Web Service Settings', 'mojito-shipping' ),
            'description' => __( 'Configuration to consume the Correos de Costa Rica web service. Important: Complete this settings and check connection before first.', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'        => 'text',
                    'label'       => __( 'Username', 'mojito-shipping' ),
                    'name'        => 'username',
                    'placeholder' => __( 'Web Service Username', 'mojito-shipping' ),
                    'class'       => 'required',
                ),
                array(
                    'type'        => 'password',
                    'label'       => __( 'Password', 'mojito-shipping' ),
                    'name'        => 'password',
                    'placeholder' => __( 'Web Service Password', 'mojito-shipping' ),
                    'class'       => 'required',
                ),
                array(
                    'type'        => 'text',
                    'label'       => __( 'User ID', 'mojito-shipping' ),
                    'name'        => 'user-id',
                    'placeholder' => __( 'Web Service User ID', 'mojito-shipping' ),
                    'class'       => 'required',
                ),
                array(
                    'type'        => 'text',
                    'label'       => __( 'Service ID', 'mojito-shipping' ),
                    'name'        => 'service-id',
                    'placeholder' => __( 'Web Service, Service ID', 'mojito-shipping' ),
                    'class'       => 'required',
                ),
                array(
                    'type'        => 'text',
                    'label'       => __( 'Client code', 'mojito-shipping' ),
                    'name'        => 'client-code',
                    'placeholder' => __( 'Web Service Client Code', 'mojito-shipping' ),
                    'class'       => 'required',
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Environment type', 'mojito-shipping' ),
                    'name'    => 'environment',
                    'options' => array(
                        'sandbox'    => __( 'Sandbox', 'mojito-shipping' ),
                        'production' => __( 'Production', 'mojito-shipping' ),
                    ),
                    'value'   => 'sandbox',
                    'tooltip' => __( 'This setting allows you to set development or production environment.', 'mojito-shipping' ),
                )
            ),
            'box-id'      => 'pymexpress-web-service',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'pymexpress',
            ),
            'class'       => 'closed',
            'tab-id'      => 'pymexpress',
        );
        /**
         * Store and sendding settings
         */
        $this->settings['pymexpress-store'] = array(
            'title'       => __( 'Your business settings', 'mojito-shipping' ),
            'description' => __( 'Set settings for your store. Please fill, save and check the connection settings before set the locations settings.', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'        => 'select',
                'label'       => __( 'Province', 'mojito-shipping' ),
                'name'        => 'province',
                'options'     => array(
                    ''  => '',
                    '1' => __( 'San José', 'mojito-shipping' ),
                    '2' => __( 'Alajuela', 'mojito-shipping' ),
                    '3' => __( 'Cartago', 'mojito-shipping' ),
                    '4' => __( 'Heredia', 'mojito-shipping' ),
                    '5' => __( 'Guanacaste', 'mojito-shipping' ),
                    '6' => __( 'Puntarenas', 'mojito-shipping' ),
                    '7' => __( 'Limón', 'mojito-shipping' ),
                ),
                'value'       => '1',
                'tooltip'     => __( 'Indicate your store\'s province', 'mojito-shipping' ),
                'placeholder' => __( 'Set the province', 'mojito-shipping' ),
                'class'       => 'required',
            ), array(
                'type'        => 'select',
                'label'       => __( 'Canton', 'mojito-shipping' ),
                'name'        => 'canton',
                'options'     => $this->pymexpress_load_cantones(),
                'tooltip'     => __( 'Indicate your store\'s canton', 'mojito-shipping' ),
                'placeholder' => __( 'Set the canton', 'mojito-shipping' ),
                'class'       => 'required',
            ), array(
                'type'        => 'select',
                'label'       => __( 'District', 'mojito-shipping' ),
                'name'        => 'district',
                'options'     => $this->pymexpress_load_distritos(),
                'tooltip'     => __( 'Indicate your store\'s district', 'mojito-shipping' ),
                'placeholder' => __( 'Set the district', 'mojito-shipping' ),
                'class'       => 'required',
            )),
            'box-id'      => 'pymexpress-store',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'pymexpress',
            ),
            'class'       => 'closed',
            'tab-id'      => 'pymexpress',
        );
        /**
         * Sender Settings
         */
        $this->settings['pymexpress-sender'] = array(
            'title'       => __( 'Sender Settings', 'mojito-shipping' ),
            'description' => __( 'Your company or store information.', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'text',
                    'label'   => __( 'Name', 'mojito-shipping' ),
                    'name'    => 'name',
                    'tooltip' => __( 'Sender Name', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'text',
                    'label'   => __( 'Address', 'mojito-shipping' ),
                    'name'    => 'address',
                    'tooltip' => __( 'Sender physical address', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'text',
                    'label'   => __( 'Zip Code', 'mojito-shipping' ),
                    'name'    => 'zip-code',
                    'tooltip' => __( 'Sender zip code. You can find yours in https://correos.go.cr/codigo-postal/', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'text',
                    'label'   => __( 'Phone', 'mojito-shipping' ),
                    'name'    => 'phone',
                    'tooltip' => __( 'Sender\'s phone number', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'email',
                    'label'   => __( 'Email', 'mojito-shipping' ),
                    'name'    => 'email',
                    'tooltip' => __( 'Sender\'s email', 'mojito-shipping' ),
                )
            ),
            'box-id'      => 'pymexpress-sender',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'pymexpress',
            ),
            'class'       => 'closed',
            'tab-id'      => 'pymexpress',
        );
        /**
         * Message In Mail Orders Settings
         */
        $this->settings['pymexpress-mail-orders'] = array(
            'title'       => __( 'Message In Mail Orders Settings', 'mojito-shipping' ),
            'description' => __( 'Custom message to show it on orders mail.', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'  => 'text',
                'label' => __( 'Label', 'mojito-shipping' ),
                'name'  => 'name',
                'value' => __( 'Correos de Costa Rica Tracking code', 'mojito-shipping' ),
            ), array(
                'type'  => 'textarea',
                'label' => __( 'Message', 'mojito-shipping' ),
                'name'  => 'message',
                'class' => 'long',
                'value' => __( 'Guide number for packages tracking', 'mojito-shipping' ),
            )),
            'box-id'      => 'pymexpress-mail-orders',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'pymexpress',
            ),
            'class'       => 'closed',
            'tab-id'      => 'pymexpress',
        );
        /**
         * Message In Mail Orders Settings
         */
        $this->settings['pymexpress-packing-costs'] = array(
            'title'       => __( 'Add packing costs to the shipping calculation', 'mojito-shipping' ),
            'description' => __( 'Custom packing costs like bags or boxes.', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'select',
                'label'   => __( 'Enable packing costs', 'mojito-shipping' ),
                'name'    => 'enable',
                'options' => array(
                    'disabled' => __( 'Disable packing costs', 'mojito-shipping' ),
                    'enable'   => __( 'Enable packing costs', 'mojito-shipping' ),
                ),
                'value'   => 'disabled',
                'tooltip' => __( 'This setting allows you to ensure include packing costs shipping rate.', 'mojito-shipping' ),
            ), array(
                'type'    => 'select',
                'label'   => __( 'EMS bag size', 'mojito-shipping' ),
                'name'    => 'size',
                'options' => array(
                    'small'  => __( 'Small bag - ₡100,00', 'mojito-shipping' ),
                    'medium' => __( 'Medium bag - ₡130,00', 'mojito-shipping' ),
                    'big'    => __( 'Big bag - ₡150,00', 'mojito-shipping' ),
                ),
                'value'   => 'small',
            ), array(
                'type'    => 'number',
                'label'   => __( 'Custom packing cost', 'mojito-shipping' ),
                'name'    => 'custom-packing-cost',
                'tooltip' => __( 'if set, will overwrite the packing cost.', 'mojito-shipping' ),
                'value'   => 0,
            )),
            'box-id'      => 'pymexpress-packing-costs',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'pymexpress',
            ),
            'class'       => 'closed',
            'tab-id'      => 'pymexpress',
        );
        /**
         * Correos de Costa Rica Fixed rates
         */
        $this->settings['pymexpress-fixed-rates'] = array(
            'title'       => __( 'Fixed rates', 'mojito-shipping' ),
            'description' => __( 'Set fixed rates. <span class="error">Caution: This will overwrite any other shipping rates calculation.</span>', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'select',
                'label'   => __( 'Enable fixed rates', 'mojito-shipping' ),
                'name'    => 'enable',
                'options' => array(
                    'disabled' => __( 'Disable fixed rates', 'mojito-shipping' ),
                    'enable'   => __( 'Enable fixed rates', 'mojito-shipping' ),
                ),
                'value'   => 'disabled',
                'tooltip' => __( 'This setting allows you to ensure fixed rates to GAM and Non-GAM shipping.', 'mojito-shipping' ),
            ), array(
                'type'    => 'number',
                'label'   => __( 'Rate for GAM', 'mojito-shipping' ),
                'name'    => 'gam-rate',
                'tooltip' => __( 'Set a fixed rate to GAM.', 'mojito-shipping' ),
                'value'   => 2000,
            ), array(
                'type'    => 'number',
                'label'   => __( 'Rate for non-GAM', 'mojito-shipping' ),
                'name'    => 'no-gam-rate',
                'tooltip' => __( 'Set a fixed rate to non-GAM.', 'mojito-shipping' ),
                'value'   => 3000,
            )),
            'box-id'      => 'pymexpress-fixed-rates',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'pymexpress',
            ),
            'class'       => 'closed',
            'tab-id'      => 'pymexpress',
        );
        /**
         * Correos de Costa Rica Exchange rate
         */
        $this->settings['pymexpress-exchange-rate'] = array(
            'title'       => __( 'Exchange Rate', 'mojito-shipping' ),
            'description' => __( 'Due Correos de Costa Rica uses colones for its rates, this option allows you to sell in another currency and convert the final shipping rate to the currency established in your store, according to the exchange rate settings.', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'select',
                'label'   => __( 'Enable exchange rate', 'mojito-shipping' ),
                'name'    => 'enable',
                'options' => array(
                    'disabled' => __( 'Disable exchange rate', 'mojito-shipping' ),
                    'enable'   => __( 'Enable exchange rate', 'mojito-shipping' ),
                ),
                'value'   => 'disabled',
                'tooltip' => __( 'This option allows you to enable the exchange rate.', 'mojito-shipping' ),
            ), array(
                'type'    => 'number',
                'label'   => __( 'Exchange rate', 'mojito-shipping' ),
                'name'    => 'rate',
                'tooltip' => __( 'Please set up the dollar price in colones.', 'mojito-shipping' ),
                'value'   => 610,
            )),
            'box-id'      => 'pymexpress-exchange-rate',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'pymexpress',
            ),
            'class'       => 'closed',
            'tab-id'      => 'pymexpress',
        );
        /**
         * Correos de Costa Rica Max Weight
         */
        $this->settings['pymexpress-max-weight'] = array(
            'title'       => __( 'Max Weight settings', 'mojito-shipping' ),
            'description' => __( 'The maximum weight allowed by Correos de Costa Rica is 30,000 grams (30 kg), you can disable this shipping method when the maximum package weight is reached.', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'select',
                'label'   => __( 'Max Weight Control', 'mojito-shipping' ),
                'name'    => 'enable',
                'options' => array(
                    'disabled' => __( 'Disable Correos de Costa Rica when order total weight is over 30 kg', 'mojito-shipping' ),
                    'enable'   => __( 'Keep Correos de Costa Rica ENABLED when order total weight is over 30 kg', 'mojito-shipping' ),
                ),
                'value'   => 'disabled',
                'tooltip' => __( 'This option allows you to disable Correos de Costa Rica when order total weight is over 30000 g (30 kg).', 'mojito-shipping' ),
            )),
            'box-id'      => 'pymexpress-max-weight',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'pymexpress',
            ),
            'class'       => 'closed',
            'tab-id'      => 'pymexpress',
        );
        /**
         * Proxy Connection Settings
         */
        $this->settings['pymexpress-proxy'] = array(
            'title'       => __( 'Proxy Connection Settings', 'mojito-shipping' ),
            'description' => __( 'Connection Settings.', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'select',
                    'label'   => __( 'Enable Proxy Connection', 'mojito-shipping' ),
                    'name'    => 'enable',
                    'options' => array(
                        'false' => __( 'Disabled', 'mojito-shipping' ),
                        'true'  => __( 'Enabled', 'mojito-shipping' ),
                    ),
                ),
                array(
                    'type'  => 'text',
                    'label' => __( 'Username', 'mojito-shipping' ),
                    'name'  => 'username',
                ),
                array(
                    'type'  => 'password',
                    'label' => __( 'Password', 'mojito-shipping' ),
                    'name'  => 'password',
                ),
                array(
                    'type'  => 'text',
                    'label' => __( 'Proxy IP', 'mojito-shipping' ),
                    'name'  => 'ip',
                ),
                array(
                    'type'  => 'text',
                    'label' => __( 'Proxy Port', 'mojito-shipping' ),
                    'name'  => 'port',
                )
            ),
            'box-id'      => 'pymexpress-proxy',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'pymexpress',
            ),
            'class'       => 'closed',
            'tab-id'      => 'pymexpress',
        );
        /**
         * Correos de Costa Rica Label
         */
        $this->settings['pymexpress-label'] = array(
            'title'       => __( 'Label settings', 'mojito-shipping' ),
            'description' => __( 'This option allows you set a custom label for Cart and Checkout.', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'text',
                'label'   => __( 'Custom label', 'mojito-shipping' ),
                'name'    => 'label',
                'value'   => '',
                'tooltip' => __( 'Dynamic allowed values: <br>%rate% for shipping rate <br>%country% for destination country <br>%weight% for package weight <br>%weight-ccr% for package weight allowed by Correos de Costa Rica', 'mojito-shipping' ),
            )),
            'box-id'      => 'pymexpress-label',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'pymexpress',
            ),
            'class'       => 'closed',
            'tab-id'      => 'pymexpress',
        );
        /**
         * Cart and Checkout Settings
         */
        $this->settings['pymexpress-cart-and-checkout'] = array(
            'title'       => __( 'Cart and Checkout options', 'mojito-shipping' ),
            'description' => __( 'Options for Cart and Checkout.', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'select',
                'label'   => __( 'Cart Address preselection', 'mojito-shipping' ),
                'name'    => 'address-preselection',
                'options' => array(
                    'yes' => __( 'Yes', 'mojito-shipping' ),
                    'no'  => __( 'No', 'mojito-shipping' ),
                ),
                'value'   => 'yes',
                'tooltip' => __( 'Pre select San José in cart', 'mojito-shipping' ),
            )),
            'box-id'      => 'pymexpress-cart-and-checkout',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'pymexpress',
            ),
            'class'       => 'closed',
            'tab-id'      => 'pymexpress',
        );
        /**
         * WS Cache Control
         */
        $this->settings['pymexpress-ws-cache-control'] = array(
            'title'       => __( 'WS Cache settings', 'mojito-shipping' ),
            'description' => __( 'Save Pymexpress Web Service responses in cache.', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'select',
                'label'   => __( 'Enable Pymexpress WS Cache?', 'mojito-shipping' ),
                'name'    => 'allow-cache',
                'options' => array(
                    'no'  => __( 'No', 'mojito-shipping' ),
                    'yes' => __( 'Yes', 'mojito-shipping' ),
                ),
                'value'   => 'no',
            ), array(
                'type'    => 'number',
                'label'   => __( 'Lifetime', 'mojito-shipping' ),
                'name'    => 'lifetime',
                'tooltip' => __( 'Determines how many seconds the cache will live.', 'mojito-shipping' ),
                'value'   => 60,
            )),
            'box-id'      => 'pymexpress-ws-cache-control',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'pymexpress',
            ),
            'class'       => 'closed',
            'tab-id'      => 'pymexpress',
        );
        /**
         * Correos de Costa Rica simple Label
         */
        $this->settings['ccr-address-fields'] = array(
            'title'       => __( 'Address fields settings', 'mojito-shipping' ),
            'description' => __( 'Settings for address fields', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'select',
                'label'   => __( 'Hide zip code field', 'mojito-shipping' ),
                'name'    => 'show-zipcode',
                'options' => array(
                    'yes' => __( 'Show zip code field', 'mojito-shipping' ),
                    'no'  => __( 'Hide zip code field', 'mojito-shipping' ),
                ),
                'value'   => 'yes',
                'tooltip' => __( 'Show zip code field.', 'mojito-shipping' ),
            )),
            'box-id'      => 'ccr-address-fields',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr',
        );
        /**
         * Method / Carrier
         * Correos de Costa Rica Simple Settings
         */
        /**
         * Store and sendding settings
         */
        $this->settings['ccr-simple-store'] = array(
            'title'       => __( 'Your business settings', 'mojito-shipping' ),
            'description' => __( 'Set settings for your store.', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'select',
                'label'   => __( 'Store location', 'mojito-shipping' ),
                'name'    => 'location',
                'options' => array(
                    ''            => '',
                    'inside-gam'  => __( 'Inside GAM', 'mojito-shipping' ),
                    'outside-gam' => __( 'Outsite GAM', 'mojito-shipping' ),
                ),
                'tooltip' => __( 'Indicate if your store is located inside or outside the GAM', 'mojito-shipping' ),
            ), array(
                'type'    => 'select',
                'label'   => __( 'Service for local shipping', 'mojito-shipping' ),
                'name'    => 'local-shipping',
                'options' => array(
                    'disabled'    => '',
                    'pymexpress'  => __( 'Pymexpress', 'mojito-shipping' ),
                    'ems-courier' => __( 'EMS Courier', 'mojito-shipping' ),
                ),
                'tooltip' => __( 'Select the service to deliver the packages inside Costa Rica', 'mojito-shipping' ),
            )),
            'box-id'      => 'ccr-simple-store',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr-simple',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr-simple',
        );
        /**
         * Correos de Costa Rica Fixed rates
         */
        $this->settings['ccr-simple-fixed-rates'] = array(
            'title'       => __( 'Fixed rates', 'mojito-shipping' ),
            'description' => __( 'Set fixed rates. <span class="error">Caution: This will overwrite any other shipping rates calculation.</span>', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'select',
                'label'   => __( 'Enable fixed rates', 'mojito-shipping' ),
                'name'    => 'enable',
                'options' => array(
                    'disabled' => __( 'Disable fixed rates', 'mojito-shipping' ),
                    'enable'   => __( 'Enable fixed rates', 'mojito-shipping' ),
                ),
                'value'   => 'disabled',
                'tooltip' => __( 'This setting allows you to ensure fixed rates to GAM and Non-GAM shipping.', 'mojito-shipping' ),
            ), array(
                'type'    => 'number',
                'label'   => __( 'Rate for GAM', 'mojito-shipping' ),
                'name'    => 'gam-rate',
                'tooltip' => __( 'Set a fixed rate to GAM.', 'mojito-shipping' ),
                'value'   => 2000,
            ), array(
                'type'    => 'number',
                'label'   => __( 'Rate for non-GAM', 'mojito-shipping' ),
                'name'    => 'no-gam-rate',
                'tooltip' => __( 'Set a fixed rate to non-GAM.', 'mojito-shipping' ),
                'value'   => 3000,
            )),
            'box-id'      => 'ccr-simple-fixed-rates',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr-simple',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr-simple',
        );
        /**
         * Correos de Costa Rica Minimal amounts
         */
        $this->settings['ccr-simple-minimal'] = array(
            'title'       => __( 'Minimal amounts', 'mojito-shipping' ),
            'description' => __( 'Set the minimal amount to charge.', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'select',
                'label'   => __( 'Enable minimal amounts', 'mojito-shipping' ),
                'name'    => 'enable',
                'options' => array(
                    'disabled' => __( 'Disable the minimal amounts', 'mojito-shipping' ),
                    'enable'   => __( 'Enable the minimal amounts', 'mojito-shipping' ),
                ),
                'value'   => 'disabled',
                'tooltip' => __( 'This setting allows you to ensure a minimum shipping charge.', 'mojito-shipping' ),
            ), array(
                'type'    => 'number',
                'label'   => __( 'General minimal amount', 'mojito-shipping' ),
                'name'    => 'amount-general',
                'tooltip' => __( 'Set a minimum charge.', 'mojito-shipping' ),
                'value'   => 2000,
            )),
            'box-id'      => 'ccr-minimal',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr-simple',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr-simple',
        );
        /**
         * Correos de Costa Rica Exchange rate
         */
        $this->settings['ccr-simple-exchange-rate'] = array(
            'title'       => __( 'Exchange Rate', 'mojito-shipping' ),
            'description' => __( 'Due Correos de Costa Rica uses colones for its rates, this option allows you to sell in another currency and convert the final shipping rate to the currency established in your store, according to the exchange rate settings.', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'select',
                'label'   => __( 'Enable exchange rate', 'mojito-shipping' ),
                'name'    => 'enable',
                'options' => array(
                    'disabled' => __( 'Disable exchange rate', 'mojito-shipping' ),
                    'enable'   => __( 'Enable exchange rate', 'mojito-shipping' ),
                ),
                'value'   => 'disabled',
                'tooltip' => __( 'This option allows you to enable the exchange rate.', 'mojito-shipping' ),
            ), array(
                'type'    => 'number',
                'label'   => __( 'Exchange rate', 'mojito-shipping' ),
                'name'    => 'rate',
                'tooltip' => __( 'Please set up the dollar price in colones.', 'mojito-shipping' ),
                'value'   => 650,
            )),
            'box-id'      => 'ccr-simple-exchange-rate',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr-simple',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr-simple',
        );
        /**
         * Correos de Costa Rica Max simple Weight
         */
        $this->settings['ccr-simple-max-weight'] = array(
            'title'       => __( 'Max Weight settings', 'mojito-shipping' ),
            'description' => __( 'The maximum weight allowed by Correos de Costa Rica is 30,000 grams (30 kg), you can disable this shipping method when the maximum package weight is reached.', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'select',
                'label'   => __( 'Max Weight Control', 'mojito-shipping' ),
                'name'    => 'enable',
                'options' => array(
                    'disabled' => __( 'Disable Correos de Costa Rica when order total weight is over 30 kg', 'mojito-shipping' ),
                    'enable'   => __( 'Keep Correos de Costa Rica ENABLED when order total weight is over 30 kg', 'mojito-shipping' ),
                ),
                'value'   => 'disabled',
                'tooltip' => __( 'This option allows you to disable Correos de Costa Rica when order total weight is over 30000 g (30 kg).', 'mojito-shipping' ),
            )),
            'box-id'      => 'ccr-simple-max-weight',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr-simple',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr-simple',
        );
        /**
         * Correos de Costa Rica simple Label
         */
        $this->settings['ccr-simple-label'] = array(
            'title'       => __( 'Label settings', 'mojito-shipping' ),
            'description' => __( 'This option allows you set a custom label for Cart and Checkout.', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'text',
                'label'   => __( 'Custom label', 'mojito-shipping' ),
                'name'    => 'label',
                'value'   => '',
                'tooltip' => __( 'Dynamic allowed values: <br>%rate% for shipping rate <br>%country% for destination country <br>%weight% for package weight <br>%weight-ccr% for package weight allowed by Correos de Costa Rica', 'mojito-shipping' ),
            )),
            'box-id'      => 'ccr-simple-label',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr-simple',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr-simple',
        );
        /**
         * Method / Carrier
         * Simple Weight-based rates
         */
        $this->settings['simple-general'] = array(
            'title'       => __( 'Simple Weight-based rates settings', 'mojito-shipping' ),
            'description' => __( 'Set your weight-based rates settings', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'number',
                    'label'   => __( 'Rate per kg', 'mojito-shipping' ),
                    'name'    => 'rate-per-kg',
                    'tooltip' => __( 'Set the shipping rate per kg', 'mojito-shipping' ),
                    'value'   => 0,
                ),
                array(
                    'type'    => 'number',
                    'label'   => __( 'Rate per lbs', 'mojito-shipping' ),
                    'name'    => 'rate-per-lbs',
                    'tooltip' => __( 'Set the shipping rate per lbs', 'mojito-shipping' ),
                    'value'   => 0,
                ),
                array(
                    'type'    => 'number',
                    'label'   => __( 'Rate per g', 'mojito-shipping' ),
                    'name'    => 'rate-per-g',
                    'tooltip' => __( 'Set the shipping rate per g', 'mojito-shipping' ),
                    'value'   => 0,
                ),
                array(
                    'type'    => 'number',
                    'label'   => __( 'Rate per oz', 'mojito-shipping' ),
                    'name'    => 'rate-per-oz',
                    'tooltip' => __( 'Set the shipping rate per lb', 'mojito-shipping' ),
                    'value'   => 0,
                )
            ),
            'box-id'      => 'simple-general',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'simple',
            ),
            'class'       => 'closed',
            'tab-id'      => 'simple',
        );
        /**
         * Simple Weight-based Exchange rate
         */
        $this->settings['simple-exchange-rate'] = array(
            'title'       => __( 'Exchange Rate', 'mojito-shipping' ),
            'description' => __( 'This option allows you to sell in another currency and convert the final shipping rate to the currency established in your store, according to the exchange rate settings.', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'select',
                'label'   => __( 'Enable exchange rate', 'mojito-shipping' ),
                'name'    => 'enable',
                'options' => array(
                    'disabled' => __( 'Disable exchange rate', 'mojito-shipping' ),
                    'enable'   => __( 'Enable exchange rate', 'mojito-shipping' ),
                ),
                'value'   => 'disabled',
                'tooltip' => __( 'This option allows you to enable the exchange rate.', 'mojito-shipping' ),
            ), array(
                'type'    => 'number',
                'label'   => __( 'Exchange rate', 'mojito-shipping' ),
                'name'    => 'rate',
                'tooltip' => __( 'Please set up the dollar price in colones.', 'mojito-shipping' ),
                'value'   => 650,
            )),
            'box-id'      => 'simple-exchange-rate',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'simple',
            ),
            'class'       => 'closed',
            'tab-id'      => 'simple',
        );
        /**
         * Simple Weight-based Label
         */
        $this->settings['simple-label'] = array(
            'title'       => __( 'Label settings', 'mojito-shipping' ),
            'description' => __( 'This option allows you set a custom label for Cart and Checkout.', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'text',
                'label'   => __( 'Custom label', 'mojito-shipping' ),
                'name'    => 'label',
                'value'   => '',
                'tooltip' => __( 'Dynamic allowed values: <br>%rate% for shipping rate <br>%weight% for package weight', 'mojito-shipping' ),
            )),
            'box-id'      => 'simple-label',
            'require'     => array(
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'simple',
            ),
            'class'       => 'closed',
            'tab-id'      => 'simple',
        );
        /**
         * Information
         */
        $this->settings['info'] = array(
            'title'       => __( 'Information', 'mojito-shipping' ),
            'description' => __( 'Settings details.', 'mojito-shipping' ),
            'callback'    => array($this, 'information'),
            'box-id'      => 'info',
            'tab-id'      => 'information',
        );
        /**
         * Other settings
         */
        $this->settings['settings'] = array(
            'title'       => __( 'Debug', 'mojito-shipping' ),
            'description' => __( 'Enable debug', 'mojito-shipping' ),
            'inputs'      => array(array(
                'type'    => 'select',
                'label'   => __( 'Enable Debug', 'mojito-shipping' ),
                'name'    => 'debug',
                'options' => array(
                    'yes' => __( 'Yes', 'mojito-shipping' ),
                    'no'  => __( 'No', 'mojito-shipping' ),
                ),
                'tooltip' => __( 'Enable this option to log more data about plugin work', 'mojito-shipping' ),
            )),
            'box-id'      => 'settings',
            'tab-id'      => 'settings',
        );
    }

    /**
     * Preload "cantones" in settings
     *
     * @return array
     */
    public function pymexpress_load_cantones() {
        $provincia = get_option( 'mojito-shipping-pymexpress-store-province' );
        $address = new Mojito_Shipping_Address();
        $cantones = $address->get_pymexpress_cantons_list( $provincia );
        asort( $cantones );
        return $cantones;
    }

    /**
     * Preload "distritos" in settings
     *
     * @return array
     */
    public function pymexpress_load_distritos() {
        $provincia = (string) get_option( 'mojito-shipping-pymexpress-store-province' );
        $canton = (string) get_option( 'mojito-shipping-pymexpress-store-canton' );
        $address = new Mojito_Shipping_Address();
        $distritos = $address->get_pymexpress_districts_list( $provincia, $canton );
        return $distritos;
    }

    /**
     * Information method
     *
     * @return string $html
     */
    public function information() {
        $settings = $this->get_live_settings();
        $html = '';
        // translators: version.
        $html .= '<div>' . sprintf( __( 'Mojito Shipping version: %s', 'mojito-shipping' ), MOJITO_SHIPPING_VERSION ) . '</div>';
        $html .= '<h4>' . __( 'Carriers or methods enabled', 'mojito-shipping' ) . ':</h4>';
        $disclaimer = '<br><br>';
        $disclaimer .= '<hr>';
        $disclaimer_items = array();
        if ( !is_array( $settings['mojito-shipping-carrier-provider'] ) ) {
            $settings['mojito-shipping-carrier-provider'] = array();
        }
        if ( 0 === count( $settings['mojito-shipping-carrier-provider'] ) ) {
            $html .= '<p class="error">-- ' . __( 'No carrier or method is enabled', 'mojito-shipping' ) . '</p>';
            return $html;
        }
        /**
         * Carriers enabled
         */
        foreach ( $settings['mojito-shipping-carrier-provider'] as $key => $carrier ) {
            $carriers_labels[$carrier] = $this->tabs[$carrier];
        }
        $html .= $this->item_list( $carriers_labels );
        $html .= '<hr class="mojito-shipping-information-separator">';
        /**
         * Information for Correos de Costa Rica.
         */
        if ( in_array( 'ccr', $settings['mojito-shipping-carrier-provider'], true ) ) {
            $info_items = array();
            // translators: details.
            $html .= '<h4>' . sprintf( __( 'Details for "%s"', 'mojito-shipping' ), $this->tabs['ccr'] ) . '</h4>';
            /**
             * Check SoapClient
             */
            if ( class_exists( 'SoapClient' ) ) {
                $info_items[] = '<p class="success">' . __( 'SoapClient is enabled', 'mojito-shipping' ) . '</p>';
            } else {
                $info_items[] = '<p class="error">' . __( 'SoapClient is not enabled', 'mojito-shipping' ) . '</p>';
            }
            /**
             * Website IP Address
             */
            $item = '';
            if ( function_exists( 'wp_remote_get' ) ) {
                $my_ip = 'https://myip.mojitowp.com';
                $my_ip .= '?type=free&version=' . MOJITO_SHIPPING_VERSION;
                $response = wp_remote_get( $my_ip );
                if ( !$response instanceof \WP_Error ) {
                    $item .= '<p class="info">';
                    // translators: response body.
                    $item .= sprintf( __( 'Your website IP Address is %s, be sure this is whitelisted by Correos de Costa Rica', 'mojito-shipping' ), $response['body'] );
                    $item .= '</p>';
                } else {
                    if ( is_array( $response->errors ) ) {
                        foreach ( $response->errors as $key => $errors ) {
                            $item .= '<p class="error">';
                            // translators: key.
                            $item .= sprintf( __( 'There was an error when checking your website IP Address: %s', 'mojito-shipping' ), $key );
                            $item .= '</p>';
                            $item .= '<ul>';
                            foreach ( $errors as $k => $error ) {
                                $item .= '<li class="error"> - ' . $error . '</li>';
                            }
                            $item .= '</ul>';
                        }
                    }
                }
            }
            $info_items[] = $item;
            /**
             * Check Connection
             */
            $url = $settings['mojito-shipping-ccr-web-service-url'];
            /**
             * Is a valid URL ?
             */
            if ( filter_var( $url, FILTER_VALIDATE_URL ) ) {
                $connection_success = false;
                $response = '';
                $error_message = '';
                $custom_proxy = get_option( 'mojito-shipping-ccr-proxy-enable', 'false' );
                $mojito_proxy = get_option( 'mojito-shipping-ccr-mojito-proxy-enable', 'false' );
                /**
                 * There any Proxy settings?
                 */
                if ( 'true' === $custom_proxy || 'true' === $mojito_proxy ) {
                    $proxy_hostname = trim( get_option( 'mojito-shipping-ccr-proxy-ip' ) );
                    $proxy_username = trim( get_option( 'mojito-shipping-ccr-proxy-username' ) );
                    $proxy_password = trim( get_option( 'mojito-shipping-ccr-proxy-password' ) );
                    $proxy_port = trim( get_option( 'mojito-shipping-ccr-proxy-port' ) );
                    $proxy_label = 'Proxy ' . $proxy_hostname . ':' . $proxy_port;
                    /**
                     * CURL is used instead of wp_remote_get because a proxy connections settings are needed
                     */
                    $ch = curl_init();
                    curl_setopt( $ch, CURLOPT_URL, $url );
                    curl_setopt( $ch, CURLOPT_PROXY, $proxy_hostname );
                    curl_setopt( $ch, CURLOPT_PROXYPORT, $proxy_port );
                    curl_setopt( $ch, CURLOPT_PROXYUSERPWD, "{$proxy_username}:{$proxy_password}" );
                    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
                    curl_setopt( $ch, CURLOPT_HEADER, 1 );
                    curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
                    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
                    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
                    // Execute the request.
                    $output = curl_exec( $ch );
                    // Check for errors.
                    if ( curl_errno( $ch ) ) {
                        // translators: curl error.
                        $error_message = sprintf( __( 'The Web Service URL cannot be reached from your website, the error message is: %s', 'mojito-shipping' ), curl_error( $ch ) );
                    } else {
                        $response = curl_getinfo( $ch );
                        if ( 200 === $response['http_code'] ) {
                            $connection_success = true;
                            // translators: proxy data.
                            $error_message = sprintf(
                                __( 'URL del Web Service connection is OK through the %s', 'mojito-shipping' ),
                                $proxy_label,
                                $proxy_hostname,
                                $proxy_port
                            );
                        }
                    }
                    curl_close( $ch );
                } else {
                    $response = wp_remote_get( $url );
                    if ( is_array( $response ) && !is_wp_error( $response ) && 200 === $response['response']['code'] ) {
                        $connection_success = true;
                        $error_message = __( 'URL del Web Service connection is OK', 'mojito-shipping' );
                    } else {
                        $error = ( isset( $response->errors['http_request_failed'][0] ) ? $response->errors['http_request_failed'][0] : $response['response']['code'] . ' ' . $response['response']['message'] );
                        // translators: response.
                        $error_message = sprintf( __( 'The Web Service URL cannot be reached from your website, the error message is: %s', 'mojito-shipping' ), $error );
                    }
                }
                if ( $connection_success ) {
                    $info_items[] = '<p class="success">' . $error_message . '</p>';
                } else {
                    $info_items[] = '<p class="error">' . $error_message . '</p>';
                }
            }
            /**
             * Check Proxy Connection
             */
            if ( 'true' === get_option( 'mojito-shipping-ccr-mojito-proxy-enable', 'false' ) ) {
                $info_items[] = '<p class="success">' . __( 'Mojito Proxy is enabled.', 'mojito-shipping' ) . '</p>';
            } elseif ( 'true' === get_option( 'mojito-shipping-ccr-proxy-enable', 'false' ) ) {
                $info_items[] = '<p class="success">' . __( 'Proxy Connection is enabled.', 'mojito-shipping' ) . '</p>';
            } else {
                $info_items[] = '<p class="info">' . __( 'Proxy Connection disabled.', 'mojito-shipping' ) . '</p>';
            }
            /**
             * Check weight unit.
             */
            $weight_unit = get_option( 'woocommerce_weight_unit' );
            if ( 'g' === $weight_unit ) {
                $info_items[] = '<p class="info">' . __( 'WooCommerce weight unit is "g"', 'mojito-shipping' ) . '</p>';
            } elseif ( 'kg' === $weight_unit ) {
                $info_items[] = '<p class="info">' . __( 'WooCommerce weight unit is "kg", conversion will be kg => g (1kg = 1000g)', 'mojito-shipping' ) . '</p>';
            } elseif ( 'lbs' === $weight_unit ) {
                $info_items[] = '<p class="info">' . __( 'WooCommerce weight unit is "lbs", conversion will be lbs => g (1lbs = 453.59g)', 'mojito-shipping' ) . '</p>';
            } elseif ( 'oz' === $weight_unit ) {
                $info_items[] = '<p class="info">' . __( 'WooCommerce weight unit is "oz", conversion will be oz => g (1oz = 28.35g)', 'mojito-shipping' ) . '</p>';
            }
            $disclaimer_items[] = __( 'The rate calculation does not include the collection amount. (Service provided by Correos de Costa Rica)', 'mojito-shipping' );
            $disclaimer_items[] = __( 'The calculated cost is only an estimate and the price may change depending on the contract, cost table, rate adjustments, and final plant weight.', 'mojito-shipping' );
            $disclaimer_items[] = __( 'This information is intended to be used as an estimate.', 'mojito-shipping' );
            $disclaimer_items[] = __( 'The final cost of your bill may vary depending on the weight, area of origin and destination, policies in force at the time of shipment and the service contract.', 'mojito-shipping' );
            $disclaimer_items[] = __( 'Package delivery is Correos de Costa Rica\'s responsibility, Mojito Shipping can\'t guarantee the correct package delivery.', 'mojito-shipping' );
            $html .= $this->item_list( $info_items );
            $html .= '<hr class="mojito-shipping-information-separator">';
        }
        /**
         * Information for Correos de Costa Rica New System. (Pymexpress)
         */
        if ( in_array( 'pymexpress', $settings['mojito-shipping-carrier-provider'], true ) ) {
            $info_items = array();
            // translators: details.
            $html .= '<h4>' . sprintf( __( 'Details for "%s"', 'mojito-shipping' ), $this->tabs['pymexpress'] ) . '</h4>';
            /**
             * Check Curl
             */
            if ( function_exists( 'curl_version' ) ) {
                $info_items[] = '<p class="success">' . __( 'Curl is enabled', 'mojito-shipping' ) . '</p>';
            } else {
                $info_items[] = '<p class="error">' . __( 'Curl is not enabled', 'mojito-shipping' ) . '</p>';
            }
            /**
             * Check webservice settings
             */
            // Username.
            if ( empty( get_option( 'mojito-shipping-pymexpress-web-service-username' ) ) ) {
                $info_items[] = '<p class="error">' . __( 'Web Service Username is empty', 'mojito-shipping' ) . '</p>';
            } else {
                $info_items[] = '<p class="success">' . __( 'Web Service Username is set', 'mojito-shipping' ) . '</p>';
            }
            // Password.
            if ( empty( get_option( 'mojito-shipping-pymexpress-web-service-password' ) ) ) {
                $info_items[] = '<p class="error">' . __( 'Web Service Password is empty', 'mojito-shipping' ) . '</p>';
            } else {
                $info_items[] = '<p class="success">' . __( 'Web Service Password is set', 'mojito-shipping' ) . '</p>';
            }
            // User id.
            if ( empty( get_option( 'mojito-shipping-pymexpress-web-service-user-id' ) ) ) {
                $info_items[] = '<p class="error">' . __( 'Web Service User ID is empty', 'mojito-shipping' ) . '</p>';
            } else {
                $info_items[] = '<p class="success">' . __( 'Web Service User ID is set', 'mojito-shipping' ) . '</p>';
            }
            // Service id.
            if ( empty( get_option( 'mojito-shipping-pymexpress-web-service-service-id' ) ) ) {
                $info_items[] = '<p class="error">' . __( 'Web Service, Service ID is empty', 'mojito-shipping' ) . '</p>';
            } else {
                $info_items[] = '<p class="success">' . __( 'Web Service, Service ID is set', 'mojito-shipping' ) . '</p>';
            }
            // Client code.
            if ( empty( get_option( 'mojito-shipping-pymexpress-web-service-client-code' ) ) ) {
                $info_items[] = '<p class="error">' . __( 'Web Service Client Code is empty', 'mojito-shipping' ) . '</p>';
            } else {
                $info_items[] = '<p class="success">' . __( 'Web Service Client Code is set', 'mojito-shipping' ) . '</p>';
            }
            // translators: Sandbox or Producction.
            $info_items[] = '<p class="info">' . sprintf( __( 'Environment: %s', 'mojito-shipping' ), get_option( 'mojito-shipping-pymexpress-web-service-environment', 'sandbox' ) ) . '</p>';
            /**
             * Website IP Address
             */
            $item = '';
            if ( function_exists( 'wp_remote_get' ) ) {
                $my_ip = 'https://myip.mojitowp.com';
                $my_ip .= '?type=free&version=' . MOJITO_SHIPPING_VERSION;
                $response = wp_remote_get( $my_ip );
                if ( !$response instanceof \WP_Error ) {
                    $item .= '<p class="info">';
                    // translators: response body.
                    $item .= sprintf( __( 'Your website IP Address is %s, be sure this is whitelisted by Correos de Costa Rica', 'mojito-shipping' ), $response['body'] );
                    $item .= '</p>';
                } else {
                    if ( is_array( $response->errors ) ) {
                        foreach ( $response->errors as $key => $errors ) {
                            $item .= '<p class="error">';
                            // translators: key.
                            $item .= sprintf( __( 'There was an error when checking your website IP Address: %s', 'mojito-shipping' ), $key );
                            $item .= '</p>';
                            $item .= '<ul>';
                            foreach ( $errors as $k => $error ) {
                                $item .= '<li class="error"> - ' . $error . '</li>';
                            }
                            $item .= '</ul>';
                        }
                    }
                }
            }
            $info_items[] = $item;
            /**
             * Check Connection
             */
            $pymexpress_wc = new Mojito_Shipping_Method_Pymexpress_WSC();
            $token = $pymexpress_wc->get_token();
            if ( !empty( $token ) ) {
                // translators: proxy data.
                $error_message = sprintf( __( 'URL del Web Service connection is OK', 'mojito-shipping' ) );
                $info_items[] = '<p class="success">' . $error_message . '</p>';
            } else {
                // translators: curl error.
                $error_message = sprintf( __( 'The Web Service URL cannot be reached from your website', 'mojito-shipping' ) );
                $info_items[] = '<p class="error">' . $error_message . '</p>';
            }
            /**
             * Check Proxy Connection
             */
            if ( 'true' === get_option( 'mojito-shipping-pymexpress-mojito-proxy-enable', 'false' ) ) {
                $info_items[] = '<p class="success">' . __( 'Mojito Proxy is enabled.', 'mojito-shipping' ) . '</p>';
            } elseif ( 'true' === get_option( 'mojito-shipping-pymexpress-proxy-enable', 'false' ) ) {
                $info_items[] = '<p class="success">' . __( 'Proxy Connection is enabled.', 'mojito-shipping' ) . '</p>';
            } else {
                $info_items[] = '<p class="info">' . __( 'Proxy Connection disabled.', 'mojito-shipping' ) . '</p>';
            }
            /**
             * Check weight unit.
             */
            $weight_unit = get_option( 'woocommerce_weight_unit' );
            if ( 'g' === $weight_unit ) {
                $info_items[] = '<p class="info">' . __( 'WooCommerce weight unit is "g"', 'mojito-shipping' ) . '</p>';
            } elseif ( 'kg' === $weight_unit ) {
                $info_items[] = '<p class="info">' . __( 'WooCommerce weight unit is "kg", conversion will be kg => g (1kg = 1000g)', 'mojito-shipping' ) . '</p>';
            } elseif ( 'lbs' === $weight_unit ) {
                $info_items[] = '<p class="info">' . __( 'WooCommerce weight unit is "lbs", conversion will be lbs => g (1lbs = 453.59g)', 'mojito-shipping' ) . '</p>';
            } elseif ( 'oz' === $weight_unit ) {
                $info_items[] = '<p class="info">' . __( 'WooCommerce weight unit is "oz", conversion will be oz => g (1oz = 28.35g)', 'mojito-shipping' ) . '</p>';
            }
            $disclaimer_items[] = __( 'The rate calculation does not include the collection amount. (Service provided by Correos de Costa Rica)', 'mojito-shipping' );
            $disclaimer_items[] = __( 'The calculated cost is only an estimate and the price may change depending on the contract, cost table, rate adjustments, and final plant weight.', 'mojito-shipping' );
            $disclaimer_items[] = __( 'This information is intended to be used as an estimate.', 'mojito-shipping' );
            $disclaimer_items[] = __( 'The final cost of your bill may vary depending on the weight, area of origin and destination, policies in force at the time of shipment and the service contract.', 'mojito-shipping' );
            $disclaimer_items[] = __( 'Package delivery is Correos de Costa Rica\'s responsibility, Mojito Shipping can\'t guarantee the correct package delivery.', 'mojito-shipping' );
            $html .= $this->item_list( $info_items );
            $html .= '<hr class="mojito-shipping-information-separator">';
        }
        /**
         * Information for Correos de Costa Rica without integration
         */
        if ( in_array( 'ccr-simple', $settings['mojito-shipping-carrier-provider'], true ) ) {
            $info_items = array();
            // translators: details.
            $html .= '<h4>' . sprintf( __( 'Details for "%s"', 'mojito-shipping' ), $this->tabs['ccr-simple'] ) . '</h4>';
            /**
             * Check weight unit.
             */
            $weight_unit = get_option( 'woocommerce_weight_unit' );
            if ( 'g' === $weight_unit ) {
                $info_items[] = '<p class="info">' . __( 'WooCommerce weight unit is "g"', 'mojito-shipping' ) . '</p>';
            } elseif ( 'kg' === $weight_unit ) {
                $info_items[] = '<p class="info">' . __( 'WooCommerce weight unit is "kg", conversion will be kg => g (1kg = 1000g)', 'mojito-shipping' ) . '</p>';
            } elseif ( 'lbs' === $weight_unit ) {
                $info_items[] = '<p class="info">' . __( 'WooCommerce weight unit is "lbs", conversion will be lbs => g (1lbs = 453.59g)', 'mojito-shipping' ) . '</p>';
            } elseif ( 'oz' === $weight_unit ) {
                $info_items[] = '<p class="info">' . __( 'WooCommerce weight unit is "oz", conversion will be oz => g (1oz = 28.35g)', 'mojito-shipping' ) . '</p>';
            }
            $disclaimer_items[] = __( 'The rate calculation does not include the collection amount. (Service provided by Correos de Costa Rica)', 'mojito-shipping' );
            $disclaimer_items[] = __( 'The calculated cost is only an estimate and the price may change depending on the contract, cost table, rate adjustments, and final plant weight.', 'mojito-shipping' );
            $disclaimer_items[] = __( 'This information is intended to be used as an estimate.', 'mojito-shipping' );
            $disclaimer_items[] = __( 'The final cost of your bill may vary depending on the weight, area of origin and destination, policies in force at the time of shipment and the service contract.', 'mojito-shipping' );
            $disclaimer_items[] = __( 'Package delivery is Correos de Costa Rica\'s responsibility, Mojito Shipping can\'t guarantee the correct package delivery.', 'mojito-shipping' );
            $html .= $this->item_list( $info_items );
            $html .= '<hr class="mojito-shipping-information-separator">';
        }
        /**
         * Information for Simple
         */
        if ( in_array( 'simple', $settings['mojito-shipping-carrier-provider'], true ) ) {
            $info_items = array();
            // translators: details.
            $html .= '<h4>' . sprintf( __( 'Details for "%s"', 'mojito-shipping' ), $this->tabs['simple'] ) . '</h4>';
            /**
             * Check weight unit.
             */
            $weight_unit = get_option( 'woocommerce_weight_unit' );
            $info_items[] = '<p class="info">' . sprintf(
                // translators: details.
                __( 'WooCommerce weight unit is "%1$s", the rate to use is %2$s%3$s per %4$s', 'mojito-shipping' ),
                $weight_unit,
                \get_woocommerce_currency_symbol(),
                get_option( 'mojito-shipping-simple-general-rate-per-' . $weight_unit ),
                $weight_unit
            ) . '</p>';
            $disclaimer_items[] = __( 'This information is intended to be used as an estimate.', 'mojito-shipping' );
            $html .= $this->item_list( $info_items );
            $html .= '<hr class="mojito-shipping-information-separator">';
        }
        /**
         * Products information.
         */
        $html .= '<h4>' . __( 'Products information', 'mojito-shipping' ) . '</h4>';
        $html .= $this->check_products_weight();
        $html .= '<hr class="mojito-shipping-information-separator">';
        /**
         * Disclaimer items
         */
        $html .= '<h4>' . __( 'Disclaimer', 'mojito-shipping' ) . '</h4>';
        $disclaimer_items = array_unique( $disclaimer_items );
        $html .= $this->item_list( $disclaimer_items );
        return $html;
    }

    /**
     * Information method
     *
     * @return string $html
     */
    public function notices() {
        $html = '';
        $html .= '<p class="notice success">' . __( 'If you like this plugin, please write a few words about it at <a href="https://wordpress.org/plugins/mojito-shipping">wordpress.org</a>. Your opinion will help other people.', 'mojito-shipping' ) . '</p>';
        $html .= '<p class="notice success">' . __( 'If you want to know more about the PRO version, visit our website: <a href="https://mojitowp.com/">mojitowp.com</a>', 'mojito-shipping' ) . '</p>';
        $html .= '<p class="notice success">' . __( 'Need help with the settings?  Check our documentation: <a href="https://mojitowp.com/documentacion/pymexpress/">mojitowp.com/documentacion</a> or write us to support@mojitowp.com', 'mojito-shipping' ) . '</p>';
        return $html;
    }

    /**
     * Detect products weight
     *
     * @param string $weight_unit WooCommerce weight unit.
     * @return string html
     */
    private function check_products_weight( $weight_unit = null ) {
        /**
         * Check products weight.
         */
        $html = '';
        $info_items = array();
        $weights = array();
        $products_without_weight = array();
        $variations_without_weight = array();
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => 100,
        );
        $query = new \WP_Query($args);
        foreach ( $query->posts as $key => $p ) {
            $product = \wc_get_product( $p->ID );
            $weight = $product->get_weight();
            if ( $product instanceof \WC_Product_Variable ) {
                $variations = $product->get_available_variations();
                foreach ( $variations as $key => $variation ) {
                    $variation_id = $variation['variation_id'];
                    $variation = \wc_get_product( $variation_id );
                    $weight = $variation->get_weight();
                    if ( '' === $weight ) {
                        $variations_without_weight[] = array(
                            'product' => $p->ID,
                            'variant' => $variation_id,
                            'summary' => $variation->get_attribute_summary(),
                        );
                    } else {
                        if ( empty( $weights[$weight] ) ) {
                            $weights[$weight] = 1;
                        } else {
                            $weights[$weight]++;
                        }
                    }
                }
            } else {
                if ( '' === $weight ) {
                    $products_without_weight[] = $p->ID;
                } else {
                    if ( empty( $weights[$weight] ) ) {
                        $weights[$weight] = 1;
                    } else {
                        $weights[$weight]++;
                    }
                }
            }
        }
        ksort( $weights );
        reset( $weights );
        $first_key = key( $weights );
        end( $weights );
        $last_key = key( $weights );
        // translators: details.
        $info_items[] = '<p class="info">' . sprintf(
            __( 'Your lightest product weight %1$s%2$s and the heaviest %3$s%4$s.', 'mojito-shipping' ),
            $first_key,
            $weight_unit,
            $last_key,
            $weight_unit
        ) . '</p>';
        $total_products_without_weight = count( $products_without_weight );
        if ( $total_products_without_weight > 0 ) {
            // translators: total_products_without_weight.
            $info_items[] = '<p class="error">' . sprintf( __( 'You have at least %s product without weight', 'mojito-shipping' ), $total_products_without_weight ) . '</p>';
            $item = 0;
            $item_list = '';
            foreach ( $products_without_weight as $key => $product_id ) {
                $item_list .= '<a target="_blank" href="' . admin_url( 'post.php?post=' . $product_id ) . '&action=edit">' . $product_id . '</a>';
                ++$item;
                if ( $item < $total_products_without_weight ) {
                    $item_list .= ' - ';
                }
            }
            $info_items[] = $item_list;
        } else {
            $info_items[] = '<p class="success">' . __( 'All its products have defined weight', 'mojito-shipping' ) . '</p>';
        }
        $total_variations_without_weight = count( $variations_without_weight );
        if ( $total_variations_without_weight > 0 ) {
            // translators: total_variations_without_weight.
            $info_items[] = '<p class="error">' . sprintf( __( 'You have %s variants without weight', 'mojito-shipping' ), $total_variations_without_weight ) . '</p>';
            $item = 0;
            $item_list = '';
            foreach ( $variations_without_weight as $key => $product_ids ) {
                $item_list .= '<a target="_blank" href="' . admin_url( 'post.php?post=' . $product_ids['product'] ) . '&action=edit">' . $product_ids['variant'] . ' (' . $product_ids['summary'] . ')</a>';
                ++$item;
                if ( $item < $total_variations_without_weight ) {
                    $item_list .= ' - ';
                }
            }
            $info_items[] = $item_list;
        } else {
            $info_items[] = '<p class="success">' . __( 'All its products have defined weight', 'mojito-shipping' ) . '</p>';
        }
        $html .= $this->item_list( $info_items );
        return $html;
    }

}

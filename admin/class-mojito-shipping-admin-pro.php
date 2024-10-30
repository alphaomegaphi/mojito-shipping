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

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin Class for PRO version.
 */
class Mojito_Shipping_Admin_Pro extends Mojito_Shipping_Admin {

    /**
     * Constructor
     */
    public function __construct() {

        $carriers = get_option( 'mojito-shipping-carrier-provider' );

        if ( ! is_array( $carriers ) ) {
            $carriers = array();
        }

        if ( 'yes' === get_option( 'mojito-shipping-ccr-pdf-export-in-orders-list', 'yes' ) ) {
            if ( in_array( 'ccr', $carriers, true ) ) {
                $this->add_guide_column( 'ccr' );
            }
        }

        if ( 'yes' === get_option( 'mojito-shipping-pymexpress-pdf-export-in-orders-list', 'yes' ) ) {
            if ( in_array( 'pymexpress', $carriers, true ) ) {
                $this->add_guide_column( 'pymexpress' );
            }
        }
    }

    /**
     * Add Column to download PDF.
     *
     * @param string $variant Carrier variant.
     * @return void
     */
    public function add_guide_column( $variant = 'pymexpress' ) {

        // Add download button to orders lists.
        add_filter(
            'manage_edit-shop_order_columns',
            function ( $columns ) use ( $variant ) {
                $title = __( 'Guide', 'mojito-shipping' );
                if ( 'pymexpress' === $variant ) {
                    $title = __( 'Pymexpress', 'mojito-shipping' );
                }
                $columns[ 'mojito_shipping_' . $variant . '_guide' ] = $title;
                return $columns;
            }
        );

        add_action(
            'manage_shop_order_posts_custom_column',
            function( $column ) use ( $variant ) {

                global $post;

                if ( 'mojito_shipping_' . $variant . '_guide' === $column ) {

                    $order = wc_get_order( $post->ID );

                    if ( ! $order->has_shipping_method( 'mojito_shipping_' . $variant ) ) {
                        return;
                    }

                    $guide_number = $order->get_meta( 'mojito_shipping_' . $variant . '_guide_number', true );
                    $reponse_code = $order->get_meta( 'mojito_shipping_' . $variant . '_ccrRegistroEnvio_response_code', true );
                    $html         = '<div class="mojito-' . $variant . '-ws">';

                    if ( empty( $guide_number ) ) {
                        $html .= '<a id="' . $order->get_id() . '" class="mojito-shipping-' . $variant . '-manual-request"> ' . __( 'Click to request. ', 'mojito-shipping' ) . '</a>';

                    } elseif ( ! empty( $reponse_code ) && ( '00' !== $reponse_code && '36' !== $reponse_code ) ) {
                        $html .= '<a id="' . $order->get_id() . '" class="mojito-shipping-' . $variant . '-manual-register"> ' . __( 'Click to request. ', 'mojito-shipping' ) . '</a>';

                    } else {

                        $html .= '<p> ' . $guide_number . ' </p>';
                        $html .= '<a id="' . $order->get_id() . '" class="download mojito-shipping-' . $variant . '-download-pdf">';
                        $html .= '<img src="' . plugin_dir_url( __DIR__ ) . 'admin/img/download.svg">';
                        $html .= '</a>';
                    }
                    $html .= '</div>';
                    echo $html;
                }
            }
        );
    }

    /**
     * Set Settings
     */
    public function set_settings() {

        parent::set_settings();

        $this->help_links = array_merge(
            $this->help_links,
            // Correos de Costa Rica with web service.
            array(
                'ccr-mojito-proxy'   => 'https://mojitowp.com/documentacion/sistema-saliente/#3.11',
                'ccr-logo'           => 'https://mojitowp.com/documentacion/sistema-saliente/#3.12',
                'ccr-tracking'       => 'https://mojitowp.com/documentacion/sistema-saliente/#3.13',
                'ccr-pdf-export'     => 'https://mojitowp.com/documentacion/sistema-saliente/#3.14',
                'ccr-shipping-rules' => 'https://mojitowp.com/documentacion/sistema-saliente/#3.16',
            ),
            // Correos de Costa Rica without web service.
            array(
                'ccr-simple-logo' => 'https://mojitowp.com/documentacion/sistema-saliente/#3.12',
            ),
            // Correos de Costa Rica with new web service scheme.
            array(
                'pymexpress-minimal'        => 'https://mojitowp.com/documentacion/pymexpress/#3.6',
                'pymexpress-mojito-proxy'   => 'https://mojitowp.com/documentacion/pymexpress/#3.11',
                'pymexpress-logo'           => 'https://mojitowp.com/documentacion/pymexpress/#3.12',
                'pymexpress-tracking'       => 'https://mojitowp.com/documentacion/pymexpress/#3.13',
                'pymexpress-pdf-export'     => 'https://mojitowp.com/documentacion/pymexpress/#3.14',
                'pymexpress-shipping-rules' => 'https://mojitowp.com/documentacion/pymexpress/#3.16',
                'pymexpress-location-rules' => 'https://mojitowp.com/documentacion/pymexpress/#3.17',
                'pymexpress-cron-control'   => 'https://mojitowp.com/documentacion/pymexpress/#3.18',
                'pymexpress-ws-cache-control'   => 'https://mojitowp.com/documentacion/pymexpress/#3.20',
            ),
        );

        /**
         * Carrier settings
         */
        $this->settings['carrier'] = array(
            'title'       => __( 'Carrier settings', 'mojito-shipping' ),
            'description' => __( 'Select your Carrier', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'      => 'multiselect',
                    'label'     => __( 'Carrier', 'mojito-shipping' ),
                    'name'      => 'provider',
                    'options'   => array(
                        'simple'     => __( 'Simple weight-based rates', 'mojito-shipping' ),
                        //'ccr'        => 'Correos de Costa Rica',
                        'pymexpress' => __( 'Correos de Costa Rica - Pymexpress', 'mojito-shipping' ),
                        'ccr-simple' => __( 'Correos de Costa Rica without integration', 'mojito-shipping' ),
                    ),
                    'tooltip'   => __( 'Select your service provider', 'mojito-shipping' ),
                    'data-type' => 'array',
                ),
            ),
            'box-id'      => 'carrier',
            'tab-id'      => 'general',
        );

        /**
         * Method / Carrier
         * Correos de Costa Rica Settings
         */
        /**
         * Store and sedding settings
         */
        $this->settings['ccr-store'] = array(
            'title'       => __( 'Your business settings', 'mojito-shipping' ),
            'description' => __( 'Set settings for your store.', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'select',
                    'label'   => __( 'Store location', 'mojito-shipping' ),
                    'name'    => 'location',
                    'options' => array(
                        ''            => '',
                        'inside-gam'  => __( 'Inside GAM', 'mojito-shipping' ),
                        'outside-gam' => __( 'Outsite GAM', 'mojito-shipping' ),
                    ),
                    'tooltip' => __( 'Indicate if your store is located inside or outside the GAM', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Service for local shipping', 'mojito-shipping' ),
                    'name'    => 'local-shipping',
                    'options' => array(
                        'disabled'    => '',
                        'pymexpress'  => __( 'Pymexpress', 'mojito-shipping' ),
                        'ems-courier' => __( 'EMS Courier', 'mojito-shipping' ),
                    ),
                    'tooltip' => __( 'Select the service to deliver the packages inside Costa Rica', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Service for international shipping', 'mojito-shipping' ),
                    'name'    => 'international-shipping',
                    'options' => array(
                        'disabled'                         => '',
                        'exporta-facil'                    => __( 'Exporta Fácil', 'mojito-shipping' ),
                        'ems-premium'                      => __( 'EMS Premium', 'mojito-shipping' ),
                        'correo-internacional-prioritario' => __( 'Correo Internacional Prioritario', 'mojito-shipping' ),
                        'correo-internacional-no-prioritario' => __( 'Correo No Internacional Prioritario', 'mojito-shipping' ),
                        'correo-internacional-prioritario-certificado' => __( 'Correo Internacional Prioritario con Certificado', 'mojito-shipping' ),
                    ),
                    'tooltip' => __( 'Select the service to deliver the packages outside Costa Rica', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Add IVA calculation', 'mojito-shipping' ),
                    'name'    => 'iva-ccr',
                    'options' => array(
                        'yes' => __( 'Yes, I must pay IVA to Correos de Costa Rica', 'mojito-shipping' ),
                        'no'  => __( 'No, my business is IVA exempt', 'mojito-shipping' ),
                    ),
                    'value'   => 'yes',
                    'tooltip' => __( 'Please be sure that your business has an IVA exemption into Correos de Costa Rica system.', 'mojito-shipping' ),
                ),
            ),
            'box-id'      => 'ccr-store',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'ccr' will show this setting.
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr',
        );

        /**
         * Mojito Proxy
         */
        $this->settings['ccr-mojito-proxy'] = array(
            'title'       => __( 'Use Mojito Proxy Connection', 'mojito-shipping' ),
            'description' => __( 'Use the proxy provided by Mojito. Please write to us with your website IP address to enable access.', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'select',
                    'label'   => __( 'Enable Mojito Proxy', 'mojito-shipping' ),
                    'name'    => 'enable',
                    'options' => array(
                        'false' => __( 'Disabled', 'mojito-shipping' ),
                        'true'  => __( 'Enabled', 'mojito-shipping' ),
                    ),
                    'tooltip' => __( 'This setting will overwrite the Proxy Connection Settings.', 'mojito-shipping' ),
                ),
            ),
            'box-id'      => 'ccr-mojito-proxy',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'ccr' will show this setting.
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
            'title'       => __( 'Minimal amounts and rounding', 'mojito-shipping' ),
            'description' => __( 'Set the minimal amount to charge.', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'select',
                    'label'   => __( 'Round the final amount', 'mojito-shipping' ),
                    'name'    => 'round-the-amount',
                    'options' => array(
                        'do-not-round'           => __( 'Do not round the amount', 'mojito-shipping' ),
                        'round-to-the-next-10'   => __( 'Round to the next 10 (eg: 3203.56 => 3210)', 'mojito-shipping' ),
                        'round-to-the-next-100'  => __( 'Round to the next 100 (eg: 3203.56 => 3300)', 'mojito-shipping' ),
                        'round-to-the-next-500'  => __( 'Round to the next 500 (eg: 3203.56 => 3500)', 'mojito-shipping' ),
                        'round-to-the-next-1000' => __( 'Round to the next 1000 (eg: 3203.56 => 4000)', 'mojito-shipping' ),
                    ),
                    'value'   => 'yes',
                    'tooltip' => __( 'Round the final amount of the shipment to facilitate the reading of the amount.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Enable minimal amounts', 'mojito-shipping' ),
                    'name'    => 'enable',
                    'options' => array(
                        'disabled' => __( 'Disable the minimal amounts', 'mojito-shipping' ),
                        'enable'   => __( 'Enable the minimal amounts', 'mojito-shipping' ),
                    ),
                    'value'   => 'disabled',
                    'tooltip' => __( 'This setting allows you to ensure a minimum shipping charge.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'number',
                    'label'   => __( 'General minimal amount', 'mojito-shipping' ),
                    'name'    => 'amount-general',
                    'tooltip' => __( 'Set a minimum charge.', 'mojito-shipping' ),
                    'value'   => 2000,
                ),
                array(
                    'type'    => 'number',
                    'label'   => __( 'Minimal amount for local Shipping inside the GAM', 'mojito-shipping' ),
                    'name'    => 'amount-inside-gam',
                    'tooltip' => __( 'Set a minimum charge for your local shipping in the GAM.', 'mojito-shipping' ),
                    'value'   => 2000,
                ),
                array(
                    'type'    => 'number',
                    'label'   => __( 'Minimal amount for local Shipping outside the GAM', 'mojito-shipping' ),
                    'name'    => 'amount-outside-gam',
                    'tooltip' => __( 'Set a minimum charge for your local shipping out the GAM.', 'mojito-shipping' ),
                    'value'   => 3000,
                ),
                array(
                    'type'    => 'number',
                    'label'   => __( 'Minimal amount for international Shipping', 'mojito-shipping' ),
                    'name'    => 'amount-international',
                    'tooltip' => __( 'Set a minimum charge for your international shipping.', 'mojito-shipping' ),
                    'value'   => 20000,
                ),
            ),
            'box-id'      => 'ccr-minimal',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'ccr' will show this setting.
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr',
        );

        /**
         * Correos de Costa Rica Logo
         */
        $this->settings['ccr-logo'] = array(
            'title'       => __( 'Correos de Costa Rica Logo', 'mojito-shipping' ),
            'description' => __( 'Show the logo of Correos de Costa Rica. Correos de Costa Rica owns all rights to its logo, brand, and others. ', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'select',
                    'label'   => __( 'Show Correos de Costa Rica logo', 'mojito-shipping' ),
                    'name'    => 'logo-ccr',
                    'options' => array(
                        'yes' => __( 'Yes, show logo', 'mojito-shipping' ),
                        'no'  => __( 'No, do not show the logo', 'mojito-shipping' ),
                    ),
                    'value'   => 'no',
                    'tooltip' => __( 'Show logo in Cart and Checkout', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Link logo to Correos de Costa Rica service page.', 'mojito-shipping' ),
                    'name'    => 'link',
                    'options' => array(
                        'yes' => __( 'Yes', 'mojito-shipping' ),
                        'no'  => __( 'No', 'mojito-shipping' ),
                    ),
                    'value'   => 'yes',
                    'tooltip' => __( 'This option allows you to enable the link to Correos de Costa Rica website.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Logo size', 'mojito-shipping' ),
                    'name'    => 'size',
                    'options' => array(
                        'full'  => __( 'Full size', 'mojito-shipping' ),
                        '320px' => __( '320px width', 'mojito-shipping' ),
                        '200px' => __( '200px width', 'mojito-shipping' ),
                        '120px' => __( '120px width', 'mojito-shipping' ),
                    ),
                    'value'   => 'full',
                    'tooltip' => __( 'Select the size to show.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Show logo before or after the label', 'mojito-shipping' ),
                    'name'    => 'position',
                    'options' => array(
                        'before' => __( 'Show the logo Before the label and price', 'mojito-shipping' ),
                        'after'  => __( 'Show the logo After the label and price', 'mojito-shipping' ),
                    ),
                    'value'   => 'after',
                    'tooltip' => __( 'This option allows you to set the position of the logo', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'text',
                    'label'   => __( 'Logo custom CSS Class', 'mojito-shipping' ),
                    'name'    => 'css-class',
                    'value'   => '',
                    'tooltip' => __( 'Custom CSS Class for the logo', 'mojito-shipping' ),
                ),
            ),
            'box-id'      => 'ccr-logo',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'ccr' will show this setting.
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr',
        );

        /**
         * Tracking
         */
        $this->settings['ccr-tracking'] = array(
            'title'       => __( 'Show Tracking Details', 'mojito-shipping' ),
            'description' => __( 'Tracking only works for the production environment.', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'select',
                    'label'   => __( 'Show tracking in user order details', 'mojito-shipping' ),
                    'name'    => 'user-order-details',
                    'options' => array(
                        'no'  => __( 'No', 'mojito-shipping' ),
                        'yes' => __( 'Yes', 'mojito-shipping' ),
                    ),
                    'value'   => 'no',
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Show tracking in admin order details', 'mojito-shipping' ),
                    'name'    => 'admin-order-details',
                    'options' => array(
                        'no'  => __( 'No', 'mojito-shipping' ),
                        'yes' => __( 'Yes', 'mojito-shipping' ),
                    ),
                    'value'   => 'no',
                ),
            ),
            'box-id'      => 'ccr-tracking',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'ccr' will show this setting.
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr',
        );

        /**
         * Free Shipping rules
         */
        $this->settings['ccr-shipping-rules'] = array(
            'title'       => __( 'Free and discount shipping rules', 'mojito-shipping' ),
            'description' => __( 'Offer free shipping to your clients when they buy a certain amount or weight.', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'select',
                    'label'   => __( 'Free Shipping rule mode', 'mojito-shipping' ),
                    'name'    => 'rule-mode',
                    'options' => array(
                        'disabled'           => __( 'Free Shipping disabled', 'mojito-shipping' ),
                        'min-amount'         => __( 'Requires a minimum order amount ', 'mojito-shipping' ),
                        'min-weight'         => __( 'Requires a minimum order weight ', 'mojito-shipping' ),
                        'min-products-count' => __( 'Requires a minimum item order count ', 'mojito-shipping' ),
                        'max-amount'         => __( 'Requires a maximum order amount ', 'mojito-shipping' ),
                        'max-weight'         => __( 'Requires a maximum order weight ', 'mojito-shipping' ),
                        'max-products-count' => __( 'Requires a maximum item order count ', 'mojito-shipping' ),
                    ),
                    'value'   => 'disabled',
                ),
                array(
                    'type'    => 'number',
                    'label'   => __( 'Minimal or maximum.', 'mojito-shipping' ),
                    'name'    => 'minmax',
                    'tooltip' => __( 'Enter the minimal or maximum items count, weight or amount to apply the free shipping rule.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Free Shipping discount mode', 'mojito-shipping' ),
                    'name'    => 'discount-mode',
                    'options' => array(
                        'fixed'   => __( 'Fixed amount', 'mojito-shipping' ),
                        'percent' => __( 'Percent discount ', 'mojito-shipping' ),
                    ),
                    'value'   => 'percent',
                ),
                array(
                    'type'    => 'number',
                    'label'   => __( 'Discount (Percent or amount).', 'mojito-shipping' ),
                    'name'    => 'discount',
                    'value'   => '100',
                    'tooltip' => '',
                ),
            ),
            'box-id'      => 'ccr-shipping-rules',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'ccr' will show this setting.
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr',
        );

        /**
         * PDF Export
         */
        $this->settings['ccr-pdf-export'] = array(
            'title'       => __( 'PDF export settings', 'mojito-shipping' ),
            'description' => __( 'Settings to export order data and guide number in PDF', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'select',
                    'label'   => __( 'PDF Content', 'mojito-shipping' ),
                    'name'    => 'content',
                    'options' => array(
                        'full'         => __( 'Full order data', 'mojito-shipping' ),
                        'minimal'      => __( 'Minimal order data', 'mojito-shipping' ),
                        'only-barcode' => __( 'Only barcode', 'mojito-shipping' ),
                    ),
                    'value'   => 'full',
                    'tooltip' => __( 'The option "Full order data" include Guide number, Sender data, Recipient data, and the Package details. The option "Minimal" will export the Barcode, the Guide number and Recipient data. The option "Only barcode" will export the Guide number and barcode only.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'include order products?', 'mojito-shipping' ),
                    'name'    => 'order-content',
                    'options' => array(
                        'no'  => __( 'No', 'mojito-shipping' ),
                        'yes' => __( 'Yes', 'mojito-shipping' ),
                    ),
                    'value'   => 'no',
                    'tooltip' => __( 'If yes, the PDF will show the products in the order. This option requires "Full order data" in the PDF Content option.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Show Correos de Costa Rica Logo', 'mojito-shipping' ),
                    'name'    => 'ccr-logo',
                    'options' => array(
                        'yes' => __( 'Yes', 'mojito-shipping' ),
                        'no'  => __( 'No', 'mojito-shipping' ),
                    ),
                    'value'   => 'no',
                    'tooltip' => __( 'Include logo in the PDF.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Show Site Logo', 'mojito-shipping' ),
                    'name'    => 'site-logo',
                    'options' => array(
                        'yes' => __( 'Yes', 'mojito-shipping' ),
                        'no'  => __( 'No', 'mojito-shipping' ),
                    ),
                    'value'   => 'no',
                    'tooltip' => __( 'Include site logo in the PDF.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Download PDF available in customer order details', 'mojito-shipping' ),
                    'name'    => 'in-customer-order',
                    'options' => array(
                        'yes' => __( 'Yes', 'mojito-shipping' ),
                        'no'  => __( 'No', 'mojito-shipping' ),
                    ),
                    'value'   => 'no',
                    'tooltip' => __( 'If Yes, an option to download the PDF will be available in the customer order details.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Download PDF available in orders list', 'mojito-shipping' ),
                    'name'    => 'in-orders-list',
                    'options' => array(
                        'yes' => __( 'Yes', 'mojito-shipping' ),
                        'no'  => __( 'No', 'mojito-shipping' ),
                    ),
                    'value'   => 'yes',
                    'tooltip' => __( 'If Yes, an option to download the PDF will be available in the orders list.', 'mojito-shipping' ),
                ),
            ),
            'box-id'      => 'ccr-pdf-export',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'ccr' will show this setting.
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr',
        );

        /**
         * Correos de Costa Rica - Pymexpress New System
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
                    'class'       => 'required', // Needs placeholder.
                ),
                array(
                    'type'        => 'password',
                    'label'       => __( 'Password', 'mojito-shipping' ),
                    'name'        => 'password',
                    'placeholder' => __( 'Web Service Password', 'mojito-shipping' ),
                    'class'       => 'required', // Needs placeholder.
                ),
                array(
                    'type'        => 'text',
                    'label'       => __( 'User ID', 'mojito-shipping' ),
                    'name'        => 'user-id',
                    'placeholder' => __( 'Web Service User ID', 'mojito-shipping' ),
                    'class'       => 'required', // Needs placeholder.
                ),
                array(
                    'type'        => 'text',
                    'label'       => __( 'Service ID', 'mojito-shipping' ),
                    'name'        => 'service-id',
                    'placeholder' => __( 'Web Service, Service ID', 'mojito-shipping' ),
                    'class'       => 'required', // Needs placeholder.
                ),
                array(
                    'type'        => 'text',
                    'label'       => __( 'Client code', 'mojito-shipping' ),
                    'name'        => 'client-code',
                    'placeholder' => __( 'Web Service Client Code', 'mojito-shipping' ),
                    'class'       => 'required', // Needs placeholder.
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'System', 'mojito-shipping' ),
                    'name'    => 'system',
                    'options' => array(
                        'PYMEXPRESS'  => __( 'Pymexpress', 'mojito-shipping' ),
                        'CORPORATIVO' => __( 'Corporativo', 'mojito-shipping' ),
                    ),
                    'value'   => 'PYMEXPRESS',
                    'tooltip' => __( '', 'mojito-shipping' ),
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
                ),
            ),
            'box-id'      => 'pymexpress-web-service',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'pymexpress' will show this setting.
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'pymexpress',
            ),
            'class'       => 'closed',
            'tab-id'      => 'pymexpress',
        );
        /**
         * Store and sedding settings
         */
        $this->settings['pymexpress-store'] = array(
            'title'       => __( 'Your business settings', 'mojito-shipping' ),
            'description' => __( 'Set settings for your store. Please fill, save and check the connection settings before set the locations settings.', 'mojito-shipping' ),
            'inputs'      => array(
                array(
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
                    'tooltip'     => __( 'Indicate your store\'s province', 'mojito-shipping' ),
                    'placeholder' => __( 'Set the province', 'mojito-shipping' ),
                    'class'       => 'required', // Needs placeholder.
                ),
                array(
                    'type'        => 'select',
                    'label'       => __( 'Canton', 'mojito-shipping' ),
                    'name'        => 'canton',
                    'options'     => $this->pymexpress_load_cantones(),
                    'tooltip'     => __( 'Indicate your store\'s canton', 'mojito-shipping' ),
                    'placeholder' => __( 'Set the canton', 'mojito-shipping' ),
                    'class'       => 'required', // Needs placeholder.
                ),
                array(
                    'type'        => 'select',
                    'label'       => __( 'District', 'mojito-shipping' ),
                    'name'        => 'district',
                    'options'     => $this->pymexpress_load_distritos(),
                    'tooltip'     => __( 'Indicate your store\'s district', 'mojito-shipping' ),
                    'placeholder' => __( 'Set the district', 'mojito-shipping' ),
                    'class'       => 'required', // Needs placeholder.
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Add IVA calculation', 'mojito-shipping' ),
                    'name'    => 'iva-ccr',
                    'options' => array(
                        'yes' => __( 'Yes, I must pay IVA to Correos de Costa Rica', 'mojito-shipping' ),
                        'no'  => __( 'No, my business is IVA exempt', 'mojito-shipping' ),
                    ),
                    'value'   => 'yes',
                    'tooltip' => __( 'Please be sure that your business has an IVA exemption into Correos de Costa Rica system.', 'mojito-shipping' ),
                ),
            ),
            'box-id'      => 'pymexpress-store',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'pymexpress' will show this setting.
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'pymexpress',
            ),
            'class'       => 'closed',
            'tab-id'      => 'pymexpress',
        );

        /**
         * Mojito Proxy
         */
        $this->settings['pymexpress-mojito-proxy'] = array(
            'title'       => __( 'Use Mojito Proxy Connection', 'mojito-shipping' ),
            'description' => __( 'Use the proxy provided by Mojito. Please write to us with your website IP address to enable access.', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'select',
                    'label'   => __( 'Enable Mojito Proxy', 'mojito-shipping' ),
                    'name'    => 'enable',
                    'options' => array(
                        'false' => __( 'Disabled', 'mojito-shipping' ),
                        'true'  => __( 'Enabled', 'mojito-shipping' ),
                    ),
                    'tooltip' => __( 'This setting will overwrite the Proxy Connection Settings.', 'mojito-shipping' ),
                ),
            ),
            'box-id'      => 'pymexpress-mojito-proxy',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'pymexpress' will show this setting.
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'pymexpress',
            ),
            'class'       => 'closed',
            'tab-id'      => 'pymexpress',
        );

        /**
         * Correos de Costa Rica Minimal amounts
         */
        $this->settings['pymexpress-minimal'] = array(
            'title'       => __( 'Minimal amounts and rounding', 'mojito-shipping' ),
            'description' => __( 'Set the minimal amount to charge.', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'select',
                    'label'   => __( 'Round the final amount', 'mojito-shipping' ),
                    'name'    => 'round-the-amount',
                    'options' => array(
                        'do-not-round'           => __( 'Do not round the amount', 'mojito-shipping' ),
                        'round-to-the-next-10'   => __( 'Round to the next 10 (eg: 3203.56 => 3210)', 'mojito-shipping' ),
                        'round-to-the-next-100'  => __( 'Round to the next 100 (eg: 3203.56 => 3300)', 'mojito-shipping' ),
                        'round-to-the-next-500'  => __( 'Round to the next 500 (eg: 3203.56 => 3500)', 'mojito-shipping' ),
                        'round-to-the-next-1000' => __( 'Round to the next 1000 (eg: 3203.56 => 4000)', 'mojito-shipping' ),
                    ),
                    'value'   => 'yes',
                    'tooltip' => __( 'Round the final amount of the shipment to facilitate the reading of the amount.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Enable minimal amounts', 'mojito-shipping' ),
                    'name'    => 'enable',
                    'options' => array(
                        'disabled' => __( 'Disable the minimal amounts', 'mojito-shipping' ),
                        'enable'   => __( 'Enable the minimal amounts', 'mojito-shipping' ),
                    ),
                    'value'   => 'disabled',
                    'tooltip' => __( 'This setting allows you to ensure a minimum shipping charge.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'number',
                    'label'   => __( 'General minimal amount', 'mojito-shipping' ),
                    'name'    => 'amount-general',
                    'tooltip' => __( 'Set a minimum charge.', 'mojito-shipping' ),
                    'value'   => 2000,
                ),
                array(
                    'type'    => 'number',
                    'label'   => __( 'Minimal amount for local Shipping inside the GAM', 'mojito-shipping' ),
                    'name'    => 'amount-inside-gam',
                    'tooltip' => __( 'Set a minimum charge for your local shipping in the GAM.', 'mojito-shipping' ),
                    'value'   => 2000,
                ),
                array(
                    'type'    => 'number',
                    'label'   => __( 'Minimal amount for local Shipping outside the GAM', 'mojito-shipping' ),
                    'name'    => 'amount-outside-gam',
                    'tooltip' => __( 'Set a minimum charge for your local shipping out the GAM.', 'mojito-shipping' ),
                    'value'   => 3000,
                ),
            ),
            'box-id'      => 'pymexpress-minimal',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'pymexpress' will show this setting.
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'pymexpress',
            ),
            'class'       => 'closed',
            'tab-id'      => 'pymexpress',
        );

        /**
         * Correos de Costa Rica Logo
         */
        $this->settings['pymexpress-logo'] = array(
            'title'       => __( 'Correos de Costa Rica Logo', 'mojito-shipping' ),
            'description' => __( 'Show the logo of Correos de Costa Rica. Correos de Costa Rica owns all rights to its logo, brand, and others. ', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'select',
                    'label'   => __( 'Show Correos de Costa Rica logo', 'mojito-shipping' ),
                    'name'    => 'logo-ccr',
                    'options' => array(
                        'yes' => __( 'Yes, show logo', 'mojito-shipping' ),
                        'no'  => __( 'No, do not show the logo', 'mojito-shipping' ),
                    ),
                    'value'   => 'no',
                    'tooltip' => __( 'Show logo in Cart and Checkout', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Link logo to Correos de Costa Rica service page.', 'mojito-shipping' ),
                    'name'    => 'link',
                    'options' => array(
                        'yes' => __( 'Yes', 'mojito-shipping' ),
                        'no'  => __( 'No', 'mojito-shipping' ),
                    ),
                    'value'   => 'yes',
                    'tooltip' => __( 'This option allows you to enable the link to Correos de Costa Rica website.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Logo size', 'mojito-shipping' ),
                    'name'    => 'size',
                    'options' => array(
                        'full'  => __( 'Full size', 'mojito-shipping' ),
                        '320px' => __( '320px width', 'mojito-shipping' ),
                        '200px' => __( '200px width', 'mojito-shipping' ),
                        '120px' => __( '120px width', 'mojito-shipping' ),
                    ),
                    'value'   => 'full',
                    'tooltip' => __( 'Select the size to show.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Show logo before or after the label', 'mojito-shipping' ),
                    'name'    => 'position',
                    'options' => array(
                        'before' => __( 'Show the logo Before the label and price', 'mojito-shipping' ),
                        'after'  => __( 'Show the logo After the label and price', 'mojito-shipping' ),
                    ),
                    'value'   => 'after',
                    'tooltip' => __( 'This option allows you to set the position of the logo', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'text',
                    'label'   => __( 'Logo custom CSS Class', 'mojito-shipping' ),
                    'name'    => 'css-class',
                    'value'   => '',
                    'tooltip' => __( 'Custom CSS Class for the logo', 'mojito-shipping' ),
                ),
            ),
            'box-id'      => 'pymexpress-logo',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'pymexpress' will show this setting.
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'pymexpress',
            ),
            'class'       => 'closed',
            'tab-id'      => 'pymexpress',
        );

        /**
         * Tracking
         */
        $this->settings['pymexpress-tracking'] = array(
            'title'       => __( 'Show Tracking Details', 'mojito-shipping' ),
            'description' => __( 'Tracking only works for the production environment.', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'select',
                    'label'   => __( 'Show tracking in user order details', 'mojito-shipping' ),
                    'name'    => 'user-order-details',
                    'options' => array(
                        'no'  => __( 'No', 'mojito-shipping' ),
                        'yes' => __( 'Yes', 'mojito-shipping' ),
                    ),
                    'value'   => 'no',
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Show tracking in admin order details', 'mojito-shipping' ),
                    'name'    => 'admin-order-details',
                    'options' => array(
                        'no'  => __( 'No', 'mojito-shipping' ),
                        'yes' => __( 'Yes', 'mojito-shipping' ),
                    ),
                    'value'   => 'no',
                ),
            ),
            'box-id'      => 'pymexpress-tracking',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'pymexpress' will show this setting.
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
            'inputs'      => array(
                array(
                    'type'    => 'select',
                    'label'   => __( 'Enable exchange rate', 'mojito-shipping' ),
                    'name'    => 'enable',
                    'options' => array(
                        'disabled' => __( 'Disable exchange rate', 'mojito-shipping' ),
                        'enable'   => __( 'Enable exchange rate', 'mojito-shipping' ),
                    ),
                    'value'   => 'disabled',
                    'tooltip' => __( 'This option allows you to enable the exchange rate.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Automatic Exchange rate', 'mojito-shipping' ),
                    'name'    => 'origin',
                    'options' => array(
                        'manual'    => __( 'Manual exchange rate', 'mojito-shipping' ),
                        'automatic' => __( 'Automatic exchange rate', 'mojito-shipping' ),
                    ),
                    'value'   => 'manual',
                    'tooltip' => __( 'Select between manual exchange rate or automatic from https://api.hacienda.go.cr/indicadores/tc', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'number',
                    'label'   => __( 'Exchange rate', 'mojito-shipping' ),
                    'name'    => 'rate',
                    'tooltip' => __( 'Please set up the dollar price in colones. This will overwrite any other exchange rate.', 'mojito-shipping' ),
                    'value'   => 610,
                ),
            ),
            'box-id'      => 'pymexpress-exchange-rate',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'pymexpress' will show this setting.
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'pymexpress',
            ),
            'class'       => 'closed',
            'tab-id'      => 'pymexpress',
        );

        /**
         * Free Shipping rules
         */
        $this->settings['pymexpress-shipping-rules'] = array(
            'title'       => __( 'Free and discount shipping rules', 'mojito-shipping' ),
            'description' => __( 'Offer free shipping to your clients when they buy a certain amount or weight.', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'select',
                    'label'   => __( 'Free Shipping rule mode', 'mojito-shipping' ),
                    'name'    => 'rule-mode',
                    'options' => array(
                        'disabled'        => __( 'Free Shipping disabled', 'mojito-shipping' ),
                        'min-amount'      => __( 'Requires a minimum order amount ', 'mojito-shipping' ),
                        'min-weight'      => __( 'Requires a minimum order weight ', 'mojito-shipping' ),
                        'min-items-count' => __( 'Requires a minimum item order count ', 'mojito-shipping' ),
                        'max-amount'      => __( 'Requires a maximum order amount ', 'mojito-shipping' ),
                        'max-weight'      => __( 'Requires a maximum order weight ', 'mojito-shipping' ),
                        'max-items-count' => __( 'Requires a maximum item order count ', 'mojito-shipping' ),
                    ),
                    'value'   => 'disabled',
                ),
                array(
                    'type'    => 'number',
                    'label'   => __( 'Minimal or maximum.', 'mojito-shipping' ),
                    'name'    => 'minmax',
                    'tooltip' => __( 'Enter the minimal or maximum weight or amount to apply the free shipping rule.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Free Shipping discount mode', 'mojito-shipping' ),
                    'name'    => 'discount-mode',
                    'options' => array(
                        'fixed'   => __( 'Fixed amount', 'mojito-shipping' ),
                        'percent' => __( 'Percent discount ', 'mojito-shipping' ),
                    ),
                    'value'   => 'percent',
                ),
                array(
                    'type'    => 'number',
                    'label'   => __( 'Discount (Percent or amount).', 'mojito-shipping' ),
                    'name'    => 'discount',
                    'value'   => '100',
                    'tooltip' => '',
                ),
            ),
            'box-id'      => 'pymexpress-shipping-rules',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'pymexpress' will show this setting.
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'pymexpress',
            ),
            'class'       => 'closed',
            'tab-id'      => 'pymexpress',
        );

        /**
         * PDF Export
         */
        $this->settings['pymexpress-pdf-export'] = array(
            'title'       => __( 'PDF export settings', 'mojito-shipping' ),
            'description' => __( 'Settings to export order data and guide number in PDF', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'select',
                    'label'   => __( 'PDF Origin', 'mojito-shipping' ),
                    'name'    => 'origin',
                    'options' => array(
                        'mojito'     => __( 'PDF generated by Mojito Shipping', 'mojito-shipping' ),
                        'pymexpress' => __( 'PDF generated by Correos de Costa Rica', 'mojito-shipping' ),
                    ),
                    'value'   => 'full',
                    'tooltip' => __( 'Select the origin of the PDF.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'PDF Content', 'mojito-shipping' ),
                    'name'    => 'content',
                    'options' => array(
                        'full'         => __( 'Full order data', 'mojito-shipping' ),
                        'minimal'      => __( 'Minimal order data', 'mojito-shipping' ),
                        'only-barcode' => __( 'Only barcode', 'mojito-shipping' ),
                    ),
                    'value'   => 'full',
                    'tooltip' => __( 'This option requires "PDF Origin" to be "PDF generated by Mojito Shipping". The option "Full order data" include Guide number, Sender data, Recipient data, and the Package details. The option "Minimal" will export the Barcode, the Guide number and Recipient data. The option "Only barcode" will export the Guide number and barcode only.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'include order products?', 'mojito-shipping' ),
                    'name'    => 'order-content',
                    'options' => array(
                        'no'  => __( 'No', 'mojito-shipping' ),
                        'yes' => __( 'Yes', 'mojito-shipping' ),
                    ),
                    'value'   => 'no',
                    'tooltip' => __( 'If yes, the PDF will show the products in the order. This option requires "Full order data" in the PDF Content option.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'include client note?', 'mojito-shipping' ),
                    'name'    => 'client-note',
                    'options' => array(
                        'no'  => __( 'No', 'mojito-shipping' ),
                        'yes' => __( 'Yes', 'mojito-shipping' ),
                    ),
                    'value'   => 'no',
                    'tooltip' => __( 'If yes, the PDF will show the products in the order. This option requires "Full order data" in the PDF Content option.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Show Correos de Costa Rica Logo', 'mojito-shipping' ),
                    'name'    => 'ccr-logo',
                    'options' => array(
                        'yes' => __( 'Yes', 'mojito-shipping' ),
                        'no'  => __( 'No', 'mojito-shipping' ),
                    ),
                    'value'   => 'no',
                    'tooltip' => __( 'Include logo in the PDF.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Show Site Logo', 'mojito-shipping' ),
                    'name'    => 'site-logo',
                    'options' => array(
                        'yes' => __( 'Yes', 'mojito-shipping' ),
                        'no'  => __( 'No', 'mojito-shipping' ),
                    ),
                    'value'   => 'no',
                    'tooltip' => __( 'Include site logo in the PDF.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Download PDF available in customer order details', 'mojito-shipping' ),
                    'name'    => 'in-customer-order',
                    'options' => array(
                        'yes' => __( 'Yes', 'mojito-shipping' ),
                        'no'  => __( 'No', 'mojito-shipping' ),
                    ),
                    'value'   => 'no',
                    'tooltip' => __( 'If Yes, an option to download the PDF will be available in the customer order details.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Download PDF available in orders list', 'mojito-shipping' ),
                    'name'    => 'in-orders-list',
                    'options' => array(
                        'yes' => __( 'Yes', 'mojito-shipping' ),
                        'no'  => __( 'No', 'mojito-shipping' ),
                    ),
                    'value'   => 'yes',
                    'tooltip' => __( 'If Yes, an option to download the PDF will be available in the orders list.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'textarea',
                    'label'   => __( 'Send PDFs to the following emails', 'mojito-shipping' ),
                    'name'    => 'send-to-emails',
                    'tooltip' => __( 'If set, Mojito Shipping will try to send the PDF guide to these email addresses. Separate with commas ","', 'mojito-shipping' ),
                ),
            ),
            'box-id'      => 'pymexpress-pdf-export',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'pymexpress' will show this setting.
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'pymexpress',
            ),
            'class'       => 'closed',
            'tab-id'      => 'pymexpress',
        );

        /**
         * Location rules
         */
        $this->settings['pymexpress-location-rules'] = array(
            'title'       => __( 'Location rules', 'mojito-shipping' ),
            'description' => __( 'Settings to exclude zones from the shipping calculation', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'select',
                    'label'   => __( 'Exclude inside GAM locations?', 'mojito-shipping' ),
                    'name'    => 'exclude-gam',
                    'options' => array(
                        'no'  => __( 'No', 'mojito-shipping' ),
                        'yes' => __( 'Yes', 'mojito-shipping' ),
                    ),
                    'value'   => 'full',
                    'tooltip' => __( 'If Yes, Pymexpress will be no active for inside GAM destinations.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Exclude outside GAM locations?', 'mojito-shipping' ),
                    'name'    => 'exclude-no-gam',
                    'options' => array(
                        'no'  => __( 'No', 'mojito-shipping' ),
                        'yes' => __( 'Yes', 'mojito-shipping' ),
                    ),
                    'value'   => 'no',
                    'tooltip' => __( 'If Yes, Pymexpress will be no active for outside GAM destinations.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'textarea',
                    'label'   => __( 'Custom zip codes to exclude', 'mojito-shipping' ),
                    'name'    => 'custom-zipcodes',
                    'tooltip' => __( 'Fill with zip code locations to exclude from the shipping calculation. Separate with commas ","', 'mojito-shipping' ),
                ),
            ),
            'box-id'      => 'pymexpress-location-rules',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'pymexpress' will show this setting.
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'pymexpress',
            ),
            'class'       => 'closed',
            'tab-id'      => 'pymexpress',
        );

        /**
         * Cron Control
         */
        $this->settings['pymexpress-cron-control'] = array(
            'title'       => __( 'Cron settings', 'mojito-shipping' ),
            'description' => __( 'Periodically run and do things. If you use Mojito Proxy we recommend enabling the Cron to keep your IP address updated in our firewall.', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'select',
                    'label'   => __( 'Allow CRON to run?', 'mojito-shipping' ),
                    'name'    => 'allow-to-run',
                    'options' => array(
                        'no'  => __( 'No', 'mojito-shipping' ),
                        'yes' => __( 'Yes', 'mojito-shipping' ),
                    ),
                    'value'   => 'no',
                ),
            ),
            'box-id'      => 'pymexpress-cron-control',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'pymexpress' will show this setting.
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
            'inputs'      => array(
                array(
                    'type'    => 'select',
                    'label'   => __( 'Enable Pymexpress WS Cache?', 'mojito-shipping' ),
                    'name'    => 'allow-cache',
                    'options' => array(
                        'no'  => __( 'No', 'mojito-shipping' ),
                        'yes' => __( 'Yes', 'mojito-shipping' ),
                    ),
                    'value'   => 'no',
                ),
                array(
                    'type'    => 'number',
                    'label'   => __( 'Lifetime', 'mojito-shipping' ),
                    'name'    => 'lifetime',
                    'tooltip' => __( 'Determines how many seconds the cache will live.', 'mojito-shipping' ),
                    'value'   => 60,
                ),
            ),
            'box-id'      => 'pymexpress-ws-cache-control',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'pymexpress' will show this setting.
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'pymexpress',
            ),
            'class'       => 'closed',
            'tab-id'      => 'pymexpress',
        );


        /**
         * Method / Carrier
         * Correos de Costa Rica Simple Settings
         */
        /**
         * Store and sedding settings
         */
        $this->settings['ccr-simple-store'] = array(
            'title'       => __( 'Your business settings', 'mojito-shipping' ),
            'description' => __( 'Set settings for your store.', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'select',
                    'label'   => __( 'Store location', 'mojito-shipping' ),
                    'name'    => 'location',
                    'options' => array(
                        ''            => '',
                        'inside-gam'  => __( 'Inside GAM', 'mojito-shipping' ),
                        'outside-gam' => __( 'Outsite GAM', 'mojito-shipping' ),
                    ),
                    'tooltip' => __( 'Indicate if your store is located inside or outside the GAM', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Service for local shipping', 'mojito-shipping' ),
                    'name'    => 'local-shipping',
                    'options' => array(
                        'disabled'    => '',
                        'pymexpress'  => __( 'Pymexpress', 'mojito-shipping' ),
                        'ems-courier' => __( 'EMS Courier', 'mojito-shipping' ),
                    ),
                    'tooltip' => __( 'Select the service to deliver the packages inside Costa Rica', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Service for international shipping', 'mojito-shipping' ),
                    'name'    => 'international-shipping',
                    'options' => array(
                        'disabled'                         => '',
                        'exporta-facil'                    => __( 'Exporta Fácil', 'mojito-shipping' ),
                        'ems-premium'                      => __( 'EMS Premium', 'mojito-shipping' ),
                        'correo-internacional-prioritario' => __( 'Correo Internacional Prioritario', 'mojito-shipping' ),
                        'correo-internacional-no-prioritario' => __( 'Correo No Internacional Prioritario', 'mojito-shipping' ),
                        'correo-internacional-prioritario-certificado' => __( 'Correo Internacional Prioritario con Certificado', 'mojito-shipping' ),
                    ),
                    'tooltip' => __( 'Select the service to deliver the packages outside Costa Rica', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Add IVA calculation', 'mojito-shipping' ),
                    'name'    => 'iva-ccr',
                    'options' => array(
                        'yes' => __( 'Yes, I must pay IVA to Correos de Costa Rica', 'mojito-shipping' ),
                        'no'  => __( 'No, my business is IVA exempt', 'mojito-shipping' ),
                    ),
                    'value'   => 'yes',
                    'tooltip' => __( 'Please be sure that your business has an IVA exemption into Correos de Costa Rica system.', 'mojito-shipping' ),
                ),
            ),
            'box-id'      => 'ccr-simple-store',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'ccr' will show this setting.
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
            'title'       => __( 'Minimal amounts and rounding', 'mojito-shipping' ),
            'description' => __( 'Set the minimal amount to charge.', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'select',
                    'label'   => __( 'Round the final amount', 'mojito-shipping' ),
                    'name'    => 'round-the-amount',
                    'options' => array(
                        'do-not-round'           => __( 'Do not round the amount', 'mojito-shipping' ),
                        'round-to-the-next-10'   => __( 'Round to the next 10 (eg: 3203.56 => 3210)', 'mojito-shipping' ),
                        'round-to-the-next-100'  => __( 'Round to the next 100 (eg: 3203.56 => 3300)', 'mojito-shipping' ),
                        'round-to-the-next-500'  => __( 'Round to the next 500 (eg: 3203.56 => 3500)', 'mojito-shipping' ),
                        'round-to-the-next-1000' => __( 'Round to the next 1000 (eg: 3203.56 => 4000)', 'mojito-shipping' ),
                    ),
                    'value'   => 'yes',
                    'tooltip' => __( 'Round the final amount of the shipment to facilitate the reading of the amount.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Enable minimal amounts', 'mojito-shipping' ),
                    'name'    => 'enable',
                    'options' => array(
                        'disabled' => __( 'Disable the minimal amounts', 'mojito-shipping' ),
                        'enable'   => __( 'Enable the minimal amounts', 'mojito-shipping' ),
                    ),
                    'value'   => 'disabled',
                    'tooltip' => __( 'This setting allows you to ensure a minimum shipping charge.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'number',
                    'label'   => __( 'General minimal amount', 'mojito-shipping' ),
                    'name'    => 'amount-general',
                    'tooltip' => __( 'Set a minimum charge.', 'mojito-shipping' ),
                    'value'   => 2000,
                ),
                array(
                    'type'    => 'number',
                    'label'   => __( 'Minimal amount for local Shipping inside the GAM', 'mojito-shipping' ),
                    'name'    => 'amount-inside-gam',
                    'tooltip' => __( 'Set a minimum charge for your local shipping in the GAM.', 'mojito-shipping' ),
                    'value'   => 2000,
                ),
                array(
                    'type'    => 'number',
                    'label'   => __( 'Minimal amount for local Shipping outside the GAM', 'mojito-shipping' ),
                    'name'    => 'amount-outside-gam',
                    'tooltip' => __( 'Set a minimum charge for your local shipping out the GAM.', 'mojito-shipping' ),
                    'value'   => 3000,
                ),
                array(
                    'type'    => 'number',
                    'label'   => __( 'Minimal amount for international Shipping', 'mojito-shipping' ),
                    'name'    => 'amount-international',
                    'tooltip' => __( 'Set a minimum charge for your international shipping.', 'mojito-shipping' ),
                    'value'   => 20000,
                ),
            ),
            'box-id'      => 'ccr-simple-minimal',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'ccr' will show this setting.
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'ccr-simple',
            ),
            'class'       => 'closed',
            'tab-id'      => 'ccr-simple',
        );

        /**
         * Correos de Costa Rica Logo
         */
        $this->settings['ccr-simple-logo'] = array(
            'title'       => __( 'Correos de Costa Rica Logo', 'mojito-shipping' ),
            'description' => __( 'Show the logo of Correos de Costa Rica. Correos de Costa Rica owns all rights to its logo, brand, and others. ', 'mojito-shipping' ),
            'inputs'      => array(
                array(
                    'type'    => 'select',
                    'label'   => __( 'Show Correos de Costa Rica logo', 'mojito-shipping' ),
                    'name'    => 'logo-ccr',
                    'options' => array(
                        'yes' => __( 'Yes, show logo', 'mojito-shipping' ),
                        'no'  => __( 'No, do not show the logo', 'mojito-shipping' ),
                    ),
                    'value'   => 'no',
                    'tooltip' => __( 'Show logo in Cart and Checkout', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Link logo to Correos de Costa Rica service page.', 'mojito-shipping' ),
                    'name'    => 'link',
                    'options' => array(
                        'yes' => __( 'Yes', 'mojito-shipping' ),
                        'no'  => __( 'No', 'mojito-shipping' ),
                    ),
                    'value'   => 'yes',
                    'tooltip' => __( 'This option allows you to enable the link to Correos de Costa Rica website.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Logo size', 'mojito-shipping' ),
                    'name'    => 'size',
                    'options' => array(
                        'full'  => __( 'Full size', 'mojito-shipping' ),
                        '320px' => __( '320px width', 'mojito-shipping' ),
                        '200px' => __( '200px width', 'mojito-shipping' ),
                        '120px' => __( '120px width', 'mojito-shipping' ),
                    ),
                    'value'   => 'full',
                    'tooltip' => __( 'Select the size to show.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Show logo before or after the label', 'mojito-shipping' ),
                    'name'    => 'position',
                    'options' => array(
                        'before' => __( 'Show the logo Before the label and price', 'mojito-shipping' ),
                        'after'  => __( 'Show the logo After the label and price', 'mojito-shipping' ),
                    ),
                    'value'   => 'after',
                    'tooltip' => __( 'This option allows you to set the position of the logo', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'text',
                    'label'   => __( 'Logo custom CSS Class', 'mojito-shipping' ),
                    'name'    => 'css-class',
                    'value'   => '',
                    'tooltip' => __( 'Custom CSS Class for the logo', 'mojito-shipping' ),
                ),
            ),
            'box-id'      => 'ccr-simple-logo',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'ccr' will show this setting.
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
                    'label'   => __( 'Rate per lb', 'mojito-shipping' ),
                    'name'    => 'rate-per-lbs',
                    'tooltip' => __( 'Set the shipping rate per lb', 'mojito-shipping' ),
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
                ),
                array(
                    'type'    => 'select',
                    'label'   => __( 'Enable minimal amounts', 'mojito-shipping' ),
                    'name'    => 'minimal-enable',
                    'options' => array(
                        'disabled' => __( 'Disable the minimal amounts', 'mojito-shipping' ),
                        'enable'   => __( 'Enable the minimal amounts', 'mojito-shipping' ),
                    ),
                    'value'   => 'disabled',
                    'tooltip' => __( 'This setting allows you to ensure a minimum shipping charge.', 'mojito-shipping' ),
                ),
                array(
                    'type'    => 'number',
                    'label'   => __( 'General minimal amount', 'mojito-shipping' ),
                    'name'    => 'minimal-amount',
                    'tooltip' => __( 'Set a minimum charge.', 'mojito-shipping' ),
                    'value'   => 0,
                ),
            ),
            'box-id'      => 'simple-general',
            'require'     => array( // If 'mojito-shipping-carrier-provider' === 'ccr' will show this setting.
                'required-setting' => 'mojito-shipping-carrier-provider',
                'required-value'   => 'simple',
            ),
            'class'       => 'closed',
            'tab-id'      => 'simple',
        );

    }

}

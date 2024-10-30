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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple method CCR
 */
class Mojito_Shipping_Method_CCR_Simple extends Mojito_Shipping_Method_CCR {

	/**
	 * Constructor for shipping class
	 *
	 * @access public
	 * @return void
	 */
	public function __construct( $instance_id = 0 ) {

		$enable_value = get_option( 'woocommerce_mojito_shipping_correos_simple_enabled' );
		$title_value  = get_option( 'woocommerce_mojito_shipping_correos_simple_title', __( 'Mojito Shipping: Correos de Costa Rica without integration', 'mojito-shipping' ) );

		$this->instance_id        = absint( $instance_id );
		$this->id                 = 'mojito_shipping_correos_simple';
		$this->method_title       = __( 'Mojito Shipping: Correos de Costa Rica without integration', 'mojito-shipping' );
		$this->method_description = __( 'Send packages using Correos de Costa Rica services manually', 'mojito-shipping' );
		$this->enabled            = 'yes';
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
		);
		$this->title              = $title_value;
		$this->enabled            = isset( $enable_value ) ? $enable_value : 'yes';

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
	 * Add admin fields
	 *
	 * @return void
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled' => array(
				'title'    => __( 'Enable/Disable', 'mojito-shipping' ),
				'type'     => 'checkbox',
				'label'    => __( 'Enable this shipping method', 'mojito-shipping' ),
				'default'  => get_option( 'woocommerce_mojito_shipping_correos_simple_enabled' ),
				'required' => false,
			),
			'title'   => array(
				'title'       => __( 'Title', 'mojito-shipping' ),
				'type'        => 'text',
				'description' => __( 'Title to be display on site', 'mojito-shipping' ),
				'default'     => get_option( 'woocommerce_mojito_shipping_correos_simple_title', __( 'Mojito Shipping: Correos de Costa Rica without integration', 'mojito-shipping' ) ),
				'desc_tip'    => true,
				'required'    => true,
			),
		);
	}

	/**
	 * Process admin options
	 */
	public function process_admin_options() {

		if ( isset( $_POST['woocommerce_mojito_shipping_correos_simple_enabled'] ) ) {
			update_option( 'woocommerce_mojito_shipping_correos_simple_enabled', 'yes' );
			$this->settings['enabled'] = 'yes';
		} else {
			update_option( 'woocommerce_mojito_shipping_correos_simple_enabled', 'no' );
			$this->settings['enabled'] = 'no';
		}

		if ( empty( $_POST['woocommerce_mojito_shipping_correos_simple_title'] ) ) {
			$title = __( 'Mojito Shipping: Correos de Costa Rica', 'mojito-shipping' );
			update_option( 'woocommerce_mojito_shipping_correos_simple_title', $title );
			$this->settings['title'] = $title;
		} else {
			$title = sanitize_text_field( $_POST['woocommerce_mojito_shipping_correos_simple_title'] );
			update_option( 'woocommerce_mojito_shipping_correos_simple_title', $title );
			$this->settings['title'] = $title;
		}
	}

	/**
	 * Calculate_shipping function.
	 *
	 * @access public
	 * @param mixed $package Package.
	 * @return void
	 */
	public function calculate_shipping( $package = array() ) {
		$this->calculate_shipping_process( $package, 'ccr-simple' );
	}
}

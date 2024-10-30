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

use WC_Shipping_Method;

class Mojito_Shipping_Method_Simple extends WC_Shipping_Method {


	/**
	 * Constructor for shipping class
	 *
	 * @access public
	 * @return void
	 */
	public function __construct( $instance_id = 0 ) {

		$enable_value = get_option( 'woocommerce_mojito_shipping_simple_enabled' );
		$title_value  = get_option( 'woocommerce_mojito_shipping_simple_title', __( 'Mojito Shipping: Simple Weight-Based', 'mojito-shipping' ) );

		$this->instance_id        = absint( $instance_id );
		$this->id                 = 'mojito_shipping_simple';
		$this->method_title       = __( 'Mojito Shipping: Simple Weight-Based', 'mojito-shipping' );
		$this->method_description = __( 'Weight-Based shipping method', 'mojito-shipping' );
		$this->enabled            = 'yes';
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
		);
		$this->title              = $title_value;
		$this->enabled            = isset( $enable_value ) ? $enable_value : 'yes';

		$this->init();

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
		$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings.
		$this->init_settings(); // This is part of the settings API. Loads settings you previously init.

		// Save settings in admin if you have any defined.
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

	}

	public function init_form_fields() {

		$this->form_fields = array(
			'enabled' => array(
				'title'    => __( 'Enable/Disable', 'mojito-shipping' ),
				'type'     => 'checkbox',
				'label'    => __( 'Enable this shipping method', 'mojito-shipping' ),
				'default'  => get_option( 'woocommerce_mojito_shipping_simple_enabled' ),
				'required' => false,
			),
			'title'   => array(
				'title'       => __( 'Title', 'mojito-shipping' ),
				'type'        => 'text',
				'description' => __( 'Title to be display on site', 'mojito-shipping' ),
				'default'     => get_option( 'woocommerce_mojito_shipping_simple_title', __( 'Mojito Shipping: Simple Weight-Based', 'mojito-shipping' ) ),
				'desc_tip'    => true,
				'required'    => true,
			),
		);
	}

	/**
	 * Process admin options
	 */
	public function process_admin_options() {

		if ( isset( $_POST['woocommerce_mojito_shipping_simple_enabled'] ) ) {
			update_option( 'woocommerce_mojito_shipping_simple_enabled', 'yes' );
			$this->settings['enabled'] = 'yes';
		} else {
			update_option( 'woocommerce_mojito_shipping_simple_enabled', 'no' );
			$this->settings['enabled'] = 'no';
		}

		if ( empty( $_POST['woocommerce_mojito_shipping_simple_title'] ) ) {
			$title = __( 'Mojito Shipping: Simple Weight-Based', 'mojito-shipping' );
			update_option( 'woocommerce_mojito_shipping_simple_title', $title );
			$this->settings['title'] = $title;
		} else {
			$title = sanitize_text_field( $_POST['woocommerce_mojito_shipping_simple_title'] );
			update_option( 'woocommerce_mojito_shipping_simple_title', $title );
			$this->settings['title'] = $title;
		}

	}

	/**
	 * Calculate_shipping function.
	 *
	 * @access public
	 * @param mixed $package
	 * @return void
	 */
	public function calculate_shipping( $package = array() ) {

		$products    = $package['contents'];
		$weight_unit = get_option( 'woocommerce_weight_unit' );
		$items       = array();

		/**
		 * Get packages weight.
		 */
		foreach ( $products as $id => $item ) {
			$items[ $id ] = array(
				'quantity' => $item['quantity'],
				'weight'   => $item['data']->get_weight(),
			);
		}

		$shipping_weight = 0;
		foreach ( $items as $id => $data ) {
			if ( is_numeric( $data['quantity'] ) && is_numeric( $data['weight'] ) ) {
				$product_weight   = $data['quantity'] * $data['weight'];
				$shipping_weight += $product_weight;
			}
		}

		$base_rate     = (float) get_option( 'mojito-shipping-simple-general-rate-per-' . $weight_unit );
		$shipping_rate = $base_rate * $shipping_weight;

		/**
		 * Exchange rates
		 */
		if ( 'enable' === get_option( 'mojito-shipping-simple-exchange-rate-enable', 'disabled' ) ) {
			$exchange_rate = get_option( 'mojito-shipping-simple-exchange-rate-rate', 590 );
			if ( $exchange_rate <= 0 ) {
				$exchange_rate = 1;
			}
			$shipping_rate = $shipping_rate / $exchange_rate;
		}

		/**
		 * Minimal rate
		 */
		if ( 'enable' === get_option( 'mojito-shipping-simple-general-minimal-enable', 'disabled' ) ) {
			$general_minimal_rate = (float) get_option( 'mojito-shipping-simple-general-minimal-amount', 0 );
			if ( $shipping_rate < $general_minimal_rate ) {
				$shipping_rate = $general_minimal_rate;
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
				$this_coupon = new \WC_Coupon( $coupon_code );
				if ( $this_coupon->get_free_shipping() ) {
					$shipping_rate = 0;
					$mojito_free_shipping = true;
				}
			}
		}

		/**
		 * Create label for checkout.
		 */
		$label = get_option( 'mojito-shipping-simple-label-label', '' );
		if ( empty( $label ) ) {
			$label = $this->title . ', ' . $shipping_weight . ' ' . $weight_unit;
		} else {
			$label = str_replace( '%rate%', $shipping_rate, $label );
			$label = str_replace( '%weight%', $shipping_weight, $label );
		}

		/**
		 * Free Shipping label
		 */
		if ( true === $mojito_free_shipping ) {
			$label .= ': ' . __( 'Free shipping', 'mojito-shipping' );
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

		$rate = apply_filters( 'mojito_shipping_checkout_custom_rate', $rate, $filter_params );
		$this->add_rate( $rate );
	}

}

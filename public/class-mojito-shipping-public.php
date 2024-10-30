<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://mojitowp.com
 * @since      1.0.0
 *
 * @package    Mojito_Shipping
 * @subpackage Mojito_Shipping/public
 */

namespace Mojito_Shipping;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Mojito_Shipping
 * @subpackage Mojito_Shipping/public
 * @author     Mojito Team <support@mojitowp.com>
 */
class Mojito_Shipping_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/mojito-shipping-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/mojito-shipping-public.js', array( 'jquery' ), $this->version, false );

		if ( 'yes' === get_option( 'mojito-shipping-ccr-pdf-export-in-customer-order' ) ) {
			wp_localize_script(
				$this->plugin_name,
				'mojito_ajax',
				array( 'ajax_url' => admin_url( 'admin-ajax.php' ) )
			);
		}
	}
}

<?php
/**
 * Mojito Shipping
 *
 * @package           Mojito_Shipping
 * @author            Mojito Team
 * @link              https://mojitowp.com/
 *
 * @wordpress-plugin
 * Plugin Name: Mojito Shipping
 * Plugin URI: https://mojitowp.com/
 * Description: Weight-based rates for WooCommerce. Simple method shipping support. Correos de Costa Rica web service support for tracking codes.
 * Version: 1.5.6
 * Requires at least: 5.2
 * Requires PHP: 7.4
 * Author: Mojito Team
 * Author URI: https://mojitowp.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mojito-shipping
 * Domain Path: /languages
 * WC requires at least: 8.2.0
 * WC tested up to: 9.0.2
  */

/**
 * If this file is called directly, abort.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Debuggin function
 */
if ( ! function_exists( 'mojito_shipping_debug' ) ) {
	/**
	 * Show a message
	 *
	 * @param mixed $message Text to log.
	 * @return void
	 */
	function mojito_shipping_debug( $message ) {
		if ( ( defined( 'MOJITO_SHIPPING_DEBUG' ) && true === MOJITO_SHIPPING_DEBUG ) || 'yes' === get_option( 'mojito-shipping-settings-debug', 'no' ) ) {
			error_log( print_r( $message, 1 ) );

			if ( class_exists( 'WC_Logger' ) ) {
				$logger = new \WC_Logger();
				$logger->log( 'debug', print_r( $message, true ), [] );
			}
		}
	}
}


if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {

	add_action(
		'admin_notices',
		function() {

			$class = 'notice notice-error';
			/* translators: PHP Version*/
			$message = sprintf( __( 'Mojito Shipping requires PHP 7.4 or higher. You are running PHP %s, please deactivate Mojito Shipping until your PHP has been updated to 7.4 or higher.', 'mojito-shipping' ), PHP_VERSION );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}
	);

} else {

	if ( function_exists( 'mojito_shipping_fs' ) ) {
		mojito_shipping_fs()->set_basename( false, __FILE__ );

	} else {

		/**
		 * Version.
		 */
		define( 'MOJITO_SHIPPING_VERSION', '1.5.6' );

		/**
		 * Define plugin constants.
		 */
		define( 'MOJITO_SHIPPING_DIR', plugin_dir_path( __FILE__ ) );

		/**
		 * Freemius start
		 */
		if ( ! function_exists( 'mojito_shipping_fs' ) ) {
			require_once MOJITO_SHIPPING_DIR . 'load-freemius.php';
		}

		/**
		 * Composer Loader
		 */
		require MOJITO_SHIPPING_DIR . 'vendor/autoload.php';

		/**
		 * Plugin activation
		 */
		register_activation_hook(
			__FILE__,
			function () {
				require_once MOJITO_SHIPPING_DIR . 'includes/class-mojito-shipping-activator.php';
				Mojito_Shipping\Mojito_Shipping_Activator::activate();
			}
		);

		/**
		 * Plugin deactivation.
		 */
		register_deactivation_hook(
			__FILE__,
			function () {
				require_once MOJITO_SHIPPING_DIR . 'includes/class-mojito-shipping-deactivator.php';
				Mojito_Shipping\Mojito_Shipping_Deactivator::deactivate();
			}
		);

		/**
		 * Compatibility with WooCommerce declarations
		 */
		add_action('before_woocommerce_init', function(){
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			}
		});

		/**
		 * The core plugin class that is used to define internationalization,
		 * admin-specific hooks, and public-facing site hooks.
		 */
		require_once MOJITO_SHIPPING_DIR . 'includes/class-mojito-shipping.php';

		/**
		 * Begins execution.
		 *
		 * @since    1.0.0
		 */
		function mojito_shipping_run() {
			global $mojito_shipping;
			if ( ! isset( $mojito_shipping ) ) {
				$mojito_shipping = new Mojito_Shipping\Mojito_Shipping();
				$mojito_shipping->run();
			}
		}

		mojito_shipping_run();
	}
}

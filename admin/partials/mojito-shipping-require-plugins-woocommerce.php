<?php
/**
 * Provide a admin area view for the plugin
 *
 * Require WooCommerce plugin
 *
 * @link       https://mojitowp.com
 * @since      1.0.0
 *
 * @package    Mojito_Shipping
 * @subpackage Mojito_Shipping/admin/partials
 */

namespace Mojito_Shipping;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooCommerce' ) ) {
	?>
	<div id="message" class="error">
		<p>
			<?php echo __( 'Mojito Shipping Plugin requires WooCommerce to be active.', 'mojito-shipping' ); ?>
			<a href="https://wordpress.org/plugins/woocommerce/" target="_blank"><strong>WooCommerce</strong></a>
		</p>
	</div>
	<?php
}

<?php
/**
 * Provide a admin area view for the plugin
 *
 * Require wc provincia canton plugin
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

$carriers = get_option( 'mojito-shipping-carrier-provider' );

if ( ! is_array( $carriers ) ) {
	$carriers = array( $carriers );
}

if ( ! class_exists( 'WC Provincia-Canton-Distrito' ) && in_array( 'ccr', $carriers, true ) ) {
	?>
	<div id="message" class="error">
		<p>
			<?php echo __( 'Mojito Shipping: Correos de Costa Rica requires WC Provincia Canton Distrito to be active.', 'mojito-shipping' ); ?>
			<a href="https://wordpress.org/plugins/wc-provincia-canton-distrito/" target="_blank"><strong>WC Provincia-Canton-Distrito</strong></a>
		</p>
	</div>
	<?php
}

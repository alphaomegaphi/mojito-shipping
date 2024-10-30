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
 * @author     Manfred Rodriguez <support@mojitowp.com>
 */

namespace Mojito_Shipping;

/**
 * Glovo API Connection Class
 */
class Mojito_Shipping_Method_Glovo_API {

	/**
	 * API Username
	 *
	 * @var string
	 */
	protected $username;

	/**
	 * API Password
	 *
	 * @var string
	 */
	protected $password;

	/**
	 * Constructor
	 */
	public function __construct( $username, $password ) {
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * Requests to API
	 *
	 * @return void
	 */
	private function request() {

	}
}

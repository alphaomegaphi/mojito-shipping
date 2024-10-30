=== Mojito Shipping ===
Contributors: quantumdev, freemius
Donate link: #
Tags: ecommerce, woocommerce, shipping, woocommerce shipping, weight-based shipping
Requires at least: 4.6
Tested up to: 6.5.5
Stable tag: 1.5.6
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Weight-based rates for WooCommerce. Simple method shipping support. Correos de Costa Rica web service support for tracking codes. Multisite support.

== Description ==

Todos invitados al WordCamp San José 2024 [https://sanjose.wordcamp.org/2024/](https://sanjose.wordcamp.org/2024/)
50% de descuento con el código WCSJ24

(Español) Documentación: [https://mojitowp.com/documentacion](https://mojitowp.com/documentacion)


= Features =

Simple weight-based shipping for WooCommerce

<ul>
	<li>
        <strong>Weight-based shipping rates</strong><br>
        Use the weight of the products to calculate shipping costs.
        <p>&nbsp;</p>
    </li>
    <li>
        <strong>Detection of products without defined weight</strong><br>
        This plugin will allow you to identify products without weight defined
        <p>&nbsp;</p>
    </li>
</ul>


= Simple weight-based Method =

<ul>
	<li>
        <strong>Set rates per kg</strong><br>        
        <p>&nbsp;</p>
    </li>
	<li>
        <strong>Set rates per g</strong><br>        
        <p>&nbsp;</p>
    </li>
	<li>
        <strong>Set rates per lbs</strong><br>        
        <p>&nbsp;</p>
    </li>
	<li>
        <strong>Set rates per oz</strong><br>        
        <p>&nbsp;</p>
    </li>
	<li>
        <strong>Minimum shipping cost (PRO)</strong><br>        
        <p>&nbsp;</p>
    </li>
</ul>


= Correos de Costa Rica Method =

Support customizations like physical store location, delivery service by Correos de Costa Rica and Web Service settings.

<ul>
    <li>
        <strong>Weight-base rates</strong><br>
        Uses the product weight to calculate the rates.
        <p>&nbsp;</p>
    </li>

    <li>
        <strong>Store location</strong><br>
        Set your store location to calculate the shipping rates.
        <p>&nbsp;</p>
    </li>

    <li>
        <strong>Web Service Settings</strong>
		Set up the Web Service settings to the integration with Correos de Costa Rica
        <p>&nbsp;</p>		
    </li>
	<li>
        <strong>Sender Settings</strong>
		Set up sender settings like Name, Address, Zip code, and Phone
        <p>&nbsp;</p>		
    </li>
	<li>
        <strong>Automatic weight unit conversion</strong>
		Mojito Shipping automatically converts kg, lbs, and oz to grams, as required by Correos de Costa Rica
        <p>&nbsp;</p>		
    </li>
	<li>
		<strong>Minimal general rate</strong><br>
		Set up minimal rate for shipping.
        <p>&nbsp;</p>		
	</li>
	<li>
		<strong>Exchange Rate</strong><br>
		This option allows you to sell in another currency and convert the final shipping rate to the currency established in your store.
        <p>&nbsp;</p>		
	</li>
	<li>
		<strong>Max Weight Control</strong><br>
		Automatically disable Correos de Costa Rica Shipping method when order total weight is over 30,000 grams (30 kg).
        <p>&nbsp;</p>		
	</li>

	<li>
		<strong>Minimal rates (PRO)</strong><br>
		Set up minimal rate for local shipping (Inside and Outside the GAM)
        <p>&nbsp;</p>		
	</li>
	<li>
		<strong>Automatic round the rates (PRO)</strong><br>
		Set up the automatic round for final rate, select between:
		<ul>
			<li>Round to the next 100 (eg: 3203.56 => 3300)</li>
			<li>Round to the next 500 (eg: 3203.56 => 3500)</li>
			<li>Round to the next 1000 (eg: 3203.56 => 4000)</li>
		</ul>
        <p>&nbsp;</p>		
	</li>
</ul>

= Correos de Costa Rica without integration Method =

Support customizations like physical store location, delivery service by Correos de Costa Rica. No uses Web Service.

<ul>
    <li>
        <strong>Weight-base rates</strong><br>
        Uses the product weight to calculate the rates.
        <p>&nbsp;</p>
    </li>

    <li>
        <strong>Store location</strong><br>
        Set your store location to calculate the shipping rates.
        <p>&nbsp;</p>
    </li>

    <li>
        <strong>Local Shipping services</strong><br>
        Select between EMS Courier and Pymexpress to calculate the shipping rates inside Costa Rica
        <p>&nbsp;</p>
    </li>
	<li>
        <strong>Automatic weight unit conversion</strong>
		Mojito Shipping automatically converts kg, lbs, and oz to grams, as required by Correos de Costa Rica
        <p>&nbsp;</p>		
    </li>
	<li>
		<strong>Minimal general rate</strong><br>
		Set up minimal rate for shipping.
        <p>&nbsp;</p>		
	</li>
	<li>
		<strong>Exchange Rate</strong><br>
		This option allows you to sell in another currency and convert the final shipping rate to the currency established in your store.
        <p>&nbsp;</p>		
	</li>
	<li>
		<strong>Max Weight Control</strong><br>
		Automatically disable Correos de Costa Rica Shipping method when order total weight is over 30,000 grams (30 kg).
        <p>&nbsp;</p>		
	</li>

	<li>
		<strong>Minimal rates (PRO)</strong><br>
		Set up minimal rate for local shipping (Inside and Outside the GAM), and international shipping
        <p>&nbsp;</p>		
	</li>
	<li>
		<strong>Automatic round the rates (PRO)</strong><br>
		Set up the automatic round for final rate, select between:
		<ul>
			<li>Round to the next 100 (eg: 3203.56 => 3300)</li>
			<li>Round to the next 500 (eg: 3203.56 => 3500)</li>
			<li>Round to the next 1000 (eg: 3203.56 => 4000)</li>
		</ul>
        <p>&nbsp;</p>		
	</li>
</ul>

= Available filters = 
[https://mojitowp.com/documentacion/pymexpress/#5.2](https://mojitowp.com/documentacion/pymexpress/#5.2)



Inspired in woo-correos-de-costa-rica-shipping plugin, thank you.


== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload the plugin files to the `/wp-content/plugins/mojito-shipping` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Settings->Mojito Shipping screen to configure the plugin
1. (Make your instructions match the desired user flow for activating and installing your plugin. Include any steps that might be needed for explanatory purposes)


== Screenshots ==
1. General Settings
2. Settings for Correos de Costa Rica 
3. Information tab

== Frequently Asked Questions ==

= Do I need Web Service Username and Password to use this plugin? =

Only if you use the method "Correos de Costa Rica", Costa Rica Post Office should give you access to their Web Service, this Web Service is used to generate the Tracking code.

If you do not have this access, please contact to Correos de Costa Rica (Costa Rica Post Office).

If you use another method like "Simple" you can use this plugin without a problem.


= Do this plugin support multisite? =


Yes, it does.


== Features included ==

Weight-based shipping rates.

Set shipping rates based on the delivery region local (inside and outside the GAM)

Set Store location (inside and outside the GAM)

Set a minimal rate.

Correos de Costa Rica Tracking code for each purchase order.

Custom settings available to restrict the use of Correos de Costa Rica as a method based on delivery areas.


Multisite support (WordPress Network support)

== Upgrade Notice ==

Todos invitados al WordCamp San José 2024 https://sanjose.wordcamp.org/2024/
50% de descuento con el código WCSJ24

== Changelog ==

= 1.5.6 =
* New RETAIL support
* Default destination postcode
* New filter mojito_shipping_pymexpress_default_postcode
* Freemius SDK updated to 2.7.3

= 1.5.5 =
* Exchange rates improvements
* Better debug information
* New filters (PRO)
* Mojito Proxy Changes

= 1.5.4 =
* New Caché control for Web Service requests
* Minor PHP Fixes

= 1.5.3 =
* PHP 7.4 compatibility fix

= 1.5.2 =
* Email sending duplicates fix
* Freemius SDK updated to 2.7.2
* TCPDF updated to 6.7.5
* PHP fixes
* Exchange rates now using mojitowp/exchange-rate

= 1.5.1 =
* Elementor compatibility fix.
* Improvement in completing order process.
* Updated locations from Correos de Costa Rica.
* Logger added to WooCommerce logs
* Improvement in location zip codes detection.

= 1.5.0 =
* WooCommerce High-Performance Order Storage compatibility
* Freemius SDK update to 2.6.2
* TCPDF updated to 6.6.5
* PHP fixes


= 1.4.4 =
* New automatic shipping discount based on the order total items count.
* New option to include packing costs in the shipping rate.
* New filter mojito_shipping_pymexpress_packing_costs
* Performance improvements

= 1.4.3 =
* Cron Upgrade, Now cron register orders with guide number but not response from Correos de Costa Rica
* Freemius SDK update to 2.5.10

= 1.4.2 =
* New 13 locations included: 
-- Mora, Jaris (10706) 
-- Mora, Quitirrisi (10707) 
-- Pérez Zeledón, La Amistad (11912) 
-- Naranjo, Palmitos (20608) 
-- Poás, Granja (20707) 
-- Guatuso, Katira (21504) 
-- Río Cuarto, Río Cuarto (21601)
-- Río Cuarto, Santa Rita (21602)
-- Río Cuarto, Santa Isabel (21603)
-- San Pablo, Rincón de Sabanilla (40902)
-- Esparza, Caldera (60206)
-- Coto Brus, Gutierrrez Brown (60806)
-- Pococí, La Colonia (70207)
* New Option: PDF Origin
* Minor PHP fixes
* New filter mojito_shipping_addresses_json_data
* New filter mojito_shipping_pymexpress_pais_destino (PRO)
* New filter mojito_shipping_pymexpress_provincia_destino (PRO)
* New filter mojito_shipping_pymexpress_canton_destino (PRO)
* New filter mojito_shipping_pymexpress_distrito_destino (PRO)
* TCPDF updated to 6.6.2
* Freemius SDK Updated to 2.5.3


= 1.4.1 =
* Minor fixes
* New Action hook "save_mojito_setting_${ mojito setting option value }
* Freemius SDK Updated to 2.4.5

= 1.4.0 =
* Transient preload fix
* Initial support for: CORPORATIVO
* Initial automatic IP registration in Mojito Proxy
* New filter mojito_shipping_pymexpress_service_id_based_on_shipping_weight
* New filter mojito_shipping_pymexpress_ws_error_${CCR WS ERROR CODE}
* Updated locations: Río Cuarto, Monteverde, etc
* Variants weight check in information tab
* TCPDF updated to 6.4.4
* Option to do not pre-select address in Cart

= 1.3.9 =
* New option to send guide over email
* New filter mojito_shipping_default_exchange_rate
* CCR without integration fixes
* PHP fixes
* Security fix

= 1.3.8 =
* Pymexpress Max to 200 characters in "observaciones" field
* Pymexpress ccrRegistroEnvio failover when zipcode is empty
* Pymexpress new parameters DistritoOrigen and DistritoDestino in ccrTarifa
* CCR without integration fix
* Auth error log
* Automatic exchange rate added to cron
* Save Automatic exchange rate
* Fix on Destination phone now using shipping phone when available

= 1.3.7 =
* Free Shipping fix
* registro_envio method fix
* New debug option
* Automatic Exchange Rates (PRO)

= 1.3.6 =
* PDF Export fixes

= 1.3.5 =
* Tracking packages fixes
* Fix in response code validation in WS request method
* Click to request fix

= 1.3.4 = 
* Code Fix for "SSL certificate problem: unable to get local issuer certificate"

= 1.3.3 = 
* Pymexpress Production URLs

= 1.3.2 =
* Calculation trigger fixes
* Minor code fixes
* Free Shipping per product: just add the attribute "mojito-free-shipping" with the value "1" to any product.

= 1.3.1 =
* PHP Version validation
* Preload All locations and zip codes to improve performance
* Minor code fixes

= 1.3.0 =
* New Pymexpress system support.

= 1.2.3 =
* Allow or disallow Cron new option
* Fix to prevent high CPU usage
* Added León Cortés


= 1.2.2 = 
* Compatibility fix
* Option to download pdf from order list

= 1.2.1 = 
* GAM / No-GAM location fixes (https://wordpress.org/support/topic/ubicaciones-gam-catalogadas-como-fuera-de-gam/)
* Fix: Strict shipping weight calculation applies only to pymexpress
* Fix: Person Shipping Address: https://wordpress.org/support/topic/persona-destino-segun-factura-y-no-segun-envio/

= 1.2.0 = 
* Free Shipping coupons support
* Free Shipping rules
* Dynamic fields in custom label
* Website IP v6 Fix
* Connection timeout
* GAM / No-GAM location fixes (https://wordpress.org/support/topic/ubicaciones-gam-catalogadas-como-fuera-de-gam/)
* Freemius SDK Updated
* WC Provincia Canton no longer required
* New Cron job for automatic guide number generation
* New Filter 'mojito_shipping_ccr_tracking_url' to change the tracking URL ('https://correos.go.cr/rastreo/')


= 1.1.19 =
* New settings filter
* New Filter 'mojito_shipping_ccr_strict_shipping_weight'
* New Filter 'mojito_shipping_ccr_pdf_after_package_content' (PRO)
* Filter 'mojito_shipping_ccr_pdf_after_package_data' is now deprecated, instead use 'mojito_shipping_ccr_pdf_after_client_notes'
* Code improvement


= 1.1.18 =
* Updated "My website IP Address" URL, fix for  "wrapper is disabled in the server configuration by allow_url_fopen=0"
* New option to download PDF from customer order details
* Minor code fixes

= 1.1.17 =
* New option to set label for Cart and Checkout
* New Exchange Rate for simple weight-based method
* PDF export improvement (PRO)
* Minor code fixes (Verification when product weight is missing)

= 1.1.16 =
* Code fixes on order edit

= 1.1.15 =
* New carrier/method "Correos de Costa Rica without integration".
* New option to export PDF (PRO)
* Improvement in information tab
* Minor code fixes

= 1.1.14 =
* Improvement in manual request and logs
* Minor code fixes

= 1.1.13 =
* New Fixed rates options for GAM and non-GAM destination.
* Fix in 'mojito_shipping_ccr_logo_src' filter
* Updated documentation links

= 1.1.12 =
* Freemius SDK Fix
* Improvements in the information tab for proxy connection check.
* Fix in tracking information for client (PRO)
* New option to show tracking information in admin order details (PRO)

= 1.1.11 =
* Correos de Costa Rica logo fixes
* New option for Guide Number manual request
* New option to show tracking information in order details (PRO)
* New filter 'mojito_shipping_ccr_logo_src' to set custom logo (PRO)
* Updated filter 'mojito_shipping_checkout_custom_rate'

= 1.1.10 =
* Updated rates based in La Gaceta #117 (May 21, 2020) (https://www.imprentanacional.go.cr/pub/2020/05/21/COMP_21_05_2020.pdf)

= 1.1.9 =
* New Feature: Option to show Correos de Costa Rica logo
* New Feature: Links to documentation
* Fixed: Review and corrected some zip codes from GAM

= 1.1.8 =
* New Feature: Mojito Proxy
* Fixed proxy connection issue.
* Fixed duplicate tracking code in the order email

= 1.1.7 =
* Fixed missing guide tracking code in order details

= 1.1.6 =
* Minor CSS fixes

= 1.1.5 =
* Connection Check for Correos de Costa Rica Web Service URL
* Minor fixes

= 1.1.4 =
* New multisite support
* New Website IP address indicator in the information tab when Correos de Costa Rica is enabled
* Minor fixes

= 1.1.3 =
* New option to disable Correos de Costa Rica Shipping method when order is over 30kg

= 1.1.2 =
* Minor fixes

= 1.1.1 =
* New Exchange Rate for Correos de Costa Rica Shipping method

= 1.1.0 =
* New Shipping Method: Simple Weight-based rates

= 1.0.1 =
* Fix to show notices after activation

= 1.0.0 =
* Initial Release
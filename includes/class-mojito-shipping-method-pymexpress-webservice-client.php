<?php

/**
 * Correos de Costa Rica Webservice client
 *
 * @link       https://mojitowp.com
 * @since      1.0.0
 * @package    Mojito_Shipping
 * @subpackage Mojito_Shipping/public
 * @author     Mojito Team <support@mojitowp.com>
 */
namespace Mojito_Shipping;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Web Service connector class
 * Updated to 2021
 */
class Mojito_Shipping_Method_Pymexpress_WSC {
    /**
     * The array of methods registered.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $methods  The methods registered with WordPress.
     */
    private $methods;

    /**
     * The array of credentials.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $credentials  The credentials.
     */
    private $credentials;

    /**
     * Array of enviroment settings.
     *
     * @since    1.3.0
     * @access   private
     * @var array
     */
    private $environment;

    /**
     * Access Token
     *
     * @since    1.3.0
     * @access   private
     * @var string
     */
    private $token;

    /**
     * Access token timestamp. Max 5min
     *
     * @since    1.3.0
     * @access   private
     * @var string
     */
    private $token_timestamp;

    /**
     * Constructor for webservice client
     *
     * @access public
     * @return void
     */
    public function __construct() {
        $this->setup();
    }

    /**
     * Setup
     *
     * @return void
     */
    private function setup() {
        $this->methods = array(
            'ccrCodProvincia',
            'ccrCodCanton',
            'ccrCodDistrito',
            'ccrCodBarrio',
            'ccrCodPostal',
            'ccrTarifa',
            'ccrGenerarGuia',
            'ccrRegistroEnvio',
            'ccrMovilTracking'
        );
        $system = get_option( 'mojito-shipping-pymexpress-web-service-system', 'PYMEXPRESS' );
        if ( 'RETAIL' == $system ) {
            $system = 'PYMEXPRESS';
        }
        $this->credentials = array(
            'Username' => get_option( 'mojito-shipping-pymexpress-web-service-username' ),
            'Password' => get_option( 'mojito-shipping-pymexpress-web-service-password' ),
            'System'   => $system,
        );
        $environment = get_option( 'mojito-shipping-pymexpress-web-service-environment', 'sandbox' );
        if ( 'sandbox' === $environment ) {
            $this->environment['auth_port'] = 442;
            $this->environment['auth_url'] = 'https://servicios.correos.go.cr:442/Token/authenticate';
            $this->environment['process_url'] = 'http://amistad.correos.go.cr:84/wsAppCorreos.wsAppCorreos.svc?WSDL';
            $this->environment['process_port'] = 84;
        } elseif ( 'production' === $environment ) {
            $this->environment['auth_port'] = 447;
            $this->environment['auth_url'] = 'https://servicios.correos.go.cr:447/Token/authenticate';
            $this->environment['process_url'] = 'https://amistadpro.correos.go.cr:444/wsAppCorreos.wsAppCorreos.svc?WSDL';
            $this->environment['process_port'] = 444;
        }
    }

    /**
     * Authentication method
     */
    private function auth() {
        $this->delay();
        if ( empty( $this->credentials['Username'] ) || empty( $this->credentials['Password'] ) ) {
            mojito_shipping_debug( __( 'Username or Password empty.', 'mojito-shipping' ) );
            $this->log( 'warning.', __( 'Username or Password empty.', 'mojito-shipping' ) );
            return;
        }
        if ( empty( $this->environment['auth_port'] ) || empty( $this->environment['auth_url'] ) ) {
            mojito_shipping_debug( __( 'auth_port or auth_url empty.', 'mojito-shipping' ) );
            $this->log( 'warning.', __( 'auth_port or auth_url empty.', 'mojito-shipping' ) );
            return;
        }
        $body = array(
            'Username' => $this->credentials['Username'],
            'Password' => $this->credentials['Password'],
            'Sistema'  => $this->credentials['System'],
        );
        $curl = curl_init();
        $parameters = array(
            CURLOPT_PORT           => $this->environment['auth_port'],
            CURLOPT_URL            => $this->environment['auth_url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => wp_json_encode( $body ),
            CURLOPT_HTTPHEADER     => array('Content-Type: application/json'),
            CURLOPT_CONNECTTIMEOUT => apply_filters( 'mojito_shipping_pymexpress_connection_time_out', 10 ),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_VERBOSE        => true,
        );
        $parameters = $this->set_proxy_settings( $parameters );
        curl_setopt_array( $curl, $parameters );
        $response = curl_exec( $curl );
        $err = curl_error( $curl );
        $info = curl_getinfo( $curl );
        curl_close( $curl );
        if ( $err ) {
            mojito_shipping_debug( $parameters );
            mojito_shipping_debug( $response );
            mojito_shipping_debug( $info );
            mojito_shipping_debug( $err );
            echo sprintf( __( 'There was an error with Correos de Costa Rica: %s', 'mojito-shipping' ), $err ) . "\n";
            echo '<a href="https://mojitowp.com/documentacion/pymexpress/#3.11">' . __( 'Checkout this documentation.', 'mojito-shipping' ) . '</a>';
            $this->log( 'error', sprintf( 'Error in auth query: %s', $err ) );
        } else {
            if ( empty( $response ) ) {
                mojito_shipping_debug( __( 'Authentication issues.', 'mojito-shipping' ) );
                return;
            }
            $this->token = $response;
            $this->token_timestamp = time();
            $_SESSION['ccr_token'] = array(
                'token' => $this->token,
                'time'  => $this->token_timestamp,
            );
            return $this->token;
        }
    }

    /**
     * Get token
     */
    public function get_token() {
        // Max token lifetime is 5 min. Due the connection timeout is 5 - 30 seconds we calculate 4 min 30 s.
        if ( empty( $_SESSION['ccr_token']['time'] ) ) {
            return $this->auth();
        }
        $current_token_time = $_SESSION['ccr_token']['time'];
        if ( time() - $current_token_time < 270 ) {
            return $_SESSION['ccr_token']['token'];
        } else {
            return $this->auth();
        }
    }

    /**
     * Get provincias from CCR WS
     *
     * @return array
     */
    public function get_provincias() {
        $provincias = array();
        $response = $this->request( 'ccrCodProvincia' );
        if ( !empty( $response['error'] ) ) {
            return $provincias;
        }
        foreach ( $response->aProvincias->accrItemGeografico as $key => $obj ) {
            $data = (array) $obj;
            $codigo = (string) $data['aCodigo'];
            $descripcion = $data['aDescripcion'];
            $provincias[$codigo] = $descripcion;
        }
        return $provincias;
    }

    /**
     * Get cantones from a Provincia
     *
     * @param string $codigo_provincia Provicia ID.
     * @return array
     */
    public function get_cantones( $codigo_provincia ) {
        $cantones = array();
        $replacements = array(
            '%CodProvincia%' => $codigo_provincia,
        );
        $data_types = array(
            '%CodProvincia%' => array(
                'type'   => 'string',
                'length' => 1,
            ),
        );
        if ( $this->check_parameters( $replacements, $data_types, __FUNCTION__ ) ) {
            $response = $this->request( 'ccrCodCanton', $replacements );
            if ( !empty( $response['error'] ) ) {
                return $cantones;
            }
            if ( is_countable( $response->aCantones->accrItemGeografico ) ) {
                foreach ( $response->aCantones->accrItemGeografico as $key => $obj ) {
                    $data = (array) $obj;
                    $codigo = (string) $data['aCodigo'];
                    $descripcion = $data['aDescripcion'];
                    $cantones[$codigo] = $descripcion;
                }
            }
        }
        return $cantones;
    }

    /**
     * Get distritos from a Provincia and Canton
     *
     * @param string $codigo_provincia Provicia ID.
     * @param string $codigo_canton Cantón ID.
     * @return array
     */
    public function get_distritos( $codigo_provincia, $codigo_canton ) {
        $distritos = array();
        $replacements = array(
            '%CodProvincia%' => $codigo_provincia,
            '%CodCanton%'    => $codigo_canton,
        );
        $data_types = array(
            '%CodProvincia%' => array(
                'type'   => 'string',
                'length' => 1,
            ),
            '%CodCanton%'    => array(
                'type'   => 'string',
                'length' => 2,
            ),
        );
        if ( $this->check_parameters( $replacements, $data_types, __FUNCTION__ ) ) {
            $response = $this->request( 'ccrCodDistrito', $replacements );
            if ( !empty( $response['error'] ) ) {
                return $distritos;
            }
            foreach ( $response->aDistritos->accrItemGeografico as $key => $obj ) {
                $data = (array) $obj;
                $codigo = (string) $data['aCodigo'];
                $descripcion = $data['aDescripcion'];
                $distritos[$codigo] = $descripcion;
            }
        }
        return $distritos;
    }

    /**
     * Get barrios from a Provincia, Canton and Distrito
     *
     * @param string $codigo_provincia Provicia ID.
     * @param string $codigo_canton Cantón ID.
     * @param string $codigo_distrito Distrito ID.
     * @return array
     */
    public function get_barrios( $codigo_provincia, $codigo_canton, $codigo_distrito ) {
        $barrios = array();
        $replacements = array(
            '%CodProvincia%' => $codigo_provincia,
            '%CodCanton%'    => $codigo_canton,
            '%CodDistrito%'  => $codigo_distrito,
        );
        $data_types = array(
            '%CodProvincia%' => array(
                'type'   => 'string',
                'length' => 1,
            ),
            '%CodCanton%'    => array(
                'type'   => 'string',
                'length' => 2,
            ),
            '%CodDistrito%'  => array(
                'type'   => 'string',
                'length' => 2,
            ),
        );
        if ( $this->check_parameters( $replacements, $data_types, __FUNCTION__ ) ) {
            $response = $this->request( 'ccrCodBarrio', $replacements );
            if ( !empty( $response['error'] ) ) {
                return $barrios;
            }
            foreach ( $response->aBarrios->accrBarrio as $key => $obj ) {
                $data = (array) $obj;
                $codigo = (string) $data['aCodBarrio'];
                $sucursal = (string) $data['aCodSucursal'];
                $nombre = $data['aNombre'];
                $barrios[] = array(
                    'codigo'   => $codigo,
                    'nombre'   => $nombre,
                    'sucursal' => $sucursal,
                );
            }
        }
        return $barrios;
    }

    /**
     * Get Zip code from a Provincia, Canton and Distrito
     *
     * @param string $codigo_provincia Provicia ID.
     * @param string $codigo_canton Cantón ID.
     * @param string $codigo_distrito Distrito ID.
     * @return string
     */
    public function get_codigo_postal( $codigo_provincia, $codigo_canton, $codigo_distrito ) {
        $zip = '';
        $replacements = array(
            '%CodProvincia%' => $codigo_provincia,
            '%CodCanton%'    => $codigo_canton,
            '%CodDistrito%'  => $codigo_distrito,
        );
        $data_types = array(
            '%CodProvincia%' => array(
                'type'   => 'string',
                'length' => 1,
            ),
            '%CodCanton%'    => array(
                'type'   => 'string',
                'length' => 2,
            ),
            '%CodDistrito%'  => array(
                'type'   => 'string',
                'length' => 2,
            ),
        );
        if ( $this->check_parameters( $replacements, $data_types, __FUNCTION__ ) ) {
            $response = $this->request( 'ccrCodPostal', $replacements );
            if ( !empty( $response['error'] ) ) {
                return $zip;
            }
            $data = (array) $response->aCodPostal;
            $zip = $data[0];
        }
        return $zip;
    }

    /**
     * Get Tarifa
     *
     * @param array $args
     * - provincia_origen  Origin Provincia.
     * - canton_origen  Origin Canton ID.
     * - provincia_destino Destination Provincia ID.
     * - canton_destino Destination Canton ID.
     * - servicio  Service.
     * - peso Weight in grams.
     * @return string
     */
    public function get_tarifa( $args ) {
        $parameters = array_merge( array(
            'provincia_origen'  => '',
            'canton_origen'     => '',
            'distrito_origen'   => '',
            'provincia_destino' => '',
            'canton_destino'    => '',
            'distrito_destino'  => '',
            'servicio'          => '',
            'peso'              => '',
        ), $args );
        $provincia_origen = $parameters['provincia_origen'];
        $canton_origen = $parameters['canton_origen'];
        $distrito_origen = $parameters['distrito_origen'];
        $provincia_destino = $parameters['provincia_destino'];
        $canton_destino = $parameters['canton_destino'];
        $distrito_destino = $parameters['distrito_destino'];
        $servicio = $parameters['servicio'];
        $peso = $parameters['peso'];
        $rate = array(
            'tarifa'    => 0,
            'descuento' => 0,
            'impuesto'  => 0,
            'respuesta' => '',
            'mensaje'   => '',
        );
        $replacements = array(
            '%ProvinciaOrigen%'  => $provincia_origen,
            '%CantonOrigen%'     => $canton_origen,
            '%ProvinciaDestino%' => $provincia_destino,
            '%CantonDestino%'    => $canton_destino,
            '%Servicio%'         => $servicio,
            '%Peso%'             => $peso,
        );
        $data_types = array(
            '%ProvinciaOrigen%'  => array(
                'type'   => 'string',
                'length' => 1,
            ),
            '%CantonOrigen%'     => array(
                'type'   => 'string',
                'length' => 2,
            ),
            '%ProvinciaDestino%' => array(
                'type'   => 'string',
                'length' => 1,
            ),
            '%CantonDestino%'    => array(
                'type'   => 'string',
                'length' => 2,
            ),
            '%Servicio%'         => array(
                'type'   => 'string',
                'length' => 5,
            ),
            '%Peso%'             => array(
                'type' => 'numeric',
            ),
        );
        if ( !empty( $distrito_origen ) ) {
            $replacements['%DistritoOrigen%'] = $distrito_origen;
            $data_types['%DistritoOrigen%'] = array(
                'type'   => 'string',
                'length' => 2,
            );
        }
        if ( !empty( $distrito_destino ) ) {
            $replacements['%DistritoDestino%'] = $distrito_destino;
            $data_types['%DistritoDestino%'] = array(
                'type'   => 'string',
                'length' => 2,
            );
        }
        if ( $this->check_parameters( $replacements, $data_types, __FUNCTION__ ) ) {
            $response = $this->request( 'ccrTarifa', $replacements );
            if ( !empty( $response['error'] ) ) {
                $rate['respuesta'] = $response['code'];
                $rate['mensaje'] = $response['error'];
                return $rate;
            }
            $data = (array) $response;
            $rate = array(
                'respuesta' => $data['aCodRespuesta'],
                'mensaje'   => $data['aMensajeRespuesta'],
                'tarifa'    => $data['aMontoTarifa'],
                'descuento' => $data['aDescuento'],
                'impuesto'  => $data['aImpuesto'],
            );
        }
        return $rate;
    }

    /**
     * Generar Guía
     *
     * @return string
     */
    public function generar_guia() {
        $response = $this->request( 'ccrGenerarGuia' );
        if ( !empty( $response['error'] ) ) {
            return '';
        }
        $data = (array) $response;
        return $data['aNumeroEnvio'];
    }

    /**
     * Registrar envío.
     *
     * @param Int   $order_id Order id.
     * @param array $params Params to register the package.
     * @return mixed
     */
    public function registro_envio( $order_id, $params ) {
        /**	
         * Correos de Costa Rica no soporta ampersand (&) en el parámetro SEND_NOMBRE y posiblemente otros
         */
        $send_nombre = get_option( 'mojito-shipping-pymexpress-sender-name' );
        $send_nombre = str_replace( '&', '', $send_nombre );
        $response = '';
        $status = '';
        $replacements = array(
            '%Cliente%'        => get_option( 'mojito-shipping-pymexpress-web-service-client-code' ),
            '%COD_CLIENTE%'    => get_option( 'mojito-shipping-pymexpress-web-service-client-code' ),
            '%DEST_APARTADO%'  => ( !empty( $params['DEST_APARTADO'] ) ? $params['DEST_APARTADO'] : '' ),
            '%DEST_DIRECCION%' => ( !empty( $params['DEST_DIRECCION'] ) ? $params['DEST_DIRECCION'] : '' ),
            '%DEST_NOMBRE%'    => ( !empty( $params['DEST_NOMBRE'] ) ? $params['DEST_NOMBRE'] : '' ),
            '%DEST_TELEFONO%'  => ( !empty( $params['DEST_TELEFONO'] ) ? $params['DEST_TELEFONO'] : '' ),
            '%DEST_ZIP%'       => ( !empty( $params['DEST_ZIP'] ) ? $params['DEST_ZIP'] : '' ),
            '%ENVIO_ID%'       => ( !empty( $params['ENVIO_ID'] ) ? $params['ENVIO_ID'] : '' ),
            '%FECHA_ENVIO%'    => gmdate( 'Y-m-d\\TH:i:s' ),
            '%MONTO_FLETE%'    => ( !empty( $params['MONTO_FLETE'] ) ? $params['MONTO_FLETE'] : 0 ),
            '%OBSERVACIONES%'  => ( !empty( $params['OBSERVACIONES'] ) ? substr( $params['OBSERVACIONES'], 0, 200 ) : '' ),
            '%PESO%'           => ( !empty( $params['PESO'] ) ? round( $params['PESO'] ) : '' ),
            '%SEND_DIRECCION%' => get_option( 'mojito-shipping-pymexpress-sender-address' ),
            '%SEND_NOMBRE%'    => $send_nombre,
            '%SEND_TELEFONO%'  => get_option( 'mojito-shipping-pymexpress-sender-phone' ),
            '%SEND_ZIP%'       => strval( get_option( 'mojito-shipping-pymexpress-sender-zip-code' ) ),
            '%SERVICIO%'       => get_option( 'mojito-shipping-pymexpress-web-service-service-id' ),
            '%USUARIO_ID%'     => intval( get_option( 'mojito-shipping-pymexpress-web-service-user-id' ) ),
            '%VARIABLE_1%'     => ( !empty( $params['VARIABLE_1'] ) ? $params['VARIABLE_1'] : '' ),
            '%VARIABLE_3%'     => ( !empty( $params['VARIABLE_3'] ) ? $params['VARIABLE_3'] : '' ),
            '%VARIABLE_4%'     => ( !empty( $params['VARIABLE_4'] ) ? $params['VARIABLE_4'] : '' ),
            '%VARIABLE_5%'     => ( !empty( $params['VARIABLE_5'] ) ? $params['VARIABLE_5'] : '' ),
            '%VARIABLE_6%'     => ( !empty( $params['VARIABLE_6'] ) ? $params['VARIABLE_6'] : '' ),
            '%VARIABLE_7%'     => ( !empty( $params['VARIABLE_7'] ) ? $params['VARIABLE_7'] : '' ),
            '%VARIABLE_8%'     => ( !empty( $params['VARIABLE_8'] ) ? $params['VARIABLE_8'] : '' ),
            '%VARIABLE_9%'     => ( !empty( $params['VARIABLE_9'] ) ? $params['VARIABLE_9'] : '' ),
            '%VARIABLE_10%'    => ( !empty( $params['VARIABLE_10'] ) ? $params['VARIABLE_10'] : '' ),
            '%VARIABLE_11%'    => ( !empty( $params['VARIABLE_11'] ) ? $params['VARIABLE_11'] : '' ),
            '%VARIABLE_12%'    => ( !empty( $params['VARIABLE_12'] ) ? $params['VARIABLE_12'] : '' ),
            '%VARIABLE_13%'    => ( !empty( $params['VARIABLE_13'] ) ? $params['VARIABLE_13'] : '' ),
            '%VARIABLE_14%'    => ( !empty( $params['VARIABLE_14'] ) ? $params['VARIABLE_14'] : '' ),
            '%VARIABLE_15%'    => ( !empty( $params['VARIABLE_15'] ) ? $params['VARIABLE_15'] : '' ),
            '%VARIABLE_16%'    => ( !empty( $params['VARIABLE_16'] ) ? $params['VARIABLE_16'] : '' ),
        );
        $data_types = array(
            '%Cliente%'        => array(
                'type'   => 'string',
                'length' => 10,
            ),
            '%COD_CLIENTE%'    => array(
                'type'   => 'string',
                'length' => 20,
            ),
            '%FECHA_ENVIO%'    => array(
                'type' => 'datetime',
            ),
            '%ENVIO_ID%'       => array(
                'type'   => 'string',
                'length' => 25,
            ),
            '%SERVICIO%'       => array(
                'type'   => 'string',
                'length' => 5,
            ),
            '%MONTO_FLETE%'    => array(
                'type' => 'numeric',
            ),
            '%DEST_NOMBRE%'    => array(
                'type'   => 'string',
                'length' => 200,
            ),
            '%DEST_DIRECCION%' => array(
                'type'   => 'string',
                'length' => 500,
            ),
            '%DEST_TELEFONO%'  => array(
                'type'   => 'string',
                'length' => 15,
            ),
            '%DEST_APARTADO%'  => array(
                'type'   => 'string',
                'length' => 20,
            ),
            '%DEST_ZIP%'       => array(
                'type'   => 'string',
                'length' => 8,
            ),
            '%SEND_NOMBRE%'    => array(
                'type'   => 'string',
                'length' => 200,
            ),
            '%SEND_DIRECCION%' => array(
                'type'   => 'string',
                'length' => 500,
            ),
            '%SEND_ZIP%'       => array(
                'type'   => 'string',
                'length' => 8,
            ),
            '%SEND_TELEFONO%'  => array(
                'type'   => 'string',
                'length' => 15,
            ),
            '%OBSERVACIONES%'  => array(
                'type'   => 'string',
                'length' => 200,
            ),
            '%USUARIO_ID%'     => array(
                'type' => 'numeric',
            ),
            '%PESO%'           => array(
                'type' => 'numeric',
            ),
            '%VARIABLE_1%'     => array(
                'type'     => 'string',
                'length'   => 10,
                'optional' => true,
            ),
            '%VARIABLE_3%'     => array(
                'type'     => 'string',
                'length'   => 1,
                'optional' => true,
            ),
            '%VARIABLE_4%'     => array(
                'type'     => 'string',
                'length'   => 100,
                'optional' => true,
            ),
            '%VARIABLE_5%'     => array(
                'type'     => 'numeric',
                'optional' => true,
            ),
            '%VARIABLE_6%'     => array(
                'type'     => 'string',
                'length'   => 2,
                'optional' => true,
            ),
            '%VARIABLE_7%'     => array(
                'type'     => 'string',
                'length'   => 1,
                'optional' => true,
            ),
            '%VARIABLE_8%'     => array(
                'type'     => 'string',
                'length'   => 10,
                'optional' => true,
            ),
            '%VARIABLE_9%'     => array(
                'type'     => 'string',
                'length'   => 1,
                'optional' => true,
            ),
            '%VARIABLE_10%'    => array(
                'type'     => 'string',
                'length'   => 1,
                'optional' => true,
            ),
            '%VARIABLE_11%'    => array(
                'type'     => 'string',
                'length'   => 1,
                'optional' => true,
            ),
            '%VARIABLE_12%'    => array(
                'type'     => 'numeric',
                'optional' => true,
            ),
            '%VARIABLE_13%'    => array(
                'type'     => 'string',
                'length'   => 50,
                'optional' => true,
            ),
            '%VARIABLE_14%'    => array(
                'type'     => 'string',
                'length'   => 50,
                'optional' => true,
            ),
            '%VARIABLE_15%'    => array(
                'type'     => 'string',
                'length'   => 50,
                'optional' => true,
            ),
            '%VARIABLE_16%'    => array(
                'type'     => 'string',
                'length'   => 10,
                'optional' => true,
            ),
        );
        if ( $this->check_parameters( $replacements, $data_types, __FUNCTION__ ) ) {
            $order = wc_get_order( $order_id );
            $order->update_meta_data( 'mojito_pymexpress_shipping_log', $replacements );
            $response = $this->request( 'ccrRegistroEnvio', $replacements );
            if ( !empty( $response['error'] ) ) {
                $this->log( 'info', sprintf( 'Args: %s', print_r( $this->clean_soap_fields_to_parameters( $replacements ), 1 ) ) );
                $order->update_meta_data( 'mojito_shipping_pymexpress_ccrRegistroEnvio_response_error', print_r( $response['error'], 1 ) );
                $order->save();
                return $status;
            }
            if ( is_object( $response ) && isset( $response->aCodRespuesta ) ) {
                $response = (array) $response;
                $this->log( 'info', sprintf(
                    'Guide number: %s, Order id: %s, CodRespuesta: %s: %s',
                    $params['ENVIO_ID'],
                    $order_id,
                    $response['aCodRespuesta'],
                    $response['aMensajeRespuesta']
                ) );
                $this->log( 'info', sprintf( 'Args: %s', print_r( $this->clean_soap_fields_to_parameters( $replacements ), 1 ) ) );
                /**
                 * Try to update order details if CCR responds code 36
                 * "El Envio [guide number] ya existe en nuesta base de datos"
                 */
                if ( '36' !== $response['aCodRespuesta'] ) {
                    $order->update_meta_data( 'mojito_shipping_pymexpress_guide_number', sanitize_text_field( $params['ENVIO_ID'] ) );
                    $order->save();
                }
                $order->update_meta_data( 'mojito_shipping_pymexpress_ccrRegistroEnvio_response_code', $response['aCodRespuesta'] );
                $order->update_meta_data( 'mojito_shipping_pymexpress_pdf', $response['aPDF'] );
                $status = sprintf( '%s: %s', $response['aCodRespuesta'], $response['aMensajeRespuesta'] );
                $order->update_meta_data( 'mojito_pymexpress_shipping_log_response', $response );
                $order->save();
            } elseif ( !empty( $response ) ) {
                $this->log( 'info', sprintf( 'CodRespuesta: %s, Args: %s', print_r( $response, 1 ), print_r( $this->clean_soap_fields_to_parameters( $replacements ), 1 ) ) );
            } else {
                $this->log( 'info', sprintf( 'Args: %s', print_r( $this->clean_soap_fields_to_parameters( $replacements ), 1 ) ) );
            }
        } else {
            $this->log( 'warning', __( 'ccrRegistroEnvio aborted.', 'mojito-shipping' ) );
            $this->log( 'warning', print_r( $replacements, 1 ) );
            $log_url = '<a target="_blank" href="' . admin_url( 'admin.php?page=wc-status&tab=logs' ) . '">' . __( 'Open WooCommerce logs', 'mojito-shipping' ) . '</a>';
            // translators: URL.
            return sprintf( __( ' Register sending aborted due errors, check: %s', 'mojito-shipping' ), $log_url );
        }
        return $status;
    }

    /**
     * Get tracking movil
     *
     * @param string $guide_number Unique package PYMEXPRESS ID.
     * @return string
     */
    public function get_tracking( $guide_number ) {
        $data = array();
        $replacements = array(
            '%NumeroEnvio%' => $guide_number,
        );
        $data_types = array(
            '%NumeroEnvio%' => array(
                'type'   => 'string',
                'length' => 50,
            ),
        );
        if ( $this->check_parameters( $replacements, $data_types, __FUNCTION__ ) ) {
            $response = $this->request( 'ccrMovilTracking', $replacements );
            if ( !empty( $response['error'] ) ) {
                return wp_json_encode( $data );
            }
            $encabezado = (array) $response->aEncabezado;
            $data['encabezado'] = array(
                'estado'          => ( !empty( $encabezado['aEstado'] ) ? $encabezado['aEstado'] : '' ),
                'fecha-recepcion' => ( !empty( $encabezado['aFechaRecepcion'] ) ? $encabezado['aFechaRecepcion'] : '' ),
                'destinatario'    => ( !empty( $encabezado['aNombreDestinatario'] ) ? $encabezado['aNombreDestinatario'] : '' ),
            );
            foreach ( $response->aEventos->accrEvento as $key => $obj ) {
                $item = (array) $obj;
                $data['eventos'][] = array(
                    'evento'     => $item['aEvento'],
                    'fecha-hora' => $item['aFechaHora'],
                    'unidad'     => $item['aUnidad'],
                );
            }
        }
        $this->log( 'info', print_r( $data, 1 ) );
        return wp_json_encode( $data );
    }

    /**
     * Check the parameters before sent to CCR WS
     *
     * @param array $replacements Fields to check.
     * @param array $data_types Rules to check the fields.
     * @param array $method Who call this?.
     * @return bool
     */
    private function check_parameters( $replacements, $data_types, $method = '' ) {
        $try_register = true;
        $replacements = $this->clean_soap_fields_to_parameters( $replacements );
        $data_types = $this->clean_soap_fields_to_parameters( $data_types );
        foreach ( $replacements as $field => $field_value ) {
            $field_params = $data_types[$field];
            /**
             * Check if field is empty and if should be
             */
            if ( empty( $field_value ) ) {
                if ( !empty( $field_params['optional'] ) && true === $field_params['optional'] ) {
                    continue;
                } else {
                    // translators: Param lenght and param data.
                    $this->log( 'warning', sprintf( __( 'Empty parameter "%1$s" called from "%2$s".', 'mojito-shipping' ), $field, $method ) );
                    $try_register = false;
                }
            }
            /**
             * Check strings
             */
            if ( 'string' === $field_params['type'] ) {
                $max_length = $data_types[$field]['length'];
                $param_len = strlen( $field_value );
                if ( $param_len > $max_length ) {
                    // translators: Param lenght and param data.
                    $this->log( 'warning', sprintf(
                        __( '"%1$s" cannot exceed %2$s characters. Given: %3$s, "%4$s" called from "%5$s"', 'mojito-shipping' ),
                        $field,
                        $max_length,
                        $param_len,
                        $field_value,
                        $method
                    ) );
                    $try_register = false;
                }
            }
            /**
             * Check numbers
             */
            if ( 'numeric' === $field_params['type'] ) {
                if ( !is_numeric( $field_value ) ) {
                    $this->log( 'warning', sprintf(
                        __( 'Bad "%1$s" Given: "%2$s" called from "%3$s"', 'mojito-shipping' ),
                        $field,
                        $field_value,
                        $method
                    ) );
                    $try_register = false;
                }
            }
        }
        return $try_register;
    }

    /**
     * Remove the '%' of replacements array
     *
     * @param array $replacements items to clean.
     * @return array
     */
    private function clean_soap_fields_to_parameters( $replacements ) {
        $data = array();
        foreach ( $replacements as $field => $field_value ) {
            $field_name = str_replace( '%', '', $field );
            $data[$field_name] = $field_value;
        }
        return $data;
    }

    /**
     * Web Service Request
     *
     * @param string $method Method to call.
     * @param array  $replacements Params.
     * @return mixed
     */
    private function request( $method = null, $replacements = array() ) {
        if ( is_null( $method ) ) {
            return;
        }
        if ( !in_array( $method, $this->methods, true ) ) {
            return -1;
        }
        $this->delay();
        $soap_fields = $this->get_soap_fields( $method, $replacements );
        $curl = curl_init();
        $parameters = array(
            CURLOPT_PORT           => $this->environment['process_port'],
            CURLOPT_URL            => $this->environment['process_url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $soap_fields,
            CURLOPT_FAILONERROR    => false,
            CURLOPT_HTTPHEADER     => array('Authorization: ' . $this->get_token(), 'Content-Type: text/xml; charset=utf-8', 'SOAPAction: http://tempuri.org/IwsAppCorreos/' . $method),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_VERBOSE        => true,
        );
        $parameters = $this->set_proxy_settings( $parameters );
        curl_setopt_array( $curl, $parameters );
        $response = curl_exec( $curl );
        mojito_shipping_debug( $method . ' ejectutado en ' . $this->environment['process_url'] );
        $err = curl_error( $curl );
        if ( $err ) {
            echo sprintf( __( 'There was an error with Correos de Costa Rica: %s', 'mojito-shipping' ), $err ) . "\n";
            echo '<a href="https://mojitowp.com/documentacion/pymexpress/#3.11">' . __( 'Checkout this documentation.', 'mojito-shipping' ) . '</a>';
            $this->log( 'error', sprintf( 'Error in service query: %s', $err ) );
            return;
        }
        // SimpleXML seems to have problems with the colon ":" in the <xxx:yyy> response tags, so take them out.
        $xml = preg_replace( '/(<\\/?)(\\w+):([^>]*>)/', '$1$2$3', $response );
        $xml = simplexml_load_string( $xml );
        $str_response = $method . 'Response';
        $str_result = $method . 'Result';
        if ( !isset( $xml->sBody->{$str_response}->{$str_result} ) ) {
            mojito_shipping_debug( $xml );
            return $xml->head->title;
        }
        $response = $xml->sBody->{$str_response}->{$str_result};
        mojito_shipping_debug( $response );
        /**
         * Error codes from CCR
         */
        if ( !empty( $response ) ) {
            if ( '00' !== (string) $response->aCodRespuesta ) {
                $this->log( 'error', 'Pymexpress: ' . $response->aMensajeRespuesta );
                return array(
                    'error' => $response->aMensajeRespuesta,
                    'code'  => $response->aCodRespuesta,
                );
            }
        }
        return $response;
    }

    /**
     * Set proxy settings in the curl options
     *
     * @param array $parameters Curl params.
     * @return array
     */
    private function set_proxy_settings( $parameters ) {
        /**
         * Proxy settings
         */
        if ( 'true' === get_option( 'mojito-shipping-pymexpress-mojito-proxy-enable', 'false' ) ) {
        } elseif ( 'true' === get_option( 'mojito-shipping-pymexpress-proxy-enable', 'false' ) ) {
            $proxy_hostname = trim( get_option( 'mojito-shipping-pymexpress-proxy-ip' ) );
            $proxy_username = rawurlencode( trim( get_option( 'mojito-shipping-pymexpress-proxy-username' ) ) );
            $proxy_password = rawurlencode( trim( get_option( 'mojito-shipping-pymexpress-proxy-password' ) ) );
            $proxy_port = rawurlencode( trim( get_option( 'mojito-shipping-pymexpress-proxy-port' ) ) );
            $parameters[CURLOPT_PROXY] = "{$proxy_hostname}:{$proxy_port}";
            $parameters[CURLOPT_PROXYUSERPWD] = "{$proxy_username}:{$proxy_password}";
        }
        return $parameters;
    }

    /**
     * Prepare soap string before request.
     *
     * @param string $method Method to evaluate.
     * @param array  $replacements Data to set into SOAP string.
     * @return string
     */
    private function get_soap_fields( $method, $replacements = array() ) {
        $fields = array(
            'ccrCodProvincia'  => "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:tem=\"http://tempuri.org/\">\r\n   <soapenv:Header/>\r\n   <soapenv:Body>\r\n      <tem:ccrCodProvincia/>\r\n   </soapenv:Body>\r\n</soapenv:Envelope>",
            'ccrCodCanton'     => "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:tem=\"http://tempuri.org/\">\r\n   <soapenv:Header/>\r\n   <soapenv:Body>\r\n      <tem:ccrCodCanton>\r\n         <tem:CodProvincia>%CodProvincia%</tem:CodProvincia>\r\n      </tem:ccrCodCanton>\r\n   </soapenv:Body>\r\n</soapenv:Envelope>",
            'ccrCodDistrito'   => "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:tem=\"http://tempuri.org/\">\r\n   <soapenv:Header/>\r\n   <soapenv:Body>\r\n      <tem:ccrCodDistrito>\r\n         <tem:CodProvincia>%CodProvincia%</tem:CodProvincia>\r\n         <tem:CodCanton>%CodCanton%</tem:CodCanton>\r\n      </tem:ccrCodDistrito>\r\n   </soapenv:Body>\r\n</soapenv:Envelope>",
            'ccrCodBarrio'     => "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:tem=\"http://tempuri.org/\">\r\n   <soapenv:Header/>\r\n   <soapenv:Body>\r\n      <tem:ccrCodBarrio>\r\n         <tem:CodProvincia>%CodProvincia%</tem:CodProvincia>\r\n         <tem:CodCanton>%CodCanton%</tem:CodCanton>\r\n         <tem:CodDistrito>%CodDistrito%</tem:CodDistrito>\r\n      </tem:ccrCodBarrio>\r\n   </soapenv:Body>\r\n</soapenv:Envelope>",
            'ccrCodPostal'     => "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:tem=\"http://tempuri.org/\">\r\n   <soapenv:Header/>\r\n   <soapenv:Body>\r\n      <tem:ccrCodPostal>\r\n         <tem:CodProvincia>%CodProvincia%</tem:CodProvincia>\r\n         <tem:CodCanton>%CodCanton%</tem:CodCanton>\r\n         <tem:CodDistrito>%CodDistrito%</tem:CodDistrito>\r\n      </tem:ccrCodPostal>\r\n   </soapenv:Body>\r\n</soapenv:Envelope>",
            'ccrTarifa'        => "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:tem=\"http://tempuri.org/\" xmlns:wsap=\"http://schemas.datacontract.org/2004/07/wsAppCorreos\">\r\n   <soapenv:Header/>\r\n   <soapenv:Body>\r\n      <tem:ccrTarifa>\r\n         <tem:reqTarifa>\r\n            <wsap:CantonDestino>%CantonDestino%</wsap:CantonDestino>\r\n            <wsap:CantonOrigen>%CantonOrigen%</wsap:CantonOrigen>\r\n            <wsap:DistritoDestino>%DistritoDestino%</wsap:DistritoDestino>\r\n            <wsap:DistritoOrigen>%DistritoOrigen%</wsap:DistritoOrigen>\r\n            <wsap:Peso>%Peso%</wsap:Peso>\r\n            <wsap:ProvinciaDestino>%ProvinciaDestino%</wsap:ProvinciaDestino>\r\n            <wsap:ProvinciaOrigen>%ProvinciaOrigen%</wsap:ProvinciaOrigen>\r\n            <wsap:Servicio>%Servicio%</wsap:Servicio>\r\n         </tem:reqTarifa>\r\n      </tem:ccrTarifa>\r\n   </soapenv:Body>\r\n</soapenv:Envelope>",
            'ccrGenerarGuia'   => "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:tem=\"http://tempuri.org/\">\r\n   <soapenv:Header/>\r\n   <soapenv:Body>\r\n      <tem:ccrGenerarGuia/>\r\n   </soapenv:Body>\r\n</soapenv:Envelope>",
            'ccrRegistroEnvio' => "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:tem=\"http://tempuri.org/\" xmlns:wsap=\"http://schemas.datacontract.org/2004/07/wsAppCorreos\">\r\n   <soapenv:Header/>\r\n   <soapenv:Body>\r\n      <tem:ccrRegistroEnvio>\r\n         <tem:ccrReqEnvio>\r\n            <wsap:Cliente>%Cliente%</wsap:Cliente>\r\n            <wsap:Envio>\r\n               <wsap:COD_CLIENTE>%COD_CLIENTE%</wsap:COD_CLIENTE>\r\n               <wsap:DEST_APARTADO>%DEST_APARTADO%</wsap:DEST_APARTADO>\r\n               <wsap:DEST_DIRECCION>%DEST_DIRECCION%</wsap:DEST_DIRECCION>\r\n               <wsap:DEST_NOMBRE>%DEST_NOMBRE%</wsap:DEST_NOMBRE>\r\n               <wsap:DEST_TELEFONO>%DEST_TELEFONO%</wsap:DEST_TELEFONO>\r\n               <wsap:DEST_ZIP>%DEST_ZIP%</wsap:DEST_ZIP>\r\n               <wsap:ENVIO_ID>%ENVIO_ID%</wsap:ENVIO_ID>\r\n               <wsap:FECHA_ENVIO>%FECHA_ENVIO%</wsap:FECHA_ENVIO>\r\n               <wsap:MONTO_FLETE>%MONTO_FLETE%</wsap:MONTO_FLETE>\r\n               <wsap:OBSERVACIONES>%OBSERVACIONES%</wsap:OBSERVACIONES>\r\n               <wsap:PESO>%PESO%</wsap:PESO>\r\n               <wsap:SEND_DIRECCION>%SEND_DIRECCION%</wsap:SEND_DIRECCION>\r\n               <wsap:SEND_NOMBRE>%SEND_NOMBRE%</wsap:SEND_NOMBRE>\r\n               <wsap:SEND_TELEFONO>%SEND_TELEFONO%</wsap:SEND_TELEFONO>\r\n               <wsap:SEND_ZIP>%SEND_ZIP%</wsap:SEND_ZIP>\r\n               <wsap:SERVICIO>%SERVICIO%</wsap:SERVICIO>\r\n               <wsap:USUARIO_ID>%USUARIO_ID%</wsap:USUARIO_ID>\r\n               <wsap:VARIABLE_1>%VARIABLE_1%</wsap:VARIABLE_1>\r\n               <wsap:VARIABLE_10>%VARIABLE_10%</wsap:VARIABLE_10>\r\n               <wsap:VARIABLE_11>%VARIABLE_11%</wsap:VARIABLE_11>\r\n               <wsap:VARIABLE_12>%VARIABLE_12%</wsap:VARIABLE_12>\r\n               <wsap:VARIABLE_13>%VARIABLE_13%</wsap:VARIABLE_13>\r\n               <wsap:VARIABLE_14>%VARIABLE_14%</wsap:VARIABLE_14>\r\n               <wsap:VARIABLE_15>%VARIABLE_15%</wsap:VARIABLE_15>\r\n               <wsap:VARIABLE_16>%VARIABLE_16%</wsap:VARIABLE_16>\r\n               <wsap:VARIABLE_3>%VARIABLE_3%</wsap:VARIABLE_3>\r\n               <wsap:VARIABLE_4>%VARIABLE_4%</wsap:VARIABLE_4>\r\n               <wsap:VARIABLE_5>%VARIABLE_5%</wsap:VARIABLE_5>\r\n               <wsap:VARIABLE_6>%VARIABLE_6%</wsap:VARIABLE_6>\r\n               <wsap:VARIABLE_7>%VARIABLE_7%</wsap:VARIABLE_7>\r\n               <wsap:VARIABLE_8>%VARIABLE_8%</wsap:VARIABLE_8>\r\n               <wsap:VARIABLE_9>%VARIABLE_9%</wsap:VARIABLE_9>\r\n            </wsap:Envio>\r\n         </tem:ccrReqEnvio>\r\n      </tem:ccrRegistroEnvio>\r\n   </soapenv:Body>\r\n</soapenv:Envelope>",
            'ccrMovilTracking' => "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:tem=\"http://tempuri.org/\">\r\n   <soapenv:Header/>\r\n   <soapenv:Body>\r\n      <tem:ccrMovilTracking>\r\n         <tem:NumeroEnvio>%NumeroEnvio%</tem:NumeroEnvio>\r\n      </tem:ccrMovilTracking>\r\n   </soapenv:Body>\r\n</soapenv:Envelope>",
        );
        $field = $fields[$method];
        foreach ( $replacements as $key => $value ) {
            if ( empty( $value ) ) {
                continue;
            }
            $field = str_replace( $key, $value, $field );
        }
        // Remove empty replacements.
        $field = preg_replace( '/(%[0-9a-zA-z_]+%)/', '', $field );
        return $field;
    }

    /**
     * Log messages to WC_Logger.
     *
     * @param String $message Message to log.
     */
    public function log( $level, $message ) {
        if ( class_exists( 'WC_Logger' ) ) {
            $logger = new \WC_Logger();
            $logger->log( $level, $message, [] );
        }
        mojito_shipping_debug( $message );
    }

    public function delay() {
        $key = 'mojito_shipping_pymexpress_last_request_time';
        if ( !isset( $_SESSION[$key] ) ) {
            $_SESSION[$key] = round( microtime( true ) * 1000 );
            return true;
        }
        $last_request_time = $_SESSION[$key];
        if ( empty( $last_request_time ) ) {
            $_SESSION[$key] = round( microtime( true ) * 1000 );
            return true;
        }
        $current_request_time = round( microtime( true ) * 1000 );
        $difference = $current_request_time - $last_request_time;
        $frecuency = 1000 / 1;
        // 1 request per second
        $sleep = $frecuency - $difference;
        if ( $difference < $frecuency ) {
            $waiting_time = 1000;
            usleep( $sleep * $waiting_time );
        }
        return true;
    }

}

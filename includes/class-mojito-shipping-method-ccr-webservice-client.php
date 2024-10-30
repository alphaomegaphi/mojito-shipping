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
 */
class Mojito_Shipping_Method_CCR_WSC {
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
     * Constructor for webservice client
     *
     * @access public
     * @return void
     */
    public function __construct() {
    }

    /**
     * Setup
     *
     * @return void
     */
    private function setup() {
        $this->methods = array('ccrGenerarGuia', 'ccrRegistroEnvio', 'ccrMovilTracking');
        $this->credentials = array(
            'User' => get_option( 'mojito-shipping-ccr-web-service-username' ),
            'Pass' => get_option( 'mojito-shipping-ccr-web-service-password' ),
        );
    }

    /**
     * Get Guide number
     *
     * @return void
     */
    public function ccr_get_guide_number() {
        $this->setup();
        $args = array(
            'Datos' => array(
                'CodCliente'  => get_option( 'mojito-shipping-ccr-web-service-client-code' ),
                'TipoCliente' => get_option( 'mojito-shipping-ccr-web-service-client-type' ),
            ),
        );
        $guide_id = null;
        $guide_data = $this->request( 'ccrGenerarGuia', $args );
        /**
         * Check if $guide_data is a valid object
         */
        if ( !is_object( $guide_data ) ) {
            return;
        }
        if ( '00' === $guide_data->ccrGenerarGuiaResult->Cod_Respuesta ) {
            $guide_id = $guide_data->ccrGenerarGuiaResult->ListadoXML;
        } else {
            $this->log( sprintf( 'Guía: %s: %s', $guide_data->ccrGenerarGuiaResult->Cod_Respuesta, $guide_data->ccrGenerarGuiaResult->Mensaje_Respuesta ) );
        }
        return $guide_id;
    }

    /**
     * Register sendding
     *
     * @param Int    $order_id Order id.
     * @param String $guide_number CCR guide number.
     * @param String $details Order details.
     * @param Float  $shipping_cost Shipping cost.
     * @param String $full_name Client name.
     * @param String $address Client address.
     * @param String $phone Client phone number.
     * @param String $post_code Client zip code.
     * @param Int    $shipping_weight order weight.
     * @return object
     */
    public function ccr_register_sending(
        $order_id,
        $guide_number,
        $details,
        $shipping_cost,
        $full_name,
        $address,
        $phone,
        $post_code,
        $shipping_weight
    ) {
        $try_register = true;
        $response = '';
        $this->setup();
        /**
         * $post_code
         * String length 20
         * Número del código postal del destinatario (campo requerido).
         */
        $param_len = strlen( $post_code );
        if ( $param_len > 20 ) {
            // translators: Param lenght and param data.
            $this->log( sprintf( __( 'DEST_APARTADO cannot exceed 20 characters. Given: %1$s, "%2$s"', 'mojito-shipping' ), $param_len, $post_code ) );
            $try_register = false;
        }
        /**
         * $service_id
         * String length 5
         * Número del servicio, Este se le será proporcionado (ver página 4).
         */
        $service_id = get_option( 'mojito-shipping-ccr-web-service-service-id' );
        $param_len = strlen( $service_id );
        if ( $param_len > 5 ) {
            // translators: Param lenght and param data.
            $this->log( sprintf( __( 'SERVICIO cannot exceed 5 characters. Given: %1$s, "%2$s"', 'mojito-shipping' ), $param_len, $service_id ) );
            $try_register = false;
        }
        /**
         * $dest_direccion
         * String length 300
         * Dirección física del destinatario.
         */
        $dest_direccion = $address;
        $param_len = strlen( $dest_direccion );
        if ( $param_len > 300 ) {
            // translators: Param lenght and param data.
            $this->log( sprintf( __( 'DEST_DIRECCION cannot exceed 300 characters. Given: %1$s, "%2$s"', 'mojito-shipping' ), $param_len, $dest_direccion ) );
            $dest_direccion = substr( $dest_direccion, 0, 300 );
        }
        /**
         * $dest_nombre
         * String length 100
         * Nombre del destinatario.
         */
        $dest_nombre = $full_name;
        $param_len = strlen( $dest_nombre );
        if ( $param_len > 100 ) {
            // translators: Param lenght and param data.
            $this->log( sprintf( __( 'DEST_NOMBRE cannot exceed 100 characters. Given: %1$s, "%2$s"', 'mojito-shipping' ), $param_len, $dest_nombre ) );
            $dest_nombre = substr( $dest_nombre, 0, 100 );
        }
        /**
         * $dest_telefono
         * String length 10
         * Número telefónico del destinatario.
         */
        $dest_telefono = $phone;
        $dest_telefono = str_replace( '-', '', $dest_telefono );
        $dest_telefono = str_replace( '+', '', $dest_telefono );
        $dest_telefono = str_replace( ' ', '', $dest_telefono );
        $param_len = strlen( $dest_telefono );
        if ( $param_len > 10 ) {
            // translators: Param lenght and param data.
            $this->log( sprintf( __( 'DEST_TELEFONO cannot exceed 10 characters. Given: %1$s, "%2$s"', 'mojito-shipping' ), $param_len, $dest_telefono ) );
            $try_register = false;
        }
        /**
         * $dest_zip
         * String length 8
         * Código postal del destinatario.
         */
        $param_len = strlen( $post_code );
        if ( $param_len > 8 ) {
            // translators: Param lenght and param data.
            $this->log( sprintf( __( 'DEST_ZIP cannot exceed 8 characters. Given: %1$s, "%2$s"', 'mojito-shipping' ), $param_len, $post_code ) );
            $try_register = false;
        }
        /**
         * $envio_id
         * String length 25
         * Número de guía generada por el proceso ccrGenerarGuia.
         */
        $envio_id = ( $guide_number ? sanitize_text_field( $guide_number ) : '' );
        $param_len = strlen( $envio_id );
        if ( $param_len > 25 || empty( $envio_id ) ) {
            // translators: Param lenght and param data.
            $this->log( sprintf( __( 'ENVIO_ID cannot exceed 25 characters. Given: %1$s, "%2$s"', 'mojito-shipping' ), $param_len, $envio_id ) );
            $try_register = false;
        }
        /**
         * $fecha_recepcion
         * Datetime
         * Fecha actual en la que se genera la guía. (Date.now)
         */
        $fecha_recepcion = strtotime( gmdate( 'Y-m-d H:i:s' ) );
        /**
         * $id_distrito_destino
         * String length 30
         * Código postal del distrito destino.
         */
        $id_distrito_destino = $post_code;
        $param_len = strlen( $post_code );
        if ( $param_len > 30 ) {
            // translators: Param lenght and param data.
            $this->log( sprintf( __( 'ID_DISTRITO_DESTINO cannot exceed 30 characters. Given: %1$s, "%2$s"', 'mojito-shipping' ), $param_len, $id_distrito_destino ) );
            $try_register = false;
        }
        /**
         * $monto_flete
         * String length ?
         * Monto del flete.
         */
        $monto_flete = $shipping_cost;
        if ( empty( $monto_flete ) || !is_numeric( $monto_flete ) || $monto_flete < 0 ) {
            // translators: Param lenght and param data.
            $this->log( sprintf( __( 'Bad MONTO_FLETE Given: %s', 'mojito-shipping' ), $monto_flete ) );
            $monto_flete = 0;
        }
        /**
         * $observaciones
         * String length 200
         * Descripción del contenido del envío. (Por ejemplo: accesorios, zapatos, libros, CDs, etc)
         */
        $observaciones = ( !empty( $details ) ? $details : '' );
        $param_len = strlen( $observaciones );
        if ( $param_len > 200 ) {
            // translators: Param lenght and param data.
            $this->log( sprintf( __( 'OBSERVACIONES cannot exceed 200 characters. Given: %1$s, "%2$s"', 'mojito-shipping' ), $param_len, $observaciones ) );
            $observaciones = substr( $observaciones, 0, 200 );
        }
        /**
         * $shipping_weight
         * Decimal
         * Peso del envío en gramos
         */
        if ( $shipping_weight < 0 ) {
            $shipping_weight = 0;
        }
        /**
         * $cliente_id
         * String length 10
         * Identificación del cliente. Este se le será proporcionado (ver página 4)
         */
        $cliente_id = get_option( 'mojito-shipping-ccr-web-service-client-code' );
        $param_len = strlen( $cliente_id );
        if ( $param_len > 10 ) {
            // translators: Param lenght and param data.
            $this->log( sprintf( __( 'CLIENTE_ID cannot exceed 10 characters. Given: %1$s, "%2$s"', 'mojito-shipping' ), $param_len, $cliente_id ) );
            $try_register = false;
        }
        /**
         * $send_direccion
         * String lenght 300
         * Dirección física del remitente.
         */
        $send_direccion = get_option( 'mojito-shipping-ccr-sender-address' );
        $param_len = strlen( $send_direccion );
        if ( $param_len > 300 ) {
            // translators: Param lenght and param data.
            $this->log( sprintf( __( 'SEND_DIRECCION cannot exceed 300 characters. Given: %1$s, "%2$s"', 'mojito-shipping' ), $param_len, $send_direccion ) );
            $send_direccion = substr( $send_direccion, 0, 300 );
        }
        /**
         * $send_nombre
         * String length 100
         * Nombre del remitente.
         */
        $send_nombre = get_option( 'mojito-shipping-ccr-sender-name' );
        $param_len = strlen( $send_nombre );
        if ( $param_len > 100 ) {
            // translators: Param lenght and param data.
            $this->log( sprintf( __( 'SEND_NOMBRE cannot exceed 100 characters. Given: %1$s, "%2$s"', 'mojito-shipping' ), $param_len, $send_nombre ) );
            $send_nombre = substr( $send_nombre, 0, 100 );
        }
        /**
         * $send_telefono
         * String length 50
         * Número telefónico del remitente.
         */
        $send_telefono = str_replace( '-', '', get_option( 'mojito-shipping-ccr-sender-phone' ) );
        $param_len = strlen( $send_telefono );
        if ( $param_len > 50 ) {
            // translators: Param lenght and param data.
            $this->log( sprintf( __( 'SEND_TELEFONO cannot exceed 50 characters. Given: %1$s, "%2$s"', 'mojito-shipping' ), $param_len, $send_telefono ) );
            $send_telefono = substr( $send_telefono, 0, 50 );
        }
        /**
         * $send_zip
         * String length 8
         * Código postal del remitente.
         */
        $send_zip = strval( get_option( 'mojito-shipping-ccr-sender-zip-code' ) );
        $param_len = strlen( $send_zip );
        if ( $param_len > 8 ) {
            // translators: Param lenght and param data.
            $this->log( sprintf( __( 'SEND_ZIP cannot exceed 8 characters. Given: %1$s, "%2$s"', 'mojito-shipping' ), $param_len, $send_zip ) );
            $send_zip = substr( $send_zip, 0, 8 );
        }
        /**
         * $usuario_id
         * Integer
         * Id del cliente. Esta se le será proporcionada. (ver página 4)
         */
        $usuario_id = intval( get_option( 'mojito-shipping-ccr-web-service-user-id' ) );
        if ( empty( $usuario_id ) ) {
            // translators: Param lenght and param data.
            $this->log( sprintf( __( 'Invalid USUARIO_ID. Given: %1$s', 'mojito-shipping' ), $usuario_id ) );
            $try_register = false;
        }
        $args = array(
            'ccrReqEnvio' => array(
                'Cliente' => get_option( 'mojito-shipping-ccr-web-service-client-code' ),
                'Envio'   => array(
                    'DEST_APARTADO'       => $post_code,
                    'SERVICIO'            => $service_id,
                    'DEST_DIRECCION'      => $dest_direccion,
                    'DEST_NOMBRE'         => $dest_nombre,
                    'DEST_TELEFONO'       => $dest_telefono,
                    'DEST_ZIP'            => $post_code,
                    'ENVIO_ID'            => $envio_id,
                    'FECHA_RECEPCION'     => $fecha_recepcion,
                    'ID_DISTRITO_DESTINO' => $id_distrito_destino,
                    'MONTO_FLETE'         => $monto_flete,
                    'OBSERVACIONES'       => $observaciones,
                    'PESO'                => $shipping_weight,
                    'CLIENTE_ID'          => $cliente_id,
                    'SEND_DIRECCION'      => $send_direccion,
                    'SEND_NOMBRE'         => $send_nombre,
                    'SEND_TELEFONO'       => $send_telefono,
                    'SEND_ZIP'            => $send_zip,
                    'USUARIO_ID'          => $usuario_id,
                ),
            ),
        );
        update_post_meta( $order_id, 'mojito_ccr_shipping_log', $args );
        if ( true === $try_register ) {
            $log = $this->request( 'ccrRegistroEnvio', $args );
        } else {
            $this->log( __( 'ccrRegistroEnvio aborted.', 'mojito-shipping' ) );
            $log_url = '<a target="_blank" href="' . admin_url( 'admin.php?page=wc-status&tab=logs' ) . '">' . __( 'Open WooCommerce logs', 'mojito-shipping' ) . '</a>';
            // translators: URL.
            return sprintf( __( ' Register sending aborted due errors, check: %s', 'mojito-shipping' ), $log_url );
        }
        /**
         * Save reponse code.
         */
        if ( true === $try_register && is_object( $log ) && isset( $log->ccrRegistroEnvioResult->Cod_Respuesta ) ) {
            $this->log( sprintf(
                'Guide number: %s, Order id: %s, Log: %s: %s',
                $guide_number,
                $order_id,
                $log->ccrRegistroEnvioResult->Cod_Respuesta,
                $log->ccrRegistroEnvioResult->Mensaje_Respuesta
            ) );
            $this->log( sprintf( 'Args: %s', print_r( $args, 1 ) ) );
            /**
             * Try to update order details if CCR responds code 36
             * "El Envio [guide number] ya existe en nuesta base de datos"
             */
            if ( '36' !== $log->ccrRegistroEnvioResult->Cod_Respuesta ) {
                update_post_meta( $order_id, 'mojito_shipping_ccr_guide_number', sanitize_text_field( $guide_number ) );
            }
            update_post_meta( $order_id, 'mojito_shipping_ccr_ccrRegistroEnvio_response_code', $log->ccrRegistroEnvioResult->Cod_Respuesta );
            $response = sprintf( '%s: %s', $log->ccrRegistroEnvioResult->Cod_Respuesta, $log->ccrRegistroEnvioResult->Mensaje_Respuesta );
            update_post_meta( $order_id, 'mojito_ccr_shipping_log_response', $response );
        } else {
            if ( !empty( $log ) ) {
                $this->log( sprintf( 'Log: %s, Args: %s', print_r( $log, 1 ), print_r( $args, 1 ) ) );
            } else {
                $this->log( sprintf( 'Args: %s', print_r( $args, 1 ) ) );
            }
        }
        return $response;
    }

    /**
     * Get tracking information
     *
     * @param String $guide_id CCR guide number.
     * @return object
     */
    public function ccr_get_movil_tracking( $guide_id ) {
        $this->setup();
        $args = array(
            'NumeroEnvio' => $guide_id,
        );
        $tracking_data = $this->request( 'ccrMovilTracking', $args );
        if ( is_object( $tracking_data ) ) {
            return $tracking_data->ccrMovilTrackingResult->Listado;
        } else {
            return;
        }
    }

    /**
     * Web Service Request
     *
     * @param String $method Method to call.
     * @param array  $args Params.
     * @return void
     */
    private function request( $method = null, $args = array() ) {
        if ( is_null( $method ) ) {
            return;
        }
        if ( empty( $args ) ) {
            return;
        }
        if ( !in_array( $method, $this->methods, true ) ) {
            return -1;
        }
        $params = array_merge( $this->credentials, $args );
        $proxy_hostname = trim( get_option( 'mojito-shipping-ccr-proxy-ip' ) );
        $proxy_username = rawurlencode( trim( get_option( 'mojito-shipping-ccr-proxy-username' ) ) );
        $proxy_password = rawurlencode( trim( get_option( 'mojito-shipping-ccr-proxy-password' ) ) );
        $proxy_port = rawurlencode( trim( get_option( 'mojito-shipping-ccr-proxy-port' ) ) );
        $web_service_url = trim( get_option( 'mojito-shipping-ccr-web-service-url' ) );
        $options = array(
            'trace'              => true,
            'uri'                => 'urn:webservices',
            'connection_timeout' => 15,
        );
        $wsdl_url = $web_service_url;
        if ( 'true' === get_option( 'mojito-shipping-ccr-mojito-proxy-enable', 'false' ) ) {
        } elseif ( 'true' === get_option( 'mojito-shipping-ccr-proxy-enable', 'false' ) ) {
            // Proxy connection.
            $options['proxy_host'] = $proxy_hostname;
            $options['proxy_port'] = $proxy_port;
            $options['proxy_login'] = $proxy_username;
            $options['proxy_password'] = $proxy_password;
        }
        try {
            $client = new \SoapClient($wsdl_url, $options);
            $result = $client->__soapCall( $method, array(
                'parameters' => $params,
            ) );
        } catch ( \Exception $e ) {
            $result = -1;
            mojito_shipping_debug( array(
                'error'  => $e,
                'libxml' => libxml_get_last_error(),
            ) );
            echo __( 'There was an error with Correos de Costa Rica: ', 'mojito-shipping' ) . $e->getMessage() . "\n";
            echo '<a href="https://mojitowp.com/documentacion/sistema-saliente/#3.11">' . __( 'Checkout this documentation.', 'mojito-shipping' ) . '</a>';
            $this->log( sprintf( 'Error in service query: %s', $e->getMessage() ) );
        } catch ( \SoapFault $s ) {
            $result = -1;
            mojito_shipping_debug( array(
                'error'  => $e,
                'libxml' => libxml_get_last_error(),
            ) );
            echo __( 'There was an error with Correos de Costa Rica: ', 'mojito-shipping' ) . $s->getMessage() . "\n";
            echo '<a href="https://mojitowp.com/documentacion/sistema-saliente/#3.11">' . __( 'Checkout this documentation.', 'mojito-shipping' ) . '</a>';
            $this->log( sprintf( 'Error in service query: %s', $s->getMessage() ) );
        }
        return $result;
    }

    /**
     * Log messages to WC_Logger.
     *
     * @param String $message Message to log.
     */
    public function log( $message ) {
        $logger = new \WC_Logger();
        $name = 'Mojito Shipping CCR';
        mojito_shipping_debug( $message );
        $logger->add( $name, $message );
    }

}

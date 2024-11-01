<?php
/**
 * Plugin Name: WooCommerce Areeba Migs
 * Description: Extends WooCommerce with Areeba MasterCard Internet Gateway Service (MIGS).
 * Version: 1.0.0
 * Text Domain: areeba-migs
 * Domain Path: /languages
 * Author: Ali Basheer
 * Author URI: http://alibasheer.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    return;
}

/**
 * Adds plugin page links
 *
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Configure")
 */
function wc_areeba_gateway_plugin_links( $links ) {
    $plugin_links = array(
        '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=areeba_migs' ) . '">' . __( 'Configure', 'areeba-migs' ) . '</a>'
    );
    return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_areeba_gateway_plugin_links' );

/**
 * Add the gateway to WC Available Gateways
 *
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + areeba gateway
 */
function wc_areeba_add_to_gateways( $gateways ) {
    $gateways[] = 'WC_Areeba_Migs';
    return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'wc_areeba_add_to_gateways' );


/**
 * WooCommerce Areeba Migs
 *
 * Extends WooCommerce with Areeba MasterCard Internet Gateway Service (MIGS).
 *
 * @class 		WC_Areeba_Migs
 * @extends		WC_Payment_Gateway
 * @version		1.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		Ali Basheer
 */
add_action( 'plugins_loaded', 'wc_areeba_migs_init', 11 );

function wc_areeba_migs_init() {

    class WC_Areeba_Migs extends WC_Payment_Gateway {

        /**
         * Constructor for the gateway.
         */
        public function __construct() {

            $this->id                 = 'areeba_migs';
            $this->icon               = apply_filters( 'wc_areeba_icon', plugins_url( 'images/mastercard.png' , __FILE__ ) );
            $this->has_fields         = false;
            $this->method_title       = __( 'Areeba Migs', 'areeba-migs' );
            $this->method_description = __( 'Allows Areeba MasterCard Internet Gateway Service (MIGS)', 'areeba-migs' );
            $this->order_button_text  = __( 'Proceed to Areeba', 'areeba-migs' );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title              = $this->get_option( 'title' );
            $this->description        = $this->get_option( 'description' );
            $this->merchant_id        = $this->get_option( 'merchant_id' );
            $this->access_code        = $this->get_option( 'access_code' );
            $this->secure_hash_secret = $this->get_option( 'secure_hash_secret' );
            $this->service_host       = $this->get_option( 'service_host' );
            $this->success_message    = $this->get_option( 'thank_you_msg' );
            $this->failed_message     = $this->get_option( 'transaction_failed_Msg' );

            $this->callback           = str_replace( 'https:', 'http:', home_url( '/wc-api/areeba-response' )  );

            // Actions
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_api_areeba-response', array( $this, 'check_areeba_migs_response' ) );
        }

        /**
         * Initialize Gateway Settings Form Fields
         */
        public function init_form_fields() {

            $this->form_fields = apply_filters( 'wc_areeba_form_fields', array(
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', 'areeba-migs' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable Areeba MIGS Payment Module.', 'areeba-migs' ),
                    'default' => 'yes',
                ),
                'title' => array(
                    'title'       => __( 'Title', 'areeba-migs' ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'areeba-migs' ),
                    'default'     => __( 'MasterCard', 'areeba-migs' ),
                    'desc_tip'    => true
                ),
                'description' => array(
                    'title'       => __( 'Description', 'areeba-migs' ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method description that the customer will see on your checkout.', 'areeba-migs' ),
                    'default'     => __( 'Pay securely by Credit Card/Debit Card through MasterCard Internet Gateway Service.', 'areeba-migs' ),
                    'desc_tip'    => true
                ),
                'merchant_id' => array(
                    'title'       => __( 'Merchant ID', 'areeba-migs' ),
                    'type'        => 'text',
                    'description' => __( 'Merchant ID, given by Areeba', 'areeba-migs' ),
                    'placeholder' => __( 'Merchant ID', 'areeba-migs' ),
                    'desc_tip'    => true
                ),
                'access_code' => array(
                    'title'       => __( 'Access Code', 'areeba-migs' ),
                    'type'        => 'text',
                    'description' => __( 'Access Code, given by Areeba', 'areeba-migs' ),
                    'placeholder' => __( 'Access Code', 'areeba-migs' ),
                    'desc_tip'    => true
                ),
                'secure_hash_secret' => array(
                    'title'       => __( 'Secure Hash Secret', 'areeba-migs' ),
                    'type'        => 'text',
                    'description' => __( 'Encrypted/Secure Hash Secret key, given by Areeba', 'areeba-migs' ),
                    'placeholder' => __( 'Secure Hash Secret', 'areeba-migs' ),
                    'desc_tip'    => true
                ),
                'service_host' => array(
                    'title'       => __( 'Areeba MIGS URL', 'areeba-migs' ),
                    'type'        => 'text',
                    'description' => __( 'Gateway URL, given by Areeba', 'areeba-migs' ),
                    'placeholder' => __( 'Areeba MIGS URL', 'areeba-migs' ),
                    'default'     => 'https://onlinepayment.areeba.com/TPGWeb/payment/prepayment.action',
                    'desc_tip'    => true
                ),
                'thank_you_msg' => array(
                    'title'       => __( 'Transaction Success Message', 'areeba-migs' ),
                    'type'        => 'textarea',
                    'description' => __( 'Put the message you want to display after a successfull transaction.', 'areeba-migs' ),
                    'placeholder' => __( 'Transaction Success Message', 'areeba-migs' ),
                    'default'     => __( 'Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.', 'areeba-migs' ),
                    'desc_tip'    => true
                ),
                'transaction_failed_Msg' => array(
                    'title'       => __( 'Transaction Failed Message', 'areeba-migs' ),
                    'type'        => 'textarea',
                    'description' => __( 'Put whatever message you want to display after a transaction failed.', 'areeba-migs' ),
                    'placeholder' => __( 'Transaction Failed Message', 'areeba-migs' ),
                    'default'     => __( 'Thank you for shopping with us. However, the transaction has been declined.', 'areeba-migs' ),
                    'desc_tip'    => true
                )
            ) );

        }

        /**
         * Process the payment and redirect to Areeba
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment( $order_id ) {

            $order        = wc_get_order( $order_id );
            $order_id_ref = $order_id . '_' . date( "ymds" );
            $order_info   = date( "Ymd" ) . '-' . rand( 1000, 9999 );
            $order_amount = 100 * $order->get_total();
            $md5Hash      = $this->secure_hash_secret;

            if( trim( $this->service_host ) == "" || $this->service_host == null ) {
                $this->service_host = "https://onlinepayment.areeba.com/TPGWeb/payment/prepayment.action";
            }

            $service_host = $this->service_host;

            $order_args = array(
                "accessCode"  => $this->access_code,
                "merchTxnRef" => $order_id_ref,
                "merchant"    => $this->merchant_id,
                "orderInfo"   => $order_info,
                "amount"      => $order_amount,
                "returnURL"   => $this->callback . '?',
            );
            ksort ( $order_args );

            foreach( $order_args as $key => $value ) {

                if ( strlen( $value ) > 0 ) {
                    $service_host .= ( $value === reset($order_args) ) ? '?' : '&' ;
                    $service_host .= urlencode( $key ) . "=" . urlencode( $value );
                    $md5Hash .= $value;
                }
            }

            $service_host .= "&vpc_SecureHash=". strtoupper( md5( $md5Hash ) );

            return array(
                'result'   => 'success',
                'redirect' => $service_host
            );
        }

        /**
         * Check and process Areeba MIGS response
         */
        public function check_areeba_migs_response() {

            global $woocommerce;

            $authorised = false;
            $md5Hash    = $this->secure_hash_secret;
            $order_id   = explode( '_', $_REQUEST['merchTxnRef'] );
            $order_id   = (int) $order_id[0];
            $order      = wc_get_order( $order_id );

            // Make sure user entered Transaction Success message otherwise use the default one
            if( trim( $this->success_message ) == "" || $this->success_message == null ) {
                $this->success_message = __( 'Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.', 'areeba-migs' );
            }

            // Make sure user entered Transaction Faild message otherwise use the default one
            if( trim( $this->failed_message ) == "" || $this->failed_message == null ) {
                $this->failed_message =  __( 'Thank you for shopping with us. However, the transaction has been declined.', 'areeba-migs' );
            }

            $msg['class']   = 'error';
            $msg['message'] = $this->failed_message;

            $vpc_Txn_Secure_Hash = addslashes($_REQUEST["vpc_SecureHash"]);
            unset($_REQUEST["vpc_SecureHash"]);
            ksort($_REQUEST);

            if ( strlen($md5Hash) > 0 && addslashes($_REQUEST['vpc_TxnResponseCode']) != "7" && $this->null2unknown($_REQUEST['vpc_TxnResponseCode']) != $this->null2unknown("") ) {

                $md5Hash_2 = $md5Hash;

                foreach( $_REQUEST as $key => $value ) {
                    if ( $key != "vpc_SecureHash" && strlen( $value ) > 0 && $key != 'action' ) {
                        $md5Hash_2 .= str_replace(" ",'+',$value);
                        $md5Hash   .= $value;
                    }
                }

                if ((strtoupper($vpc_Txn_Secure_Hash) == strtoupper(md5($md5Hash)) || strtoupper($vpc_Txn_Secure_Hash) == strtoupper(md5($md5Hash_2))) && $this->null2unknown( addslashes( $_REQUEST['vpc_TxnResponseCode'] ) ) == "0" ) {
                    $authorised = true;
                }
            }

            if( $authorised  && $order->get_status() !== 'completed' && $order->get_status() != 'processing' ) {

                try {

                    $msg['class']   = 'success';
                    $msg['message'] = $this->success_message;
                    $order->payment_complete();
                    $order->add_order_note( sprintf('Areeba MIGS Payment successful<br/>Receipt Number: %s', $this->null2unknown( addslashes( $_REQUEST['vpc_ReceiptNo'] ) ) ) );
                    $woocommerce->cart->empty_cart();
                } catch( Exception $e ) {

                    $msg['class']   = 'error';
                    $msg['message'] = $this->failed_message;
                    $order->update_status('failed');
                    $order->add_order_note( __( 'Payment Transaction Failed', 'areeba-migs' ) );
                }

            } else {
                $error_msg      = $this->response_description( $this->null2unknown( addslashes( $_REQUEST['vpc_TxnResponseCode'] ) ) );
                $msg['class']   = 'error';
                $msg['message'] = $this->failed_message . ' ' . $error_msg . '.';
                $order->update_status('failed');
                $order->add_order_note( $error_msg );
            }

            wc_add_notice( $msg['message'], $msg['class'] );
            wp_redirect( $order->get_checkout_order_received_url() );
            exit;
        }

        /**
         * Change empty data to user friendly sentence
         *
         * @param $data
         * @return string
         */
        private function null2unknown( $data ) {

            if ( $data == "" ) {
                return "No Value Returned";
            } else {
                return $data;
            }
        }

        /**
         * Response description according to Areeba MIGS
         *
         * @param $response_code Response code from payment gateway provider after payment request
         * @return string Description string of the response code
         */
        private function response_description( $response_code ) {
            switch ( $response_code ) {
                case "0"  : $result = __( 'Transaction Successful', 'areeba-migs' ); break;
                case "1"  : $result = __( 'Unknown Error', 'areeba-migs' ); break;
                case "2"  : $result = __( 'Bank Declined Transaction', 'areeba-migs' ); break;
                case "3"  : $result = __( 'No Reply from Bank', 'areeba-migs', 'areeba-migs' ); break;
                case "4"  : $result = __( 'Expired Card', 'areeba-migs' ); break;
                case "5"  : $result = __( 'Insufficient funds', 'areeba-migs' ); break;
                case "6"  : $result = __( 'Error Communicating with Bank', 'areeba-migs' ); break;
                case "7"  : $result = __( 'Payment Server System Error', 'areeba-migs' ); break;
                case "8"  : $result = __( 'Transaction Type Not Supported', 'areeba-migs' ); break;
                case "9"  : $result = __( 'Bank declined transaction (Do not contact Bank)', 'areeba-migs' ); break;
                case "?"  : $result = __( 'Transaction status is unknown', 'areeba-migs' ); break;
                case "A"  : $result = __( 'Transaction Aborted', 'areeba-migs' ); break;
                case "B"  : $result = __( 'Transaction Pending', 'areeba-migs' ); break;
                case "C"  : $result = __( 'Transaction Cancelled', 'areeba-migs' ); break;
                case "D"  : $result = __( 'Deferred transaction has been received and is awaiting processing', 'areeba-migs' ); break;
                case "E"  : $result = __( 'Invalid Credit Card', 'areeba-migs' ); break;
                case "F"  : $result = __( '3D Secure Authentication failed', 'areeba-migs' ); break;
                case "G"  : $result = __( 'Invalid Merchant', 'areeba-migs' ); break;
                case "I"  : $result = __( 'Card Security Code verification failed', 'areeba-migs' ); break;
                case "J"  : $result = __( 'Transaction already in use', 'areeba-migs' ); break;
                case "L"  : $result = __( 'Shopping Transaction Locked (Please try the transaction again later)', 'areeba-migs' ); break;
                case "M"  : $result = __( 'Please enter all required fields', 'areeba-migs' ); break;
                case "N"  : $result = __( 'Cardholder is not enrolled in Authentication scheme', 'areeba-migs' ); break;
                case "P"  : $result = __( 'Transaction has been received by the Payment Adaptor and is being processed', 'areeba-migs' ); break;
                case "Q"  : $result = __( 'IP Blocked', 'areeba-migs' ); break;
                case "R"  : $result = __( 'Transaction was not processed - Reached limit of retry attempts allowed', 'areeba-migs' ); break;
                case "S"  : $result = __( 'Duplicate SessionID (OrderInfo)', 'areeba-migs' ); break;
                case "T"  : $result = __( 'Address Verification Failed', 'areeba-migs' ); break;
                case "U"  : $result = __( 'Card Security Code Failed', 'areeba-migs' ); break;
                case "V"  : $result = __( 'Address Verification and Card Security Code Failed', 'areeba-migs' ); break;
                case "X"  : $result = __( 'Card Blocked', 'areeba-migs' ); break;
                case "Y"  : $result = __( 'Invalid URL', 'areeba-migs' ); break;
                case "Z"  : $result = __( 'Bin Blocked', 'areeba-migs' ); break;
                case "BL" : $result = __( 'Card Bin Limit Reached', 'areeba-migs' ); break;
                case "CL" : $result = __( 'Card Limit Reached', 'areeba-migs' ); break;
                case "LM" : $result = __( 'Merchant Amount Limit Reached', 'areeba-migs' ); break;

                default  : $result = __( 'Unknown response code', 'areeba-migs' );
            }
            return $result;
        }

    } // end WC_Areeba_Migs class
}
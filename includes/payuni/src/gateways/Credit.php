<?php
/**
 * Payuni_Payment_Credit class file
 *
 * @package payuni
 */

namespace PAYUNI\Gateways;

defined( 'ABSPATH' ) || exit;

/**
 * Payuni_Payment_Credit class for Credit Card payment
 */
class Credit extends AbstractGateway {

	/**
	 * Constructor
	 */
	public function __construct() {

		parent::__construct();

		$this->plugin_name        = 'payuni-payment-credit';
		$this->version            = '1.0.0';
		$this->has_fields         = true;
		$this->order_button_text  = __( 'PAYUNi Credit Card', 'woomp' );
		$this->id                 = 'payuni-credit';
		$this->method_title       = __( 'PAYUNi Credit Card Payment', 'woomp' );
		$this->method_description = __( 'Pay with PAYUNi Credit Card', 'woomp' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title            = $this->get_option( 'title' );
		$this->description      = $this->get_option( 'description' );
		$this->api_endpoint_url = 'api/credit';

		// add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		// add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		// add_filter( 'payuni_transaction_args_' . $this->id, array( $this, 'add_args' ), 10, 2 );
	}

	/**
	 * Setup form fields for payment
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'     => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				/* translators: %s: Gateway method title */
				'label'   => sprintf( __( 'Enable %s', 'woomp' ), $this->method_title ),
				'default' => 'no',
			),
			'title'       => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'default'     => $this->method_title,
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'css'         => 'width: 400px;',
				'default'     => $this->order_button_text,
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Payment fields
	 */
	public function payment_fields() { ?>
	<div class='card-wrapper' style="float:left"></div>
	<form id="payuniCreditForm" style="clear:both; padding-top:10px">
		<input type="text" id="payuni_card_number" name="payuni_card_number" placeholder="請輸入卡號，ex:1234 1234 1234 12345" style="width: 100%; max-width:350px; margin-bottom:5px; border-radius:5px">
		<br>
		<input type="text" id="payuni_card_expiry" name="payuni_card_expiry" placeholder="請輸入到期日，ex:01/24"/ style="width: 100%; max-width:350px; margin-bottom:5px; border-radius:5px" maxlength="7">
		<br>
		<input type="text" id="payuni_card_cvc" name="payuni_card_cvc" maxlength="3" placeholder="請輸入驗證碼，ex:123" style="width: 100%; max-width:350px; border-radius:5px"/>
	</form>
	<script>
		jQuery(function($){
			$(document).ready(function(){
				$('#payuniCreditForm').card({
					container: '.card-wrapper',
					formSelectors: {
						numberInput: 'input#payuni_card_number',
						expiryInput: 'input#payuni_card_expiry',
						cvcInput: 'input#payuni_card_cvc',
					},
				});	
			}) 
		})
	</script>
		<?php
	}

	/**
	 * Process payment
	 *
	 * @param string $order_id The order id.
	 * @return array
	 */
	public function process_payment( $order_id ) {

		if ( isset( $_POST['payuni_card_number'] ) && ! empty( $_POST['payuni_card_number'] ) ) {
			$order->update_meta_data( 'payuni_card_number', $_POST['payuni_card_number'] );
		}

		if ( isset( $_POST['payuni_card_expiry'] ) && ! empty( $_POST['payuni_card_expiry'] ) ) {
			$order->update_meta_data( 'payuni_card_expiry', $_POST['payuni_card_expiry'] );
		}

		if ( isset( $_POST['payuni_card_cvs'] ) && ! empty( $_POST['payuni_card_cvs'] ) ) {
			$order->update_meta_data( 'payuni_card_cvs', $_POST['payuni_card_cvs'] );
		}

		$order   = new \WC_Order( $order_id );
		$request = new Request( new self() );
		$resp    = $request->build_request( $order );
		
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Filter payment api arguments for virtual account payment
	 *
	 * @param array    $args The payment api arguments.
	 * @param WC_Order $order The order object.
	 * @return array
	 */
	public function add_args( $args, $order ) {
		return array_merge(
			$args,
			array(
				'CardNo'      => $order->get_meta( 'payuni_card_number' ),
				'CardExpired' => $order->get_meta( 'payuni_card_expiry' ),
				'CardCVC'     => $order->get_meta( 'payuni_card_cvc' ),
			)
		);
	}

	/**
	 * Redirect to paynow payment page
	 *
	 * @param WC_Order $order The order object.
	 * @return void
	 */
	public function receipt_page( $order ) {
		$request = new Request( $this );
		$request->build_request_form( $order );
	}

	/**
	 * Order meta fields for payment
	 *
	 * @return array
	 */
	public static function order_metas() {
		return array(
			'_payuni_tran_id'     => __( 'Transaction No', 'wan-pay' ),
			'_payuni_pan_no4'     => __( 'Card Last 4 Num', 'wan-pay' ),
			'_payuni_tran_status' => __( 'Tran Status', 'wan-pay' ),
		);
	}

	/**
	 * Display payment detail after order table
	 *
	 * @param WC_Order $order The order object.
	 * @return void
	 */
	public function get_detail_after_order_table( $order ) {
		if ( get_post_meta( $order->get_id(), '_payment_method', true ) === $this->id ) {
			echo '<h3 style="display:block">' . esc_html( __( '交易明細', 'wan-pay' ) ) . '</h3><table class="shop_table wanpya_payment_details"><tbody>';
			echo '<tr><td><strong>' . esc_html( __( '交易結果：', 'wan-pay' ) ) . esc_html( get_post_meta( $order->get_id(), '_payuni_result', true ) ) . '</strong></td>';
			echo '</tbody></table>';
		}
	}


}

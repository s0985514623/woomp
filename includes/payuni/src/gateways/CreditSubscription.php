<?php
/**
 * Payuni_Payment_Credit class file
 *
 * @package payuni
 */

namespace PAYUNI\Gateways;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Payuni_Payment_Credit class for Credit Card payment
 */
class CreditSubscription extends AbstractGateway {


	/**
	 * Constructor
	 */
	public function __construct() {

		parent::__construct();

		$this->plugin_name = 'payuni-payment-credit-subscription';
		$this->version     = '1.0.0';
		$this->has_fields  = true;
		// $this->order_button_text = __( '統一金流 PAYUNi 信用卡', 'woomp' );

		$this->id                 = 'payuni-credit-subscription';
		$this->method_title       = __( '統一金流 PAYUNi 信用卡定期定額', 'woomp' );
		$this->method_description = __( '透過統一金流 PAYUNi 信用卡定期定額付款', 'woomp' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title            = $this->get_option( 'title' );
		$this->description      = $this->get_option( 'description' );
		$this->supports         = [
			'products',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'subscription_payment_method_change',
			'subscription_payment_method_change_customer',
			'subscription_payment_method_change_admin',
			'multiple_subscriptions',
			'tokenization',
		];
		$this->api_endpoint_url = 'api/credit';

		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			[
				$this,
				'process_admin_options',
			]
		);

		add_filter( 'payuni_transaction_args_' . $this->id, [ $this, 'add_args' ], 10, 3 );
	}

	/**
	 * Setup form fields for payment
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = [
			'enabled'     => [
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				/* translators: %s: Gateway method title */
				'label'   => sprintf( __( 'Enable %s', 'woomp' ), $this->method_title ),
				'default' => 'no',
			],
			'title'       => [
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'default'     => $this->method_title,
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'desc_tip'    => true,
			],
			'description' => [
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'css'         => 'width: 400px;',
				'default'     => $this->order_button_text,
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
				'desc_tip'    => true,
			],
		];
	}

	/**
	 * 針對信用卡定期定額付款額外添加傳入的 API 參數
	 * 整理過後應該不用添加新的參數，所以直接 return
	 *
	 * @param array                                                                         $args  The payment api arguments.
	 * @see PAYUNI\Gateways\Request::get_transaction_args()
	 * @param \WC_Order                                                                     $order The order object.
	 * @param ?array{number:string, expiry:string, cvc:string, token_id:string, new:string} $card_data 卡片資料
	 *
	 * @return array
	 */
	public function add_args( array $args, \WC_Order $order, ?array $card_data ): array {
		return $args;
	}

	/**
	 * Process payment
	 *
	 * @param string $order_id The order id.
	 *
	 * @return array
	 */
	public function process_payment( $order_id ): array {

		// phpcs:disable
		$number   = ( isset($_POST[ $this->id . '-card-number' ]) ) ? wc_clean(wp_unslash($_POST[ $this->id . '-card-number' ])) : '';
		$expiry   = ( isset($_POST[ $this->id . '-card-expiry' ]) ) ? wc_clean(wp_unslash(str_replace(' ', '', $_POST[ $this->id . '-card-expiry' ]))) : '';
		$cvc      = ( isset($_POST[ $this->id . '-card-cvc' ]) ) ? wc_clean(wp_unslash($_POST[ $this->id . '-card-cvc' ])) : '';
		$token_id = ( isset($_POST[ 'wc-' . $this->id . '-payment-token' ]) ) ? wc_clean(wp_unslash($_POST[ 'wc-' . $this->id . '-payment-token' ])) : ''; // 如果是 新增付款方式，這個值會是 new
		$new      = ( isset($_POST[ 'wc-' . $this->id . '-new-payment-method' ]) ) ? wc_clean(wp_unslash($_POST[ 'wc-' . $this->id . '-new-payment-method' ])) : ''; // □ 儲存付款資訊，下次付款更方便的 checkbox
		// phpcs:enable
		/**
		 * @var array{number:string, expiry:string, cvc:string, token_id:string, new:string} $card_data 卡片資料
		 */
		$card_data = [
			'number'   => str_replace( ' ', '', $number ),
			'expiry'   => str_replace( '/', '', $expiry ),
			'cvc'      => $cvc,
			'token_id' => $token_id,
			'new'      => $new,
		];

		$request = new Request( new self() );

		/**
		 * 如果沒有註冊費，需要扣 5 元來取得 token
		 * 如果有註冊費，那就直接扣訂單金額就好
		 *
		 * @see https://github.com/j7-dev/woomp/issues/46#issuecomment-2143679058
	*/
		$order       = \wc_get_order( $order_id );
		$order_total = (int) $order->get_total();

		// 如果總金額為 0 ，就走 hash request 扣 5 元，之後退款.
		if ( 0 === $order_total ) {
			return $request->build_hash_request( $order, $card_data );
		}

		return $request->build_request( $order, $card_data );
	}

	/**
	 * Display payment detail after order table
	 *
	 * @param WC_Order $order The order object.
	 *
	 * @return void
	 */
	public function get_detail_after_order_table( $order ) {
		if ( $order->get_payment_method() === $this->id ) {

			$status   = $order->get_meta( '_payuni_resp_status', true );
			$message  = $order->get_meta( '_payuni_resp_message', true );
			$trade_no = $order->get_meta( '_payuni_resp_trade_no', true );
			$card_4no = $order->get_meta( '_payuni_card_number', true );

			$html = '
				<h2 class="woocommerce-order-details__title">交易明細</h2>
				<div class="responsive-table">
					<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
						<tbody>
							<tr>
								<th>狀態碼：</th>
								<td>' . esc_html( $status ) . '</td>
							</tr>
							<tr>
								<th>交易訊息：</th>
								<td>' . esc_html( $message ) . '</td>
							</tr>
							<tr>
								<th>交易編號：</th>
								<td>' . esc_html( $trade_no ) . '</td>
							</tr>
							<tr>
								<th>卡號末四碼：</th>
								<td>' . esc_html( $card_4no ) . '</td>
							</tr>
						</tbody>
					</table>
				</div>
			';
			echo $html;
		}
	}
}

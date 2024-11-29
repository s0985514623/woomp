<?php
/**
 * WooMP 訂單管理類別
 *
 * 此類別處理所有與 WooCommerce 訂單相關的功能，包含：
 * 1. 訂單狀態管理
 * 2. 訂單列表顯示優化
 * 3. 物流資訊處理
 * 4. 批次處理功能
 *
 * @package WooMP
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // 離開，防止直接訪問
}

if ( ! class_exists( 'WooMP_Order' ) ) {
	/**
	 * WooMP 訂單管理類別
	 */
	class WooMP_Order { //phpcs:ignore
		/**
		 * 初始化類別
		 *
		 * 註冊所有需要的 hooks 和 filters
		 *
		 * @return void
		 */
		public static function init() {
			$class = new self();

			// 訂單狀態相關
			add_action( 'init', [ $class, 'add_order_status' ] );
			add_filter( 'wc_order_statuses', [ $class, 'add_order_statuses' ] );
			add_filter( 'woocommerce_reports_order_statuses', [ $class, 'add_order_statuses' ] );
			add_filter( 'woocommerce_order_is_paid_statuses', [ $class, 'add_report_paid_statuses' ] );

			// 物流相關
			add_filter( 'wp_ajax_delete_shipping_ecpay_cvs', [ $class, 'delete_shipping_ecpay_cvs' ] );
			add_action( 'woocommerce_admin_order_data_after_shipping_address', [ $class, 'add_choose_cvs_btn' ] );
			add_action( 'admin_enqueue_scripts', [ $class, 'enqueue_choose_cvs_script' ] );

			// 訂單列表優化
			add_filter( 'manage_shop_order_posts_columns', [ $class, 'shop_order_columns' ], 11, 1 );
			add_action( 'manage_shop_order_posts_custom_column', [ $class, 'shop_order_column' ], 11, 2 );

			// 批次處理
			add_filter( 'bulk_actions-edit-shop_order', [ $class, 'bulk_action' ], 99, 1 );
			add_filter( 'handle_bulk_actions-edit-shop_order', [ $class, 'print_shipping_note' ], 10, 3 );
		}

		/**
		 * 註冊自定義訂單狀態
		 *
		 * @return void
		 */
		public function add_order_status() {
			$custom_statuses = [
				'wc-wmp-in-transit' => [
					'label'                     => '配送中',
					'public'                    => true,
					'show_in_admin_status_list' => true,
					'show_in_admin_all_list'    => true,
					'exclude_from_search'       => false,
				],
				'wc-wmp-shipped'    => [
					'label'                     => '已出貨',
					'public'                    => true,
					'show_in_admin_status_list' => true,
					'show_in_admin_all_list'    => true,
					'exclude_from_search'       => false,
				],
			];

			foreach ( $custom_statuses as $status_key => $status_args ) {
				register_post_status( $status_key, $status_args );
			}
		}

		/**
		 * 新增訂單狀態到 WooCommerce 狀態列表
		 *
		 * @param array $order_statuses 現有訂單狀態.
		 * @return array 更新後的訂單狀態
		 */
		public function add_order_statuses( $order_statuses ) {
			$new_order_statuses = [];
			foreach ( $order_statuses as $key => $status ) {
				$new_order_statuses[ $key ] = $status;
				if ( 'wc-processing' === $key ) {
					$new_order_statuses['wc-wmp-in-transit'] = '配送中';
					$new_order_statuses['wc-wmp-shipped']    = '已出貨';
				}
			}
			return $new_order_statuses;
		}

		/**
		 * 新增已付款狀態到報表
		 *
		 * @param array $statuses 現有狀態.
		 * @return array 更新後的狀態
		 */
		public function add_report_paid_statuses( $statuses ) {
			$statuses[] = 'wmp-in-transit';
			$statuses[] = 'wmp-shipped';
			return array_unique( $statuses );
		}

		/**
		 * Ajax 處理刪除綠界物流資訊
		 *
		 * @return void
		 */
		public function delete_shipping_ecpay_cvs() {
			// 檢查 nonce 和權限
			if ( ! check_ajax_referer( 'delete_shipping_ecpay_cvs', 'nonce', false ) || ! current_user_can( 'edit_shop_orders' ) ) {
				wp_send_json_error( '權限不足' );
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$order_id = isset( $_POST['orderId'] ) ? absint( $_POST['orderId'] ) : 0;
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$ecpay_shipping_id = isset( $_POST['ecpayShippingId'] ) ? sanitize_text_field( wp_unslash( $_POST['ecpayShippingId'] ) ) : '';

			if ( ! $order_id || empty( $ecpay_shipping_id ) ) {
				wp_send_json_error( '參數錯誤' );
			}

			$ecpay_shipping_info = get_post_meta( $order_id, '_ecpay_shipping_info', true );
			if ( ! is_array( $ecpay_shipping_info ) ) {
				wp_send_json_error( '找不到物流資訊' );
			}

			unset( $ecpay_shipping_info[ $ecpay_shipping_id ] );
			update_post_meta( $order_id, '_ecpay_shipping_info', $ecpay_shipping_info );

			wp_send_json_success( '已成功刪除物流資訊' );
		}

		/**
		 * 在訂單列表增加自定義欄位
		 *
		 * @param array $columns 現有欄位.
		 * @return array 更新後的欄位
		 */
		public function shop_order_columns( $columns ) {
			$add_index = array_search( 'shipping_address', array_keys( $columns ), true ) + 1;
			$pre_array = array_splice( $columns, 0, $add_index );
			$array     = [
				'wmp_payment_no'  => __( '金流單號', 'ry-woocommerce-tools' ),
				'wmp_shipping_no' => __( '物流單號', 'ry-woocommerce-tools' ),
			];
			return array_merge( $pre_array, $array, $columns );
		}

		/**
		 * 顯示訂單列表自定義欄位內容
		 *
		 * @param string $column  欄位名稱.
		 * @param int    $post_id 訂單 ID.
		 * @return void
		 */
		public function shop_order_column( $column, $post_id ) {
			$order = wc_get_order( $post_id );
			if ( ! $order ) {
				return;
			}

			switch ( $column ) {
				case 'wmp_payment_no':
					echo esc_html( $order->get_transaction_id() );
					break;

				case 'wmp_shipping_no':
					$this->display_shipping_number( $order );
					break;

				case 'billing_address':
				case 'shipping_address':
					$this->display_address_phone( $column, $order );
					break;
			}
		}

		/**
		 * 顯示物流單號
		 *
		 * @param WC_Order $order WooCommerce 訂單物件.
		 * @return void
		 */
		private function display_shipping_number( $order ) {
			$shipping_list   = $order->get_meta( '_ecpay_shipping_info', true );
			$paynow_shipping = get_post_meta( $order->get_id(), '_paynow_shipping_paymentno', true );

			if ( is_array( $shipping_list ) ) {
				foreach ( $shipping_list as $item ) {
					if ( isset( $item['LogisticsType'] ) ) {
						switch ( $item['LogisticsType'] ) {
							case 'CVS':
							case 'HOME':
								echo esc_html(
									! empty( $item['PaymentNo'] )
									? $item['PaymentNo'] . ' ' . $item['ValidationNo']
									: $item['ID']
								);
								break;
							case 'POST':
								echo esc_html( $item['BookingNote'] ?? '' );
								break;
						}
					}
				}
			} elseif ( $paynow_shipping ) {
				echo esc_html( $paynow_shipping );
			} else {
				$this->display_shipping_input( $order->get_id() );
			}
		}

		/**
		 * 顯示物流單號輸入框
		 *
		 * @param int $order_id 訂單 ID.
		 * @return void
		 */
		private function display_shipping_input( $order_id ) {
			$current_no = get_post_meta( $order_id, 'wmp_shipping_no', true );
			?>
			<div class="shippingNoWrap">
				<input type="text"
					name="shippingNo"
					placeholder="請輸入物流單號"
					value="<?php echo esc_attr( $current_no ); ?>"
					style="width: 100%;"
					maxlength="100">
				<input type="hidden" class="orderId" value="<?php echo esc_attr( $order_id ); ?>">
				<div class="shipping-no-loading">
					<div class="lds-spinner">
			<?php for ( $i = 0; $i < 12; $i++ ) : ?>
							<div></div>
						<?php endfor; ?>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * 顯示地址和電話資訊
		 *
		 * @param string   $column 欄位名稱.
		 * @param WC_Order $order  WooCommerce 訂單物件.
		 * @return void
		 */
		private function display_address_phone( $column, $order ) {
			if ( get_option( 'wc_woomp_setting_show_phone', 1 ) !== 'yes' ) {
				return;
			}

			if ( 'billing_address' === $column ) {
				printf(
					'<span class="billing-phone" style="display: block;">電話 %s</span>',
					esc_html( $order->get_billing_phone() )
				);
			} elseif ( 'shipping_address' === $column && $order->get_shipping_phone() ) {
				printf(
					'<span class="shipping-phone" style="display: block;">電話 %s</span>',
					esc_html( $order->get_shipping_phone() )
				);
			}
		}

		/**
		 * 處理列印物流單的批次操作
		 *
		 * @param string $redirect_to 重定向 URL.
		 * @param string $action      執行的動作.
		 * @param array  $ids         訂單 ID 列表.
		 * @return string|void
		 */
		public function print_shipping_note( $redirect_to, $action, $ids ) {
			if ( false !== strpos( $action, 'ry_print_ecpay_' ) ) {
				$redirect_to = add_query_arg(
					[
						'orderid'  => implode( ',', $ids ),
						'type'     => substr( $action, 15 ),
						'noheader' => 1,
					],
					admin_url( 'admin.php?page=ry_print_ecpay_shipping' )
				);
				wp_redirect( $redirect_to );
				exit();
			} elseif ( 'wmp_print_hct' === $action ) {
				set_time_limit( 0 );
				$csv_arr   = [];
				$csv_arr[] = [ '序號', '訂單號', '收件人姓名', '收件人地址', '收件人電話', '託運備註', '商品別編號', '商品數量', '才積重量', '代收貨款', '指定配送日期', '指定配送時間' ];

				$filename = current_time( 'Y-m-d' ) . '-hct-export.csv';

				header( 'Pragma: no-cache' );
				header( 'Expires: 0' );
				header( 'Content-Disposition: attachment;filename="' . $filename . '";' );
				header( 'Content-Type: application/csv; charset=UTF-8' );

				$i = 1;

				foreach ( $ids as $id ) {
					$order     = wc_get_order( $id );
					$csv_arr[] = [
						'serial'           => $i,
						'order_id'         => $id,
						'shipping_name'    => ( $order->get_shipping_last_name() ) ? $order->get_shipping_last_name() . $order->get_shipping_first_name() : $order->get_billing_last_name() . $order->get_billing_first_name(),
						'shipping_address' => ( $order->get_shipping_address_1() ) ? $order->get_shipping_postcode() . $order->get_shipping_state() . $order->get_shipping_city() . $order->get_shipping_address_1() : $order->get_billing_postcode() . $order->get_billing_state() . $order->get_billing_city() . $order->get_billing_address_1(),
						'phone'            => $order->get_billing_phone(),
						'note'             => '',
						'product_num'      => '',
						'qty'              => count( $order->get_items() ),
						'weight'           => '',
						'amount'           => ( 'woomp_cod_gateway' === $order->get_payment_method() ) ? $order->get_total() : '',
						'deliver_date'     => '',
						'deliver_time'     => '',
					];
					++$i;
				}

				$csv_arr_length = count( $csv_arr );
				for ( $j = 0; $j < $csv_arr_length; $j++ ) {
					echo $this->csvstr( $csv_arr[ $j ] ) . "\n";
				}
			}
		}

		/**
		 * 確保輸出內容符合 CSV 格式
		 *
		 * @param array $fields CSV 欄位資料.
		 * @return string|false
		 */
		private function csvstr( array $fields ) {
			$f = fopen( 'php://memory', 'r+' );
			if ( fputcsv( $f, $fields ) === false ) {
				return false;
			}
			rewind( $f );
			$csv_line = stream_get_contents( $f );
			return rtrim( $csv_line );
		}

		/**
		 * 新增批次處理選項
		 *
		 * @param array $actions 現有批次處理選項.
		 * @return array 更新後的批次處理選項
		 */
		public function bulk_action( $actions ) {
			if ( ! wc_string_to_bool( get_option( 'RY_WT_enabled_ecpay_shipping' ) ) ) {
				return $actions;
			}

			$cvs_type = RY_WT::get_option( 'ecpay_shipping_cvs_type' );

			if ( 'B2C' === $cvs_type ) {
				$actions['ry_print_ecpay_cvs_711']        = __( 'Print ECPay shipping booking note (711)', 'woomp' );
				$actions['ry_print_ecpay_cvs_711_freeze'] = __( 'Print ECPay shipping booking note (711 Freeze)', 'woomp' );
				$actions['ry_print_ecpay_cvs_family']     = __( 'Print ECPay shipping booking note (family)', 'woomp' );
				$actions['ry_print_ecpay_cvs_hilife']     = __( 'Print ECPay shipping booking note (hilife)', 'woomp' );
			} elseif ( 'C2C' === $cvs_type ) {
				$actions['ry_print_ecpay_cvs_711']    = __( 'Print ECPay shipping booking note (711)', 'woomp' );
				$actions['ry_print_ecpay_cvs_family'] = __( 'Print ECPay shipping booking note (family)', 'woomp' );
				$actions['ry_print_ecpay_cvs_hilife'] = __( 'Print ECPay shipping booking note (hilife)', 'woomp' );
				$actions['ry_print_ecpay_cvs_okmart'] = __( 'Print ECPay shipping booking note (okmart)', 'woomp' );
			}

			$actions['ry_print_ecpay_home_tcat'] = __( 'Print ECPay shipping booking note (tcat)', 'woomp' );

			return $actions;
		}

		/**
		 * 增加訂單頁面重新選擇超商按鈕
		 *
		 * @param WC_Order $order WooCommerce 訂單物件.
		 * @return void
		 */
		public function add_choose_cvs_btn( $order ) {
			if ( get_option( RY_WT::$option_prefix . 'enabled_ecpay_shipping', 1 ) === 'yes' ) {
				foreach ( $order->get_items( 'shipping' ) as $item_id => $item ) {
					$method_class = RY_ECPay_Shipping::get_order_support_shipping( $item );
					if ( $method_class !== false && strpos( $method_class, 'cvs' ) !== false ) {
						echo '<div class="edit_address">
							<button type="button" class="button choose-cvs" style="margin-top: 10px;">' .
						esc_html__( 'Update convenience store', 'woomp' ) .
						'</button><p style="margin-top: 10px;">' .
						esc_html__( 'After choosing cvs, you need update the order to save changing.', 'woomp' ) .
						'</p></div>';
					}
				}
			}
		}

		/**
		 * 註冊重新選擇超商 JS
		 *
		 * @return void
		 */
		/**
		 * 註冊重新選擇超商 JS
		 *
		 * @return void
		 */
		public function enqueue_choose_cvs_script() {
			global $pagenow;

			// 只在訂單編輯頁面載入 JS
			if ( 'post.php' !== $pagenow || ! isset( $_GET['post'] ) || 'shop_order' !== get_post_type( $_GET['post'] ) ) { // phpcs:ignore
				return;
			}

			// 取得訂單物件
			$order = wc_get_order( $_GET['post'] ); // phpcs:ignore

			// phpcs:disable

			// 取得 ECPay API 連接資訊
			list($MerchantID, $HashKey, $HashIV, $CVS_type) = RY_ECPay_Shipping::get_ecpay_api_info();

			// 是否有選擇超商
			$choosed_cvs = [];

			// 如果有送出超商資訊，則將其儲存
			if ( isset( $_POST['MerchantID'] ) && $_POST['MerchantID'] == $MerchantID ) {
				$choosed_cvs = [
					'CVSStoreID'   => isset( $_POST['CVSStoreID'] ) ? sanitize_text_field( wp_unslash( $_POST['CVSStoreID'] ) ) : '',
					'CVSStoreName' => isset( $_POST['CVSStoreName'] ) ? sanitize_text_field( wp_unslash( $_POST['CVSStoreName'] ) ) : '',
					'CVSAddress'   => isset( $_POST['CVSAddress'] ) ? sanitize_text_field( wp_unslash( $_POST['CVSAddress'] ) ) : '',
					'CVSTelephone' => isset( $_POST['CVSTelephone'] ) ? sanitize_text_field( wp_unslash( $_POST['CVSTelephone'] ) ) : '',
				];
			}

			// 註冊 JS
			wp_register_script( 'wmp-admin-shipping', WOOMP_PLUGIN_URL . 'admin/js/choose-cvs.js', [ 'jquery' ], null, false );

			// 將 ECPay API 連接資訊傳遞給 JS
			wp_localize_script(
				'wmp-admin-shipping',
				'ECPayInfo',
				[
					'postUrl'  => RY_ECPay_Shipping_Api::get_map_post_url(),
					'postData' => [
						'MerchantID'       => $MerchantID,
						'LogisticsType'    => '',
						'LogisticsSubType' => '',
						'IsCollection'     => 'Y',
						'ServerReplyURL'   => esc_url( WC()->api_request_url( 'ry_ecpay_map_callback' ) ),
						'ExtraData'        => 'ry' . $order->get_id(),
					],
					'newStore' => $choosed_cvs,
				]
			);



			// 載入 JS
			wp_enqueue_script( 'wmp-admin-shipping' );

			// 迴圈訂單中的運輸方式，將運輸方式加入 JS 的資料中
			foreach ( $order->get_items( 'shipping' ) as $item_id => $item ) {
				$method_class = RY_ECPay_Shipping::get_order_support_shipping( $item );
				if ( $method_class !== false && strpos( $method_class, 'cvs' ) !== false ) {
					wp_localize_script(
						'wmp-admin-shipping',
						'ECPayInfo',
						[
							'postData' => [
								'LogisticsType'    => $method_class::$LogisticsType,
								'LogisticsSubType' => $method_class::$LogisticsSubType . ( ( 'C2C' == $CVS_type ) ? 'C2C' : '' ),
							],
						]
					);
				}
			}
			// phpcs:enable
		}
	}

	// 初始化類別
	WooMP_Order::init();
}

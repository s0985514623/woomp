<?php

/**
 * 訂單相關功能
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooMP_Order' ) ) {
	class WooMP_Order {
		/**
		 * 初始化
		 */
		public static function init() {
			$class = new self();
			add_action( 'init', array( $class, 'add_order_status_in_transit' ) );
			add_action( 'wc_order_statuses', array( $class, 'add_order_statuses' ) );
			add_filter( 'woocommerce_reports_order_statuses', array( $class, 'add_order_statuses' ) );
			add_filter( 'woocommerce_order_is_paid_statuses', array( $class, 'add_report_paid_statuses' ) );
		}

		/**
		 * 增加訂單狀態 - 配送中
		 */
		public function add_order_status_in_transit() {

			register_post_status(
				'wc-wmp-in-transit',
				array(
					'label'                     => '配送中',
					'public'                    => true,
					'show_in_admin_status_list' => true,
					'show_in_admin_all_list'    => true,
					'exclude_from_search'       => false,
				)
			);
		}

		public function add_order_statuses( $order_statuses ) {
			$order_statuses['wc-wmp-in-transit'] = '配送中';
			return $order_statuses;
		}

		public function add_report_paid_statuses( $statues ) {
			$statues[] = 'wc-wmp-in-transit';
			return $statues;
		}

	}
	WooMP_Order::init();
}

<?php



$settings = array(
	array(
		'title' => __('統一金流 PAYUNi 設定', 'woomp'),
		'type'  => 'title',
		'id'    => 'payment_general_setting',
	),
	array(
		'title' => '',
		'id'    => 'payuni_payment_doc',
		'type'  => 'title',
		'desc'  => '<p>預設信用卡金流手續費為2.8%，欲申請優惠費率2.4%，請<a href="https://www.newpay.com.tw/index.php/product/secend/339-morepower" target="_blank">點此填表</a><br>遇到問題請至 <a href="https://cloud.luke.cafe" target="_blank">站長路可網站</a>右下角對話框詢問</p>',
	),
	array(
		'title'   => __('Debug log', 'woocommerce'),
		'id'      => 'payuni_payment_log',
		'type'    => 'checkbox',
		'default' => 'no',
		'desc'    => __('Enable logging', 'woocommerce') . '<br>'
			. sprintf(
				/* translators: %s: Path of log file */
				__('Log PAYUNi payemnt events/message, inside %s', 'woomp'),
				'<code>' . WC_Log_Handler_File::get_log_file_path('payuni_payment') . '</code>'
			),
	),
	array(
		'title'   => __('3D Authorization', 'woomp'),
		'type'    => 'checkbox',
		'default' => 'no',
		'desc'    => __('啟用 3D 驗證<br>相關設定請參考<a target="_blank" href="https://cloud.luke.cafe/docs/payuni-3d">說明文件</a>', 'woomp'),
		'id'      => 'payuni_3d_auth',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'payment_general_setting',
	),
	array(
		'title' => __('API Settings Test', 'woomp'),
		'type'  => 'title',
		'desc'  => __('Enter your payuni testing API credentials', 'woomp'),
		'id'    => 'payuni_payment_api_settings_test',
	),
	array(
		'title'   => __('Sandbox', 'woomp'),
		'type'    => 'checkbox',
		'default' => 'no',
		'desc'    => __('Check this box if you want to use test/sandbox mode.', 'woomp'),
		'id'      => 'payuni_payment_testmode',
	),
	array(
		'title'    => __('MerchantID test', 'woomp'),
		'type'     => 'text',
		'desc'     => __('This is the Merchant ID when you apply PAYUNi API', 'woomp'),
		'desc_tip' => true,
		'id'       => 'payuni_payment_merchant_no_test',
	),
	array(
		'title'    => __('Hash Key test', 'woomp'),
		'type'     => 'text',
		'desc'     => __('This is the Hash Key when you apply PAYUNi API', 'woomp'),
		'desc_tip' => true,
		'id'       => 'payuni_payment_hash_key_test',
	),
	array(
		'title'    => __('Hash IV test', 'woomp'),
		'type'     => 'text',
		'desc'     => __('This is the Hash IV when you apply PAYUNi API', 'woomp'),
		'desc_tip' => true,
		'id'       => 'payuni_payment_hash_iv_test',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'payuni_payment_api_settings_test',
	),
	array(
		'title' => __('API Settings', 'woomp'),
		'type'  => 'title',
		'desc'  => __('Enter your payuni API credentials', 'woomp'),
		'id'    => 'payuni_payment_api_settings',
	),
	array(
		'title'    => __('MerchantID', 'woomp'),
		'type'     => 'text',
		'desc'     => __('This is the Merchant ID when you apply PAYUNi API', 'woomp'),
		'desc_tip' => true,
		'id'       => 'payuni_payment_merchant_no',
	),
	array(
		'title'    => __('Hash Key', 'woomp'),
		'type'     => 'text',
		'desc'     => __('This is the Hash Key when you apply PAYUNi API', 'woomp'),
		'desc_tip' => true,
		'id'       => 'payuni_payment_hash_key',
	),
	array(
		'title'    => __('Hash IV', 'woomp'),
		'type'     => 'text',
		'desc'     => __('This is the Hash IV when you apply PAYUNi API', 'woomp'),
		'desc_tip' => true,
		'id'       => 'payuni_payment_hash_iv',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'payuni_payment_api_settings',
	),
);

return $settings;

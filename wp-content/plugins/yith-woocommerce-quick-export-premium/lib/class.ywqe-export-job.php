<?php
/**
 * YWQE_Export_Job
 *
 * @author  Your Inspiration Themes
 * @package YITH WooCommerce Quick Export
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YWQE_Export_Job' ) ) {
	/**
	 * Single exportation job
	 *
	 * @since 1.0.0
	 */
	class YWQE_Export_Job {

		/**
		 * Set if the job start now or is scheduled
		 * @var int
		 */
		public $autostart = 0;

		/**
		 * @var Type of the exportation that this job will execute
		 */
		public $export_items;

		/**
		 * @var specify if this is a repeated job
		 */
		public $recurrency;

		/**
		 * @var string Separator from columns in CSV files
		 */
		public $fields_separator = ";";

		/**
		 * @var string Separator for new lines in CSV files
		 */
		public $newline_separator = "\r\n";

		/**
		 * @var array list all customer columns to shown on CSV file
		 */
		protected $customers_visible_columns = array();

		/**
		 * @var array list all orders columns to shown on CSV file
		 */
		protected $orders_visible_columns = array();

		public $name = '';
		public $order_status = 'any';
		public $export_on_date = null;
		public $export_on_time = null;
		public $start_filter = '';
		public $end_filter = '';


		/**
		 * Constructor
		 *
		 * @access public
		 * @since  1.0.0
		 */
		public function __construct( $args ) {

			$this->init_settings( $args );

		}

		/**
		 * Initialize job parameters
		 *
		 * @param $args parameters specifying the type of exportation
		 */
		public function init_settings( $args ) {

			$customer_columns = array(
				'ID',
				'user_login',
				'user_pass',
				'user_nickname',
				'user_email',
				'user_url',
				'user_registered',
				'user_activation_key',
				'user_status',
				'display_name',
				'spam',
				'deleted'
			);

			if ( apply_filters( 'ywqe_display_pruchased_products_column_for_customers_export', false ) ){

				array_splice( $customer_columns, 5, 0,'purchased_products' );
			}

			if ( defined( 'YITH_FUNDS_INIT' ) ){

				array_splice( $customer_columns, 5, 0,'user_funds' );
			}


			$this->customers_visible_columns = apply_filters( 'yith_quick_export_customer_columns_order', $customer_columns );

			if ( YITH_WooCommerce_Quick_Export::get_instance()->show_customer_billing_data ) {

				$billing_columns = array(
					'billing_first_name',
					'billing_last_name',
					'billing_address_1',
					'billing_address_2',
					'billing_city',
					'billing_postcode',
					'billing_country',
					'billing_state',
					'billing_company',
					'billing_email',
					'billing_phone'
				);

				$billing_columns = apply_filters( 'yith_quick_export_customer_billing_columns_order', $billing_columns );

				$this->customers_visible_columns = array_merge( $this->customers_visible_columns, $billing_columns );
			}

			if ( YITH_WooCommerce_Quick_Export::get_instance()->show_customer_shipping_data ) {
				$shipping_columns = array(
					'shipping_first_name',
					'shipping_last_name',
					'shipping_company',
					'shipping_address_1',
					'shipping_address_2',
					'shipping_city',
					'shipping_postcode',
					'shipping_country',
					'shipping_state'
				);

				$shipping_columns                = apply_filters( 'yith_quick_export_customer_shipping_columns_order', $shipping_columns );
				$this->customers_visible_columns = array_merge( $this->customers_visible_columns, $shipping_columns );
			}

			$orders_columns = array(
				'id',
				'status',
				'order_date',
				'order_key',
				'purchased_products',
				'currency',
				'payment_method_title',
				'prices_include_tax',
				'customer_ip_address',
				'customer_user_agent',
				'customer_user',
				'created_via',
				'order_version',
				'order_shipping',
				'billing_country',
				'billing_first_name',
				'billing_last_name',
				'billing_company',
				'billing_address_1',
				'billing_address_2',
				'billing_city',
				'billing_state',
				'billing_postcode',
				'billing_email',
				'billing_phone',
				'shipping_country',
				'shipping_first_name',
				'shipping_last_name',
				'shipping_company',
				'shipping_address_1',
				'shipping_address_2',
				'shipping_city',
				'shipping_state',
				'shipping_postcode',
				'discount_total',
				'discount_total_tax',
				'order_tax',
				'order_shipping_tax',
				'order_total',
				'recorded_sales',
				'recorded_coupon_usage_counts',
				'paid_date',
				'completed_date'
			);


			if ( function_exists( 'YITH_YWGC' ) ){

				array_splice( $orders_columns, 5, 0,'purchased_gift_card_codes' );
				array_splice( $orders_columns, 5, 0,'applied_gift_card_codes' );
			}

			if ( function_exists( 'YITH_Vendors' ) ){

				array_splice( $orders_columns, 5, 0,'vendors' );
			}


			array_splice( $orders_columns, 5, 0,'order_notes' );


			$this->orders_visible_columns = apply_filters( 'yith_quick_export_orders_columns_order', $orders_columns );

			//  Set the job settings
			$defaults = array(
				'name'           => esc_html__( "Export data", 'yith-woocommerce-quick-export' ),
				'autostart'      => 0,
				'export_items'   => array(),
				'order_status'   => 'any',
				'export_on_date' => null,
				'export_on_time' => null,
				'recurrency'     => 'none',
				'start_filter'   => '2000-01-01',
				'end_filter'     => date( "Y-m-d H:i:s" )
			);

			$args = wp_parse_args( $args, $defaults );

			//  Map job settings to class fields
			foreach ( $args as $key => $value ) {
				$this->{$key} = $value;
			}
		}

		/**
		 * Export requested data to an archive file
		 */
		private function export_data() {
			$zip_filepath = $this->create_filename();

			$zip_folder = trailingslashit( str_replace( '.zip', '', $zip_filepath ) );

			if ( ! file_exists( $zip_folder ) ) {
				wp_mkdir_p( $zip_folder );
			}

			$files = array();

			if ( in_array( 'customers', $this->export_items ) ) {
				$customers_filepath = $zip_folder . 'customers.csv';

				file_put_contents( $customers_filepath, $this->render_customers() );
				$files[] = $customers_filepath;
			}

			if ( in_array( 'orders', $this->export_items ) ) {
				$orders_filepath = $zip_folder . 'orders.csv';

				file_put_contents( $orders_filepath, $this->render_orders() );
				$files[] = $orders_filepath;
			}

			if ( in_array( 'coupons', $this->export_items ) ) {

				$coupons_filepath = $zip_folder . 'applied_coupons.csv';
				file_put_contents( $coupons_filepath, $this->render_applied_coupons() );
				$files[] = $coupons_filepath;

				$coupons_filepath = $zip_folder . 'coupons.csv';
				file_put_contents( $coupons_filepath, $this->render_all_coupons() );
				$files[] = $coupons_filepath;
			}

			if ( in_array( 'gift_cards', $this->export_items ) ) {

				$gift_cards_filepath = $zip_folder . 'gift_cards.csv';

				file_put_contents( $gift_cards_filepath, $this->render_all_gift_cards() );
				$files[] = $gift_cards_filepath;
			}

			if ( count( $files ) > 0 ) {
				$zip_file = yith_create_zip( $files,
					$zip_filepath,
					$zip_folder,
					true );

				$dropbox = ywqe_get_dropbox();
				$dropbox->send_document_to_dropbox( $zip_filepath );

				return $zip_filepath;
			}

			return null;
		}

		/**
		 * Start execution of the job
		 */
		public function start( $silent_mode = false ) {

			//  The current job can be an auto start exportation job or a scheduled one.
			if ( $silent_mode ) {
				$result = $this->export_data();

				return $result;
			}

			if ( $this->autostart ) {
				//  Start the job now and send it to the browser so it can
				//  be downloaded.

				$result = $this->export_data();
				if ( ! is_null( $result ) ) {
					yith_download_file( $result );
				}
			} else {
				//  Set a unique id for every job to be scheduled
				$this->id = round( microtime( true ) * 1000 );

				$start_job_time = strtotime( $this->export_on_date . " " . $this->export_on_time ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

				$job_args = serialize( $this );
				$temp     = unserialize( $job_args );

				$scheduled_hook = 'ywqe_scheduled_export';

				if ( "none" === $this->recurrency ) {
					wp_schedule_single_event( $start_job_time, $scheduled_hook, array( $job_args ) );
				} else {
					//Schedule the event
					wp_schedule_event( $start_job_time, $this->recurrency, $scheduled_hook, array( $job_args ) );
				}
			}
		}

		/**
		 * Build a CSV row for columns title
		 *
		 * @param $columns columns to be shown
		 *
		 * @return string CSV formatted row
		 */
		protected function get_csv_columns_title_row( $columns ) {

			$csv_title = '';
			foreach ( $columns as $column ) {
				$csv_title .= ucwords( strtolower( $column ) ) . YITH_WooCommerce_Quick_Export::get_instance()->fields_separator;
			}

			$csv_title .= $this->newline_separator;

			return $csv_title;
		}

		/**
		 * Build a CSV row for a specific customer
		 *
		 * @param $customer
		 */
		protected function get_customer_csv( $customer ) {

			$csv_row = '';

			foreach ( $this->customers_visible_columns as $column ) {
				if ($column == 'purchased_products'){
					$current_user = $customer;
					if ( 0 == $current_user->ID ) return;
					// GET USER ORDERS (COMPLETED + PROCESSING)
					$customer_orders = get_posts( array(
						'numberposts' => -1,
						'meta_key' => '_customer_user',
						'meta_value' => $current_user->ID,
						'post_type' => wc_get_order_types(),
						'post_status' => array_keys( wc_get_is_paid_statuses() ),
					) );

					// LOOP THROUGH ORDERS AND GET PRODUCT IDS
					if ( ! $customer_orders || empty( $customer_orders ) ){
						$csv_row .= '" "' . $this->fields_separator;
						continue;
					}
					$product_names = array();
					foreach ( $customer_orders as $customer_order ) {
						$order = wc_get_order( $customer_order->ID );
						$items = $order->get_items();
						foreach ( $items as $item ) {
							$product_name = $item->get_name();
							$product_names[] = $product_name;
						}
					}
					$product_names = array_unique( $product_names );
					$product_names_str = implode( ",", $product_names );
					$csv_row .= '"' . $product_names_str . '"' . $this->fields_separator;
				}
				else if ( $column == 'user_funds'){
					$customer_     = new YITH_YWF_Customer( $customer->ID );
					$funds        = $customer_->get_funds();
					$csv_row .= '"' . $funds . '"' . $this->fields_separator;
				}

				else {

					$value = apply_filters('yith_wcqe_get_customer_csv_value',yit_get_prop($customer, $column),$customer,$column);

					$csv_row .= '"' . trim($value) . '"' . $this->fields_separator;
				}
			}

			return $csv_row;
		}


		/**
		 * Build a CSV row for a specific order
		 *
		 * @param $order
		 */
		protected function get_order_csv( $order ) {

			$csv_row = '';

			$items = $order->get_items();

			foreach ( $this->orders_visible_columns as $column ) {

				if ($column == 'purchased_products'){
					$product_names = array();
					foreach ( $items as $item ) {
						$product_name = $item->get_name();
						$product_quantity = $item->get_quantity();
						$product_names[] = $product_quantity . ' x ' . $product_name;
					}
					$product_names = array_unique( $product_names );
					$value = apply_filters( 'yith_wcqe_get_order_purchased_products_names', implode( ",", $product_names ) , $product_names, $items );
				}
				else if ( $column == 'purchased_gift_card_codes' ){
					$gift_card_data_array = array();
					foreach ( $items as $item ) {

						$gift_card_code_array = wc_get_order_item_meta( $item->get_id(), '_ywgc_gift_card_code', true );

						if ( is_array($gift_card_code_array)){
							foreach ( $gift_card_code_array as $code ){
								$gift_card = YITH_YWGC()->get_gift_card_by_code( $code );

								$gift_card_data = $gift_card->get_code();

								$gift_card_data_array[] = $gift_card_data;
							}
						}

					}
					$gift_card_data_array = array_unique( $gift_card_data_array );
					$value = implode( ",", $gift_card_data_array );
				}
				else if ( $column == 'applied_gift_card_codes' ){
					$gift_card_data_array = array();

					$gift_card_code_array = get_post_meta( $order->get_id(), '_ywgc_applied_gift_cards', true );

					$gift_card_code_array = maybe_unserialize( $gift_card_code_array );

					if ( is_array($gift_card_code_array)){
						foreach ( $gift_card_code_array as $code => $value ){
							$gift_card = YITH_YWGC()->get_gift_card_by_code( $code );

							$gift_card_data = $gift_card->get_code();

							$gift_card_data_array[] = $gift_card_data;
						}
					}


					$gift_card_data_array = array_unique( $gift_card_data_array );
					$value = implode( ",", $gift_card_data_array );
				}
				else if ( $column == 'vendors' ){
					$vendors = array();

					foreach ( $items as $item ){

						if( $item instanceof WC_Order_Item ){

							$commission_meta = 'yith_wcmv_vendor_suborder' == $order->get_created_via() ? '_commission_id' : '_child__commission_id';
							$commission_id = $item->get_meta( $commission_meta );
							$commission = YITH_Commission( $commission_id );

							if( $commission->exists() ){
								$vendor = $commission->get_vendor();
								if( $vendor->is_valid() ){
									$vendors[ $vendor->id ] = $vendor->name;
								}
							}
							else{
								$product = $item->get_product();

								if ( $product->get_type() == 'variation'){
									$parent_product = wc_get_product( $product->get_parent_id() );
									$product_id = $parent_product->get_id();
								}
								else{
									$product_id = $product->get_id();
								}

								$vendor = yith_get_vendor( $product_id, 'product' );

								$vendors[ $vendor->id ] = $vendor->name;
							}
						}
					}

					$value = implode( ",", $vendors );

				}
				else if ( $column == 'order_notes' ){

					$notes = array();

					$notes_array = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );

					foreach ( $notes_array as $notes_obj ){


						if ( $notes_obj->added_by != 'system'
						     && substr( $notes_obj->content, 0, 20 ) != "Order status changed"
						     && substr( $notes_obj->content, 0, 20 ) != "État de la commande"){

							$notes[] = $notes_obj->content;
						}
					}

					$value = implode( ",", $notes );

				}
				else{
					if ( yit_get_prop($order, $column ) != '' )
						$value = apply_filters('yith_wcqe_column_value', yit_get_prop($order, $column ), $order, $column);
					else
						$value = get_post_meta($order->get_id(), '_' . $column, true );

				}

				$csv_row .= '"' . trim( $value) . '"' . $this->fields_separator;
			}

			return $csv_row;
		}

		/**
		 * Extract customers for the specific time interval and retrieve a CSV formatted text
		 *
		 * @return string CSV formatted text
		 */
		protected function render_customers(){

			$usercount = count_users();

			$total_users = $usercount['avail_roles']['customer'];

			$offset = 0;
			$customers_array = array();

			while ($offset < $total_users) {

				$args = array(
					'role' => 'customer',
					'fields' => 'all_with_meta',
					'orderby' => 'user_registered',
					'order' => 'DESC',
					'number' => 100,
					'offset' => $offset,

				);

				$customers_array[] = get_users(apply_filters('yith_quick_export_render_customers_args', $args));

				$offset = $offset + 100;
			}

			$customer_csv = $this->get_csv_columns_title_row($this->customers_visible_columns);

			foreach ($customers_array as $customers) {

				foreach ($customers as $k => $customer) {

					if (is_object($customer) && $this->in_interval( apply_filters( 'ywqe_customers_export_interval', $customer->user_registered, $customer ) ) ) {

						//  Add customer informations
						$customer_csv .= $this->get_customer_csv($customer);
						//  close row
						$customer_csv .= $this->newline_separator;
					}
				}

			}

			return $customer_csv;
		}

		/**
		 * Extract orders for the specific time interval and retrieve a CSV formatted text
		 *
		 * @return string CSV formatted text
		 */
		protected function render_orders() {
			global $wpdb;
			$prepare_query = 'SELECT COUNT(ID) FROM ' . $wpdb->posts . ' WHERE post_type = \'shop_order\'';
			$n_posts = $wpdb->get_var( $prepare_query );

			$offset = 0;
			$orders = array();

			$start_date = date ( 'Y-m-d 00:00:00', strtotime( $this->start_filter ) );
			$end_date = date ( 'Y-m-d 23:59:59', strtotime( $this->end_filter ) );


			while( $offset < $n_posts ){
				$args = array(
					'posts_per_page' => 100,
					'date_query' => array(
						array(
							'after'     => $start_date,
							'before'    => $end_date,
							'inclusive' => true,
						),
					),
					'post_type'      => 'shop_order',
					'post_status'    => 'any',
					'orderby'        => 'post_date',
					'order'          => 'ASC',
					'offset'		 => $offset
				);
				$orders_to_push = get_posts( $args );
				
				foreach ( $orders_to_push as $order ){

					if ( !in_array( $order->post_status, array_values( $this->order_status ) ) ){
						continue;
					}

					$orders[] = $order;
				}
				$offset = $offset+100;
			}

			apply_filters( 'yith_quick_export_render_orders_args',$orders);

			$orders_csv = $this->get_csv_columns_title_row( $this->orders_visible_columns );

			foreach ( $orders as $order ) {

				$order = wc_get_order( $order );

				if ( $this->in_interval( yit_get_prop($order, 'order_date') ) ) {
					//  Add orders informations
					$orders_csv .= $this->get_order_csv( $order );

					//  close row
					$orders_csv .= $this->newline_separator;
				}
			}

			return $orders_csv;
		}

		private function in_interval( $date, $start_interval = null, $end_interval = null ) {
			if ( is_null( $start_interval ) ) {
				$start_interval = $this->start_filter;
			}

			if ( is_null( $end_interval ) ) {
				$end_interval = $this->end_filter;
			}

			if ( strtotime( $date ) < strtotime( $start_interval ) ) {
				return false;
			}

			if ( strtotime( $date ) > ( strtotime( $end_interval . "+1 day" ) ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Extract coupons for the specific time interval and retrieve a CSV formatted text
		 *
		 * @return string CSV formatted text
		 */
		protected function render_applied_coupons() {
			global $wpdb;
			$prepare_query = 'SELECT COUNT(ID) FROM ' . $wpdb->posts . ' WHERE post_type = \'shop_order\'';
			$n_posts = $wpdb->get_var( $prepare_query );

			$offset = 0;
			$orders = array();
			while( $offset < $n_posts ){
				$args = array(
					'posts_per_page' => 100,
					'post_type'      => 'shop_order',
					'post_status'    => 'any',
					'orderby'        => 'post_date',
					'order'          => 'ASC',
					'offset'		 => $offset
				);
				$orders_to_push = get_posts( $args );
				foreach ( $orders_to_push as $order ){

					if ( !in_array( $order->post_status, array_values( $this->order_status ) ) ){
						continue;
					}

					$orders[] = $order;
				}
				$offset = $offset+100;
			}

			$coupons_csv = '';

			$coupons_columns = array(
				'order_id',
				'order_date',
				'coupon_id',
				'discount_type',
				'coupon_amount',
				'coupon_name',
				'order_discount'
			);

			$coupons_csv = $this->get_csv_columns_title_row( $coupons_columns );

			foreach ( $orders as $order ) {
				$order = wc_get_order( $order );

				if ( $this->in_interval( yit_get_prop($order, 'order_date') ) ) {
					//  if there aren't coupon used in this order, skip it
					if (version_compare(WC()->version, '3.7.0', '<')){
						$coupons = $order->get_used_coupons();
					}else{
						$coupons = $order->get_coupon_codes();
					}
					if ( count( $coupons ) == 0 ) {
						continue;
					}

					foreach ( $coupons as $coupon ) {

						$wc_coupon = new WC_Coupon( $coupon );

						foreach ( $coupons_columns as $column ) {
							switch ( $column ) {
								case 'order_id' :
									$coupons_csv .= sprintf( '"%d"%s', yit_get_prop( $order, 'id' ), $this->fields_separator );
									break;

								case 'order_date' :
									$coupons_csv .= sprintf( '"%s"%s', yit_get_prop( $order, 'order_date' ), $this->fields_separator );
									break;

								case 'coupon_id' :
									$coupons_csv .= sprintf( '"%s"%s', yit_get_prop( $wc_coupon, 'id' ), $this->fields_separator );
									break;

								case 'discount_type' :
									$coupons_csv .= sprintf( '"%s"%s', yit_get_prop( $wc_coupon, 'discount_type' ), $this->fields_separator );
									break;

								case 'coupon_amount' :
									$coupons_csv .= sprintf( '"%s"%s', yit_get_prop( $wc_coupon, 'coupon_amount' ), $this->fields_separator );
									break;

								case 'coupon_name' :
									$coupons_csv .= sprintf( '"%s"%s', yit_get_prop($wc_coupon, 'code'), $this->fields_separator );
									break;

								case 'order_discount' :
									$coupons_csv .= sprintf( '"%s"%s', $this->get_coupon_amount_for_order( yit_get_prop( $order, 'id' ), yit_get_prop($wc_coupon, 'code') ), $this->fields_separator );
									break;

								default :
									$value = apply_filters( 'yith_quick_export_coupon_column', '', $order, $coupon, $column );
									$coupons_csv .= sprintf( '"%s"%s', $value, $this->fields_separator );
							}
						}
						$coupons_csv .= $this->newline_separator;
					}
				}
			}

			return $coupons_csv;
		}

		/**
		 * Extract coupons for the specific time interval and retrieve a CSV formatted text
		 *
		 * @return string CSV formatted text
		 */
		protected function render_all_coupons() {

			$args = array(
				'posts_per_page'   => -1,
				'orderby'          => 'title',
				'order'            => 'asc',
				'post_type'        => 'shop_coupon',
				'post_status'      => 'publish',
			);

			$coupons = get_posts( apply_filters( 'yith_quick_export_render_all_coupons_args', $args ) );

			$coupons_csv = '';

			$coupons_columns = array(
				'coupon_id',
				'discount_type',
				'coupon_amount',
				'coupon_name',
			);

			$coupons_csv = $this->get_csv_columns_title_row( $coupons_columns );

			foreach ( $coupons as $coupon ) {

				foreach ( $coupons_columns as $column ) {
					switch ( $column ) {

						case 'coupon_id' :
							$coupons_csv .= sprintf( '"%s"%s', yit_get_prop( $coupon, 'ID' ), $this->fields_separator );
							break;

						case 'discount_type' :
							$coupons_csv .= sprintf( '"%s"%s', yit_get_prop( $coupon, 'discount_type' ), $this->fields_separator );
							break;

						case 'coupon_amount' :
							$coupons_csv .= sprintf( '"%s"%s', yit_get_prop( $coupon, 'coupon_amount' ), $this->fields_separator );
							break;

						case 'coupon_name' :
							$coupons_csv .= sprintf( '"%s"%s', yit_get_prop( $coupon, 'name' ), $this->fields_separator );
							break;

						default :
							$value = apply_filters( 'yith_quick_export_all_coupon_column', '', $coupon, $column );
							$coupons_csv .= sprintf( '"%s"%s', $value, $this->fields_separator );
					}

				}

				$coupons_csv .= $this->newline_separator;

			}

			return $coupons_csv;
		}

		/**
		 * Extract gift cards for the specific time interval and retrieve a CSV formatted text
		 *
		 * @return string CSV formatted text
		 */
		protected function render_all_gift_cards() {

			global $wpdb;
			$prepare_query = 'SELECT COUNT(ID) FROM ' . $wpdb->posts . ' WHERE post_type = \'gift_card\'';
			$n_posts = $wpdb->get_var( $prepare_query );

			$offset = 0;
			$gift_cards = array();
			while( $offset < $n_posts ){
				$args = array(
					'posts_per_page'   => 100,
					'orderby'          => 'title',
					'order'            => 'asc',
					'date_query' => array(
						array(
							'after'     => $this->start_filter,
							'before'    => $this->end_filter,
							'inclusive' => true,
						),
					),
					'post_type'        => 'gift_card',
					'post_status'      => 'publish',
					'offset'		 => $offset,
				);
				$gift_cards_to_push = get_posts( $args );

				foreach ( $gift_cards_to_push as $gift_card ){

					$gift_cards[] = $gift_card;
				}
				$offset = $offset+100;
			}

			$date_format = apply_filters('yith_wcgc_date_format','Y-m-d');

			$gift_cards_csv = '';

			$gift_cards_columns = array(
				'gift_card_order_id',
				'gift_card_id',
				'gift_card_code',
				'gift_card_amount',
				'gift_card_balance',
				'gift_card_sender_name',
				'gift_card_recipient_name',
				'gift_card_recipient_email',
				'gift_card_message',
				'gift_card_expiration_date',
				'gift_card_delivery_date',
				'gift_card_internal_notes',

			);

			$gift_cards_csv = $this->get_csv_columns_title_row( $gift_cards_columns );

			foreach ( $gift_cards as $gift_card ) {

				if ( $this->in_interval( yit_get_prop( $gift_card, 'post_date' ) ) ) {

					$gift_card_object = new YWGC_Gift_Card_Premium( array( 'ID' => yit_get_prop( $gift_card, 'ID' ) ) );

					$order        = wc_get_order( $gift_card_object->order_id );
					$order_number = is_object( $order ) ? $order->get_order_number() : "";


					$expiration_date = $gift_card_object->expiration ? date( $date_format, (int) $gift_card_object->expiration ) : '';
					$delivery_date   = $gift_card_object->delivery_date ? date( $date_format, (int) $gift_card_object->delivery_date ) : '';

					foreach ( $gift_cards_columns as $column ) {
						switch ( $column ) {

							case 'gift_card_order_id' :
								$gift_cards_csv .= sprintf( '"%s"%s', $order_number, $this->fields_separator );
								break;
							case 'gift_card_id' :
								$gift_cards_csv .= sprintf( '"%s"%s', yit_get_prop( $gift_card, 'ID' ), $this->fields_separator );
								break;

							case 'gift_card_code' :
								$gift_cards_csv .= sprintf( '"%s"%s', $gift_card_object->get_code(), $this->fields_separator );
								break;

							case 'gift_card_amount' :
								$gift_cards_csv .= sprintf( '"%s"%s', $gift_card_object->total_amount, $this->fields_separator );
								break;

							case 'gift_card_balance' :
								$gift_cards_csv .= sprintf( '"%s"%s', $gift_card_object->get_balance(), $this->fields_separator );
								break;

							case 'gift_card_sender_name' :
								$gift_cards_csv .= sprintf( '"%s"%s', $gift_card_object->sender_name, $this->fields_separator );
								break;

							case 'gift_card_recipient_name' :
								$gift_cards_csv .= sprintf( '"%s"%s', $gift_card_object->recipient_name, $this->fields_separator );
								break;

							case 'gift_card_recipient_email' :
								$gift_cards_csv .= sprintf( '"%s"%s', $gift_card_object->recipient, $this->fields_separator );
								break;

							case 'gift_card_message' :
								$gift_cards_csv .= sprintf( '"%s"%s', $gift_card_object->message, $this->fields_separator );
								break;

							case 'gift_card_expiration_date' :
								$gift_cards_csv .= sprintf( '"%s"%s', $expiration_date, $this->fields_separator );
								break;

							case 'gift_card_delivery_date' :
								$gift_cards_csv .= sprintf( '"%s"%s', $delivery_date, $this->fields_separator );
								break;

							case 'gift_card_internal_notes' :
								$gift_cards_csv .= sprintf( '"%s"%s', $gift_card_object->internal_notes, $this->fields_separator );
								break;

							default :
								$value          = apply_filters( 'yith_quick_export_all_gift_cards_column', '', $gift_cards, $column );
								$gift_cards_csv .= sprintf( '"%s"%s', $value, $this->fields_separator );
						}

					}

					$gift_cards_csv .= $this->newline_separator;

				}
			}

			return $gift_cards_csv;
		}


		/**
		 * Retrieve the amount of discount for a coupon in a specific order
		 *
		 * @param $order_id    the id of the order in which the coupon was used
		 * @param $coupon_name the name of the coupon used
		 *
		 * @return float|int
		 */
		private function get_coupon_amount_for_order( $order_id, $coupon_name ) {

			global $wpdb;

			$prepare_query = "
				SELECT meta_value as amount
				FROM {$wpdb->prefix}woocommerce_order_items itm
				LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta meta
				ON itm.order_item_id = meta.order_item_id
				WHERE order_item_type = 'coupon'
				AND order_id = %s
				AND order_item_name='%s'
				AND meta_key='discount_amount'";

			$results = $wpdb->get_results( $wpdb->prepare( $prepare_query, $order_id, $coupon_name ) );

			if ( isset( $results[0] ) ) {
				return round( $results[0]->amount, 2 );
			} else {
				return 0;
			}
		}

		/**
		 * Create a folder with a specific pattern, used to store files created with an exportation job
		 *
		 * @return string folder name
		 */
		public function create_storing_folder( $date = null ) {

			$folder_pattern = get_option( 'ywqe_folder_format' );
			$date           = isset( $date ) ? $date : getdate();

			$folder_pattern = str_replace(
				array(
					'{{year}}',
					'{{month}}',
					'{{day}}',
					'{{hours}}',
					'{{minutes}}',
					'{{seconds}}',
				),
				array(
					$date['year'],
					sprintf( "%02d", $date['mon'] ),
					sprintf( "%02d", $date['mday'] ),
					sprintf( "%02d", $date['hours'] ),
					sprintf( "%02d", $date['minutes'] ),
					sprintf( "%02d", $date['seconds'] ),
				),
				$folder_pattern );

			if ( ! file_exists( YITH_YWQE_DOCUMENT_SAVE_DIR . $folder_pattern ) ) {
				wp_mkdir_p( YITH_YWQE_DOCUMENT_SAVE_DIR . $folder_pattern );
			}

			return YITH_YWQE_DOCUMENT_SAVE_DIR . $folder_pattern;
		}

		/**
		 * Return the filename associated to the document, based on plugin settings.
		 *
		 * @return mixed|string|void
		 */
		public function create_filename( $date = null ) {

			$date   = isset( $date ) ? $date : getdate();
			$folder = $this->create_storing_folder( $date );

			$pattern = get_option( 'ywqe_filename_format' );
			$pattern = str_replace(
				array(
					'{{year}}',
					'{{month}}',
					'{{day}}',
					'{{hours}}',
					'{{minutes}}',
					'{{seconds}}',
				),
				array(
					$date['year'],
					sprintf( "%02d", $date['mon'] ),
					sprintf( "%02d", $date['mday'] ),
					sprintf( "%02d", $date['hours'] ),
					sprintf( "%02d", $date['minutes'] ),
					sprintf( "%02d", $date['seconds'] ),
				),
				$pattern );

			$pattern_loop = $pattern;

			$i = 0;
			//  Ensure the filename is univoque
			do {

				if ( $i ) {
					$pattern_loop = sprintf( "%s(%s)", $pattern, $i );
				}

				$filepath = sprintf( '%s/%s.zip', $folder, $pattern_loop );
				$i ++;
			} while ( file_exists( $filepath ) );

			return $filepath;
		}
	}
}

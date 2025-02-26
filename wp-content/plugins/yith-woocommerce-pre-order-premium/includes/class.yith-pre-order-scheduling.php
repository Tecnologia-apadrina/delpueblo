<?php
/*
 * This file belongs to the YIT Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */
if ( ! defined( 'YITH_WCPO_VERSION' ) ) {
	exit( 'Direct access forbidden.' );
}

/**
 *
 *
 * @class      YITH_Pre_Order_Scheduling
 * @package    Yithemes
 * @since      Version 1.0.0
 * @author     Your Inspiration Themes
 *
 */

if ( ! class_exists( 'YITH_Pre_Order_Scheduling' ) ) {
	/**
	 * Class YITH_Pre_Order
	 *
	 * @author Carlos Mora <carlos.eugenio@yourinspiration.it>
	 */
	class YITH_Pre_Order_Scheduling {

		/**
		 * Construct
		 *
		 * @author Carlos Mora <carlos.eugenio@yourinspiration.it>
		 * @since 1.0
		 */
		public function __construct(){
			add_action( 'ywpo_preorder_date_notification', array( $this, 'send_pre_order_date_notification' ) );
			add_action( 'ywpo_preorder_date_end_check', array( $this, 'pre_order_date_end_check' ) );
			add_action( 'ywpo_preorder_is_for_sale_single_notification', array( $this, 'send_pre_order_is_for_sale_single_notification' ) );
		}

		public function send_pre_order_date_notification() {
			$is_checked = get_option( 'yith_wcpo_enable_pre_order_notification' );
			$num_days = get_option( 'yith_wcpo_notification_number_days' );
			if ( 'yes' == $is_checked && !empty( $num_days ) ) {
				$pre_order_notification = array();
				$args = array(
					'post_type' => array( 'product', 'product_variation' ),
					'numberposts' => - 1,
					'fields' => 'ids',
					'meta_query' => array(
						array(
							'key' => '_ywpo_preorder',
							'value' => 'yes',
							'compare' => '='
						),
						array(
							'key' => '_ywpo_preorder_notified',
							'compare' => 'NOT EXISTS'
						)
					));

				$posts = get_posts( $args );
				if ( ! empty( $posts ) ) {
					foreach ( $posts as $id ) {
						$pre_order = new YITH_Pre_Order_Product( $id );
						$timestamp =  $pre_order->get_for_sale_date_timestamp();
						// If the Pre-Order product has date, goes on.
						if ( ! empty( $timestamp ) ) {
							$notify_date = strtotime( ( sprintf( '-%d days', $num_days )), (int) $timestamp );

							if ( time() > $notify_date ) {
								$pre_order_notification[] = $pre_order;
								yit_save_prop( $pre_order->product, '_ywpo_preorder_notified', 'yes' );
							}
						}
					}
					// If it has Pre-Order products to notify, send the email
					if ( ! empty( $pre_order_notification ) ) {
						WC()->mailer();
						do_action( 'yith_ywpo_sale_date_end', $pre_order_notification );
					}
				}
			}
		}

		public function pre_order_date_end_check() {
			$auto_for_sale = get_option( 'yith_wcpo_enable_pre_order_purchasable' );
			$is_checked_notification = get_option( 'yith_wcpo_enable_pre_order_notification_for_sale' );
			$args = array(
				'post_type' => array( 'product', 'product_variation' ),
				'numberposts' => - 1,
				'fields' => 'ids',
				'meta_key'    => '_ywpo_preorder',
				'meta_value'  => 'yes'
			);
			// Get all Pre-Order ids
			$posts = get_posts( $args );
			if ( ! empty( $posts ) ) {
				foreach ( $posts as $id ) {
					$pre_order = new YITH_Pre_Order_Product( $id );
					$timestamp =  $pre_order->get_for_sale_date_timestamp();
                    // If Pre-Order date is going to end in next 12 hours it will be true.
					$is_end_next_12h = ( ! empty( $timestamp ) && time() > ( $timestamp - ( HOUR_IN_SECONDS * 12 ) ) );
                    if ( ( $is_end_next_12h && 'yes' == $auto_for_sale ) || ( $is_end_next_12h && 'yes' == $is_checked_notification ) ) {
						wp_schedule_single_event( $timestamp, 'ywpo_preorder_is_for_sale_single_notification' , array( $id ) );
					}
				}
			}
		}

		public function send_pre_order_is_for_sale_single_notification( $pre_order_id ) {
			$pre_order_product = new YITH_Pre_Order_Product( $pre_order_id );
			$auto_for_sale = get_option( 'yith_wcpo_enable_pre_order_purchasable' );
			$is_checked_notification = get_option( 'yith_wcpo_enable_pre_order_notification_for_sale' );
			if ( 'yes' == $auto_for_sale ) {
				$pre_order_product->clear_pre_order_product();
				wc_delete_product_transients( $pre_order_id );
			}
			if ( 'yes' == $is_checked_notification ) {
				$customers = self::get_pre_order_customers( $pre_order_id );
				if ( ! $customers ) {
					return;
				}
				WC()->mailer();
				foreach ( $customers as $customer ) {
					do_action( 'yith_ywpo_is_for_sale', $customer, $pre_order_id );
				}
			}

		}

		/**
		 * Get all the customers that have purchased a product in pre-order
		 *
		 * @param int $product_id
		 *
		 * @return array|bool $customers
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public static function get_pre_order_customers( $product_id, $new_sale_date = '' ) {
			global $wpdb;

			$wpcl_orders = '';
			$customers   = array();
			$product     = wc_get_product( $product_id );

			if ( $product->is_type( 'variation' ) ) {
				$customerquery = "SELECT order_id FROM {$wpdb->prefix}woocommerce_order_itemmeta woim 
        		LEFT JOIN {$wpdb->prefix}woocommerce_order_items oi 
        		ON woim.order_item_id = oi.order_item_id 
        		WHERE meta_key = '_variation_id' AND meta_value = %d
        		GROUP BY order_id;";
			} else {
				$customerquery = "SELECT order_id FROM {$wpdb->prefix}woocommerce_order_itemmeta woim 
        		LEFT JOIN {$wpdb->prefix}woocommerce_order_items oi 
        		ON woim.order_item_id = oi.order_item_id 
        		WHERE meta_key = '_product_id' AND meta_value = %d
        		GROUP BY order_id;";
			}
			$order_ids = $wpdb->get_col( $wpdb->prepare( $customerquery, $product_id ) );

			if ( $order_ids ) {
				$args = array(
					'post_type'      => 'shop_order',
					'post__in'       => $order_ids,
					'posts_per_page' => 999,
					'order'          => 'ASC',
					'post_status'    => 'any',
				);
				$wpcl_orders = new WP_Query( $args );
			}
			if ( ! $wpcl_orders ) {
				return false;
			}
			$user_ids = array();
			foreach ( $wpcl_orders->posts as $wpcl_order ) {
				$order = wc_get_order( $wpcl_order->ID );
				$user_id = $order->get_user_id();
				//Prevent duplicated customers
				if ( ! in_array( $user_id, $user_ids ) ) {
					foreach ( $order->get_items() as $item_id => $item ) {

						$item_is_pre_order = ! empty( $item['ywpo_item_preorder'] ) ? $item['ywpo_item_preorder'] : '';
						$item_timestamp = ! empty( $item['ywpo_item_for_sale_date'] ) ? $item['ywpo_item_for_sale_date'] : '';
						$item_is_notified = ! empty( $item['ywpo_item_preorder_notified'] ) ? $item['ywpo_item_preorder_notified'] : '';

						if ( 'yes' != $item_is_pre_order ) {
							continue;
						}

						if ( 'yes' == $item_is_notified ) {
							continue;
						}

						if ( $order instanceof WC_Data ) {
							$customers[] = array(
								'name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
								'email' => $order->get_billing_email(),
								'order' => $order->get_id()
							);
						} else {
							$customers[] = array(
								'name'  => $order->billing_first_name . ' ' . $order->billing_last_name,
								'email' => $order->billing_email,
								'order' => $order->get_id()
							);
						}

						$user_ids[] = $user_id;

						if ( ! $new_sale_date ) {
							wc_update_order_item_meta( $item_id, '_ywpo_item_preorder_notified', 'yes' );
						}

						// Change the order item meta only for Pre-Order which haven't end its date yet.
						if ( ( $item_timestamp > time() || empty( $item_timestamp ) ) && $new_sale_date && apply_filters( 'yith_ywpo_update_availability_date_on_orders', true, $item, $item_id, $order, $new_sale_date, $customers ) ) {
							$format_date = str_replace( '/', '-', $new_sale_date );
							$format_date = $format_date . ':00';
							$format_date = get_gmt_from_date( $format_date );
							$new_date    = strtotime( $format_date );
							wc_update_order_item_meta( $item_id, '_ywpo_item_for_sale_date', $new_date );
						}

					}
				}
			}
			return $customers;
		}

	}
}
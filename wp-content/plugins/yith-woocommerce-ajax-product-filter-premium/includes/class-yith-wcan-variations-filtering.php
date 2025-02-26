<?php
/**
 * Additional query modifications for Variations filtering
 *
 * Appends additional clauses to products queries when filtering,
 * in order to only show products when they have at least one variation matching
 * the attribute selections/stock status requested with layered nav.
 *
 * This feature require the usage of wc_product_attributes_lookup table,
 * which can be created, populated and maintained up to date enabling option
 * under WooCommerce -> Settings -> Products -> Advanced -> Product attributes lookup table.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\AjaxProductFilter\Classes
 * @version 4.0.0
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCAN_Variations_Filtering' ) ) {
	/**
	 * Query Handling
	 *
	 * @since 4.0.0
	 */
	class YITH_WCAN_Variations_Filtering {
		/**
		 * Instance of the Filterer for ProductAttributes lookup table
		 *
		 * @var Automattic\WooCommerce\Internal\ProductAttributesLookup\Filterer;
		 */
		protected $filterer;

		/**
		 * Instance of the DataStore for ProductAttributes lookup table
		 *
		 * @var Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore;
		 */
		protected $data_store;

		/**
		 * Whether variations filtering is active or not
		 *
		 * @var bool
		 */
		protected $is_filtering_active = null;

		/**
		 * Temporary cache of queried results
		 *
		 * @var array
		 */
		protected $products_or_variations_by_attributes = array();

		/**
		 * Main instance
		 *
		 * @var YITH_WCAN_Variations_Filtering
		 * @since 4.0.0
		 */
		protected static $instance = null;

		/**
		 * Constructor method
		 */
		protected function __construct() {
			// check on filtering status, before applying changes to the query.
			if ( ! $this->is_filtering_active() ) {
				return;
			}

			// query changes.
			add_filter( 'posts_clauses', array( $this, 'append_additional_clauses' ), 10, 2 );
			add_filter( 'option_woocommerce_attribute_lookup_enabled', '__return_empty_string' );
			add_filter( 'yith_wcan_query_relevant_term_objects', array( $this, 'update_matching_products' ), 10, 3 );
			add_filter( 'yith_wcan_query_relevant_in_stock_products', array( $this, 'update_matching_products' ) );

			// layout changes.
			add_action( 'init', array( $this, 'maybe_apply_layout_changes' ) );
		}

		/**
		 * Apply layout changes to variable product template, when a single variation matches filters
		 */
		public function maybe_apply_layout_changes() {
			if ( ! apply_filters( 'yith_wcan_variations_filtering_alter_product_layout', ! yith_wcan_is_excluded() ) ) {
				return;
			}

			add_filter( 'woocommerce_product_get_image_id', array( $this, 'filter_loop_image' ), 10, 2 );
			add_filter( 'woocommerce_variable_price_html', array( $this, 'filter_loop_price' ), 10, 2 );
			add_filter( 'woocommerce_product_title', array( $this, 'filter_loop_title' ), 10, 2 );
			add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'filter_loop_add_to_cart_url' ), 10, 2 );
			add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'filter_loop_add_to_cart_text' ), 10, 2 );
			add_filter( 'the_title', array( $this, 'filter_loop_title' ), 10, 2 );
			add_filter( 'post_type_link', array( $this, 'filter_loop_permalink' ), 10, 2 );
		}

		/**
		 * Append clauses to the products query
		 *
		 * @param array     $clauses Query clauses.
		 * @param \WP_Query $query Query being executed.
		 * @return array Filtered list of query clauses.
		 */
		public function append_additional_clauses( $clauses, $query ) {
			global $wpdb;

			$q = YITH_WCAN_Query();

			if ( ! $this->is_filtering_active() || ! $q->should_process_query( $query ) || ! $q->get_layered_nav_chosen_attributes() ) {
				return $clauses;
			}

			$matching_products = $this->get_filtered_products_or_variations();

			if ( ! empty( $matching_products ) ) {
				$products_join = implode( ', ', array_unique( array_merge( wp_list_pluck( $matching_products, 'product_or_parent_id' ), wp_list_pluck( $matching_products, 'product_id' ) ) ) );

				$clauses['where'] .= " AND {$wpdb->posts}.ID IN ( {$products_join} )";
			} else {
				$clauses['where'] .= ' AND 1=0';
			}

			return $clauses;
		}

		/**
		 * Update list of matching products used for attributes/in stock filters
		 *
		 * @param array  $products List of matching products.
		 * @param string $taxonomy Slug of the taxonomy (used only when filtering products matching an attribute).
		 * @param int    $term_id  Term id being matched (used only when filtering products matching an attribute).
		 *
		 * @return array List of filtered product ids.
		 */
		public function update_matching_products( $products, $taxonomy = false, $term_id = false ) {
			$q = YITH_WCAN_Query();

			$attributes    = $q->get_layered_nav_chosen_attributes();
			$in_stock_only = $q->is_stock_only() || 'yes' === yith_wcan_get_option( 'yith_wcan_hide_out_of_stock_products', 'no' );

			if ( ! $this->is_filtering_active() || empty( $products ) || ! str_starts_with( $taxonomy, 'pa_' ) ) {
				return $products;
			}

			$current_action = current_action();

			if ( 'yith_wcan_query_relevant_term_objects' === $current_action && $term_id ) {
				$term = get_term( $term_id, $taxonomy );

				if ( ! $term || is_wp_error( $term ) ) {
					return $products;
				}

				// override attributes for current taxonomy, and set only current term as active.
				$attributes[ $taxonomy ]['terms'] = array( $term->slug );
			} elseif ( 'yith_wcan_query_relevant_in_stock_products' === $current_action ) {
				$in_stock_only = true;
			}

			// find matching variations.
			$matching_products = $this->get_filtered_products_or_variations_by_attributes( $attributes, $in_stock_only );

			if ( empty( $matching_products ) ) {
				return array();
			}

			return array_intersect( $products, array_unique( wp_list_pluck( $matching_products, 'product_or_parent_id' ) ) );
		}

		/**
		 * Filters product image to show on loop
		 *
		 * @param int        $image_id Image id to filter.
		 * @param WC_Product $product  Product object.
		 *
		 * @return int Image id.
		 */
		public function filter_loop_image( $image_id, $product ) {
			$matching_variation = $this->get_single_matching_variation( $product );

			if ( ! $matching_variation ) {
				return $image_id;
			}

			return $matching_variation->get_image_id();
		}

		/**
		 * Filters product title to show on loop
		 *
		 * @param string     $title Title to filter.
		 * @param WC_Product $product  Product object.
		 *
		 * @return string Filtered title.
		 */
		public function filter_loop_title( $title, $product ) {
			$matching_variation = $this->get_single_matching_variation( $product );

			if ( ! $matching_variation ) {
				return $title;
			}

			$formatted_variation_list = wc_get_formatted_variation( $matching_variation, true, false, false );

			return $matching_variation->get_title() . ' - <span class="description">' . $formatted_variation_list . '</span>';
		}

		/**
		 * Filters product permalink to show on loop
		 *
		 * @param string     $permalink Permalink to filter.
		 * @param WC_Product $product   Product object.
		 *
		 * @return string Filtered permalink.
		 */
		public function filter_loop_permalink( $permalink, $product ) {
			$matching_variation = $this->get_single_matching_variation( $product );

			if ( ! $matching_variation ) {
				return $permalink;
			}

			remove_filter( 'post_type_link', array( $this, 'filter_loop_permalink' ) );
			$permalink = $matching_variation->get_permalink();
			add_filter( 'post_type_link', array( $this, 'filter_loop_permalink' ), 10, 2 );

			return $permalink;
		}

		/**
		 * Filter product price to show on loop
		 *
		 * @param string     $price   Price html to filter.
		 * @param WC_Product $product Product object.
		 *
		 * @return string Filtered price html.
		 */
		public function filter_loop_price( $price, $product ) {
			$matching_variation = $this->get_single_matching_variation( $product );

			if ( ! $matching_variation ) {
				return $price;
			}

			return $matching_variation->get_price_html();
		}

		/**
		 * Filter Add to Cart url to use on loop
		 *
		 * @param string     $url     Add to Cart url to filter.
		 * @param WC_Product $product Product object.
		 *
		 * @return string Filtered Add to Cart url.
		 */
		public function filter_loop_add_to_cart_url( $url, $product ) {
			$matching_variation = $this->get_single_matching_variation( $product );

			if ( ! $matching_variation ) {
				return $url;
			}

			$q = YITH_WCAN_Query();

			$attributes           = $q->get_layered_nav_chosen_attributes();
			$query_args           = array();
			$variation_attributes = $matching_variation->get_attributes();

			foreach ( $variation_attributes as $attribute_name => $variation_attribute ) {
				if ( empty( $variation_attribute ) && empty( $attributes[ $attribute_name ] ) ) {
					return $url;
				}

				$query_args[ "attribute_$attribute_name" ] = $variation_attribute ? $variation_attribute : array_shift( $attributes[ $attribute_name ]['terms'] );
			}

			remove_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'filter_loop_add_to_cart_url' ) );
			$add_variation_url = $matching_variation->add_to_cart_url();
			add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'filter_loop_add_to_cart_url' ), 10, 2 );

			return add_query_arg( $query_args, $add_variation_url );
		}

		/**
		 * Filter Add to Cart text to use on loop
		 *
		 * @param string     $text    Add to Cart text to filter.
		 * @param WC_Product $product Product object.
		 *
		 * @return string Filtered Add to Cart text.
		 */
		public function filter_loop_add_to_cart_text( $text, $product ) {
			$matching_variation = $this->get_single_matching_variation( $product );

			if ( ! $matching_variation ) {
				return $text;
			}

			$q = YITH_WCAN_Query();

			$attributes           = $q->get_layered_nav_chosen_attributes();
			$variation_attributes = $matching_variation->get_attributes();

			foreach ( $variation_attributes as $attribute_name => $variation_attribute ) {
				if ( empty( $variation_attribute ) && empty( $attributes[ $attribute_name ] ) ) {
					return $text;
				}
			}

			remove_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'filter_loop_add_to_cart_text' ) );
			$add_variation_text = $matching_variation->add_to_cart_text();
			add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'filter_loop_add_to_cart_text' ), 10, 2 );

			return $add_variation_text;
		}

		/**
		 * Returns a list of products that match current query condition (simple matching or variable with at least one variation matching)
		 *
		 * Query will search for products that have the specified set of attributes,
		 * either as variation attribute in one of their variation, or as a plain product attribute
		 *
		 * @return array Array of product ids matching the list of attributes.
		 */
		protected function get_filtered_products_or_variations() {
			$q = YITH_WCAN_Query();

			$attributes    = $q->get_layered_nav_chosen_attributes();
			$in_stock_only = $q->is_stock_only() || 'yes' === yith_wcan_get_option( 'yith_wcan_hide_out_of_stock_products', 'no' );

			return $this->get_filtered_products_or_variations_by_attributes( $attributes, $in_stock_only );
		}

		/**
		 * Returns a list of products that matches passed attributes list
		 *
		 * Query will search for products that have the specified set of attributes,
		 * either as variation attribute in one of their variation, or as a plain product attribute
		 *
		 * @param array $attributes    Array of attributes to be used for filtering.
		 * @param bool  $in_stock_only Whether to search for in-stock products only.
		 *
		 * @return array Array of product ids matching the list of attributes.
		 */
		protected function get_filtered_products_or_variations_by_attributes( $attributes, $in_stock_only = false ) {
			global $wpdb;

			$lookup_table = $this->get_lookup_table_name();

			if ( empty( $lookup_table ) || empty( $attributes ) ) {
				return array();
			}

			$hash_parts      = array_merge(
				$attributes,
				$in_stock_only ? array( 'in_stock' => 'yes' ) : array()
			);
			$calculated_hash = md5( http_build_query( $hash_parts ) );
			$product_ids     = $this->products_or_variations_by_attributes[ $calculated_hash ] ?? false;

			if ( false === $product_ids ) {
				// retrieve term ids from slugs.
				foreach ( $attributes as $taxonomy => & $data ) {
					$term_ids = get_terms(
						array(
							'taxonomy'   => $taxonomy,
							'fields'     => 'ids',
							'hide_empty' => false,
							'slug'       => $data['terms'],
						)
					);

					if ( ! $term_ids || is_wp_error( $term_ids ) ) {
						unset( $attributes[ $taxonomy ] );
					}

					$data['term_ids'] = $term_ids;
				}

				if ( empty( $attributes ) ) {
					$product_ids = array();
				} else {
					// in stock condition.
					$in_stock_condition = '';

					if ( $in_stock_only ) {
						$in_stock_condition = 'AND in_stock = 1';
					}

					// join taxonomies and term ids for query purpose.
					$taxonomies_list  = array_unique( array_keys( $attributes ) );
					$taxonomies_count = count( $taxonomies_list );
					$taxonomies_plc   = trim( str_repeat( '%s, ', $taxonomies_count ), ', ' );
					$taxonomies_join  = $wpdb->prepare( $taxonomies_plc, $taxonomies_list ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$terms_list       = array_unique( array_merge( ...array_values( wp_list_pluck( $attributes, 'term_ids' ) ) ) );
					$terms_plc        = trim( str_repeat( '%d, ', count( $terms_list ) ), ', ' );
					$terms_join       = $wpdb->prepare( $terms_plc, $terms_list ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

					// process query to retrieve matching product ids.
					$product_ids = $wpdb->get_results(
						<<<EOQ
						SELECT
							product_or_parent_id,
							IF( temp1.product_id IS NOT NULL, temp1.product_id, temp2.product_id ) AS product_id
						FROM (
							SELECT product_or_parent_id, product_id, count( DISTINCT( taxonomy ) ) AS cnt
							FROM {$lookup_table}
							WHERE taxonomy IN ( {$taxonomies_join} )
								AND term_id IN ( {$terms_join} )
								{$in_stock_condition}
								AND is_variation_attribute = 1
							GROUP BY product_id
						) AS temp1
						LEFT JOIN (
							SELECT product_or_parent_id, product_id, count( DISTINCT( taxonomy ) ) AS cnt
							FROM {$lookup_table}
							WHERE taxonomy IN ( {$taxonomies_join} )
								AND term_id IN ( {$terms_join} )
								{$in_stock_condition}
								AND is_variation_attribute = 0
							GROUP BY product_or_parent_id
						) AS temp2 USING ( product_or_parent_id )
						WHERE IF( temp1.cnt IS NULL, 0, temp1.cnt ) + IF( temp2.cnt IS NULL, 0, temp2.cnt ) = {$taxonomies_count}
						UNION
						SELECT
							product_or_parent_id,
							IF( temp1.product_id IS NOT NULL, temp1.product_id, temp2.product_id ) AS product_id
						FROM (
							SELECT product_or_parent_id, product_id, count( DISTINCT( taxonomy ) ) AS cnt
							FROM {$lookup_table}
							WHERE taxonomy IN ( {$taxonomies_join} )
								AND term_id IN ( {$terms_join} )
								{$in_stock_condition}
								AND is_variation_attribute = 0
							GROUP BY product_or_parent_id
						) AS temp1
						LEFT JOIN (
							SELECT product_or_parent_id, product_id, count( DISTINCT( taxonomy ) ) AS cnt
							FROM {$lookup_table}
							WHERE taxonomy IN ( {$taxonomies_join} )
								AND term_id IN ( {$terms_join} )
								{$in_stock_condition}
								AND is_variation_attribute = 1
							GROUP BY product_id
						) AS temp2 USING ( product_or_parent_id )
						WHERE IF( temp1.cnt IS NULL, 0, temp1.cnt ) + IF( temp2.cnt IS NULL, 0, temp2.cnt ) = {$taxonomies_count}
						EOQ,
						ARRAY_A
					);
				}

				// cache results for future usage.
				$this->products_or_variations_by_attributes[ $calculated_hash ] = $product_ids;
			}

			return $product_ids;
		}

		/**
		 * Retrieve single variation of passed product matching current attribute selection
		 *
		 * In case we're filtering, and a variable product is passed to this method,
		 * system will retrieve the single variation of the product matching current attributes selection
		 * If more than one variation is matching, or none does, system will return false.
		 *
		 * @param WC_Product|int $product Product object or product id.
		 * @return WC_Product_Variation|bool Variation found, or false if none (or more than one) matched.
		 */
		public function get_single_matching_variation( $product ) {
			if ( $product instanceof WP_Post ) {
				$product = wc_get_product( $product->ID );
			} elseif ( is_scalar( $product ) ) {
				$product = wc_get_product( (int) $product );
			}

			if (
				! $product ||
				! $product instanceof WC_Product ||
				! $product->is_type( array( 'variable' ) )
			) {
				return false;
			}

			$q = YITH_WCAN_Query();

			if ( ! $this->is_filtering_active() || ! $q->should_filter() ) {
				return false;
			}

			$product_id = $product->get_id();

			$matching_variation_id = YITH_WCAN_Cache_Helper::get_for_current_query( 'single_matching_variation', $product_id );

			if ( false === $matching_variation_id ) {
				$matching_products = $this->get_filtered_products_or_variations();
				$matching_records  = wp_list_filter(
					$matching_products,
					array(
						'product_or_parent_id' => $product_id,
					)
				);
				$matching_records  = wp_list_filter(
					$matching_records,
					array(
						'product_id' => $product_id,
					),
					'NOT'
				);

				if ( empty( $matching_records ) || count( $matching_records ) > 1 ) {
					$matching_variation_id = 0;
				} else {
					$first_matching_record = current( $matching_records );
					$matching_variation_id = $first_matching_record['product_id'] ?? 0;
				}

				YITH_WCAN_Cache_Helper::set_for_current_query( 'single_matching_variation', $matching_variation_id, $product_id );
			}

			$matching_variation = $matching_variation_id ? wc_get_product( $matching_variation_id ) : false;

			if ( ! $matching_variation || ! $matching_variation->is_type( 'variation' ) ) {
				return false;
			}

			return $matching_variation;
		}

		/**
		 * Test that checks if filtering is active
		 *
		 * @return bool Whether variations filtering is active
		 */
		public function is_filtering_active() {
			if ( is_null( $this->is_filtering_active ) ) {
				$filterer   = $this->get_filterer();
				$data_store = $this->get_data_store();

				$this->is_filtering_active = 'yes' === get_option( 'yith_woocommerce_variations_filtering' ) && $filterer && $filterer->filtering_via_lookup_table_is_active() && $data_store && $data_store->check_lookup_table_exists();
			}

			return $this->is_filtering_active;
		}

		/**
		 * Retrieves data store object for ProductAttributes lookup table
		 *
		 * @return Automattic\WooCommerce\Internal\ProductAttributesLookup\Filterer
		 */
		protected function get_filterer() {
			if ( ! class_exists( 'Automattic\WooCommerce\Internal\ProductAttributesLookup\Filterer' ) ) {
				return null;
			}

			if ( empty( $this->filterer ) ) {
				$this->filterer = wc_get_container()->get( Automattic\WooCommerce\Internal\ProductAttributesLookup\Filterer::class );
			}

			return $this->filterer;
		}

		/**
		 * Retrieves data store object for ProductAttributes lookup table
		 *
		 * @return Automattic\WooCommerce\Internal\ProductAttributesLookup\Filterer
		 */
		protected function get_data_store() {
			if ( ! class_exists( 'Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore' ) ) {
				return null;
			}

			if ( empty( $this->data_store ) ) {
				$this->data_store = wc_get_container()->get( Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore::class );
			}

			return $this->data_store;
		}

		/**
		 * Retrieves name of the DB table used by WooCommerce for lookup
		 *
		 * @return string
		 */
		protected function get_lookup_table_name() {
			$data_store = $this->get_data_store();

			if ( ! $data_store ) {
				return '';
			}

			return $data_store->get_lookup_table_name();
		}

		/**
		 * Returns class instance
		 *
		 * @return YITH_WCAN_Variations_Filtering Class single instance
		 */
		public static function instance() {
			if ( is_null( static::$instance ) ) {
				static::$instance = new static();
			}

			return static::$instance;
		}
	}
}

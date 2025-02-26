<?php
/**
 * Manage install, and performs all post update operations
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\AjaxProductFilter\Classes
 * @version 4.0.0
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCAN_Install_Extended' ) ) {
	/**
	 * Filter Presets Handling
	 *
	 * @since 4.0.0
	 */
	class YITH_WCAN_Install_Extended extends YITH_WCAN_Install {

		/**
		 * Name of Filters Lookup table
		 *
		 * @var string
		 */
		public static $filter_sessions;

		/**
		 * Hooks methods required to install/update plugin
		 *
		 * @return void
		 */
		public static function init() {
			global $wpdb;

			// initialize db tables.
			self::$filter_sessions = "{$wpdb->prefix}yith_wcan_filter_sessions";
			$wpdb->filter_sessions = self::$filter_sessions;

			parent::init();

			add_action( 'init', array( __CLASS__, 'install_endpoints' ), 15 );
		}

		/**
		 * Install required endpoints
		 *
		 * @return void
		 */
		public static function install_endpoints() {
			$session_param = YITH_WCAN_Sessions_Factory::get_session_query_param();

			/**
			 * APPLY_FILTERS: yith_wcan_session_endpoint_places_bitmap
			 *
			 * Filters places bitmap used when registering rewrite endpoint.
			 *
			 * @param int $places_bitmap Bitmap of endpoint "places".
			 *
			 * @return int
			 */
			add_rewrite_endpoint( $session_param, apply_filters( 'yith_wcan_session_endpoint_places_bitmap', EP_PERMALINK | EP_PAGES | EP_CATEGORIES ) );

			/**
			 * Hot fix for static front pages.
			 * EP_ROOT bitmask for endpoints seems not to work well with static homepages; even if request is correctly
			 * parser, page_id isn't added to query_vars, with the result of defaulting to post archives.
			 *
			 * For this very reason we use a specific rewrite rule for static homepages, containing filter_session param
			 */
			$front_page = get_option( 'page_on_front' );

			if ( $front_page ) {
				add_rewrite_rule( "^$session_param(/(.*))?/?$", "index.php?page_id=$front_page&$session_param=\$matches[2]", 'top' );
			}

			/**
			 * Hot fix for shop page, working as product archive page.
			 * Endpoint would work for the shop page, but if you add it to the url, ^shop/?$ rewrite rule, automagically
			 * added to manage products archive, won't match any longer
			 *
			 * For this very reason we use a specific rewrite rule for shop products archive.
			 */
			$shop_page_id = wc_get_page_id( 'shop' );

			if ( current_theme_supports( 'woocommerce' ) ) {
				$has_archive = $shop_page_id && get_post( $shop_page_id ) ? urldecode( get_page_uri( $shop_page_id ) ) : 'shop';

				add_rewrite_rule( "^$has_archive/$session_param(/(.*))?/?$", "index.php?post_type=product&$session_param=\$matches[2]", 'top' );
			}

			/**
			 * Hot fix for product taxonomies.
			 * Even if we use EP_ALL when adding endpoint, system won't register it for custom taxonomies that were created
			 * using EP_NONE as ep_mask; unfortunately this is default value, and our endpoint wouldn't be registered
			 * for any of them
			 *
			 * Refer to https://core.trac.wordpress.org/ticket/33728 for further information on the problem.
			 */
			$taxonomies = YITH_WCAN_Query::instance()->get_supported_taxonomies();

			foreach ( $taxonomies as $tax_id => $tax ) {
				if ( ! $tax->public ) {
					continue;
				}

				$rewrite_slug = is_array( $tax->rewrite ) && ! empty( $tax->rewrite['slug'] ) ? $tax->rewrite['slug'] : $tax_id;

				add_rewrite_rule( "$rewrite_slug/(.+?)/$session_param(/(.*))?/?$", "index.php?$tax_id=\$matches[1]&$session_param=\$matches[3]", 'top' );

			}
		}

		/**
		 * Returns DB structure for the plugin
		 *
		 * @return string
		 */
		public static function get_db_structure() {
			global $wpdb;

			$db_structure = parent::get_db_structure();

			$table   = self::$filter_sessions;
			$collate = '';

			if ( $wpdb->has_cap( 'collation' ) ) {
				$collate = $wpdb->get_charset_collate();
			}

			$db_structure .= "CREATE TABLE {$table} (
							ID BIGINT( 20 ) NOT NULL AUTO_INCREMENT,
							hash CHAR( 32 ) NOT NULL,
							token CHAR( 10 ) NOT NULL,
							origin_url TEXT NOT NULL,
							query_vars TEXT NOT NULL,
							expiration timestamp NULL DEFAULT NULL,
							PRIMARY KEY  ( ID ),
							UNIQUE KEY filter_hash ( hash ),
							UNIQUE KEY filter_token ( token ),
							KEY filter_expiration ( expiration )
						) $collate;";

			return $db_structure;
		}
	}
}

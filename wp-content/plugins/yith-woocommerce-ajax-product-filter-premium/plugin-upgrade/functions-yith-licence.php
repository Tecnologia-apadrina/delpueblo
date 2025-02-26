<?php
/**
 * This file belongs to the YIT Plugin Framework.
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @author  YITH
 * @package YITH/PluginUpgrade
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! function_exists( 'yith_plugin_onboarding_registration_hook' ) ) {
	/**
	 * Register the plugin when activated for onboarding process.
	 * Please note: use this function through register_activation_hook.
	 */
	function yith_plugin_onboarding_registration_hook() {
		$plugin = str_replace( 'activate_', '', current_filter() );
		if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) || apply_filters( "yith_licence_onboarding_registration_{$plugin}", true ) ) {

			$onboarding_queue = get_transient( 'yith_plugin_licence_onboarding_queue' );
			if ( empty( $onboarding_queue ) || ! is_array( $onboarding_queue ) ) {
				$onboarding_queue = array();
			}

			$onboarding_queue[] = $plugin;
			set_transient( 'yith_plugin_licence_onboarding_queue', $onboarding_queue, 30 * MINUTE_IN_SECONDS );
		}
	}
}

if ( ! function_exists( 'yith_plugin_upgrade_get_home_url' ) ) {
	/**
	 * Get the home url without protocol
	 *
	 * @since  5.0.0
	 * @return string The home url.
	 */
	function yith_plugin_upgrade_get_home_url(): string {

		add_filter( 'wpml_get_home_url', 'yith_plugin_upgrade_wpml_home_path', PHP_INT_MAX, 2 );

		$home_url = home_url();

		remove_filter( 'wpml_get_home_url', 'yith_plugin_upgrade_wpml_home_path', PHP_INT_MAX );

		$schemes = array( 'https://', 'http://', 'www.' );

		foreach ( $schemes as $scheme ) {
			$home_url = str_replace( $scheme, '', $home_url );
		}

		if ( false !== strpos( $home_url, '?' ) ) {
			list( $base, $query ) = explode( '?', $home_url, 2 );
			$home_url             = $base;
		}

		return untrailingslashit( $home_url );
	}
}

if ( ! function_exists( 'yith_plugin_upgrade_wpml_home_path' ) ) {
	/**
	 * Prevent WPML filter home url.
	 *
	 * @since 5.1.2
	 * @param string $wpml_home_url Filtered WPML home path.
	 * @param string $original_home_url Original home path.
	 * @return string
	 */
	function yith_plugin_upgrade_wpml_home_path( $wpml_home_url, $original_home_url ) {
		return $original_home_url;
	}
}

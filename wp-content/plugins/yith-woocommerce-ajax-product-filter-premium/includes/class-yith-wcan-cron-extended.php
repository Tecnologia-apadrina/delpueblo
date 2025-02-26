<?php
/**
 * Cron Extended
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\AjaxProductFilter\Classes
 * @version 4.0.0
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCAN_Cron_Extended' ) ) {
	/**
	 * This class handles cron for filter plugin
	 *
	 * @since 4.0.0
	 */
	class YITH_WCAN_Cron_Extended extends YITH_WCAN_Cron {

		/**
		 * Returns registered crons
		 *
		 * @return array Array of registered crons ans callbacks
		 */
		public function get_crons() {
			if ( empty( $this->crons ) ) {
				$this->crons = array_merge(
					parent::get_crons(),
					array(
						'yith_wcan_delete_expired_sessions'   => array(
							'schedule' => 'daily',
							'callback' => array( $this, 'delete_expired_sessions' ),
						),
					)
				);
			}

			/**
			 * APPLY_FILTERS: yith_wcan_crons
			 *
			 * Filters array of crons available for the plugin.
			 *
			 * @param array $crons Array of existing crons.
			 *
			 * @return array
			 */
			return apply_filters( 'yith_wcan_crons', $this->crons );
		}

		/**
		 * Delete expired session wishlist
		 *
		 * @return void
		 */
		public function delete_expired_sessions() {
			try {
				WC_Data_Store::load( 'filter_session' )->delete_expired();
			} catch ( Exception $e ) {
				return;
			}
		}
	}
}

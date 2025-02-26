<?php

if ( ! defined ( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


if ( ! class_exists( 'YITH_Quick_Export_DropBox' ) ) {

	/**
	 * @class   YITH_Quick_Export_DropBox
	 *
	 * @author  Your Inspiration Themes
	 * @package YITH WooCommerce Quick Export
	 * @version 1.0.0
	 */
	class YITH_Quick_Export_DropBox {

		/**
		 * Dropbox info
		 */
		public $base_dir_backup			= '';
		public $dropbox_accesstoken		= '';
		public $dropbox_redurect_uri	= 'https://update.yithemes.com/dropbox-apps/authorize-new.php?app=quick-export';
		public $dropbox_app_key 		= 'p30fly4jdiuhswy';

		/**
		 * Single instance of the class
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 */
		public static function get_instance() {

			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Construct
		 */
		private function __construct() {

			if ( isset( $_POST['ywqe_dropbox_access_token'] ) ) {
				$this->dropbox_accesstoken = $_POST['ywqe_dropbox_access_token'];
			} else {
				$this->dropbox_accesstoken = get_option( 'ywqe_dropbox_access_token' );
			}

		}

		/**
		 * Initialize
		 */
		public function initialize( $base_dir_backup ) {
			$this->base_dir_backup = $base_dir_backup;
		}

		/**
		 * Save DropBox access token
		 */
		public function custom_save_ywqe_dropbox() {

			if ( isset( $_POST['ywqe_dropbox_access_token'] ) ) {
				update_option( 'ywqe_dropbox_access_token', $_POST['ywqe_dropbox_access_token'] );
			}

		}

		/**
		 * Disable the DropBox backup
		 */
		public function disable_dropbox_backup() {

			/*
			if ( $this->dropbox_accesstoken ) {
				try {
					delete_option( 'ywqe_dropbox_access_token' );

					$dbxClient = new Dropbox\Client( $this->dropbox_accesstoken, "PHP-Example/1.0" );

					//  try to retrieve information to verify if access token is valid
					return $dbxClient->disableAccessToken();

				} catch ( \Dropbox\Exception $e ) {
					error_log( esc_html__( 'Dropbox backup: unable to disable authorization > ', 'yith-woocommerce-quick-export' ) . $e->getMessage() );
				}
			}
			*/

		}

		/**
		 * Upload document to dropbox, if access token is valid
		 */
		public function send_document_to_dropbox( $filepath ) {

			if ( ! $this->dropbox_accesstoken ) {
				error_log( esc_html__( 'Error: no Access Token.', 'yith-woocommerce-quick-export' ) );
				return;
			}

			$dropbox_accesstoken = $this->dropbox_accesstoken;

			if ( file_exists( $filepath ) ) {

				$doc_full_path	= $filepath;
				$doc_folder		= 'Exports';
				$doc_path		= $this->get_doc_path();
				$file 			= file_get_contents( $doc_full_path );

				$ch = curl_init();

				curl_setopt( $ch, CURLOPT_URL, "https://content.dropboxapi.com/2/files/upload");
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $file );
				curl_setopt( $ch, CURLOPT_POST, 1 );

				$headers = array();
				$headers[] = "Authorization: Bearer $dropbox_accesstoken";
				$headers[] = "Dropbox-Api-Arg: {\"path\": \"/$doc_folder/$doc_path\",\"mode\": \"overwrite\",\"autorename\": true,\"mute\": false}";
				$headers[] = "Content-Type: application/octet-stream";
				curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

				$result = curl_exec( $ch );

				if ( curl_errno( $ch ) ) {
					error_log( esc_html__( 'Error: unable to send file to Dropbox.', 'yith-woocommerce-quick-export' ) );
					error_log( 'Error:' . curl_error( $ch ) );
				}

				curl_close ($ch);

			}

		}

		/**
		 * Get filename
		 */
		function get_doc_path() {

			$folder_pattern = get_option( 'ywqe_filename_format' );
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
					date('y'),
					sprintf( "%02d", date('m') ),
					sprintf( "%02d", date('d') ),
					sprintf( "%02d", date('H') ),
					sprintf( "%02d", date('i') ),
					sprintf( "%02d", date('s') ),
				),
				$folder_pattern );
			return $folder_pattern . '.zip';

		}

	}

}

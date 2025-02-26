<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! defined( 'YWQE_RESET_DROPBOX' ) ) {
	define( 'YWQE_RESET_DROPBOX', 'reset-dropbox' );
}

if ( ! class_exists( 'YWQE_Custom_Types' ) ) {

	/**
	 * custom types fields
	 *
	 * @class YWQE_Custom_Types
	 * @package Yithemes
	 * @since   1.0.0
	 * @author  Your Inspiration Themes
	 */
	class YWQE_Custom_Types {

		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * @var store the DropBox singleton Instance
		 */
		protected $dropbox;

		public function __construct() {

			/**
			 * Manage showing and saving of dropbox save button
			 */
			add_action( 'woocommerce_admin_field_ywqe_dropbox', array( $this, 'yit_enable_dropbox' ), 10, 1 );
			add_action( 'woocommerce_admin_settings_sanitize_option_ywqe_dropbox_key', array( $this, 'yit_save_dropbox' ), 10, 1 );
		}

		public function yit_enable_dropbox( $args = array() ) {

			if ( empty( $args ) ) {
				return;
			}

			$dropbox = ywqe_get_dropbox();
			
			$args['value']       = ( get_option( $args['id'] ) ) ? get_option( $args['id'] ) : '';
			$name                = isset( $args['name'] ) ? $args['name'] : '';
			$dropbox_accesstoken = get_option( 'ywqe_dropbox_access_token' );
			
			$show_dropbox_login = false;

			// Dropbox API v2 fix
			$dropbox_app_key		= $dropbox::get_instance()->dropbox_app_key;
			$dropbox_redurect_uri	= $dropbox::get_instance()->dropbox_redurect_uri;
			$dropbox_accesstoken	= $dropbox::get_instance()->dropbox_accesstoken;

			?>
			<tr valign="top">
				<th scope="row">
					<label for="ywqe_enable_dropbox"><?php echo $name; ?></label>
				</th>
				<td class="forminp forminp-color plugin-option">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo $name; ?></span></legend>

						<p style="margin-bottom: 10px;">
							<?php
							$example_url = '<a class="thickbox" href="' . YITH_YWQE_ASSETS_IMAGES_URL . 'dropbox-howto.jpg?TB_iframe=true&width=600&height=550">';
                            echo sprintf( esc_html__('Authorize this plugin to access to your Dropbox space.<br/>All <b>new documents</b> will be sent to your Dropbox space as soon as they are created.<br/>Copy and paste the authorization code here, as in %sthis short guide%s.','yith-woocommerce-quick-export'), $example_url, '</a>' );
 							?>
 						</p>

 						<p style="margin-bottom: 10px;">
							<label for="ywqe_dropbox_key"><strong><?php _e( 'Access Token', 'yith-woocommerce-quick-export' ); ?>:</strong></label>
							<input type="password" id="ywqe_dropbox_access_token" name="ywqe_dropbox_access_token" value="<?php echo $dropbox_accesstoken; ?>" style="width: 50%;">
						</p>

						<div style="margin-bottom: 10px;">
							<a href="https://www.dropbox.com/1/oauth2/authorize?client_id=<?php echo $dropbox_app_key; ?>&response_type=code&redirect_uri=<?php echo $dropbox_redurect_uri; ?>"
								id="ywqe_enable_dropbox_button"
								class="button button-primary"
								target="_blank"><?php _e( 'Get new Access Token', 'yith-woocommerce-quick-export' ); ?></a>
						</div>

					</fieldset>
				</td>
			</tr>
			<?php
		}

		/**
		 * Save dropbox access token
		 */
		public function yit_save_dropbox( $option_value ) {
			if ( isset( $_POST['ywqe_dropbox_access_token'] ) && ( ! empty( $_POST['ywqe_dropbox_access_token'] ) ) ) {
				update_option( 'ywqe_dropbox_access_token', $_POST['ywqe_dropbox_access_token'] );
			}
		}
	}
}

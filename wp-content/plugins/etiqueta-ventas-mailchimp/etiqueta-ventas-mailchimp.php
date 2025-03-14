<?php
/*
Plugin Name: Mailchimp Tagging for WooCommerce
Description: Añade una etiqueta a los clientes en mailchimp con el nombre de la categoría del producto
Version: 1.0
Author: Guillermo Cano
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class.
 */
class Mailchimp_WooCommerce_Tagging {

    /**
     * Plugin version.
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Mailchimp API Key.
     *
     * @var string
     */
    private $api_key;

    /**
     * Mailchimp List ID.
     *
     * @var string
     */
    private $list_id;

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'woocommerce_thankyou', array( $this, 'add_mailchimp_tag' ) );
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_init', array( $this, 'test_mailchimp_connection' ) );

        // Load settings.
        $this->api_key = get_option( 'mailchimp_api_key' );
        $this->list_id = get_option( 'mailchimp_list_id' );

        // Schedule cron job
        add_action( 'wp', array( $this, 'schedule_cron_job' ) );
        add_action( 'mailchimp_daily_sync', array( $this, 'daily_sync' ) );

        // Handle manual sync request
        add_action( 'admin_post_mailchimp_manual_sync', array( $this, 'manual_sync' ) );
    }

    /**
     * Schedule the cron job if not already scheduled.
     */
    public function schedule_cron_job() {
        if ( ! wp_next_scheduled( 'mailchimp_daily_sync' ) ) {
            wp_schedule_event( time(), 'daily', 'mailchimp_daily_sync' );
        }
    }

    /**
     * Unschedule the cron job upon plugin deactivation.
     */
    public static function deactivate() {
        $timestamp = wp_next_scheduled( 'mailchimp_daily_sync' );
        wp_unschedule_event( $timestamp, 'mailchimp_daily_sync' );
    }

    /**
     * Add plugin options page.
     */
    public function add_plugin_page() {
        add_options_page(
            'Mailchimp Tagging Settings',
            'Mailchimp Tagging',
            'manage_options',
            'mailchimp-tagging',
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Register settings.
     */
    public function register_settings() {
        register_setting( 'mailchimp-tagging-settings-group', 'mailchimp_api_key' );
        register_setting( 'mailchimp-tagging-settings-group', 'mailchimp_list_id' );
    }

    /**
     * Create the admin page content.
     */
    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>Mailchimp Tagging Settings</h1>
            <?php
            if ( isset( $_GET['mailchimp_test_result'] ) ) {
                $result = $_GET['mailchimp_test_result'];
                if ( $result == 'success' ) {
                    echo '<div class="notice notice-success is-dismissible"><p>Mailchimp connection test successful!</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>Mailchimp connection test failed: ' . esc_html( $result ) . '</p></div>';
                }
            }
            ?>
            <form method="post" action="options.php">
                <?php
                    settings_fields( 'mailchimp-tagging-settings-group' );
                    do_settings_sections( 'mailchimp-tagging-settings-group' );
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Mailchimp API Key</th>
                        <td><input type="text" name="mailchimp_api_key" value="<?php echo esc_attr( get_option('mailchimp_api_key') ); ?>" /></td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Mailchimp List ID</th>
                        <td><input type="text" name="mailchimp_list_id" value="<?php echo esc_attr( get_option('mailchimp_list_id') ); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <form method="post" action="<?php echo esc_url( admin_url( 'options-general.php?page=mailchimp-tagging' ) ); ?>">
                <input type="hidden" name="mailchimp_test_connection" value="1">
                <?php submit_button( 'Test Mailchimp Connection', 'secondary' ); ?>
            </form>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="mailchimp_manual_sync">
                <?php submit_button( 'Force Mailchimp Sync', 'secondary' ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Test Mailchimp Connection
     */
    public function test_mailchimp_connection() {
        if ( isset( $_POST['mailchimp_test_connection'] ) && $_POST['mailchimp_test_connection'] == 1 ) {
            $api_key = get_option( 'mailchimp_api_key' );
            $list_id = get_option( 'mailchimp_list_id' );

            if ( empty( $api_key ) || empty( $list_id ) ) {
                wp_safe_redirect( admin_url( 'options-general.php?page=mailchimp-tagging&mailchimp_test_result=API key and List ID are required' ) );
                exit;
            }

            $endpoint = "https://us1.api.mailchimp.com/3.0/lists/" . $list_id;

            $args = array(
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode( 'user:' . $api_key ),
                    'Content-Type'  => 'application/json',
                ),
                'method'  => 'GET',
                'timeout' => 15,
            );

            $response = wp_remote_get( $endpoint, $args );

            if ( is_wp_error( $response ) ) {
                wp_safe_redirect( admin_url( 'options-general.php?page=mailchimp-tagging&mailchimp_test_result=' . urlencode( $response->get_error_message() ) ) );
                exit;
            } else {
                $body = wp_remote_retrieve_body( $response );
                $data = json_decode( $body );

                if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
                    wp_safe_redirect( admin_url( 'options-general.php?page=mailchimp-tagging&mailchimp_test_result=success' ) );
                    exit;
                } else {
                    wp_safe_redirect( admin_url( 'options-general.php?page=mailchimp-tagging&mailchimp_test_result=' . urlencode( $data->detail ) ) );
                    exit;
                }
            }
        }
    }

    /**
     * Add Mailchimp tag based on product category.
     *
     * @param int $order_id Order ID.
     */
    public function add_mailchimp_tag( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        $customer_email = $order->get_billing_email();

        if ( ! $customer_email ) {
            return;
        }

        foreach ( $order->get_items() as $item_id => $item ) {
            $product_id = $item->get_product_id();
            $terms      = get_the_terms( $product_id, 'product_cat' );

            if ( is_array( $terms ) ) {
                foreach ( $terms as $term ) {
                    $tag_name = sanitize_title( $term->name ); // Sanitize tag name for Mailchimp compatibility.
                    $this->add_tag_to_mailchimp( $customer_email, $tag_name );
                }
            }
        }
    }

    /**
     * Add a tag to a Mailchimp subscriber.
     *
     * @param string $email_address Subscriber email address.
     * @param string $tag_name      Tag name to add.
     */
    private function add_tag_to_mailchimp( $email_address, $tag_name ) {
        if ( empty( $this->api_key ) || empty( $this->list_id ) ) {
            error_log( 'Mailchimp API Key or List ID not set.' );
            return;
        }

        $member_hash = md5( strtolower( $email_address ) );
        $endpoint    = "https://us1.api.mailchimp.com/3.0/lists/{$this->list_id}/members/{$member_hash}/tags";

        $args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( 'user:' . $this->api_key ),
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode(
                array(
                    array(
                        'name'    => $tag_name,
                        'status' => 'active',
                    ),
                )
            ),
            'method'  => 'POST',
            'timeout' => 15,
        );

        $response = wp_remote_post( $endpoint, $args );

        if ( is_wp_error( $response ) ) {
            error_log( 'Error connecting to Mailchimp: ' . $response->get_error_message() );
        } else {
            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body );

            if ( wp_remote_retrieve_response_code( $response ) >= 300 ) {
                error_log( 'Mailchimp API Error: ' . print_r( $data, true ) );
            } else {
                error_log( "Successfully added tag '{$tag_name}' to {$email_address}." );
            }
        }
    }

    /**
     * Daily sync to check and update Mailchimp tags.
     */
    public function daily_sync() {
        $args = array(
            'post_type'      => 'shop_subscription',
            'post_status'    => 'any',
            'posts_per_page' => -1,
        );

        $subscriptions = get_posts( $args );

        foreach ( $subscriptions as $subscription_post ) {
            $subscription = wcs_get_subscription( $subscription_post->ID );
            $customer_email = $subscription->get_billing_email();

            foreach ( $subscription->get_items() as $item_id => $item ) {
                $product_id = $item->get_product_id();
                $terms      = get_the_terms( $product_id, 'product_cat' );

                if ( is_array( $terms ) ) {
                    foreach ( $terms as $term ) {
                        $tag_name = sanitize_title( $term->name ); // Sanitize tag name for Mailchimp compatibility.
                        $this->add_tag_to_mailchimp( $customer_email, $tag_name );
                    }
                }
            }
        }
    }

    /**
     * Manual sync to check and update Mailchimp tags.
     */
    public function manual_sync() {
        $this->daily_sync();
        wp_safe_redirect( admin_url( 'options-general.php?page=mailchimp-tagging&mailchimp_manual_sync_result=success' ) );
        exit;
    }
}

// Initialize the plugin.
new Mailchimp_WooCommerce_Tagging();

// Deactivation hook to unschedule the cron job.
register_deactivation_hook( __FILE__, array( 'Mailchimp_WooCommerce_Tagging', 'deactivate' ) );
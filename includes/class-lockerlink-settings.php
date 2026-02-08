<?php
/**
 * LockerLink Settings Tab for WooCommerce.
 */

defined( 'ABSPATH' ) || exit;

class LockerLink_Settings {

    /**
     * Initialize settings hooks.
     */
    public static function init() {
        add_filter( 'woocommerce_settings_tabs_array', array( __CLASS__, 'add_settings_tab' ), 50 );
        add_action( 'woocommerce_settings_tabs_lockerlink', array( __CLASS__, 'output_settings' ) );
        add_action( 'woocommerce_update_options_lockerlink', array( __CLASS__, 'save_settings' ) );
        add_action( 'wp_ajax_lockerlink_test_connection', array( __CLASS__, 'ajax_test_connection' ) );
    }

    /**
     * Add the LockerLink tab to WooCommerce settings.
     */
    public static function add_settings_tab( $tabs ) {
        $tabs['lockerlink'] = 'LockerLink';
        return $tabs;
    }

    /**
     * Output the settings page.
     */
    public static function output_settings() {
        $webhook_url = get_option( 'lockerlink_webhook_url', '' );
        $api_key     = get_option( 'lockerlink_api_key', '' );
        $enabled     = get_option( 'lockerlink_enabled', 'yes' );

        $is_configured = ! empty( $webhook_url ) && ! empty( $api_key );
        ?>
        <div class="lockerlink-settings-wrap">
            <div class="lockerlink-settings-header">
                <div class="lockerlink-logo-section">
                    <img src="<?php echo esc_url( LOCKERLINK_PLUGIN_URL . 'assets/lockerlink-logo.png' ); ?>" alt="LockerLink" class="lockerlink-logo" />
                </div>
                <div class="lockerlink-connection-status">
                    <?php if ( $is_configured ) : ?>
                        <span class="lockerlink-status-badge lockerlink-status-connected">Connected</span>
                    <?php else : ?>
                        <span class="lockerlink-status-badge lockerlink-status-disconnected">Not Configured</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="lockerlink-settings-body">
                <table class="form-table lockerlink-form-table">
                    <tr>
                        <th scope="row">
                            <label for="lockerlink_enabled">Enable Plugin</label>
                        </th>
                        <td>
                            <label class="lockerlink-toggle">
                                <input type="checkbox" id="lockerlink_enabled" name="lockerlink_enabled" value="yes" <?php checked( $enabled, 'yes' ); ?> />
                                <span class="lockerlink-toggle-slider"></span>
                            </label>
                            <p class="description">Enable or disable the LockerLink integration.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="lockerlink_webhook_url">Webhook URL</label>
                        </th>
                        <td>
                            <input type="url" id="lockerlink_webhook_url" name="lockerlink_webhook_url"
                                   value="<?php echo esc_attr( $webhook_url ); ?>"
                                   class="regular-text lockerlink-input"
                                   placeholder="https://your-server.com/api/webhooks/woocommerce/ll_id_..." />
                            <p class="description">Copy the full Webhook URL from your LockerLink Integrations page.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="lockerlink_api_key">Secret Key</label>
                        </th>
                        <td>
                            <input type="password" id="lockerlink_api_key" name="lockerlink_api_key"
                                   value="<?php echo esc_attr( $api_key ); ?>"
                                   class="regular-text lockerlink-input"
                                   placeholder="ll_sk_..." />
                            <p class="description">Your secret key from LockerLink's Integrations page. Used for webhook signing.</p>
                        </td>
                    </tr>
                </table>

                <div class="lockerlink-actions">
                    <button type="button" id="lockerlink-test-connection" class="button lockerlink-btn-secondary" <?php echo $is_configured ? '' : 'disabled'; ?>>
                        Test Connection
                    </button>
                    <span id="lockerlink-test-result"></span>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(function($) {
            // Enable/disable test button based on field values.
            function checkFields() {
                var hasUrl = $('#lockerlink_webhook_url').val().trim() !== '';
                var hasKey = $('#lockerlink_api_key').val().trim() !== '';
                $('#lockerlink-test-connection').prop('disabled', !(hasUrl && hasKey));
            }
            $('#lockerlink_webhook_url, #lockerlink_api_key').on('input', checkFields);

            // Test connection AJAX.
            $('#lockerlink-test-connection').on('click', function() {
                var $btn = $(this);
                var $result = $('#lockerlink-test-result');

                $btn.prop('disabled', true).text('Testing...');
                $result.html('');

                var $badge = $('.lockerlink-connection-status .lockerlink-status-badge');

                $.post(ajaxurl, {
                    action: 'lockerlink_test_connection',
                    nonce: '<?php echo wp_create_nonce( 'lockerlink_test_connection' ); ?>',
                    webhook_url: $('#lockerlink_webhook_url').val().trim()
                }, function(response) {
                    if (response.success) {
                        $result.html('<span class="lockerlink-test-success">&#10003; Connection successful</span>');
                        $badge.removeClass('lockerlink-status-disconnected').addClass('lockerlink-status-connected').text('Connected');
                    } else {
                        $result.html('<span class="lockerlink-test-error">&#10007; ' + (response.data || 'Connection failed') + '</span>');
                        $badge.removeClass('lockerlink-status-connected').addClass('lockerlink-status-disconnected').text('Connection Failed');
                    }
                    $btn.prop('disabled', false).text('Test Connection');
                }).fail(function() {
                    $result.html('<span class="lockerlink-test-error">&#10007; Request failed</span>');
                    $badge.removeClass('lockerlink-status-connected').addClass('lockerlink-status-disconnected').text('Connection Failed');
                    $btn.prop('disabled', false).text('Test Connection');
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Save settings and re-register webhooks if credentials changed.
     */
    public static function save_settings() {
        $old_url = get_option( 'lockerlink_webhook_url', '' );
        $old_key = get_option( 'lockerlink_api_key', '' );

        // Sanitize and save.
        $new_url = isset( $_POST['lockerlink_webhook_url'] ) ? esc_url_raw( wp_unslash( $_POST['lockerlink_webhook_url'] ) ) : '';
        $new_key = isset( $_POST['lockerlink_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['lockerlink_api_key'] ) ) : '';
        $enabled = isset( $_POST['lockerlink_enabled'] ) ? 'yes' : 'no';

        // Strip trailing slash from URL.
        $new_url = rtrim( $new_url, '/' );

        update_option( 'lockerlink_webhook_url', $new_url );
        update_option( 'lockerlink_api_key', $new_key );
        update_option( 'lockerlink_enabled', $enabled );

        // Clean up old options from previous version.
        delete_option( 'lockerlink_server_url' );
        delete_option( 'lockerlink_api_id' );

        // Re-register webhooks if credentials changed.
        if ( $new_url !== $old_url || $new_key !== $old_key ) {
            if ( class_exists( 'LockerLink_Webhooks' ) ) {
                LockerLink_Webhooks::delete_webhooks();
                if ( ! empty( $new_url ) && ! empty( $new_key ) ) {
                    LockerLink_Webhooks::create_webhooks();
                }
            }
        }
    }

    /**
     * AJAX handler for testing connection.
     */
    public static function ajax_test_connection() {
        check_ajax_referer( 'lockerlink_test_connection', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $webhook_url = isset( $_POST['webhook_url'] ) ? esc_url_raw( wp_unslash( $_POST['webhook_url'] ) ) : '';

        if ( empty( $webhook_url ) ) {
            wp_send_json_error( 'Webhook URL is required.' );
        }

        $response = wp_remote_post( $webhook_url, array(
            'timeout' => 15,
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( array( 'ping' => true ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code >= 200 && $code < 300 ) {
            wp_send_json_success();
        } else {
            wp_send_json_error( 'Server returned HTTP ' . $code );
        }
    }

    /**
     * Get a setting value.
     */
    public static function get( $key, $default = '' ) {
        return get_option( 'lockerlink_' . $key, $default );
    }

    /**
     * Check if the plugin is configured and enabled.
     */
    public static function is_active() {
        return get_option( 'lockerlink_enabled', 'yes' ) === 'yes'
            && ! empty( get_option( 'lockerlink_webhook_url', '' ) )
            && ! empty( get_option( 'lockerlink_api_key', '' ) );
    }
}

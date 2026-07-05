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
        $is_enabled    = ( $enabled === 'yes' );

        $webhook_ids   = (array) get_option( 'lockerlink_webhook_ids', array() );
        $webhook_count = count( array_filter( $webhook_ids ) );
        $last_delivery = get_option( 'lockerlink_last_webhook_delivery', 0 );

        // Header status badge reflects overall readiness.
        if ( $is_configured && $is_enabled ) {
            $header_badge_class = 'lockerlink-status-connected';
            $header_badge_text  = 'Connected';
        } elseif ( $is_configured && ! $is_enabled ) {
            $header_badge_class = 'lockerlink-status-paused';
            $header_badge_text  = 'Paused';
        } else {
            $header_badge_class = 'lockerlink-status-disconnected';
            $header_badge_text  = 'Not Configured';
        }
        ?>
        <div class="lockerlink-settings-wrap">
            <div class="lockerlink-settings-header">
                <div class="lockerlink-logo-section">
                    <img src="<?php echo esc_url( LOCKERLINK_PLUGIN_URL . 'assets/lockerlink-logo.png' ); ?>" alt="LockerLink" class="lockerlink-logo" />
                    <p class="lockerlink-tagline">Smart locker pickup for your WooCommerce store</p>
                </div>
                <div class="lockerlink-connection-status">
                    <span class="lockerlink-status-badge <?php echo esc_attr( $header_badge_class ); ?>"><?php echo esc_html( $header_badge_text ); ?></span>
                </div>
            </div>

            <div class="lockerlink-settings-body">

                <!-- How it works -->
                <div class="lockerlink-callout">
                    <h3 class="lockerlink-callout-title">How it works</h3>
                    <ol class="lockerlink-steps">
                        <li><span class="lockerlink-step-num">1</span><span class="lockerlink-step-text">Copy your <strong>Webhook URL</strong> and <strong>Secret Key</strong> from the <em>Integrations</em> page of your LockerLink dashboard.</span></li>
                        <li><span class="lockerlink-step-num">2</span><span class="lockerlink-step-text">Paste them below, turn the integration on, and save. LockerLink registers the required webhooks automatically.</span></li>
                        <li><span class="lockerlink-step-num">3</span><span class="lockerlink-step-text">Add <strong>Locker Pickup</strong> as a method in your WooCommerce shipping zones so customers can choose it at checkout.</span></li>
                        <li><span class="lockerlink-step-num">4</span><span class="lockerlink-step-text">When you assign a compartment in LockerLink, the customer is emailed pickup details automatically.</span></li>
                    </ol>
                </div>

                <!-- Connection settings -->
                <h3 class="lockerlink-section-title">Connection settings</h3>
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
                            <p class="description">Turn LockerLink on or off. While off, no order data is sent and incoming updates are rejected.</p>
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
                            <p class="description">Find this on your LockerLink dashboard under <strong>Integrations</strong>. Copy the full URL exactly as shown.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="lockerlink_api_key">Secret Key</label>
                        </th>
                        <td>
                            <div class="lockerlink-secret-field">
                                <input type="password" id="lockerlink_api_key" name="lockerlink_api_key"
                                       value="<?php echo esc_attr( $api_key ); ?>"
                                       class="regular-text lockerlink-input"
                                       placeholder="ll_sk_..." autocomplete="off" spellcheck="false" />
                                <button type="button" id="lockerlink-reveal-key" class="lockerlink-reveal-btn" aria-label="Show secret key" aria-pressed="false">Show</button>
                            </div>
                            <p class="description">Also on the <strong>Integrations</strong> page. Used to securely sign webhook traffic &mdash; keep it private.</p>
                        </td>
                    </tr>
                </table>

                <div class="lockerlink-actions">
                    <button type="button" id="lockerlink-test-connection" class="button lockerlink-btn-secondary" <?php echo $is_configured ? '' : 'disabled'; ?>>
                        Test Connection
                    </button>
                    <span id="lockerlink-test-result" class="lockerlink-pill lockerlink-pill-idle" aria-live="polite">Not tested yet</span>
                </div>

                <!-- Integration status -->
                <h3 class="lockerlink-section-title">Integration status</h3>
                <div class="lockerlink-status-panel">
                    <div class="lockerlink-status-row">
                        <span class="lockerlink-status-dot <?php echo $is_enabled ? 'is-green' : 'is-amber'; ?>"></span>
                        <span class="lockerlink-status-key">Plugin</span>
                        <span class="lockerlink-status-val"><?php echo $is_enabled ? 'Enabled' : 'Disabled'; ?></span>
                    </div>
                    <div class="lockerlink-status-row">
                        <span class="lockerlink-status-dot <?php echo $is_configured ? 'is-green' : 'is-red'; ?>"></span>
                        <span class="lockerlink-status-key">Credentials</span>
                        <span class="lockerlink-status-val"><?php echo $is_configured ? 'Configured' : 'Missing &mdash; add your URL and key above'; ?></span>
                    </div>
                    <div class="lockerlink-status-row">
                        <span class="lockerlink-status-dot <?php echo ( $webhook_count > 0 ) ? 'is-green' : ( $is_configured ? 'is-amber' : 'is-grey' ); ?>"></span>
                        <span class="lockerlink-status-key">Webhooks</span>
                        <span class="lockerlink-status-val">
                            <?php
                            if ( $webhook_count > 0 ) {
                                echo esc_html( sprintf( _n( '%d webhook active', '%d webhooks active', $webhook_count, 'lockerlink' ), $webhook_count ) );
                            } elseif ( $is_configured ) {
                                echo 'Not registered yet &mdash; save settings to register';
                            } else {
                                echo 'Waiting for credentials';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="lockerlink-status-row">
                        <span class="lockerlink-status-dot <?php echo $last_delivery ? 'is-green' : 'is-grey'; ?>"></span>
                        <span class="lockerlink-status-key">Last activity</span>
                        <span class="lockerlink-status-val">
                            <?php
                            if ( $last_delivery ) {
                                /* translators: %s: human-readable time difference, e.g. "5 minutes" */
                                echo esc_html( sprintf( __( '%s ago', 'lockerlink' ), human_time_diff( $last_delivery, current_time( 'timestamp' ) ) ) );
                            } else {
                                echo 'No orders sent to LockerLink yet';
                            }
                            ?>
                        </span>
                    </div>
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

            // Reveal / hide secret key.
            $('#lockerlink-reveal-key').on('click', function() {
                var $field = $('#lockerlink_api_key');
                var reveal = $field.attr('type') === 'password';
                $field.attr('type', reveal ? 'text' : 'password');
                $(this).text(reveal ? 'Hide' : 'Show')
                       .attr('aria-pressed', reveal ? 'true' : 'false')
                       .attr('aria-label', reveal ? 'Hide secret key' : 'Show secret key');
            });

            // Render the test result as a status pill.
            function setPill($el, state, text) {
                $el.removeClass('lockerlink-pill-idle lockerlink-pill-testing lockerlink-pill-success lockerlink-pill-error')
                   .addClass('lockerlink-pill-' + state)
                   .text(text);
            }

            // Test connection AJAX.
            $('#lockerlink-test-connection').on('click', function() {
                var $btn = $(this);
                var $result = $('#lockerlink-test-result');
                var $badge = $('.lockerlink-connection-status .lockerlink-status-badge');

                $btn.prop('disabled', true).text('Testing...');
                setPill($result, 'testing', 'Testing…');

                $.post(ajaxurl, {
                    action: 'lockerlink_test_connection',
                    nonce: '<?php echo esc_js( wp_create_nonce( 'lockerlink_test_connection' ) ); ?>',
                    webhook_url: $('#lockerlink_webhook_url').val().trim()
                }, function(response) {
                    if (response.success) {
                        setPill($result, 'success', '✓ Connected');
                        $badge.removeClass('lockerlink-status-disconnected lockerlink-status-paused').addClass('lockerlink-status-connected').text('Connected');
                    } else {
                        setPill($result, 'error', '✗ ' + (response.data || 'Connection failed'));
                        $badge.removeClass('lockerlink-status-connected lockerlink-status-paused').addClass('lockerlink-status-disconnected').text('Connection Failed');
                    }
                    $btn.prop('disabled', false).text('Test Connection');
                }).fail(function() {
                    setPill($result, 'error', '✗ Request failed');
                    $badge.removeClass('lockerlink-status-connected lockerlink-status-paused').addClass('lockerlink-status-disconnected').text('Connection Failed');
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

<?php
/**
 * LockerLink Plugin Updater.
 *
 * Checks GitHub releases for new versions and exposes them to
 * WordPress's built-in plugin update system. Also sends a lightweight
 * version telemetry ping to the LockerLink backend.
 */

defined( 'ABSPATH' ) || exit;

class LockerLink_Updater {

    /**
     * GitHub repository in "owner/repo" format.
     */
    const GITHUB_REPO = 'Jcapehart2/wc-lockerlink-plugin';

    /**
     * How often to check for updates (in seconds). Default: 12 hours.
     */
    const CHECK_INTERVAL = 43200;

    /**
     * Transient key for caching the update check.
     */
    const TRANSIENT_KEY = 'lockerlink_update_check';

    /**
     * Initialize update hooks.
     */
    public static function init() {
        add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'check_for_update' ) );
        add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 20, 3 );
        add_action( 'upgrader_process_complete', array( __CLASS__, 'clear_cache' ), 10, 2 );

        // Clear our cache when WordPress force-checks for updates (Dashboard > Updates > Check again).
        if ( is_admin() && isset( $_GET['force-check'] ) ) {
            delete_transient( self::TRANSIENT_KEY );
        }
    }

    /**
     * Check GitHub for a newer release and inject it into the update transient.
     *
     * @param object $transient The update_plugins transient.
     * @return object
     */
    public static function check_for_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $release = self::get_latest_release();
        if ( ! $release ) {
            return $transient;
        }

        $current_version = LOCKERLINK_VERSION;
        $latest_version  = ltrim( $release['tag_name'], 'v' );

        // Send version telemetry to LockerLink backend.
        self::send_telemetry( $current_version );

        if ( version_compare( $latest_version, $current_version, '>' ) ) {
            $plugin_slug = LOCKERLINK_PLUGIN_BASENAME;

            $transient->response[ $plugin_slug ] = (object) array(
                'slug'        => 'woocommerce-lockerlink',
                'plugin'      => $plugin_slug,
                'new_version' => $latest_version,
                'url'         => $release['html_url'],
                'package'     => $release['zipball_url'],
                'tested'      => '6.9',
                'icons'       => array(
                    'default' => LOCKERLINK_PLUGIN_URL . 'assets/lockerlink-logo.png',
                ),
            );
        }

        return $transient;
    }

    /**
     * Provide plugin info for the WordPress plugin details modal.
     *
     * @param false|object|array $result The result object.
     * @param string             $action The API action.
     * @param object             $args   Plugin API arguments.
     * @return false|object
     */
    public static function plugin_info( $result, $action, $args ) {
        if ( $action !== 'plugin_information' ) {
            return $result;
        }

        if ( ! isset( $args->slug ) || $args->slug !== 'woocommerce-lockerlink' ) {
            return $result;
        }

        $release        = self::get_latest_release();
        $latest_version = $release ? ltrim( $release['tag_name'], 'v' ) : LOCKERLINK_VERSION;
        $download_link  = $release ? $release['zipball_url'] : '';
        $changelog      = $release && ! empty( $release['body'] ) ? nl2br( esc_html( $release['body'] ) ) : 'No changelog available.';

        return (object) array(
            'name'            => 'LockerLink for WooCommerce',
            'slug'            => 'woocommerce-lockerlink',
            'version'         => $latest_version,
            'author'          => '<a href="https://joinlockerlink.com">LockerLink</a>',
            'author_profile'  => 'https://joinlockerlink.com',
            'homepage'        => 'https://joinlockerlink.com',
            'requires'        => '6.0',
            'tested'          => '6.9',
            'requires_php'    => '7.4',
            'download_link'   => $download_link,
            'trunk'           => $download_link,
            'last_updated'    => $release ? date( 'Y-m-d' ) : '',
            'sections'        => array(
                'description'  => 'Connect your WooCommerce store to LockerLink smart locker pickup. Adds locker pickup shipping, auto-registers webhooks, and syncs assignment updates.',
                'changelog'    => $changelog,
            ),
            'banners'         => array(),
        );
    }

    /**
     * Fetch the latest release from GitHub. Cached via transient.
     *
     * @return array|false Release data or false on failure.
     */
    private static function get_latest_release() {
        $cached = get_transient( self::TRANSIENT_KEY );
        if ( is_array( $cached ) ) {
            return $cached;
        }
        // Transient value of 'error' means we recently failed — don't retry yet.
        if ( $cached === 'error' ) {
            return false;
        }

        $url = sprintf( 'https://api.github.com/repos/%s/releases/latest', self::GITHUB_REPO );

        $response = wp_remote_get( $url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept'     => 'application/vnd.github.v3+json',
                'User-Agent' => 'LockerLink-WooCommerce/' . LOCKERLINK_VERSION,
            ),
        ) );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            // Cache the failure briefly to avoid hammering GitHub.
            set_transient( self::TRANSIENT_KEY, 'error', 600 );
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body['tag_name'] ) ) {
            set_transient( self::TRANSIENT_KEY, 'error', 600 );
            return false;
        }

        $release = array(
            'tag_name'   => $body['tag_name'],
            'html_url'   => $body['html_url'],
            'body'       => isset( $body['body'] ) ? $body['body'] : '',
            'zipball_url' => $body['zipball_url'],
        );

        set_transient( self::TRANSIENT_KEY, $release, self::CHECK_INTERVAL );

        return $release;
    }

    /**
     * Clear the cached release data after an upgrade.
     *
     * @param WP_Upgrader $upgrader The upgrader instance.
     * @param array        $options  Upgrade options.
     */
    public static function clear_cache( $upgrader, $options ) {
        if ( $options['action'] === 'update' && $options['type'] === 'plugin' ) {
            delete_transient( self::TRANSIENT_KEY );
        }
    }

    /**
     * Send a lightweight version telemetry ping to the LockerLink backend.
     * Fire-and-forget — failures are silently ignored.
     *
     * @param string $current_version The currently installed plugin version.
     */
    private static function send_telemetry( $current_version ) {
        $webhook_url = get_option( 'lockerlink_webhook_url', '' );
        if ( empty( $webhook_url ) ) {
            return;
        }

        // Only send once per day.
        $last_ping = get_option( 'lockerlink_telemetry_last_ping', 0 );
        if ( ( time() - $last_ping ) < DAY_IN_SECONDS ) {
            return;
        }

        // Derive the base server URL from the webhook URL.
        $parsed = wp_parse_url( $webhook_url );
        if ( empty( $parsed['scheme'] ) || empty( $parsed['host'] ) ) {
            return;
        }
        $base_url = $parsed['scheme'] . '://' . $parsed['host'];
        if ( ! empty( $parsed['port'] ) ) {
            $base_url .= ':' . $parsed['port'];
        }

        $telemetry_url = $base_url . '/api/integrations/woocommerce/telemetry';

        wp_remote_post( $telemetry_url, array(
            'timeout'  => 5,
            'blocking' => false,
            'headers'  => array( 'Content-Type' => 'application/json' ),
            'body'     => wp_json_encode( array(
                'pluginVersion' => $current_version,
                'siteUrl'       => get_site_url(),
                'wcVersion'     => defined( 'WC_VERSION' ) ? WC_VERSION : 'unknown',
                'phpVersion'    => PHP_VERSION,
                'wpVersion'     => get_bloginfo( 'version' ),
            ) ),
        ) );

        update_option( 'lockerlink_telemetry_last_ping', time(), false );
    }
}

<?php
/**
 * Plugin Name: LockerLink for WooCommerce
 * Plugin URI: https://joinlockerlink.com
 * Description: Connect your WooCommerce store to LockerLink smart locker pickup. Adds locker pickup shipping, auto-registers webhooks, and syncs assignment updates.
 * Version: 1.0.2
 * Author: LockerLink
 * Author URI: https://joinlockerlink.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lockerlink
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 9.6
 */

defined( 'ABSPATH' ) || exit;

define( 'LOCKERLINK_VERSION', '1.0.2' );
define( 'LOCKERLINK_PLUGIN_FILE', __FILE__ );
define( 'LOCKERLINK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LOCKERLINK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LOCKERLINK_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check if WooCommerce is active before initializing.
 */
function lockerlink_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'lockerlink_woocommerce_missing_notice' );
        return false;
    }
    return true;
}

/**
 * Admin notice when WooCommerce is not active.
 */
function lockerlink_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><strong>LockerLink for WooCommerce</strong> requires WooCommerce to be installed and active.</p>
    </div>
    <?php
}

/**
 * Initialize the plugin after all plugins are loaded.
 */
function lockerlink_init() {
    if ( ! lockerlink_check_woocommerce() ) {
        return;
    }

    require_once LOCKERLINK_PLUGIN_DIR . 'includes/class-lockerlink-settings.php';
    require_once LOCKERLINK_PLUGIN_DIR . 'includes/class-lockerlink-shipping.php';
    require_once LOCKERLINK_PLUGIN_DIR . 'includes/class-lockerlink-webhooks.php';
    require_once LOCKERLINK_PLUGIN_DIR . 'includes/class-lockerlink-callback.php';
    require_once LOCKERLINK_PLUGIN_DIR . 'includes/class-lockerlink-order-meta.php';
    require_once LOCKERLINK_PLUGIN_DIR . 'includes/class-lockerlink-updater.php';

    LockerLink_Settings::init();
    LockerLink_Webhooks::init();
    LockerLink_Callback::init();
    LockerLink_Order_Meta::init();
    LockerLink_Updater::init();
}
add_action( 'plugins_loaded', 'lockerlink_init' );

/**
 * Register the shipping method after WooCommerce initializes shipping.
 */
function lockerlink_register_shipping_method( $methods ) {
    if ( class_exists( 'LockerLink_Shipping' ) ) {
        $methods['lockerlink_locker_pickup'] = 'LockerLink_Shipping';
    }
    return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'lockerlink_register_shipping_method' );

/**
 * Plugin activation hook.
 */
function lockerlink_activate() {
    if ( ! lockerlink_check_woocommerce() ) {
        return;
    }

    require_once LOCKERLINK_PLUGIN_DIR . 'includes/class-lockerlink-webhooks.php';
    LockerLink_Webhooks::create_webhooks();
}
register_activation_hook( __FILE__, 'lockerlink_activate' );

/**
 * Plugin deactivation hook.
 */
function lockerlink_deactivate() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    require_once LOCKERLINK_PLUGIN_DIR . 'includes/class-lockerlink-webhooks.php';
    LockerLink_Webhooks::delete_webhooks();
}
register_deactivation_hook( __FILE__, 'lockerlink_deactivate' );

/**
 * Enqueue admin assets.
 */
function lockerlink_admin_assets( $hook ) {
    $screen = get_current_screen();

    $is_lockerlink_page = false;

    // WooCommerce settings page with LockerLink tab.
    if ( $hook === 'woocommerce_page_wc-settings' && isset( $_GET['tab'] ) && $_GET['tab'] === 'lockerlink' ) {
        $is_lockerlink_page = true;
    }

    // Order edit pages (HPOS and legacy).
    if ( $screen && in_array( $screen->id, array( 'shop_order', 'woocommerce_page_wc-orders' ), true ) ) {
        $is_lockerlink_page = true;
    }

    if ( $is_lockerlink_page ) {
        wp_enqueue_style(
            'lockerlink-admin',
            LOCKERLINK_PLUGIN_URL . 'assets/lockerlink-admin.css',
            array(),
            LOCKERLINK_VERSION
        );
    }
}
add_action( 'admin_enqueue_scripts', 'lockerlink_admin_assets' );

/**
 * Enqueue frontend assets for order details page.
 */
function lockerlink_frontend_assets() {
    if ( is_account_page() ) {
        wp_enqueue_style(
            'lockerlink-frontend',
            LOCKERLINK_PLUGIN_URL . 'assets/lockerlink-frontend.css',
            array(),
            LOCKERLINK_VERSION
        );
    }
}
add_action( 'wp_enqueue_scripts', 'lockerlink_frontend_assets' );

/**
 * Add settings link on the plugins page.
 */
function lockerlink_plugin_action_links( $links ) {
    $settings_link = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=lockerlink' ) . '">Settings</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . LOCKERLINK_PLUGIN_BASENAME, 'lockerlink_plugin_action_links' );

/**
 * Declare HPOS compatibility.
 */
function lockerlink_declare_hpos_compatibility() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
}
add_action( 'before_woocommerce_init', 'lockerlink_declare_hpos_compatibility' );

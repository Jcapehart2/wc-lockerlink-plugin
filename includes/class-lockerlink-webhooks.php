<?php
/**
 * LockerLink Webhook Auto-Registration.
 *
 * Creates order.created and order.updated webhooks pointing to the LockerLink backend.
 * Filters delivery so only orders with lockerlink shipping reach LockerLink.
 */

defined( 'ABSPATH' ) || exit;

class LockerLink_Webhooks {

    /**
     * Option key for stored webhook IDs.
     */
    const OPTION_KEY = 'lockerlink_webhook_ids';

    /**
     * Webhook name prefix used to identify our webhooks.
     */
    const NAME_PREFIX = 'LockerLink - ';

    /**
     * Initialize webhook hooks.
     */
    public static function init() {
        add_filter( 'woocommerce_webhook_should_deliver', array( __CLASS__, 'filter_webhook_delivery' ), 10, 3 );
    }

    /**
     * Create both webhooks programmatically.
     * Always cleans up existing LockerLink webhooks first to prevent duplicates.
     */
    public static function create_webhooks() {
        $delivery_url = rtrim( get_option( 'lockerlink_webhook_url', '' ), '/' );
        $api_key      = get_option( 'lockerlink_api_key', '' );

        if ( empty( $delivery_url ) || empty( $api_key ) ) {
            return;
        }

        // Always clean up any existing LockerLink webhooks first.
        self::delete_all_lockerlink_webhooks();

        $topics      = array( 'order.created', 'order.updated' );
        $webhook_ids = array();

        foreach ( $topics as $topic ) {
            $webhook = new WC_Webhook();
            $webhook->set_name( self::NAME_PREFIX . $topic );
            $webhook->set_topic( $topic );
            $webhook->set_delivery_url( $delivery_url );
            $webhook->set_secret( $api_key );
            $webhook->set_status( 'active' );
            $webhook->set_user_id( get_current_user_id() ?: 1 );
            $webhook->save();

            if ( $webhook->get_id() ) {
                $webhook_ids[] = $webhook->get_id();
            }
        }

        update_option( self::OPTION_KEY, $webhook_ids );
    }

    /**
     * Delete webhooks on plugin deactivation.
     */
    public static function delete_webhooks() {
        self::delete_all_lockerlink_webhooks();
        delete_option( self::OPTION_KEY );
    }

    /**
     * Find and delete ALL LockerLink webhooks by name prefix.
     * This catches orphaned webhooks from previous installs/updates.
     */
    private static function delete_all_lockerlink_webhooks() {
        $data_store  = WC_Data_Store::load( 'webhook' );
        $webhook_ids = $data_store->search_webhooks( array(
            'limit'  => 100,
            'status' => 'all',
        ) );

        foreach ( $webhook_ids as $id ) {
            $webhook = wc_get_webhook( $id );
            if ( ! $webhook ) {
                continue;
            }

            // Match by name prefix.
            if ( strpos( $webhook->get_name(), self::NAME_PREFIX ) === 0 ) {
                $webhook->delete( true );
            }
        }
    }

    /**
     * Filter webhook delivery — only send to LockerLink if the order uses lockerlink shipping.
     *
     * @param bool       $should_deliver Whether the webhook should deliver.
     * @param WC_Webhook $webhook        The webhook instance.
     * @param mixed      $arg            The resource ID (order ID).
     * @return bool
     */
    public static function filter_webhook_delivery( $should_deliver, $webhook, $arg ) {
        // Only filter our own webhooks.
        $our_ids = get_option( self::OPTION_KEY, array() );
        if ( ! in_array( $webhook->get_id(), (array) $our_ids, true ) ) {
            return $should_deliver;
        }

        // Get the order.
        $order = wc_get_order( $arg );
        if ( ! $order ) {
            return false;
        }

        // Check if any shipping line uses lockerlink.
        $shipping_methods = $order->get_shipping_methods();
        foreach ( $shipping_methods as $method ) {
            if ( $method->get_method_id() === 'lockerlink' ) {
                return true;
            }
        }

        return false;
    }
}

<?php
/**
 * LockerLink Webhook Auto-Registration.
 *
 * Creates order.created and order.updated webhooks pointing to the LockerLink backend.
 * Filters delivery so only orders with local_pickup shipping reach LockerLink.
 */

defined( 'ABSPATH' ) || exit;

class LockerLink_Webhooks {

    /**
     * Option key for stored webhook IDs.
     */
    const OPTION_KEY = 'lockerlink_webhook_ids';

    /**
     * Initialize webhook hooks.
     */
    public static function init() {
        add_filter( 'woocommerce_webhook_should_deliver', array( __CLASS__, 'filter_webhook_delivery' ), 10, 3 );
    }

    /**
     * Create both webhooks programmatically.
     */
    public static function create_webhooks() {
        $delivery_url = rtrim( get_option( 'lockerlink_webhook_url', '' ), '/' );
        $api_key      = get_option( 'lockerlink_api_key', '' );

        if ( empty( $delivery_url ) || empty( $api_key ) ) {
            return;
        }

        $topics     = array( 'order.created', 'order.updated' );
        $webhook_ids = array();

        foreach ( $topics as $topic ) {
            $webhook = new WC_Webhook();
            $webhook->set_name( 'LockerLink - ' . $topic );
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
     * Delete webhooks created by this plugin.
     */
    public static function delete_webhooks() {
        $webhook_ids = get_option( self::OPTION_KEY, array() );

        if ( ! is_array( $webhook_ids ) ) {
            return;
        }

        foreach ( $webhook_ids as $id ) {
            $webhook = wc_get_webhook( $id );
            if ( $webhook ) {
                $webhook->delete( true );
            }
        }

        delete_option( self::OPTION_KEY );
    }

    /**
     * Filter webhook delivery — only send to LockerLink if the order uses local_pickup.
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

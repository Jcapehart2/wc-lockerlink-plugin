<?php
/**
 * LockerLink Callback Endpoint.
 *
 * Receives assignment updates from the LockerLink backend via REST API.
 * Verifies HMAC signature, updates order meta, and adds customer-visible order notes.
 */

defined( 'ABSPATH' ) || exit;

class LockerLink_Callback {

    /**
     * Initialize the REST endpoint.
     */
    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    /**
     * Register the assignment-update REST route.
     */
    public static function register_routes() {
        register_rest_route( 'lockerlink/v1', '/assignment-update', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'handle_update' ),
            'permission_callback' => array( __CLASS__, 'verify_signature' ),
        ) );
    }

    /**
     * Verify the HMAC-SHA256 signature from LockerLink.
     *
     * @param WP_REST_Request $request The incoming request.
     * @return bool|WP_Error
     */
    public static function verify_signature( $request ) {
        $signature = $request->get_header( 'x-lockerlink-signature' );
        if ( empty( $signature ) ) {
            return new WP_Error(
                'lockerlink_missing_signature',
                'Missing x-lockerlink-signature header.',
                array( 'status' => 401 )
            );
        }

        $api_key = get_option( 'lockerlink_api_key', '' );
        if ( empty( $api_key ) ) {
            return new WP_Error(
                'lockerlink_not_configured',
                'LockerLink plugin is not configured.',
                array( 'status' => 500 )
            );
        }

        $body            = $request->get_body();
        $expected_sig    = base64_encode( hash_hmac( 'sha256', $body, $api_key, true ) );

        if ( ! hash_equals( $expected_sig, $signature ) ) {
            return new WP_Error(
                'lockerlink_invalid_signature',
                'Invalid signature.',
                array( 'status' => 401 )
            );
        }

        return true;
    }

    /**
     * Handle the assignment update callback.
     *
     * @param WP_REST_Request $request The incoming request.
     * @return WP_REST_Response
     */
    public static function handle_update( $request ) {
        $params = $request->get_json_params();

        $order_id          = isset( $params['orderId'] ) ? absint( $params['orderId'] ) : 0;
        $status            = isset( $params['status'] ) ? sanitize_text_field( $params['status'] ) : '';
        $compartment_label = isset( $params['compartmentLabel'] ) ? sanitize_text_field( $params['compartmentLabel'] ) : '';
        $locker_name       = isset( $params['lockerName'] ) ? sanitize_text_field( $params['lockerName'] ) : '';
        $pickup_url        = isset( $params['pickupUrl'] ) ? esc_url_raw( $params['pickupUrl'] ) : '';
        $unlock_token      = isset( $params['unlockToken'] ) ? sanitize_text_field( $params['unlockToken'] ) : '';

        if ( empty( $order_id ) || empty( $status ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'message' => 'orderId and status are required.',
            ), 400 );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return new WP_REST_Response( array(
                'success' => false,
                'message' => 'Order not found.',
            ), 404 );
        }

        // Update order meta.
        $order->update_meta_data( '_lockerlink_status', $status );

        if ( ! empty( $compartment_label ) ) {
            $order->update_meta_data( '_lockerlink_compartment', $compartment_label );
        }
        if ( ! empty( $locker_name ) ) {
            $order->update_meta_data( '_lockerlink_locker', $locker_name );
        }
        if ( ! empty( $pickup_url ) ) {
            $order->update_meta_data( '_lockerlink_pickup_url', $pickup_url );
        }
        if ( ! empty( $unlock_token ) ) {
            $order->update_meta_data( '_lockerlink_unlock_token', $unlock_token );
        }

        $order->save();

        // Add order notes and trigger emails based on status.
        switch ( $status ) {
            case 'assigned':
                $order->add_order_note(
                    'LockerLink: Order assigned.' . self::format_details( $locker_name, $compartment_label ),
                    false // Internal note, no customer email.
                );
                break;

            case 'loaded':
                $order->add_order_note(
                    'LockerLink: Order loaded into locker.' . self::format_details( $locker_name, $compartment_label ),
                    false
                );
                break;

            case 'notified':
                // Trigger the custom pickup-ready email.
                do_action( 'lockerlink_order_pickup_ready', $order_id, $locker_name, $compartment_label, $pickup_url );
                $order->add_order_note(
                    'LockerLink: Pickup email sent to customer.' . self::format_details( $locker_name, $compartment_label ),
                    false
                );
                break;

            case 'picked_up':
                $order->add_order_note( 'LockerLink: Order picked up from locker.', false );
                break;

            case 'cancelled':
                $order->add_order_note( 'LockerLink: Locker assignment cancelled.', false );
                break;

            default:
                $order->add_order_note( sprintf( 'LockerLink status updated: %s', $status ), false );
                break;
        }

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Assignment update received.',
        ), 200 );
    }

    /**
     * Build a details string from available fields, skipping empty ones.
     */
    private static function format_details( $locker_name, $compartment_label ) {
        $parts = array();
        if ( ! empty( $locker_name ) ) {
            $parts[] = 'Locker: ' . $locker_name;
        }
        if ( ! empty( $compartment_label ) ) {
            $parts[] = 'Compartment: ' . $compartment_label;
        }
        return ! empty( $parts ) ? ' ' . implode( ', ', $parts ) . '.' : '';
    }
}

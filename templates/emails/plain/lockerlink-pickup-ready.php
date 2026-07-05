<?php
/**
 * LockerLink Pickup Ready Email (Plain text).
 *
 * @var WC_Order $order
 * @var string   $email_heading
 * @var string   $locker_name
 * @var string   $compartment_label
 * @var string   $pickup_url
 * @var WC_Email $email
 */

defined( 'ABSPATH' ) || exit;

echo "= " . wp_strip_all_tags( $email_heading ) . " =\n\n";

echo "Hi " . $order->get_billing_first_name() . ",\n\n";

$location = ! empty( $locker_name ) ? ' at ' . $locker_name : '';
echo "Your order #" . $order->get_order_number() . " is ready for pickup" . $location . ".\n\n";

if ( ! empty( $compartment_label ) ) {
    echo "When you arrive, look for compartment " . $compartment_label . ".\n\n";
}

if ( ! empty( $pickup_url ) ) {
    echo "Use this link to unlock the locker when you're there: " . $pickup_url . "\n\n";
}

echo "View your order: " . $order->get_view_order_url() . "\n";

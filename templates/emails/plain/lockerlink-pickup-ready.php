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

echo "Your order #" . $order->get_order_number() . " has been loaded into a smart locker and is ready for pickup.\n\n";

if ( ! empty( $locker_name ) ) {
    echo "Locker: " . $locker_name . "\n";
}
if ( ! empty( $compartment_label ) ) {
    echo "Compartment: " . $compartment_label . "\n";
}
echo "\n";

if ( ! empty( $pickup_url ) ) {
    echo "Pick up your order here: " . $pickup_url . "\n\n";
}

echo "View your order: " . $order->get_view_order_url() . "\n";

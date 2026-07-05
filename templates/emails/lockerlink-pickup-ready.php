<?php
/**
 * LockerLink Pickup Ready Email (HTML).
 *
 * @var WC_Order $order
 * @var string   $email_heading
 * @var string   $locker_name
 * @var string   $compartment_label
 * @var string   $pickup_url
 * @var WC_Email $email
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>Hi <?php echo esc_html( $order->get_billing_first_name() ); ?>,</p>

<p>Your order <strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong> is ready for pickup<?php echo ! empty( $locker_name ) ? ' at <strong>' . esc_html( $locker_name ) . '</strong>' : ''; ?>.</p>

<?php if ( ! empty( $compartment_label ) ) : ?>
<p>When you arrive, look for compartment <strong><?php echo esc_html( $compartment_label ); ?></strong>.</p>
<?php endif; ?>

<?php if ( ! empty( $pickup_url ) ) : ?>
<p>Use the link below to unlock the locker when you're there:</p>
<p style="text-align: center; margin: 28px 0;">
    <a href="<?php echo esc_url( $pickup_url ); ?>" style="display: inline-block; background-color: #00A8E8; color: #ffffff; font-size: 16px; font-weight: 700; padding: 14px 36px; border-radius: 8px; text-decoration: none;">Unlock &amp; Pick Up</a>
</p>
<?php endif; ?>

<p style="color: #5a6a7d; font-size: 13px;">You can also view your pickup details anytime from your <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">order page</a>.</p>

<?php
do_action( 'woocommerce_email_footer', $email );

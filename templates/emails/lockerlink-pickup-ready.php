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

<p>Your order <strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong> has been loaded into a smart locker and is ready for pickup.</p>

<?php if ( ! empty( $locker_name ) || ! empty( $compartment_label ) ) : ?>
<table cellspacing="0" cellpadding="12" width="100%" style="border: 1px solid #e2e8f0; border-radius: 8px; border-collapse: separate; margin: 20px 0;">
    <?php if ( ! empty( $locker_name ) ) : ?>
    <tr>
        <td style="background: #f0f4f8; <?php echo ! empty( $compartment_label ) ? 'border-bottom: 1px solid #e2e8f0;' : ''; ?> font-weight: 600; color: #5a6a7d; width: 140px;">Locker</td>
        <td style="<?php echo ! empty( $compartment_label ) ? 'border-bottom: 1px solid #e2e8f0;' : ''; ?> color: #0d1b2a;"><?php echo esc_html( $locker_name ); ?></td>
    </tr>
    <?php endif; ?>
    <?php if ( ! empty( $compartment_label ) ) : ?>
    <tr>
        <td style="background: #f0f4f8; font-weight: 600; color: #5a6a7d;">Compartment</td>
        <td style="color: #0d1b2a;"><?php echo esc_html( $compartment_label ); ?></td>
    </tr>
    <?php endif; ?>
</table>
<?php endif; ?>

<?php if ( ! empty( $pickup_url ) ) : ?>
<p style="text-align: center; margin: 28px 0;">
    <a href="<?php echo esc_url( $pickup_url ); ?>" style="display: inline-block; background-color: #00A8E8; color: #ffffff; font-size: 16px; font-weight: 700; padding: 14px 36px; border-radius: 8px; text-decoration: none;">Unlock &amp; Pick Up</a>
</p>
<?php endif; ?>

<p style="color: #5a6a7d; font-size: 13px;">You can also view your pickup details anytime from your <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">order page</a>.</p>

<?php
do_action( 'woocommerce_email_footer', $email );

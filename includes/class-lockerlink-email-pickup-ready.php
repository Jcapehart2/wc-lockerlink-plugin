<?php
/**
 * LockerLink Pickup Ready Email.
 *
 * Sent to the customer when their order is loaded into a locker
 * and ready for pickup.
 */

defined( 'ABSPATH' ) || exit;

class LockerLink_Email_Pickup_Ready extends WC_Email {

    /**
     * Locker details passed to the template.
     */
    public $locker_name;
    public $compartment_label;
    public $pickup_url;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'lockerlink_pickup_ready';
        $this->customer_email = true;
        $this->title          = __( 'LockerLink Pickup Ready', 'lockerlink' );
        $this->description    = __( 'Sent to the customer when their order is loaded into a locker and ready for pickup.', 'lockerlink' );
        $this->heading        = __( 'Your order is ready for pickup!', 'lockerlink' );
        $this->subject        = __( 'Your order is ready for locker pickup', 'lockerlink' );
        $this->template_html  = 'emails/lockerlink-pickup-ready.php';
        $this->template_plain = 'emails/plain/lockerlink-pickup-ready.php';
        $this->template_base  = LOCKERLINK_PLUGIN_DIR . 'templates/';

        // Trigger on custom action.
        add_action( 'lockerlink_order_pickup_ready', array( $this, 'trigger' ), 10, 4 );

        parent::__construct();
    }

    /**
     * Trigger the email.
     *
     * @param int    $order_id         The order ID.
     * @param string $locker_name      The locker name.
     * @param string $compartment_label The compartment label.
     * @param string $pickup_url       The pickup URL.
     */
    public function trigger( $order_id, $locker_name = '', $compartment_label = '', $pickup_url = '' ) {
        $this->setup_locale();

        if ( $order_id ) {
            $this->object            = wc_get_order( $order_id );
            $this->recipient         = $this->object->get_billing_email();
            $this->locker_name       = $locker_name;
            $this->compartment_label = $compartment_label;
            $this->pickup_url        = $pickup_url;

            $this->placeholders['{order_number}'] = $this->object->get_order_number();
            $this->placeholders['{locker_name}']  = $locker_name;
        }

        if ( $this->is_enabled() && $this->get_recipient() ) {
            $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        }

        $this->restore_locale();
    }

    /**
     * Get the HTML content.
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            array(
                'order'             => $this->object,
                'email_heading'     => $this->get_heading(),
                'locker_name'       => $this->locker_name,
                'compartment_label' => $this->compartment_label,
                'pickup_url'        => $this->pickup_url,
                'sent_to_admin'     => false,
                'plain_text'        => false,
                'email'             => $this,
            ),
            '',
            $this->template_base
        );
    }

    /**
     * Get the plain text content.
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'order'             => $this->object,
                'email_heading'     => $this->get_heading(),
                'locker_name'       => $this->locker_name,
                'compartment_label' => $this->compartment_label,
                'pickup_url'        => $this->pickup_url,
                'sent_to_admin'     => false,
                'plain_text'        => true,
                'email'             => $this,
            ),
            '',
            $this->template_base
        );
    }

    /**
     * Default subject.
     */
    public function get_default_subject() {
        return __( 'Your order #{order_number} is ready for locker pickup', 'lockerlink' );
    }

    /**
     * Default heading.
     */
    public function get_default_heading() {
        return __( 'Your order is ready for pickup!', 'lockerlink' );
    }
}

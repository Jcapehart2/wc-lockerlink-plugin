<?php
/**
 * LockerLink Shipping Method — Locker Pickup.
 *
 * Uses method_id "lockerlink" to avoid conflicts with WooCommerce's built-in Local Pickup.
 */

defined( 'ABSPATH' ) || exit;

function lockerlink_init_shipping_method() {
    if ( ! class_exists( 'WC_Shipping_Method' ) ) {
        return;
    }

    class LockerLink_Shipping extends WC_Shipping_Method {

        /**
         * Constructor.
         */
        public function __construct( $instance_id = 0 ) {
            $this->id                 = 'lockerlink';
            $this->instance_id        = absint( $instance_id );
            $this->method_title       = __( 'Locker Pickup (LockerLink)', 'lockerlink' );
            $this->method_description = __( 'Allow customers to pick up orders from a LockerLink smart locker.', 'lockerlink' );
            $this->supports           = array(
                'shipping-zones',
                'instance-settings',
                'instance-settings-modal',
            );

            $this->init();
        }

        /**
         * Initialize settings.
         */
        public function init() {
            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option( 'title', __( 'Locker Pickup', 'lockerlink' ) );
            $this->cost  = $this->get_option( 'cost', 0 );

            add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        /**
         * Define settings fields for shipping zone configuration.
         */
        public function init_form_fields() {
            $this->instance_form_fields = array(
                'title' => array(
                    'title'       => __( 'Title', 'lockerlink' ),
                    'type'        => 'text',
                    'description' => __( 'Shipping method title shown to customers at checkout.', 'lockerlink' ),
                    'default'     => __( 'Locker Pickup', 'lockerlink' ),
                    'desc_tip'    => true,
                ),
                'cost' => array(
                    'title'       => __( 'Cost', 'lockerlink' ),
                    'type'        => 'price',
                    'description' => __( 'Cost for locker pickup. Leave as 0 for free.', 'lockerlink' ),
                    'default'     => 0,
                    'desc_tip'    => true,
                    'placeholder' => '0.00',
                ),
            );
        }

        /**
         * Calculate shipping cost.
         */
        public function calculate_shipping( $package = array() ) {
            $this->add_rate( array(
                'id'    => $this->get_rate_id(),
                'label' => $this->title,
                'cost'  => $this->cost,
            ) );
        }
    }
}
add_action( 'woocommerce_shipping_init', 'lockerlink_init_shipping_method' );

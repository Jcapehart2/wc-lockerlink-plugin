<?php
/**
 * LockerLink Order Meta Display.
 *
 * Adds a meta box on the admin order edit page and displays pickup info
 * on the customer-facing order details page.
 */

defined( 'ABSPATH' ) || exit;

class LockerLink_Order_Meta {

    /**
     * Initialize hooks.
     */
    public static function init() {
        // Admin meta box.
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );

        // Customer-facing order details.
        add_action( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'display_customer_pickup_info' ) );
    }

    /**
     * Add the LockerLink meta box to order edit screens.
     */
    public static function add_meta_box() {
        $screen_ids = array( 'shop_order', 'woocommerce_page_wc-orders' );

        foreach ( $screen_ids as $screen_id ) {
            add_meta_box(
                'lockerlink-pickup-info',
                'LockerLink Pickup',
                array( __CLASS__, 'render_meta_box' ),
                $screen_id,
                'side',
                'high'
            );
        }
    }

    /**
     * Render the admin meta box content.
     *
     * @param WP_Post|WC_Order $post_or_order The post or order object.
     */
    public static function render_meta_box( $post_or_order ) {
        $order = ( $post_or_order instanceof WC_Order ) ? $post_or_order : wc_get_order( $post_or_order->ID );

        if ( ! $order ) {
            echo '<p>Order not found.</p>';
            return;
        }

        $status      = $order->get_meta( '_lockerlink_status' );
        $locker      = $order->get_meta( '_lockerlink_locker' );
        $compartment = $order->get_meta( '_lockerlink_compartment' );
        $pickup_url  = $order->get_meta( '_lockerlink_pickup_url' );

        if ( empty( $status ) ) {
            // Check if this is a local_pickup order awaiting assignment.
            $is_locker_order = false;
            foreach ( $order->get_shipping_methods() as $method ) {
                if ( $method->get_method_id() === 'local_pickup' ) {
                    $is_locker_order = true;
                    break;
                }
            }

            if ( $is_locker_order ) {
                echo '<div class="lockerlink-meta-box">';
                echo '<div class="lockerlink-meta-status">';
                echo '<span class="lockerlink-badge lockerlink-badge-awaiting">Awaiting Assignment</span>';
                echo '</div>';
                echo '<p class="lockerlink-meta-note">This order uses locker pickup. Assignment details will appear here once processed in LockerLink.</p>';
                echo '</div>';
            } else {
                echo '<p class="lockerlink-meta-note">Not a locker pickup order.</p>';
            }
            return;
        }

        $badge_class = self::get_badge_class( $status );
        $status_label = self::get_status_label( $status );

        echo '<div class="lockerlink-meta-box">';

        // Status badge.
        echo '<div class="lockerlink-meta-status">';
        echo '<span class="lockerlink-badge ' . esc_attr( $badge_class ) . '">' . esc_html( $status_label ) . '</span>';
        echo '</div>';

        // Details table.
        if ( ! empty( $locker ) || ! empty( $compartment ) ) {
            echo '<table class="lockerlink-meta-table">';

            if ( ! empty( $locker ) ) {
                echo '<tr><td class="lockerlink-meta-label">Locker</td><td>' . esc_html( $locker ) . '</td></tr>';
            }
            if ( ! empty( $compartment ) ) {
                echo '<tr><td class="lockerlink-meta-label">Compartment</td><td>' . esc_html( $compartment ) . '</td></tr>';
            }

            echo '</table>';
        }

        // Pickup URL.
        if ( ! empty( $pickup_url ) ) {
            echo '<div class="lockerlink-meta-pickup-link">';
            echo '<a href="' . esc_url( $pickup_url ) . '" target="_blank" rel="noopener noreferrer" class="button lockerlink-btn-primary">Open Pickup Page</a>';
            echo '</div>';
        }

        echo '</div>';
    }

    /**
     * Display pickup info on the customer-facing order details page.
     *
     * @param WC_Order $order The order.
     */
    public static function display_customer_pickup_info( $order ) {
        $status      = $order->get_meta( '_lockerlink_status' );
        $locker      = $order->get_meta( '_lockerlink_locker' );
        $compartment = $order->get_meta( '_lockerlink_compartment' );
        $pickup_url  = $order->get_meta( '_lockerlink_pickup_url' );

        // Only show for active locker statuses.
        if ( ! in_array( $status, array( 'assigned', 'loaded', 'notified' ), true ) ) {
            return;
        }

        ?>
        <div class="lockerlink-customer-pickup">
            <h2>Locker Pickup Details</h2>
            <div class="lockerlink-pickup-card">
                <div class="lockerlink-pickup-details">
                    <?php if ( ! empty( $locker ) ) : ?>
                        <div class="lockerlink-pickup-row">
                            <span class="lockerlink-pickup-label">Locker</span>
                            <span class="lockerlink-pickup-value"><?php echo esc_html( $locker ); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ( ! empty( $compartment ) ) : ?>
                        <div class="lockerlink-pickup-row">
                            <span class="lockerlink-pickup-label">Compartment</span>
                            <span class="lockerlink-pickup-value"><?php echo esc_html( $compartment ); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ( ! empty( $pickup_url ) ) : ?>
                    <div class="lockerlink-pickup-action">
                        <a href="<?php echo esc_url( $pickup_url ); ?>" target="_blank" rel="noopener noreferrer" class="lockerlink-pickup-btn">
                            Unlock &amp; Pick Up
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Get the CSS class for a status badge.
     */
    private static function get_badge_class( $status ) {
        $map = array(
            'awaiting_assignment' => 'lockerlink-badge-awaiting',
            'assigned'            => 'lockerlink-badge-assigned',
            'loaded'              => 'lockerlink-badge-loaded',
            'notified'            => 'lockerlink-badge-notified',
            'unlocked'            => 'lockerlink-badge-unlocked',
            'picked_up'           => 'lockerlink-badge-picked-up',
            'cancelled'           => 'lockerlink-badge-cancelled',
        );
        return isset( $map[ $status ] ) ? $map[ $status ] : 'lockerlink-badge-default';
    }

    /**
     * Get a human-readable label for a status.
     */
    private static function get_status_label( $status ) {
        $map = array(
            'awaiting_assignment' => 'Awaiting Assignment',
            'assigned'            => 'Assigned',
            'loaded'              => 'Loaded',
            'notified'            => 'Notified',
            'unlocked'            => 'Unlocked',
            'picked_up'           => 'Picked Up',
            'cancelled'           => 'Cancelled',
        );
        return isset( $map[ $status ] ) ? $map[ $status ] : ucfirst( str_replace( '_', ' ', $status ) );
    }
}

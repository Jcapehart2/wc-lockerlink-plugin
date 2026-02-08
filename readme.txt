=== LockerLink for WooCommerce ===
Contributors: lockerlink
Tags: woocommerce, locker, pickup, shipping, smart locker
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
WC requires at least: 7.0
WC tested up to: 9.6
Stable tag: 1.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect your WooCommerce store to LockerLink smart locker pickup.

== Description ==

LockerLink for WooCommerce integrates your online store with the LockerLink smart locker platform. Customers can choose locker pickup at checkout, and store owners manage assignments through the LockerLink dashboard.

**Features:**

* Adds "Locker Pickup" as a shipping method at checkout
* Auto-registers webhooks with your LockerLink server (no manual setup)
* Filters orders so only locker pickups are sent to LockerLink
* Receives assignment updates and displays compartment details on the order
* Customer email notifications via WooCommerce's built-in order notes
* Admin meta box showing locker assignment status
* Customer-facing pickup button on the order details page

**How it works:**

1. Install the plugin and enter your LockerLink API credentials
2. Add "Locker Pickup" to a shipping zone
3. Customers select locker pickup at checkout
4. Orders are automatically sent to LockerLink
5. When you assign a locker compartment, the customer is notified with pickup details

== Installation ==

1. Upload the `woocommerce-lockerlink` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to WooCommerce > Settings > LockerLink
4. Enter your LockerLink Server URL, API ID, and Secret Key
5. Add "Locker Pickup" to a shipping zone under WooCommerce > Settings > Shipping

== Frequently Asked Questions ==

= Where do I get my API credentials? =

Log in to your LockerLink dashboard, go to Settings > Integrations, and generate a new API key pair.

= Do I need to set up webhooks manually? =

No. The plugin automatically creates and manages webhooks when you save your credentials.

= Does this work with WooCommerce HPOS? =

Yes. The plugin is fully compatible with WooCommerce High-Performance Order Storage.

== Changelog ==

= 1.0.0 =
* Initial release
* Locker Pickup shipping method
* Automatic webhook registration
* Assignment update callback endpoint
* Admin order meta box
* Customer-facing pickup details

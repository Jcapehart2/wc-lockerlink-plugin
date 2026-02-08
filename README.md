# LockerLink for WooCommerce

Connect your WooCommerce store to [LockerLink](https://joinlockerlink.com) smart locker pickup. Customers choose locker pickup at checkout, and you manage assignments from the LockerLink dashboard.

![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue?logo=wordpress)
![WooCommerce](https://img.shields.io/badge/WooCommerce-7.0%2B-7B2D8B?logo=woocommerce)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white)
![License](https://img.shields.io/badge/License-GPL--2.0--or--later-green)

## What it does

- **Locker Pickup shipping method** — Adds "Locker Pickup" as a shipping option at checkout via standard WooCommerce shipping zones
- **Auto-registers webhooks** — No manual webhook setup. The plugin creates and manages `order.created` and `order.updated` webhooks automatically
- **Smart filtering** — Only orders with locker pickup shipping are sent to LockerLink. Regular shipments are never forwarded
- **Assignment sync** — When you assign an order to a locker compartment in LockerLink, the plugin updates the WooCommerce order with pickup details
- **Customer notifications** — Pickup instructions (locker name, compartment, unlock link) are sent to customers via WooCommerce's built-in email system
- **Auto-updates** — Plugin checks for new releases from this repository and shows updates in the WordPress dashboard

## Installation

1. Download the [latest release](https://github.com/Jcapehart2/wc-lockerlink-plugin/releases/latest) zip file
2. In WordPress admin, go to **Plugins > Add New > Upload Plugin**
3. Upload the zip and activate
4. Go to **WooCommerce > Settings > LockerLink**
5. Enter your **Webhook URL** and **Secret Key** from LockerLink's Integrations page
6. Click **Test Connection** to verify
7. Add "Locker Pickup" to a shipping zone under **WooCommerce > Settings > Shipping**

## Configuration

### Settings (WooCommerce > Settings > LockerLink)

| Field | Description |
|-------|-------------|
| **Enable Plugin** | Toggle the integration on/off |
| **Webhook URL** | Full webhook URL from LockerLink's Integrations page |
| **Secret Key** | Secret key for HMAC webhook signing |

### Shipping Zones

Add the **Locker Pickup (LockerLink)** shipping method to any shipping zone. You can customize the title shown to customers and optionally set a cost (free by default).

## How it works

```
Customer checkout ──→ WooCommerce (Plugin) ──→ LockerLink Backend
                                             ←── Assignment callback
                      WooCommerce emails ←── Order meta + order note
```

1. Customer selects "Locker Pickup" at checkout
2. Plugin sends the order to LockerLink via webhook
3. Store owner assigns the order to a locker compartment in the LockerLink dashboard
4. LockerLink calls back with compartment details
5. Plugin updates the order and emails the customer pickup instructions
6. Customer uses the pickup link to unlock their compartment

## Order Lifecycle

| LockerLink Status | What happens in WooCommerce |
|---|---|
| Awaiting Assignment | Order placed, waiting for locker assignment |
| Assigned | Meta box shows locker + compartment details |
| Loaded | Customer receives email with pickup instructions |
| Picked Up | Order note added: "Order picked up from locker" |
| Cancelled | Order note added: "Locker assignment cancelled" |

## Requirements

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 7.4+
- HPOS compatible (High-Performance Order Storage)

## For developers

### Callback endpoint

The plugin registers a REST endpoint for receiving assignment updates from LockerLink:

```
POST /wp-json/lockerlink/v1/assignment-update
```

Authenticated via HMAC-SHA256 signature in the `x-lockerlink-signature` header.

### Order meta keys

| Key | Value |
|-----|-------|
| `_lockerlink_status` | Current LockerLink status |
| `_lockerlink_locker` | Locker name |
| `_lockerlink_compartment` | Compartment label |
| `_lockerlink_pickup_url` | Pickup/unlock URL |
| `_lockerlink_unlock_token` | Unlock token |

### Plugin structure

```
woocommerce-lockerlink/
├── woocommerce-lockerlink.php            # Main plugin file
├── includes/
│   ├── class-lockerlink-settings.php     # WooCommerce settings tab
│   ├── class-lockerlink-shipping.php     # Locker Pickup shipping method
│   ├── class-lockerlink-webhooks.php     # Webhook auto-registration + filtering
│   ├── class-lockerlink-callback.php     # REST endpoint for assignment updates
│   ├── class-lockerlink-order-meta.php   # Admin meta box + customer pickup display
│   └── class-lockerlink-updater.php      # GitHub release auto-updater
├── assets/
│   ├── lockerlink-admin.css              # Admin branding styles
│   ├── lockerlink-frontend.css           # Customer-facing pickup card
│   └── lockerlink-logo.png               # Logo
└── readme.txt                            # WordPress plugin readme
```

## License

GPL-2.0-or-later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).

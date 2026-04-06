# WooCommerce Local Pickup Enhancements

[![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue.svg)](https://wordpress.org/)  
[![PHP](https://img.shields.io/badge/PHP-8.1+-orange.svg)]()  
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.x+-green.svg)]()  
[![License](https://img.shields.io/badge/License-GPLv2+-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)  

Enhance your WooCommerce store’s **local pickup experience** with a Shopify-style checkout flow, confirmation modals, store info display, Google Maps integration, and email enhancements.

---

## Features

- **Pickup Confirmation Modal**  
  Customers must confirm store pickup before completing the checkout, reducing accidental pickup orders.

- **Shipping Section Replacement**  
  Selecting local pickup hides the shipping address fields and displays:
  - Store address  
  - Pickup hours  
  - Instructions  
  - Google Maps thumbnail linking to the store location

- **Customizable Pickup Information**  
  Admin can configure store address, hours, instructions, and Google Maps URL via a **settings panel** in WordPress admin.

- **Ready for Pickup Order Status**  
  Adds a custom WooCommerce order status `Ready for Pickup` and triggers a dedicated pickup email.

- **Email Integration**  
  Automatically includes pickup info in order emails instead of a tracking number.

- **AJAX-Compatible**  
  Fully compatible with WooCommerce checkout updates — the UI remains consistent after AJAX refreshes.

---

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`.  
2. Activate the plugin via WordPress admin.  
3. Navigate to **WooCommerce → Pickup Settings** to configure:
   - Store Address  
   - Pickup Hours  
   - Instructions  
   - Google Maps URL  

---

## Usage

1. On checkout, customers see a **delivery method toggle**:  
   - Ship to address  
   - Pick up in store  

2. Selecting **Pick up in store** triggers a **confirmation modal**.  

3. After confirming, the **shipping section is replaced** with pickup info and a clickable Google Maps thumbnail.  

4. Admin can update pickup info anytime via **Pickup Settings**.  

5. Mark orders as **Ready for Pickup** in WooCommerce to trigger the dedicated pickup email.

---

## Screenshots

**Delivery Method Toggle:**  
![Delivery Method Toggle](assets/delivery-toggle.png)  

**Pickup Confirmation Modal:**  
![Pickup Modal](assets/pickup-modal.png)  

**Pickup Details with Google Maps Thumbnail:**  
![Pickup Details](assets/pickup-details.png)  

**Ready for Pickup Email Example:**  
![Pickup Email](assets/pickup-email.png)  

---

## Changelog

### 3.0
- Fully integrated pickup info replacing shipping fields  
- Added Google Maps thumbnail for store location  
- Confirmation modal for pickup selection  
- Custom `Ready for Pickup` order status and email  
- Admin settings panel for store info  

---

## Frequently Asked Questions

**Q: Can I add multiple pickup locations?**  
A: This version supports only a single pickup location. Multi-location support may be added in a future version.

**Q: Do I need a Google Maps API key?**  
A: Yes, for the static map thumbnail. Add your API key in the `Google Maps API URL field` if required. The thumbnail will be ignored if you choose not to enter an API key.

**Q: Does this work with WPML/multi-language sites?**  
A: Currently, pickup info is static. WPML support may be added in a future release.

---

## Support

Open an issue in this GitHub repository for bug reports or feature requests.

---

## License

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html)
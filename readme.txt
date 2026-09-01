=== ShipQora – All-in-One Shipping Solution, Table Rate Shipping, Weight Based Shipping & Conditional Checkout Rules for WooCommerce ===
Contributors: ShipQora
Tags: shipping, woocommerce shipping, conditional shipping, shipping rates, table rate shipping
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

All-in-one WooCommerce shipping: hide methods, calculate dynamic costs, adjust rates, and control payment methods with rules.

== Description ==

**ShipQora** is an all-in-one shipping solution for WooCommerce. It gives store owners rule-based control over shipping methods, shipping costs, and payment method visibility — without writing custom code.

Instead of relying on WooCommerce's built-in shipping settings alone, **ShipQora** lets you build rules that combine selected shipping methods with configurable conditions. When a rule's conditions are met, **ShipQora** applies the feature you've configured: hiding a shipping method, adjusting its cost, or restricting payment options.

**ShipQora** offers flexible **Table Rates Shipping** rules to fit any store setup. Whether you need **Weight Based Shipping**, **Quantity Based Shipping**, or **Volume Based Shipping**, **ShipQora** gives you full control over how rates are calculated at checkout.

**With ShipQora you can:**

* Set up **Table Rates Shipping** using cart totals, item quantities, weight, or volume
* Create dynamic **Weight Based Shipping** rates with custom weight brackets
* Charge **Quantity Based Shipping** fees based on item count
* Calculate **Volume Based Shipping** costs for bulky or oversized products
* Hide selected shipping methods when specific conditions are met
* Ensure priority shipping methods take precedence by hiding other methods when available
* Apply product-specific shipping costs to selected shipping methods
* Increase, decrease, or override shipping costs based on custom rules
* Hide payment methods (like Cash on Delivery) according to cart conditions

Each rule follows the same simple structure: select the shipping methods the rule applies to, set the conditions that should trigger it, and choose the feature you want applied. This makes it possible to build precise, store-specific shipping logic without editing theme or plugin code.

**ShipQora** is built for WooCommerce store owners who need more control over checkout shipping and payment options than WooCommerce's default settings provide — whether that's hiding methods for certain order types, pricing shipping dynamically based on cart contents, or limiting payment options under specific conditions.


## 🌟 Key Features
**1. Advanced Conditional Table Rate Shipping**
Build precise shipping rate matrices that adjust based on what's in the cart:

* **Weight-Based Shipping:** Set custom weight brackets or per-unit weight fees (e.g., $5 for 0–2 kg, $10 for 2–5 kg).
* **Quantity-Based Shipping:** Charge tiered delivery fees based on total item count.
* **Volume-Based Shipping:** Calculate shipping costs based on total package volume for bulky or oversized products.
* **Cart Subtotal Rates:** Adjust rates automatically depending on total order value.

**2. Hide Unwanted Shipping & Payment Methods**

* **Hide Specific Shipping Methods:** Automatically hide shipping options when specific conditions are met (e.g., hide Flat Rate when Free Shipping is active).
* **Priority Shipping Override:** Display only your preferred shipping method when available, automatically hiding all secondary options.
* **Conditional Payment Hiding:** Limit or hide payment methods like **Cash on Delivery (COD)** based on cart weight, total, or specific items.

**3. Conditional Dynamic Cost Adjustments & Fee Overrides**

* **Product-Specific Costs:** Add extra handling or shipping fees when heavy or delicate products are added to the cart.
* **Fee Rules:** Increase, discount, or override default shipping prices dynamically at checkout.

## Features
## Hide Selected Shipping Methods

Hide specific shipping methods when your configured conditions are met. This gives you precise control over which delivery options customers see at checkout, so you're not stuck showing shipping methods that don't make sense for a given order.

## Hide Other Shipping Methods

Select the shipping method(s) that should take precedence. When your selected shipping method(s) are available on the checkout page, **ShipQora** hides the other available shipping methods.

This is useful when you want a specific shipping method to be the customer's only visible option whenever it's available, without manually disabling every alternative method.

**Example use case:** When a premium or preferred shipping method is available for an order, hide the remaining shipping options so customers only see the one you want to highlight.

## Cart-Based Shipping Cost

Calculate shipping costs dynamically based on values from the customer's cart. **ShipQora** currently supports calculating shipping costs using:

* Cart total
* Item count
* Weight
* Volume

This lets you move beyond flat-rate shipping and price shipping according to what's actually in the cart.

**Example use cases:**

* Calculate shipping cost based on the total weight of items in the cart
* Calculate shipping cost based on the number of items purchased
* Calculate shipping cost based on the cart's total volume
* Calculate shipping cost based on the cart subtotal

## Product-Based Shipping Cost

Apply product-specific shipping costs to selected shipping methods when your configured conditions are met. This is useful for stores where certain products require different shipping handling than the rest of the catalog.

**Example use case:** Apply a specific shipping cost to a shipping method when a particular product (or set of products) is present in the cart.

## Shipping Cost Adjustment

Increase, decrease, or override the shipping cost of selected shipping methods based on your configured rules. This gives you fine-grained control over final shipping prices beyond what a shipping method's default rate provides.

**Example use cases:**

* Increase shipping cost under certain conditions
* Decrease shipping cost under certain conditions
* Override a shipping method's cost entirely under certain conditions

## Hide Payment Methods

Control the visibility of WooCommerce payment methods at checkout using configurable rules. Hide specific payment methods when your defined conditions are met, giving you the ability to restrict how customers can pay under certain circumstances.


### Table Rates Shipping
Create flexible, rule-based table rate shipping matrixes. Combine cart subtotals, item quantities, total weight, product volume, and customer locations to set accurate shipping charges for every order type.

**Weight Based Shipping**
Set up weight-based shipping rules using custom weight brackets or per-unit weight calculations. Automatically apply different shipping costs when orders reach specific weight thresholds.

**Quantity Based Shipping**
Configure quantity-based shipping rates to charge fees based on the exact number of items in the cart or set tiered pricing as cart volume increases.

**Volume Based Shipping**
Calculate shipping fees based on total package dimensions and volume, making it easy to cover shipping costs for large or bulky items.


== Frequently Asked Questions ==

= What is ShipQora? =

ShipQora is an all-in-one shipping plugin for WooCommerce that lets you control shipping methods, calculate dynamic shipping costs, adjust shipping rates, and control payment method visibility using configurable rules and conditions.

= Does ShipQora require WooCommerce? =

Yes. ShipQora is built as an extension for WooCommerce and requires WooCommerce to be installed and active.

= Can ShipQora hide specific shipping methods? =

Yes. You can select specific shipping methods and hide them when your configured rule conditions are met.

= Can ShipQora hide other shipping methods when a selected shipping method is available? =

Yes. You can select priority shipping method(s), and when they're available at checkout, ShipQora hides the other available shipping methods.

= Can ShipQora calculate shipping costs based on cart total? =

Yes. Cart-Based Shipping Cost supports calculating shipping cost based on the cart total.

= Can ShipQora calculate shipping costs based on item count? =

Yes. Cart-Based Shipping Cost supports calculating shipping cost based on the number of items in the cart.

= Can ShipQora calculate shipping costs based on weight? =

Yes. Cart-Based Shipping Cost supports calculating shipping cost based on cart weight.

= Can ShipQora calculate shipping costs based on volume? =

Yes. Cart-Based Shipping Cost supports calculating shipping cost based on cart volume.

= Can ShipQora apply product-based shipping costs? =

Yes. Product-Based Shipping Cost lets you apply specific shipping costs to selected shipping methods based on the products in the cart and your configured conditions.

= Can ShipQora adjust shipping costs? =

Yes. Shipping Cost Adjustment lets you increase, decrease, or override the shipping cost of selected shipping methods based on your configured rules.

= Can ShipQora hide payment methods? =

Yes. You can hide specific WooCommerce payment methods when your configured conditions are met.

= Can ShipQora set up Table Rates Shipping? =
Yes. Both Cart-Based and Product-Based features support table rate rules using weight, quantity, volume, and order totals.

= Does ShipQora support Weight Based, Quantity Based, or Volume Based Shipping? =
Yes. You can calculate dynamic shipping costs based on the total weight, total quantity of items, or total volume of products in the cart.


== Changelog ==

= 1.0.0 =
* Initial release of ShipQora.
=== ShipQora – All-in-One Shipping Solution for WooCommerce ===
Contributors: ShipQora
Tags: shipping, woocommerce shipping, conditional shipping, shipping rates, shipping rules
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

All-in-one WooCommerce shipping: hide methods, calculate dynamic costs, adjust rates, and control payment methods with rules.

== Description ==

ShipQora is an all-in-one shipping solution for WooCommerce. It gives store owners rule-based control over shipping methods, shipping costs, and payment method visibility — without writing custom code.

Instead of relying on WooCommerce's built-in shipping settings alone, ShipQora lets you build rules that combine selected shipping methods with configurable conditions. When a rule's conditions are met, ShipQora applies the feature you've configured: hiding a shipping method, adjusting its cost, or restricting payment options.

With ShipQora you can:

* Hide selected shipping methods when specific conditions are met
* Ensure priority shipping methods take precedence by hiding other methods when they're available
* Calculate shipping costs dynamically based on cart total, item count, weight, or volume
* Apply product-specific shipping costs to selected shipping methods
* Increase, decrease, or override shipping costs based on your own rules
* Hide payment methods according to configured conditions

Each rule follows the same simple structure: select the shipping methods the rule applies to, set the conditions that should trigger it, and choose the feature you want applied. This makes it possible to build precise, store-specific shipping logic without editing theme or plugin code.

ShipQora is built for WooCommerce store owners who need more control over checkout shipping and payment options than WooCommerce's default settings provide — whether that's hiding methods for certain order types, pricing shipping dynamically based on cart contents, or limiting payment options under specific conditions.

== Features ==

= Hide Selected Shipping Methods =

Hide specific shipping methods when your configured conditions are met. This gives you precise control over which delivery options customers see at checkout, so you're not stuck showing shipping methods that don't make sense for a given order.

= Hide Other Shipping Methods =

Select the shipping method(s) that should take precedence. When your selected shipping method(s) are available on the checkout page, ShipQora hides the other available shipping methods.

This is useful when you want a specific shipping method to be the customer's only visible option whenever it's available, without manually disabling every alternative method.

**Example use case:** When a premium or preferred shipping method is available for an order, hide the remaining shipping options so customers only see the one you want to highlight.

= Cart-Based Shipping Cost =

Calculate shipping costs dynamically based on values from the customer's cart. ShipQora currently supports calculating shipping costs using:

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

= Product-Based Shipping Cost =

Apply product-specific shipping costs to selected shipping methods when your configured conditions are met. This is useful for stores where certain products require different shipping handling than the rest of the catalog.

**Example use case:** Apply a specific shipping cost to a shipping method when a particular product (or set of products) is present in the cart.

= Shipping Cost Adjustment =

Increase, decrease, or override the shipping cost of selected shipping methods based on your configured rules. This gives you fine-grained control over final shipping prices beyond what a shipping method's default rate provides.

**Example use cases:**
* Increase shipping cost under certain conditions
* Decrease shipping cost under certain conditions
* Override a shipping method's cost entirely under certain conditions

= Hide Payment Methods =

Control the visibility of WooCommerce payment methods at checkout using configurable rules. Hide specific payment methods when your defined conditions are met, giving you the ability to restrict how customers can pay under certain circumstances.

== How It Works ==

ShipQora is built around a simple rule structure:

**Rule → Selected Methods → Conditions → Feature/Action**

1. Create a rule.
2. Select the shipping method(s) the rule should apply to.
3. Choose the feature you want to apply (hide methods, adjust cost, hide payment methods, etc.).
4. Configure the conditions that determine when the rule should run.
5. Save the rule. ShipQora applies it automatically at checkout whenever the conditions are met.

This structure lets you combine multiple features and conditions to build shipping logic tailored to your store, without custom development.

== Installation ==

1. Upload the ShipQora plugin files, or install it directly through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Make sure WooCommerce is installed and active, as ShipQora requires WooCommerce to function.
4. Open ShipQora from your WordPress admin area.
5. Create a new rule.
6. Select the shipping method(s) the rule should apply to.
7. Choose the feature you want the rule to perform.
8. Configure the conditions for the rule.
9. Save and test the rule at checkout to confirm it behaves as expected.

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

== Screenshots ==

1. ShipQora rule editor for configuring shipping rules.
2. Select shipping methods and configure rule features.
3. Configure conditional shipping rules.
4. Configure cart-based shipping costs.
5. Configure product-based shipping costs.
6. Configure shipping cost adjustments.
7. Configure payment method visibility rules.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release of ShipQora.
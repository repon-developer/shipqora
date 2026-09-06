=== ShipQora – All-in-One Shipping Solution - Advanced Conditional Shipping for WooCommerce ===
Contributors: ShipQora
Tags: shipping, woocommerce shipping, conditional shipping, shipping rates, table rate shipping
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced conditional shipping for WooCommerce. Easily hide shipping methods, adjust dynamic rates, set table rates, and restrict payments.

== Description ==

**ShipQora** is an all-in-one plugin built for **advanced conditional shipping for WooCommerce**. It provides e-commerce store owners with complete, automated control over checkout shipping methods, dynamic rates, custom handling fees, and payment gateway visibility — completely code-free.

Managing complex shipping scenarios in WooCommerce often requires custom PHP snippets or multiple single-purpose plugins. **ShipQora** eliminates that friction by combining flexible conditional logic with dynamic rule triggers. Seamlessly create rules based on cart subtotals, package weight, item quantity, volumetric dimensions, destination locations, or specific product categories.

When a customer reaches checkout, **ShipQora** instantly evaluates their cart and applies your rules: hiding non-applicable delivery options, adjusting shipping costs on the fly, or restricting specific payment gateways (such as Cash on Delivery) to protect your profit margins.

---

## Why Choose ShipQora for WooCommerce Shipping?

Standard WooCommerce shipping options can be restrictive for growing online stores. **ShipQora** bridges the gap between basic flat rates and complex fulfillment requirements:

- **Boost Conversion Rates:** Avoid confusing buyers at checkout by automatically hiding irrelevant, expensive, or duplicate shipping choices.
- **Protect Profit Margins:** Pass accurate delivery costs to customers by using precise weight, volume, and cart value conditions.
- **Prevent Unwanted Payment Methods:** Automatically restrict options like Cash on Delivery (COD) for heavy items or high-value orders to reduce order cancellations and fraud risk.
- **Zero Coding Required:** Set up complex logical rules in minutes through a clean, beginner-friendly interface.

---

## Key Features at a Glance

- **Advanced Conditional Shipping & Table Rates:** Build custom shipping rate matrices using **advanced conditional shipping for WooCommerce** that calculate dynamically at checkout.
- **Weight-Based Shipping Rules:** Charge tiered delivery fees or per-unit weight surcharges based on total order weight (e.g., $5 for 0–2 kg, $10 for 2–5 kg).
- **Quantity-Based Shipping:** Set custom rate tiers based on the total number of items in the customer's cart.
- **Volume & Dimension-Based Shipping:** Calculate precise fees based on overall package volume for bulky, oversized, or dimensional products.
- **Cart Subtotal Rules:** Offer dynamic rate discounts, extra fees, or special rules based on total order spend.
- **Hide Specific Shipping Methods:** Conceal specific delivery options when criteria are met (e.g., hide standard Flat Rate when Free Shipping criteria are satisfied).
- **Priority Shipping Override:** Highlight preferred shipping choices and automatically hide all secondary choices when priority methods become available.
- **Conditional Payment Gateway Hiding:** Automatically hide or show payment methods like Cash on Delivery (COD), Stripe, or PayPal based on cart contents or total weight.
- **Product-Specific Handling Fees:** Add specialized surcharges when hazardous, fragile, heavy, or cold-chain products are added to the cart.
- **Dynamic Fee Rules:** Increase, discount, or override standard WooCommerce shipping prices dynamically during checkout.

---

## Detailed Feature Breakdown

### 1. Smart Shipping Method Visibility
Take granular control over which delivery methods appear in the cart and checkout. 

- **Hide Unmatched Options:** Automatically hide express delivery for remote postal codes or restrict heavy freight options for low-weight items.
- **Priority Method Overrides:** Define primary shipping choices that take precedence. When a priority method is triggered, secondary choices are instantly suppressed to streamline customer decision-making.

### 2. Advanced Dynamic Cost Adjustments
Modify WooCommerce shipping rates dynamically without changing default shipping zone settings:

- **Increase Cost:** Automatically attach extra handling charges, oversized item fees, or hazardous materials surcharges.
- **Decrease Cost:** Reward high-value customers with dynamic shipping discounts when order values hit designated thresholds.
- **Override Cost:** Replace existing zone rates with custom flat fees or rule-based rates based on precise cart logic.

### 3. Flexible Table Rate Calculations
Implement custom matrix pricing based on customer shopping behavior:

- **Tiered Weight Rules:** Charge flat or incremental fees for specific weight ranges.
- **Item Count Tiers:** Charge tiered rates based on total volume (e.g., $2/item for 1–5 items, $1/item for 6+ items).
- **Package Dimensions:** Use total volumetric calculations to ensure shipping charges accurately cover actual carrier fees for large items.

### 4. Conditional Payment Gateway Control
Align payment gateway availability with order risks and delivery methods:

- Restrict **Cash on Delivery (COD)** for orders exceeding maximum total weight or spend thresholds.
- Limit payment options when specific high-risk or digital products are present in the order.

---

## Real-World Use Cases

- **Scenario 1: Hide Standard Shipping When Free Shipping Appears**
  Prevent customer confusion by hiding standard flat-rate shipping options as soon as the cart order total qualifies for free shipping.
- **Scenario 2: Heavy & Bulky Product Surcharges**
  Automatically add a $15 handling fee to the checkout rate whenever a product from the "Furniture" or "Heavy Equipment" category is in the cart.
- **Scenario 3: Restricting Cash on Delivery (COD)**
  Disable COD for orders weighing over 10 kg or for orders with a cart total over $500 to minimize return risk and shipping fees.
- **Scenario 4: Tiered Volume Discounts**
  Offer reduced shipping rates as cart item counts grow, driving higher average order values across your shop.

---

## How Advanced Conditional Shipping Works in ShipQora

Creating a custom rule takes just three straightforward steps:

1. **Select Shipping Methods:** Choose which default or custom WooCommerce shipping methods your rule should target.
2. **Define Trigger Conditions:** Set up one or multiple logical conditions using attributes like cart weight, item quantity, total subtotal, package volume, or specific line items.
3. **Specify the Action:** Tell ShipQora what action to take when conditions match — hide the shipping method, adjust the shipping rate, or hide specific payment options.

---


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
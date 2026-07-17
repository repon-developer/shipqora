<?php

namespace ShipFlex;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Order Discount class
 */
final class Order_Discount_Main {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter('shipflex/rewards_configuration', array($this, 'rewards_configuration'));

		add_filter(Settings_Fields::get_hook_name('reward_rule_form', 'order-discount'), array($this, 'rule_settings_fields'));
		add_filter(Settings_Fields::get_hook_name('reward_line_order-discount'), array($this, 'reward_line_settings_fields'));

		add_action('woocommerce_cart_calculate_fees', array($this, 'apply_order_discount'));
		add_action('woocommerce_checkout_create_order', array($this, 'before_create_order'));
		add_action('woocommerce_store_api_checkout_update_order_from_request', array($this, 'before_create_order'));
	}

	/**
	 * Add reward configuration of order discount to the reward types list
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function rewards_configuration($rewards_configuration) {
		$rewards_configuration['order-discount'] = array(
			'base_model' => 'order_discount',
			'name' => esc_html__('Order Discount', 'shipflex'),
			'line_item_title' => esc_html__('Order Discount Line', 'shipflex'),
			'description' => esc_html__('Gives a fixed or percentage discount to the customer when the reward conditions are met.', 'shipflex'),
			'badge_default_values' => array(
				'badge_icon' => '🏷️',
				'badge_offer_text' => esc_html__('Order Discount', 'shipflex'),
				'badge_primary_text' => esc_html__('Spend [cart_reward_minimum_required]', 'shipflex'),
				'badge_additional_text' => esc_html__('Get [cart_reward_discount] Off', 'shipflex'),
			),

			'predefined_badge_templates' => array(
				'order-discount-product-listing' => array(
					'model_key' => 'order_discount.product_listing_badge',
					'label' => __('Order Discount - Product Listing Badge', 'shipflex'),
					'allow_badge_templates' => array('general-product-listing', 'general-single-product')
				),

				'order-discount-single-product' => array(
					'model_key' => 'order_discount.single_product_badge',
					'label' => __('Order Discount - Single Product Badge', 'shipflex'),
					'allow_badge_templates' => array('general-product-listing', 'general-single-product', 'order-discount-product-listing')
				)
			),
		);

		return $rewards_configuration;
	}

	/**
	 * Settings fields of discount
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function rule_settings_fields($setting_fields) {
		$setting_fields = array_merge($setting_fields, array(
			'order_discount_line_item' => array(
				'priority' => 10,
				'model_key' => 'order_discount_line_item',
				'callback' => array($this, 'order_discount_line_item'),
			),

			'add_new_line_button' => array(
				'priority' => 10000,
				'callback' => array($this, 'add_new_line_button')
			),
		));

		return $setting_fields;
	}

	/**
	 * Settings fields of this reward line item
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function reward_line_settings_fields($setting_fields) {
		$common_fields = Rewards::get_instance()->reward_component_fields();

		$common_fields['product_settings'] = array_merge($common_fields['product_settings'], array(
			'sidebar' => 'this.$root.set_sidebar("order-discount-product-settings")',
			'option_note' => esc_html__('These settings allow you to define how the discount is applied based on the selected conditions. Leave this field empty or set it to 0 to apply the discount without a minimum requirement.', 'shipflex'),
		));

		$common_fields['incentive_calculation'] = array(
			'priority' => 15,
			'callback' => array($this, 'incentive_calculation_field'),
			'label' => esc_html__('Discount Calculation', 'shipflex'),
			'sidebar' => 'this.$root.set_sidebar("order-discount-calculation")',
			'label_note' => esc_html__('Configure how this reward is calculated in the above "Product Settings".', 'shipflex'),
			'option_note' => esc_html__('These settings control the discount type, value, and how the discount is applied during checkout.', 'shipflex'),
			'option_note_callback' => array($this, 'product_based_lite_notice'),
			'related_models' => array(
				'incentive_amount' => '',
				'incentive_amount_type' => 'fixed',
				'increase_by_multiple_tier' => false
			)
		);

		$common_fields['badge_offer_text'] = array_merge($common_fields['badge_offer_text'], array(
			'conditions' => array('true !== hide_badge'),
			'option_note' => sprintf(
				/* translators: %1$s: cart_reward_minimum_required, %2$s: cart_reward_discount shortcode */
				esc_html__('Use the shortcode %1$s to display the minimum requirement for this offer line, and %2$s to display the fixed discount or percentage.', 'shipflex'),
				'<code>[cart_reward_minimum_required]</code>',
				'<code>[cart_reward_discount]</code>',
			)
		));

		$common_fields['badge_primary_text'] = array_merge($common_fields['badge_primary_text'], array(
			'conditions' => array('true !== hide_badge'),
			'label_note' => esc_html__('Customize the text displayed on product listings and single product pages to promote order discount.', 'shipflex'),
			'option_note' => sprintf(
				/* translators: %1$s: cart_reward_minimum_required, %2$s: cart_reward_discount shortcode */
				esc_html__('Use the shortcode %1$s to display the minimum requirement for this offer line, and %2$s to display the fixed discount or percentage.', 'shipflex'),
				'<code>[cart_reward_minimum_required]</code>',
				'<code>[cart_reward_discount]</code>',
			)
		));

		$common_fields['badge_additional_text'] = array_merge($common_fields['badge_additional_text'], array(
			'conditions' => array('true !== hide_badge'),
			'label_note' => esc_html__('Customize the text displayed on product listings and single product pages to promote order discount.', 'shipflex'),
			'option_note' => sprintf(
				/* translators: %1$s: cart_reward_minimum_required, %2$s: cart_reward_discount shortcode */
				esc_html__('Use the shortcode %1$s to display the minimum requirement for this offer line, and %2$s to display the fixed discount or percentage.', 'shipflex'),
				'<code>[cart_reward_minimum_required]</code>',
				'<code>[cart_reward_discount]</code>',
			),
		));

		$rewards_configuration = Reward::get_rewards_configuration();
		if (isset($rewards_configuration['order-discount']['badge_default_values']) && is_array($rewards_configuration['order-discount']['badge_default_values'])) {
			foreach ($rewards_configuration['order-discount']['badge_default_values'] as $model_key => $default_value) {
				$common_fields[$model_key]['default_value'] = $default_value;
			}
		}

		return array_merge($setting_fields, $common_fields);
	}

	/**
	 * First discount line item
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function order_discount_line_item(Form_Control $form_control) { ?>
		<tbody>
			<template
				is="vue:reward-line-order-discount"
				:line-data="order_discount_line_item"
				@update="(value) => order_discount_line_item = value"></template>
		</tbody>
	<?php
	}

	/**
	 * Add add new discount line button at footer
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_new_line_button() {
		$settings = array(
			'center' => true,
			'utm_source' => 'add+order+discount+tier',
			'title' => '🚀 Want to offer more order discount tiers like the one above?',
			'description' => 'Upgrade to the <strong>Pro version</strong> to add multiple order discount tiers within a single reward rule and create more flexible reward campaigns for your customers.',
		); ?>

		<tfoot>
			<tr class="pro-notice-row">
				<td colspan="2">
					<?php Global_Options::output_lite_notice($settings) ?>
				</td>
			</tr>
		</tfoot>
	<?php
	}

	/**
	 * Settings for incentive calculation
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function incentive_calculation_field(Form_Control $form_control) {
		$form_control->output_before_input_options();

		$calculation_types = apply_filters('shipflex/reward_line/order-discount/calculation_types', array(
			'percentage' => esc_html__('Percentage', 'shipflex'),
			'fixed' => esc_html__('Fixed amount', 'shipflex'),
		)); ?>

		<div class="field-row">
			<select v-model="incentive_amount_type">
				<?php
				foreach ($calculation_types as $type => $label) {
					printf('<option value="%s">%s</option>', esc_attr($type), esc_html($label));
				} ?>
			</select>

			<input type="number" v-model="incentive_amount" v-if="'product_based' !== incentive_amount_type" placeholder="0.00" step="0.01">
			<span v-if="'percentage' == incentive_amount_type" style="align-self: center; margin-inline-end: 7px">%</span>

			<label style="margin-inline-start: 7px;" v-if="'fixed' == incentive_amount_type">
				<input type="checkbox" v-model="increase_by_multiple_tier">
				<?php esc_html_e('Multiply by multiple tiers', 'shipflex') ?>
				<a @click.prevent.stop="this.$root.current_sidebar = 'cart-product-settings'" class="sidebar-icon dashicons dashicons-editor-help" href="/"></a>
			</label>
		</div>
	<?php
		$form_control->output_after_input_options();
	}

	/**
	 * Show product based discount available notice
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function product_based_lite_notice() {
		Global_Options::output_lite_notice(array(
			'utm_source' => 'product+based+discount',
			'title' => '✨ Need Product-Based Discount Calculations?',
			'description' => 'Unlock <strong>Product-Based Discount</strong> in the <strong>Pro version</strong> to calculate rewards using selected products instead of the entire cart.',
		));
	}

	/**
	 * Apply order discount
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function apply_order_discount($cart) {
		if (Settings::get_instance()->is_reward_disabled('order-discount')) {
			return;
		}

		if (is_admin() && !defined('DOING_AJAX')) {
			return;
		}

		$discount = Session_Reward::get_instance()->get_reward('order-discount');
		if ($discount <= 0) {
			return;
		}

		if (WC()->cart->get_subtotal() < $discount) {
			return;
		}

		$discount_text = Settings::get_instance()->get_text('order_discount.texts.order_summary_discount_label');
		$cart->add_fee($discount_text, -$discount, false);
	}

	/**
	 * Add meta data at this order
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function before_create_order($order) {
		if (Settings::get_instance()->is_reward_disabled('discount')) {
			return;
		}

		$discount_lines = Session_Reward::get_instance()->get_reward('discount');
		if (false === $discount_lines) {
			return;
		}

		$discount = array_sum(reset($discount_lines));
		if ($discount <= 0) {
			return;
		}

		if (WC()->cart->get_subtotal() < $discount) {
			return;
		}

		$order->update_meta_data('shipflex_discount', $discount);
	}
}

new Order_Discount_Main();

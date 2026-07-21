<?php

namespace ShipFlex\Feature;

use ShipFlex\Utils;
use ShipFlex\Feature;
use ShipFlex\Form_Control;
use ShipFlex\Condition\Main;
use ShipFlex\Settings_Fields;

if (!defined('ABSPATH')) {
	exit;
}

final class Product_Based_Shipping extends Feature {

	/**
	 * Hold the feature id of this feature
	 * 
	 * @var string
	 */
	protected $feature_id = 'product-based-shipping';

	/**
	 * Constructor.
	 */
	public function __construct($data = null) {
		if (!is_array($data)) {
			return;
		}

		parent::__construct($data);
	}

	/**
	 * Configuration of this feature
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_configuration() {
		return array(
			'priority' => 40,
			'base_model' => 'product_based_shipping',
			'name' => esc_html__('Product-Based Shipping', 'shipflex'),
			'section_title' => esc_html__('Product-Based Shipping', 'shipflex'),
			'description' => esc_html__('Apply product-specific shipping costs to the selected shipping methods when the conditions are met.', 'shipflex'),
		);
	}

	/**
	 * Manage shipping rate object
	 * 
	 * @since 1.0.0
	 * @param WC_Shipping_Rate $shipping_rate
	 * @return WC_Shipping_Rate
	 */
	public function modify_shipping_rate($shipping_rate) {
		$current_shipping_cost = $shipping_rate->get_cost();
		$tier_items = apply_filters(Utils::get_hook_name('feature', $this->get_id(), 'modify-shipping-rate'), array($this->lite_tier));

		$tier_shipping_costs = array();
		foreach ($tier_items as $tier_item) {
			$tier_item = wp_parse_args($tier_item, array(
				'type' => '',
				'amount' => '',
				'min_cost' => '',
				'max_cost' => '',
				'condition_groups' => array(),
			));

			if (empty($tier_item['type'])) {
				continue;
			}

			$amount = trim($tier_item['amount']);
			if (strlen($amount) == 0 && 'free_shipping' != $tier_item['type']) {
				continue;
			}

			if ('free_shipping' == $tier_item['type']) {
				$tier_shipping_costs[] = 0.00;
				continue;
			}

			$matched = Main::get_instance()->is_matched_conditions($tier_item['condition_groups'], $this);
			if (false === $matched) {
				continue;
			}

			$amount = floatval($tier_item['amount']);
			if ('increase_percentage' == $tier_item['type']) {
				$amount = $current_shipping_cost + ($current_shipping_cost * $amount / 100);
			}

			if ('decrease_percentage' == $tier_item['type']) {
				$amount = $current_shipping_cost - ($current_shipping_cost * $amount / 100);
			}

			if ('increase_amount' == $tier_item['type']) {
				$amount = $current_shipping_cost + $amount;
			}

			if ('decrease_amount' == $tier_item['type']) {
				$amount = $current_shipping_cost - $amount;
			}

			$min_cost = trim($tier_item['min_cost']);
			if (strlen($min_cost) > 0) {
				$amount = max(floatval($min_cost), $amount);
			}

			$max_cost = trim($tier_item['max_cost']);
			if (strlen($max_cost) > 0) {
				$amount = min(floatval($max_cost), $amount);
			}

			if ($amount < 0) {
				$amount = 0.00;
			}

			$tier_shipping_costs[] = $amount;
		}

		if (count($tier_shipping_costs) > 0) {
			$current_shipping_cost = max($tier_shipping_costs);
		}

		$hook_name = Utils::get_hook_name('feature', $this->get_id(), 'shipping-cost');
		$shipping_cost = apply_filters($hook_name, $current_shipping_cost, array($tier_shipping_costs));
		$shipping_rate->set_cost($shipping_cost);

		return $shipping_rate;
	}

	/**
	 * Output component
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_component() {
		$settings_fields = Settings_Fields::get_instance($this->get_id()); ?>
		<tr class="row-group-heading">
			<td colspan="2">
				<div class="heading-line">
					<?php esc_html_e('Layer', 'shipflex') ?> #{{tierNo}}
				</div>
			</td>
		</tr>

		<template v-if="!collapse">
			<?php $settings_fields->output_fields('product-layer') ?>
		</template>
	<?php
	}

	/**
	 * Output settings fields of rule editor
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_rule_editor(Settings_Fields $settings_fields) { ?>
		<table class="table-shipflex-form">
			<thead>
				<tr>
					<td colspan="2">
						<?php echo esc_html($this->get_configuration_value('section_title')) ?>
					</td>
				</tr>
			</thead>

			<?php $settings_fields->output_fields($this->get_id()); ?>
		</table>
	<?php
	}

	/**
	 * Add settings field of rule editor of current feature
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_editor_settings_fields(Settings_Fields $settings_fields) {
		$settings_fields->add_setting('layer_items', array(
			'priority' => 10,
			'default_value' => array(),
			'model_key' => $this->get_model_key('layer_items'),
			'callback' => array($this, 'layer_items_setting_field'),
		), $this->get_id());
	}

	/**
	 * Output line items setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function layer_items_setting_field() { ?>
		<tbody>
			<template
				is="vue:feature-product-based-shipping"
				:feature-data="product_based_shipping?.layer_items"
				@update="(value) => product_based_shipping.layer_items = value">
			</template>
		</tbody>
	<?php
	}

	/**
	 * Add component settings field 
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_component_settings_fields(Settings_Fields $settings_fields) {
		$settings_fields->add_setting('product_source', array(
			'priority' => 5,
			'label' => esc_html__('Product Source', 'shipflex'),
			'callback' => array($this, 'product_source_setting_field'),
			'label_note' => esc_html__('Choose the product data to match, then select one or more items for this layer.', 'shipflex'),
			'option_note' => esc_html__("This layer will apply only when the customer's cart contains the selected items.", 'shipflex'),
			'related_models' => array(
				'amount' => '',
				'type' => 'increase_amount',
			)
		), 'product-layer');

		$settings_fields->add_setting('condition_groups', array(
			'priority' => 1000,
			'default_value' => array(),
			'model_key' => 'condition_groups',
			'callback' => array(General::class, 'component_condition_group_setting_field'),
			'extra_settings' => array(
				'add_group_method' => 'add_condition_group()',
				'delete_group_method' => 'delete_condition_group(index)'
			)
		), 'product-layer');
	}

	/**
	 * Output adjust cost setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function product_source_setting_field(Form_Control $form_control) {
		$form_control->output_before_input_options(); ?>
		<div class="field-row">
			<cart-option
				:hide-operator="true"
				based-on="taxonomy:product_cat"
				option-label="<?php esc_html_e('{{option_label}}', 'shipflex') ?>">
			</cart-option>
		</div>
<?php
		$form_control->output_after_input_options();
	}
}

Feature::add_feature(Product_Based_Shipping::class);

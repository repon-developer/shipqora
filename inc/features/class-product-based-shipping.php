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
			'name' => esc_html__('Product-Based Shipping Cost', 'shipflex'),
			'section_title' => esc_html__('Product-Based Shipping Cost', 'shipflex'),
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
					<?php esc_html_e('Product Rule', 'shipflex') ?> #{{tier_no}}
					<?php $this->get_form_table_header_action(); ?>
				</div>
			</td>
		</tr>

		<template v-if="!collapse || tier_no == 1">
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
			'default_value' => array((object) array()),
			'model_key' => $this->get_model_key('layers'),
			'callback' => array($this, 'layer_items_setting_field'),
		), $this->get_id());

		$settings_fields->add_setting('add_new_layer', array(
			'priority' => 10000,
			'callback' => array($this, 'add_new_layer_setting_field'),
		), $this->get_id());
	}

	/**
	 * Output line items setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function layer_items_setting_field() { ?>
		<tbody v-for="(layer, layer_index) in <?php echo esc_attr($this->get_model_key('layers')) ?>" :key="layer?.id">
			<template
				:feature-data="layer"
				:tier-index="layer_index"
				is="vue:feature-product-based-shipping"
				delete-warning="<?php esc_html_e('Are you sure you want to delete this layer?', 'shipflex') ?>"
				@update="(value) => <?php echo esc_attr($this->get_model_key('layers')) ?>[layer_index] = value"
				@delete="delete_collection('<?php echo esc_attr($this->get_model_key('layers')) ?>', layer_index)"
				@duplicate="(value, position) => duplicate_collection('<?php echo esc_attr($this->get_model_key('layers')) ?>', value, position)">
			</template>
		</tbody>
	<?php
	}

	/**
	 * Output add new layer button
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_new_layer_setting_field(Form_Control $form_control) {
		$form_control->output_row(); ?>
		<td class="no-padding" colspan="2">
			<a style="--inputHeight: 46px;font-size: 16px" @click.prevent="add_collection('<?php echo esc_attr($this->get_model_key('layers')) ?>')" class="button button-primary button-full-width" href="#">
				<?php esc_html_e('Add Product Rule', 'codiepress-cart-rewards-pro'); ?>
			</a>
		</td>
	<?php
		$form_control->output_row('close');
	}

	/**
	 * Add component settings field 
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_component_settings_fields(Settings_Fields $settings_fields) {
		$settings_fields->add_setting('product_source', array(
			'priority' => 10,
			'default_value' => (object) array(),
			'model_key' => 'product_source',
			'label' => esc_html__('Target Products', 'shipflex'),
			'callback' => array($this, 'product_source_setting_field'),
			'label_note' => esc_html__('Choose which cart items this rule applies to based on categories, tags, or classes.', 'shipflex'),
			'option_note' => esc_html__('This rule calculates shipping costs individually for each matching item in the cart.', 'shipflex'),
		), 'product-layer');

		$settings_fields->add_setting('exclude_products', array(
			'priority' => 10.10,
			'conditions' => array('tier_no == 1'),
			'row_attributes' => array('class' => 'pro-notice-row'),
			'callback' => array($this, 'exclude_products_setting_field'),
		), 'product-layer');

		$settings_fields->add_setting('shipping_cost_calculation', array(
			'priority' => 20,
			'label' => esc_html__('Calculate Cost By', 'shipflex'),
			'callback' => array($this, 'shipping_cost_setting_field'),
			'label_note' => esc_html__('Select the product metric used to determine the shipping rate for each matching item.', 'shipflex'),
			'related_models' => array(
				'calculation_value' => '',
				'calculate_basis' => 'fixed_amount',
				'calculation_type' => 'per_unit_or_percentage',
			)
		), 'product-layer');

		$settings_fields->add_setting('advanced_calculation', array(
			'priority' => 20.10,
			'default_value' => array((object)array()),
			'model_key' => 'advanced_calculation_tiers',
			'label' => esc_html__('Advanced Calculation Settings', 'shipflex'),
			'callback' => array($this, 'advanced_calculation_setting_field'),
			'label_note' => esc_html__('Set up tiered pricing brackets for your products. You can add optional conditions to each block—if multiple blocks match, the one with the highest priority will be applied.', 'shipflex'),
			'conditions' => array('calculate_basis !== "fixed_amount" && calculation_type == "advanced_calculation"'),
		), 'product-layer');

		$settings_fields->add_setting('condition_groups', array(
			'priority' => 1000,
			'default_value' => array(),
			'model_key' => 'condition_groups',
			'callback' => array(General::class, 'condition_group_setting_field'),
		), 'product-layer');
	}

	/**
	 * Output setting field of product source
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
				:cart-option-data="product_source"
				@on-update="(value) => product_source = value"
				option-label="<?php esc_html_e('{{option_label}}', 'shipflex') ?>">
			</cart-option>
		</div>
	<?php
		$form_control->output_after_input_options();
	}

	/**
	 * Output adjust cost setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function exclude_products_setting_field(Form_Control $form_control) {
		$line_button_data = array('utm_source' => 'exclude+products');
		$form_control->output_row(); ?>
		<td colspan="2">
			<div class="shipflex-pro-notice">
				<h3>🚀 Want to Exclude Specific Products?</h3>
				<div class="description">Upgrade to the <strong>Pro version</strong> to exclude selected products from the <strong>"Target Products"</strong> and create more precise shipping cost with greater control over product eligibility.</div>
				<div class="gap-10"></div>
				<?php Utils::get_lite_button($line_button_data) ?>
			</div>
		</td>
	<?php
		$form_control->output_row('close');
	}

	/**
	 * Output shipping cost setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_cost_setting_field(Form_Control $form_control) {
		$form_control->output_before_input_options(); ?>
		<div class="field-row">
			<select v-model="calculate_basis">
				<option value="fixed_amount"><?php esc_html_e('Fixed Amount', 'shipflex') ?></option>
				<option v-for="(metric, value) in calculation_metrics" :value="value" :key="value">{{metric.long_title}}</option>
			</select>

			<select v-model="calculation_type" v-if="calculate_basis !== 'fixed_amount'">
				<option value="per_unit_or_percentage">
					<template v-if="'subtotal' == calculate_basis"><?php esc_html_e('Percentage', 'shipflex') ?></template>
					<template v-if="'subtotal' != calculate_basis">{{calculation_type_label}}</template>
				</option>
				<option value="advanced_calculation"><?php esc_html_e('Advanced Calculation', 'shipflex') ?></option>
			</select>

			<template v-if="show_calculation_value">
				<input v-model="calculation_value" type="number" min="0" placeholder="0.00">
				<span v-if="calculate_basis == 'subtotal'">%</span>
			</template>
		</div>

		<div class="field-note" v-if="calculate_basis == 'fixed_amount'">
			<?php esc_html_e('Applies a single fixed shipping cost to each matching product.', 'shipflex') ?>
		</div>

		<div class="field-note" v-if="calculate_basis == 'subtotal'">
			<?php esc_html_e('Choose "Percentage" to charge a % of the item value, or "Advanced Calculation" for subtotal ranges.', 'shipflex') ?>
		</div>

		<div class="field-note" v-if="calculate_basis == 'quantity'">
			<?php esc_html_e('Charge a rate per item unit (e.g. $2 per item), or choose "Advanced Calculation" for quantity brackets.', 'shipflex') ?>
		</div>

		<div class="field-note" v-if="calculate_basis == 'weight'">
			<?php esc_html_e('Charge a rate per weight unit (e.g. $1.50 per kg), or choose "Advanced Calculation" for weight brackets.', 'shipflex') ?>
		</div>

		<div class="field-note" v-if="calculate_basis == 'volume'">
			<?php esc_html_e('Charge a rate per volume unit (e.g. $0.50 per cm³), or choose "Advanced Calculation" for volume brackets.', 'shipflex') ?>
		</div>
	<?php
		$form_control->output_after_input_options();
	}

	/**
	 * Output setting field for advanced calculation
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function advanced_calculation_setting_field(Form_Control $form_control) {
		$form_control->output_before_input_options(); ?>

		<shipping-cost-range
			:number="index"
			:key="shipping_rage_data?.id"
			:range-data="shipping_rage_data"
			:calculate-basis="calculate_basis"
			v-for="(shipping_rage_data, index) in advanced_calculation_tiers"
			@delete="delete_collection('advanced_calculation_tiers', index)"
			@update="(range_data) => advanced_calculation_tiers[index] = range_data"
			@duplicate="(range_data) => duplicate_collection('advanced_calculation_tiers', range_data, index+1)">
		</shipping-cost-range>

		<div style="padding: 6px">
			<a class="button button-full-width" href="#" @click.prevent="add_shipping_cost_range()"><?php esc_html_e('+ Add New Cost Range', 'shipflex') ?></a>
		</div>
<?php
		$form_control->output_after_input_options();
	}
}

Feature::add_feature(Product_Based_Shipping::class);

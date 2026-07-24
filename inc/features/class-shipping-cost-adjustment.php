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

/**
 * Shipping Cost Adjustment class
 */
final class Shipping_Cost_Adjustment extends Feature {

	/**
	 * Hold the feature id of this feature
	 * 
	 * @var string
	 */
	protected $feature_id = 'shipping-cost-adjustment';

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
			'priority' => 30,
			'base_model' => 'shipping_cost_adjustment',
			'name' => esc_html__('Shipping Cost Adjustment', 'shipflex'),
			'section_title' => esc_html__('Shipping Cost Adjustment', 'shipflex'),
			'description' => esc_html__('Increase, decrease, or override shipping costs based on your configured rules.', 'shipflex'),
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
		$settings_fields = Settings_Fields::get_instance($this->get_id());
		$tier_header = apply_filters(Utils::get_hook_name('feature', $this->get_id(), 'tier-header'), null);
		echo wp_kses_post($tier_header); ?>
		<template v-if="!collapse && !additionalTier">
			<?php $settings_fields->output_fields('tier-item') ?>
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
		$settings_fields->add_setting('lite_tier', array(
			'priority' => 10,
			'default_value' => array(),
			'model_key' => $this->get_model_key('lite_tier'),
			'callback' => array($this, 'lite_tier_setting_field'),
		), $this->get_id());
	}

	/**
	 * Output line items setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function lite_tier_setting_field() { ?>
		<tbody>
			<template
				is="vue:feature-shipping-cost-adjustment"
				:feature-data="shipping_cost_adjustment?.lite_tier"
				@update="(value) => shipping_cost_adjustment.lite_tier = value">
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
		$settings_fields->add_setting('shipping_cost_adjustment', array(
			'priority' => 1000,
			'label' => esc_html__('Adjustment', 'shipflex'),
			'callback' => array($this, 'shipping_cost_adjustment_setting_field'),
			'label_note' => esc_html__('Choose how the shipping cost should be adjusted and enter the value to apply.', 'shipflex'),
			'option_note' => esc_html__('Enter an amount or percentage based on the selected adjustment type.', 'shipflex'),
			'related_models' => array(
				'amount' => '',
				'type' => 'increase_amount',
			)
		), 'tier-item');

		$settings_fields->add_setting('shipping_cost_limit', array(
			'priority' => 1000,
			'label' => esc_html__('Cost Limits', 'shipflex'),
			'callback' => array($this, 'shipping_cost_limit_setting_field'),
			'label_note' => esc_html__('Set the minimum and maximum allowed shipping cost after the adjustment is applied.', 'shipflex'),
			'option_note' => esc_html__('Leave either field empty to disable that limit.', 'shipflex'),
			'related_models' => array(
				'min_cost' => '',
				'max_cost' => '',
			)
		), 'tier-item');

		$settings_fields->add_setting('condition_groups', array(
			'priority' => 1000,
			'default_value' => array(),
			'model_key' => 'condition_groups',
			'callback' => array(General::class, 'condition_group_setting_field'),
		), 'tier-item');
	}

	/**
	 * Output adjust cost setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_cost_adjustment_setting_field(Form_Control $form_control) {
		$form_control->output_before_input_options(); ?>
		<div class="field-row">
			<select v-model="type">
				<option value="increase_amount"><?php esc_html_e('Increase by Amount', 'shipflex') ?></option>
				<option value="decrease_amount"><?php esc_html_e('Decrease by Amount', 'shipflex') ?></option>
				<option value="increase_percentage"><?php esc_html_e('Increase by Percentage', 'shipflex') ?></option>
				<option value="decrease_percentage"><?php esc_html_e('Decrease by Percentage', 'shipflex') ?></option>
				<option value="-" disabled>-------------------------</option>
				<option value="free_shipping"><?php esc_html_e('Free Shipping', 'shipflex') ?></option>
				<option value="fixed_amount"><?php esc_html_e('Set Fixed Cost', 'shipflex') ?></option>
			</select>

			<template v-if="'free_shipping' !== type">
				<input type="number" v-model="amount" min="0" placeholder="<?php esc_html_e('Amount', 'shipflex') ?>">
				<span v-if="'increase_percentage' == type || 'decrease_percentage' == type">%</span>
			</template>
		</div>
	<?php
		$form_control->output_after_input_options();
	}

	/**
	 * Setting field for min/max shipping cost
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_cost_limit_setting_field(Form_Control $form_control) {
		$form_control->output_before_input_options(); ?>
		<div class="field-row">
			<input type="number" v-model="min_cost" placeholder="<?php esc_html_e('Min', 'shipflex') ?>">
			<input type="number" v-model="max_cost" placeholder="<?php esc_html_e('Max', 'shipflex') ?>">
		</div>
	<?php
		$form_control->output_after_input_options();
	}
}

Feature::add_feature(Shipping_Cost_Adjustment::class);

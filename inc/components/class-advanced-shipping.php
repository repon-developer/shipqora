<?php

namespace ShipFlex\Component;

use ShipFlex\Utils;
use ShipFlex\Settings_Fields;

if (!defined('ABSPATH')) {
	exit;
}

final class Advanced_Shipping {
	/**
	 * Hold the current instance
	 * 
	 * @since 1.0.0
	 * @var Advanced_Shipping
	 */
	private static $instance = null;

	/**
	 * Get instance of current class
	 * 
	 * @since 1.0.0
	 * @return Advanced_Shipping
	 */
	public static function get_instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * VueJS component of cart option
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function output_component() {
		$settings_fields = Settings_Fields::get_instance('advanced-shipping-cost'); ?>
		<template id="shipflex-advanced-calculation-component">
			<table class="table-shipflex-form">
				<thead>
					<tr>
						<td colspan="2"><?php esc_html_e('Tier', 'shipflex') ?> #{{variation_no}} (Qty: 0 to 15) → $8.00 Fixed</td>
					</tr>
				</thead>

				<?php $settings_fields->output_fields('general'); ?>
			</table>
		</template>
	<?php
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action('init', array($this, 'add_settings_fields'), 1);
		add_filter('shipflex/admin_enqueue_scripts', array($this, 'enqueue_scripts'), 10, 2);
	}

	/**
	 * Implement require styles and scripts of cart option
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function enqueue_scripts($values, $source) {
		if (Utils::is_plugin_screen('rule-editor') && 'localize' == $source) {
			$settings_fields = Settings_Fields::get_instance('advanced-shipping-cost');
			$values['advanced_shipping_cost_models'] = $settings_fields->get_models();
		}

		return $values;
	}

	/**
	 * Add settings field 
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_settings_fields() {
		$settings_fields = Settings_Fields::get_instance('advanced-shipping-cost');

		$settings_fields->add_setting('metric_condition', array(
			'priority' => 10,
			'label' => esc_html__('[ Selected Metric ] Condition', 'shipflex'),
			'callback' => array($this, 'metric_condition_setting_field'),
			'label_note' => esc_html__('Define the range or threshold required for this rule to apply to an item.', 'shipflex'),
			'option_note' => esc_html__("This rule applies whenever an item's [ metric ] meets this condition.", 'shipflex'),
			'related_models' => array(
				'metric_value1' => '',
				'metric_value2' => '',
				'metric_operator' => 'greater_than',
			)
		), 'general');

		$settings_fields->add_setting('tier_shipping_cost', array(
			'priority' => 10,
			'label' => esc_html__('Tier Shipping Cost', 'shipflex'),
			'callback' => array($this, 'shipping_cost_setting_field'),
			'label_note' => esc_html__("Specify the shipping cost to add when this tier's condition is met.", 'shipflex'),
			'option_note' => esc_html__('This rate will be calculated for each matching item and added to the total shipping fee.', 'shipflex'),
			'related_models' => array(
				'shipping_cost_type' => 'fixed_cost',
				'shipping_cost_value' => 'flat_rate',
			)
		), 'general');
	}

	/**
	 * Output setting field of product source
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function metric_condition_setting_field($form_control) {
		$form_control->output_before_input_options(); ?>
		<div class="field-row">
			<select v-model="metric_operator">
				<?php Utils::get_operators_options(array('greater_than', 'less_than', 'greater_than_or_equal', 'less_than_or_equal', 'between')); ?>
			</select>
			<input v-model="metric_value1" type="number" min="0" placeholder="0.00">
			<input v-model="metric_value2" type="number" min="0" placeholder="0.00" v-if="metric_operator == 'between'">
		</div>
	<?php
		$form_control->output_after_input_options();
	}

	/**
	 * Output setting field of product source
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_cost_setting_field($form_control) {
		$form_control->output_before_input_options(); ?>
		<div class="field-row">
			<select v-model="shipping_cost_type">
				<option value="fixed_cost"><?php esc_html_e('Fixed Cost', 'shipflex') ?></option>
				<option value="percentage" v-if="calculateBasis == 'subtotal'"><?php esc_html_e('Percentage', 'shipflex') ?></option>
				<option value="cost_per_unit" v-if="calculateBasis !== 'subtotal'"><?php esc_html_e('Cost per Unit', 'shipflex') ?></option>
			</select>

			<input v-model="shipping_cost_value" type="number" min="0" placeholder="0.00">
			<span v-if="shipping_cost_type == 'percentage'">%</span>
		</div>
<?php
		$form_control->output_after_input_options();
	}
}

Advanced_Shipping::get_instance();

<?php

namespace ShipFlex\Component;

use ShipFlex\Feature\General;
use ShipFlex\Form_Control;
use ShipFlex\Utils;
use ShipFlex\Settings_Fields;

if (!defined('ABSPATH')) {
	exit;
}

final class Tiered_Shipping {
	/**
	 * Hold the current instance
	 * 
	 * @since 1.0.0
	 * @var Tiered_Shipping
	 */
	private static $instance = null;

	/**
	 * Get instance of current class
	 * 
	 * @since 1.0.0
	 * @return Tiered_Shipping
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
		$actions = array(
			'duplicate' => array(
				'priority' => 5,
				'content' => '<a @click.prevent="duplicate_item()" class="button button-small" href="#"><span class="dashicons dashicons-admin-page"></span>' . esc_html__('Duplicate', 'shipflex') . '</a>'
			),

			'delete' => array(
				'priority' => 10,
				'content' => '<a @click.prevent="delete_item()" class="button button-small" href="#"><span class="dashicons dashicons-trash"></span>' . esc_html__('Delete', 'shipflex') . '</a>'
			),

			'collapse' => array(
				'priority' => 1000,
				'content' => '<a  @click.prevent="collapse = !collapse" class="btn-collapse dashicons" :class="collapse_button_class" href="#"></a>'
			)
		);

		$settings_fields = Settings_Fields::get_instance('tiered-shipping'); ?>
		<template id="shipflex-tiered-shipping-component">
			<table class="table-shipflex-form table-shipflex-form-tiered-rates">
				<thead>
					<tr>
						<td colspan="2">
							<div class="heading-line">
								<?php esc_html_e('Rate Tier', 'shipflex') ?> #{{tier_no}}
								<?php Utils::get_form_table_header_action($actions, 'tiered-shipping'); ?>
							</div>
						</td>
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
			$settings_fields = Settings_Fields::get_instance('tiered-shipping');
			$values['tiered_shipping_models'] = $settings_fields->get_models();
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
		$settings_fields = Settings_Fields::get_instance('tiered-shipping');

		$settings_fields->add_setting('metric_condition', array(
			'priority' => 10,
			'label' => '{{metric_label}} Ranges',
			'callback' => array($this, 'metric_condition_setting_field'),
			'label_note' => esc_html__('Define the range or threshold required for this rule to apply to an item.', 'shipflex'),
			'option_note' => esc_html__("This rule applies whenever an item's {{metric_label_lower}} meets this condition.", 'shipflex'),
			'related_models' => array(
				'metric_value1' => '',
				'metric_value2' => '',
				'metric_operator' => 'greater_than',
			)
		), 'general');

		$settings_fields->add_setting('tier_shipping_cost', array(
			'priority' => 20,
			'label' => esc_html__('Shipping Cost', 'shipflex'),
			'callback' => array($this, 'shipping_cost_setting_field'),
			'label_note' => esc_html__("Specify the shipping cost to add when this tier's condition is met.", 'shipflex'),
			'option_note' => esc_html__('This rate will be calculated for each matching item and added to the total shipping fee.', 'shipflex'),
			'related_models' => array(
				'shipping_cost_type' => 'fixed_cost',
				'shipping_cost_value' => '',
			)
		), 'general');

		$settings_fields->add_setting('tier_shipping_cost', array(
			'priority' => 20,
			'default_value' => '',
			'placeholder' => '10',
			'model_key' => 'priority',
			'type' => Form_Control::TEXTBOX_NUMBER,
			'label' => esc_html__('Priority', 'shipflex'),
			'related_models' => array(
				'shipping_cost_type' => 'fixed_cost',
				'shipping_cost_value' => '',
			)
		), 'general');

		$settings_fields->add_setting('condition_groups', array(
			'priority' => 1000,
			'default_value' => array(array()),
			'model_key' => 'condition_groups',
			'callback' => array(General::class, 'condition_group_setting_field'),
			'extra_settings' => array(
				'add_group_method' => 'add_condition_group()',
				'delete_group_method' => 'delete_condition_group(index)',
				'supported_condition_types' => array('cart:subtotal', 'cart:total_quantity', 'cart:total_weight', 'cart:total_volume')
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

		<table class="shipflex-cost-range-table">
			<thead>
				<tr>
					<th>From ( > )</th>
					<th>To ( <= )</th>
					<th>Cost Type</th>
					<th>Rate ($)</th>
				</tr>
			</thead>

			<tbody>
				<tr>
					<td><input type="number" placeholder="0" disabled></td>
					<td><input type="number" placeholder="5"></td>
					<td>
						<select>
							<option value="">Fixed Cost</option>
							<option value="">Cost per Unit</option>
							<option value="">Percentage</option>
						</select>
					</td>

					<td><input type="number" placeholder="0.00"></td>
				</tr>

				<tr>
					<td><input type="number" placeholder="5" disabled></td>
					<td><input type="number" placeholder="max"></td>
					<td>
						<select>
							<option value="">Fixed Cost</option>
							<option value="">Cost per Unit</option>
							<option value="">Percentage</option>
						</select>
					</td>

					<td><input type="number" placeholder="0.00"></td>
				</tr>
			</tbody>

		</table>

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
				<option value="cost_per_unit" v-if="calculateBasis !== 'subtotal'">{{unit_label('<?php esc_html_e('Cost per unit_label:upper_case', 'shipflex') ?>')}}</option>
			</select>

			<input v-model="shipping_cost_value" type="number" min="0" placeholder="0.00">
			<span v-if="shipping_cost_type == 'percentage'">%</span>
		</div>
<?php
		$form_control->output_after_input_options();
	}
}

Tiered_Shipping::get_instance();

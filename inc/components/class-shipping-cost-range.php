<?php

namespace ShipFlex\Component;

use ShipFlex\Utils;
use ShipFlex\Form_Control;
use ShipFlex\Feature\General;
use ShipFlex\Settings_Fields;

if (!defined('ABSPATH')) {
	exit;
}

final class Shipping_Cost_Range_Tier {
	/**
	 * Hold the current instance
	 * 
	 * @since 1.0.0
	 * @var Shipping_Cost_Range_Tier
	 */
	private static $instance = null;

	/**
	 * Get instance of current class
	 * 
	 * @since 1.0.0
	 * @return Shipping_Cost_Range_Tier
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

		$settings_fields = Settings_Fields::get_instance('shipping-cost-range'); ?>
		<template id="shipflex-shipping-cost-range-component">
			<table class="table-shipflex-form table-shipping-cost-range-tier">
				<thead>
					<tr>
						<td colspan="2">
							<div class="heading-line">
								<?php esc_html_e('Cost Ranges', 'shipflex') ?> #{{tier_no}}
								<?php Utils::get_form_table_header_action($actions, 'shipping-cost-range'); ?>
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
			$settings_fields = Settings_Fields::get_instance('shipping-cost-range');
			$values['shipping_cost_range_model'] = $settings_fields->get_models();
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
		$settings_fields = Settings_Fields::get_instance('shipping-cost-range');

		$settings_fields->add_setting('shipping_cost_ranges', array(
			'priority' => 10,
			'model_key' => 'shipping_cost_ranges',
			'label' => esc_html__('Shipping Cost Ranges', 'shipflex'),
			'callback' => array($this, 'shipping_cost_ranges_setting_field'),
			'label_note' => esc_html__('Define the {{metric_label_short_lower}} thresholds and fee calculations for this range.', 'shipflex'),
			'option_note' => esc_html__("Define item quantity brackets and their corresponding calculation types. The system will match the exact bracket for the cart's item count to compute the final shipping cost.", 'shipflex'),
		), 'general');

		$settings_fields->add_setting('priority', array(
			'priority' => 20,
			'default_value' => '',
			'placeholder' => '10',
			'model_key' => 'priority',
			'type' => Form_Control::TEXTBOX_NUMBER,
			'label' => esc_html__('Priority', 'shipflex'),
			'label_note' => esc_html__('Set the priority for this Cost Range block. If multiple blocks match, the block with the highest priority number will apply.', 'shipflex'),
			'option_note' => esc_html__('Higher numbers take precedence over lower numbers (e.g., Priority 15 executes before Priority 10).', 'shipflex'),

		), 'general');

		$settings_fields->add_setting('condition_groups', array(
			'priority' => 1000,
			'default_value' => array(),
			'model_key' => 'condition_groups',
			'callback' => array(General::class, 'condition_group_setting_field'),
		), 'general');
	}

	/**
	 * Output setting field of shipping cost ranges tier
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_cost_ranges_setting_field($form_control) {
		$form_control->output_before_input_options(); ?>
		<template v-if="true">
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
		</template>
		<a class="button button-small" @click.prevent="add_cost_line()" href="#"><?php esc_html_e('+ Add Cost Range Line', 'shipflex') ?></a>

<?php
		$form_control->output_after_input_options();
	}
}

Shipping_Cost_Range_Tier::get_instance();

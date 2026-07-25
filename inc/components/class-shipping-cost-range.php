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
		$actions = Utils::get_component_heading_actions();
		$actions['delete']['content'] = '<a @click.prevent="delete_tier()" class="button button-small" href="#"><span class="dashicons dashicons-trash"></span>' . esc_html__('Delete', 'shipflex') . '</a>';
		$action_contents = apply_filters(Utils::get_component_heading_actions_hook('shipping-cost-range'), $actions);
		$settings_fields = Settings_Fields::get_instance('shipping-cost-range'); ?>
		<template id="shipflex-shipping-cost-range-component">
			<table class="table-shipflex-form table-shipping-cost-range-tier">
				<thead>
					<tr class="row-group-heading">
						<td colspan="2">
							<div class="heading-line">
								<?php esc_html_e('Cost Ranges', 'shipflex') ?> #{{tier_no}}
								<?php Utils::output_component_heading_actions($action_contents); ?>
							</div>
						</td>
					</tr>
				</thead>

				<tbody v-if="!collapse">
					<?php $settings_fields->output_fields('general'); ?>
				</tbody>
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
			'default_value' => array(array()),
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
			'type' => Form_Control::NUMBER,
			'label' => esc_html__('Priority', 'shipflex'),
			'label_note' => esc_html__('Set the priority for this Cost Range block. If multiple blocks match, the block with the highest priority number will apply.', 'shipflex'),
			'option_note' => esc_html__('Higher numbers take precedence over lower numbers. Only the highest-priority rule will be executed (e.g., if Priority 15 and Priority 10 both match, only Priority 15 will be executed)..', 'shipflex'),

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
		<table class="shipflex-cost-range-table" v-if="shipping_cost_ranges?.length">
			<thead>
				<tr>
					<th><?php esc_html_e('From ( > )', 'shipflex') ?></th>
					<th><?php esc_html_e('To ( <= )', 'shipflex') ?></th>
					<th><?php esc_html_e('Cost Type', 'shipflex') ?></th>
					<th>
						<?php
						printf(
							/* translators: %s for currency symbol */
							esc_html__('Cost (%s) or Percentage', 'shipflex'),
							get_woocommerce_currency_symbol()
						) ?>
					</th>

					<th class="column-delete"></th>
				</tr>
			</thead>

			<tbody>
				<tr v-for="(range, index) in shipping_cost_ranges" :key="range?.id" :class="error_classes(index)">
					<td><input type="number" placeholder="0" disabled :value="get_range_minimum(index)"></td>
					<td><input v-model="range.max" class="range-input-max" type="number" placeholder="<?php esc_html_e('max', 'shipflex') ?>"></td>
					<td>
						<select v-model="range.type">
							<option value="fixed_amount"><?php esc_html_e('Fixed Amount', 'shipflex') ?></option>
							<option value="per_unit_or_percentage">{{calculation_type_label}}</option>
						</select>
					</td>

					<td><input v-model="range.value" class="range-input-value" type="number" placeholder="0.00" min="0" step="0.001"></td>
					<td class="column-delete">
						<a @click.prevent="delete_cost_range(index)" class="btn-delete dashicons dashicons-remove" href="#"></a>
					</td>
				</tr>
			</tbody>

		</table>

		<a class="button button-small" @click.prevent="add_cost_range()" href="#"><?php esc_html_e('+ Add Cost Range Line', 'shipflex') ?></a>
<?php
		$form_control->output_after_input_options();
	}
}

Shipping_Cost_Range_Tier::get_instance();

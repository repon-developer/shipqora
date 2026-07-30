<?php

namespace ShipFlex\Component;

use ShipFlex\Condition\Main;
use ShipFlex\Utils;
use ShipFlex\Form_Control;
use ShipFlex\Feature\General;
use ShipFlex\Settings_Fields;
use ShipFlex\Component_Methods;

if (!defined('ABSPATH')) {
	exit;
}

final class Shipping_Cost_Range_Tier {
	/**
	 * Provides common helper methods of component
	 *
	 * @since 1.0.0
	 */
	use Component_Methods;

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
	 * Hold priority of current shipping cost range
	 * 
	 * @since 1.0.0
	 * @var int
	 */
	private $priority = 10;

	/**
	 * Hold all ranges of shipping cost
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	private $range_lines = array();

	/**
	 * Hold total of range line
	 * 
	 * @since 1.0.0
	 * @var int
	 */
	private $total_range_line = 0;

	/**
	 * Hold condition groups
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	private $condition_groups = array();

	/**
	 * Constructor.
	 */
	public function __construct($cost_range = null) {
		if (!is_array($cost_range)) {
			return;
		}

		$cost_range = wp_parse_args($cost_range, array('priority' => '10', 'condition_groups' => array(), 'range_lines' => array()));
		if (absint($cost_range['priority']) > 0) {
			$this->priority = absint($cost_range['priority']);
		}

		if (!is_array($cost_range['condition_groups'])) {
			$this->condition_groups = array();
		}

		$range_lines = array();
		if (is_array($cost_range['range_lines']) && count($cost_range['range_lines']) > 0) {
			$range_lines = $cost_range['range_lines'];
		}

		$this->total_range_line = count($range_lines);

		$this->range_lines = array_filter($range_lines, function ($item, $item_no) {
			$item = wp_parse_args($item, array('max' => '', 'value' => ''));
			if (strlen(trim($item['value'])) == 0) {
				return false;
			}

			if (($item_no + 1) < $this->total_range_line) {
				return strlen($item['max']) > 0 || $item['max'] > 0;
			}

			return true;
		}, ARRAY_FILTER_USE_BOTH);
	}

	/**
	 * Get priority of this shipping cost range
	 * 
	 * @since 1.0.0
	 * @return int
	 */
	public function get_priority() {
		return $this->priority;
	}

	/**
	 * Check if has validate range lines
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function has_validate_ranges() {
		return is_array($this->range_lines) && count($this->range_lines) > 0;
	}

	/**
	 * Check if all condition matched
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function is_condition_matched() {
		return Main::get_instance()->is_matched_conditions($this->condition_groups);
	}

	/**
	 * Calculate shipping cost of ranges
	 * 
	 * @since 1.0.0
	 * @return mixed
	 */
	public function calculate_shipping_cost($total_value, $calculate_basis) {
		$previous_max = 0;

		$range_costs = array_map(function ($range) use (&$total_value, &$previous_max, $calculate_basis) {
			if (empty($range['type']) || !in_array($range['type'], array('fixed_amount', 'per_unit_or_percentage'))) {
				$range['type'] = 'fixed_amount';
			}

			if (empty($range['max'])) {
				$range['max'] = 9999999999999999;
			}

			$calculate_with = min($total_value, $range['max'] - $previous_max);
			if ($calculate_with <= 0) {
				return 0;
			}

			$previous_max = $range['max'];
			$total_value = $total_value - $calculate_with;

			$range_cost = $range['value'];
			if ('per_unit_or_percentage' === $range['type']) {
				$range_cost = $calculate_with * $range['value'];
				if ('subtotal' == $calculate_basis) {
					$range_cost = $range_cost / 100;
				}
			}

			return $range_cost;
		}, $this->range_lines);

		return floatval(array_sum($range_costs));
	}

	/**
	 * Init required action of shipping cost range
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function init_hook() {
		add_action('init', array($this, 'add_settings_fields'), 1);
		add_action('admin_footer', array($this, 'output_vue_component'));
		add_filter('shipflex/admin_enqueue_scripts', array($this, 'enqueue_scripts'), 10, 2);
	}

	/**
	 * VueJS component of cart option
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_vue_component() {
		$settings_fields = Settings_Fields::get_instance('shipping-cost-range'); ?>
		<template id="shipflex-shipping-cost-range-component">
			<table class="table-shipflex-form table-shipping-cost-range-tier">
				<thead>
					<?php $this->output_heading_row(esc_html__('Cost Ranges #{{tierNo}}', 'shipflex'), array('shipping-cost-range')) ?>
				</thead>

				<tbody v-if="!collapse">
					<?php $settings_fields->output_fields('general'); ?>
				</tbody>
			</table>
		</template>
	<?php
	}

	/**
	 * Implement require styles and scripts of cart option
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function enqueue_scripts($values, $source) {
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

		$settings_fields->add_setting('range_lines', array(
			'priority' => 10,
			'model_key' => 'range_lines',
			'default_value' => array(array()),
			'label' => esc_html__('Shipping Cost Ranges', 'shipflex'),
			'callback' => array($this, 'range_lines_setting_field'),
			'label_note' => esc_html__('Define the {{metric_label_short_lower}} thresholds and fee calculations for this range.', 'shipflex'),
			'option_note' => esc_html__("Define item {{metric_label_short_lower}} brackets and their corresponding calculation types. The system will match the exact bracket for the cart's item count to compute the final shipping cost.", 'shipflex'),
		), 'general');

		$settings_fields->add_setting('priority', array(
			'priority' => 20,
			'default_value' => '',
			'placeholder' => '10',
			'model_key' => 'priority',
			'type' => Form_Control::NUMBER,
			'label' => esc_html__('Priority', 'shipflex'),
			'label_note' => esc_html__('Set the priority for this Cost Range block. If multiple blocks match, the block with the highest priority number will apply.', 'shipflex'),
			'option_note' => esc_html__('Higher numbers take precedence over lower numbers. Only the highest-priority rule will be executed (e.g., if Priority 15 and Priority 10 both match, only Priority 15 will be executed).', 'shipflex'),

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
	public function range_lines_setting_field($form_control) {
		$form_control->output_before_input_options(); ?>
		<table class="shipflex-cost-range-table" v-if="range_lines?.length">
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
				<tr v-for="(range, index) in range_lines" :key="range?.id" :class="error_classes(index)">
					<td><input type="number" placeholder="0" disabled :value="get_range_minimum(index)"></td>
					<td>
						<input
							type="number"
							v-model="range.max"
							class="range-input-max"
							placeholder="<?php esc_html_e('max', 'shipflex') ?>"
							title="<?php esc_attr_e('Leave empty or enter "max" to apply to any value above the lower bound', 'shipflex') ?>" />
					</td>
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

Shipping_Cost_Range_Tier::get_instance()->init_hook();

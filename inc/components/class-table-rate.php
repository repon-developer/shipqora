<?php

namespace ShipQora_WooCommerce\Component;

use ShipQora_WooCommerce\Utils;
use ShipQora_WooCommerce\Form_Control;
use ShipQora_WooCommerce\Condition\Main;
use ShipQora_WooCommerce\Feature\General;
use ShipQora_WooCommerce\Settings_Fields;
use ShipQora_WooCommerce\Component_Methods;
use ShipQora_WooCommerce\Global_Settings_Fields;

if (!defined('ABSPATH')) {
	exit;
}

final class Table_Rate {
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
	 * @var Table_Rate
	 */
	private static $instance = null;

	/**
	 * Get instance of current class
	 * 
	 * @since 1.0.0
	 * @return Table_Rate
	 */
	public static function get_instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hold all ranges of shipping cost
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	private $shipping_rates = array();

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
	 * Hold all extra data of table rate
	 * 
	 * @var array
	 */
	private $meta_data = [];

	/**
	 * Constructor.
	 */
	public function __construct($table_rates_data = null) {
		if (!is_array($table_rates_data)) {
			return;
		}

		$table_rates_data = wp_parse_args($table_rates_data, array('condition_groups' => array(), 'shipping_rates' => array()));
		if (is_array($table_rates_data['condition_groups'])) {
			$this->condition_groups = $table_rates_data['condition_groups'];
		}

		$shipping_rates = array();
		if (is_array($table_rates_data['shipping_rates']) && count($table_rates_data['shipping_rates']) > 0) {
			$shipping_rates = $table_rates_data['shipping_rates'];
		}

		$this->total_range_line = count($shipping_rates);

		$this->shipping_rates = array_filter($shipping_rates, function ($item, $item_no) {
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
	 * isset magic method
	 * 
	 * @since 1.0.0
	 * @param string $key
	 * @param boolean
	 */
	public function __isset($key) {
		return isset($this->meta_data[$key]);
	}

	/**
	 * Set magic method
	 * 
	 * @since 1.0.0
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set($key, $value) {
		$this->meta_data[$key] = $value;
	}

	/**
	 * Get magic method
	 * 
	 * @since 1.0.0
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key) {
		return isset($this->meta_data[$key]) ? $this->meta_data[$key] : null;
	}

	/**
	 * Check if has validate range lines
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function has_validate_ranges() {
		return is_array($this->shipping_rates) && count($this->shipping_rates) > 0;
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
		}, $this->shipping_rates);

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
		add_filter('shipqora/admin_enqueue_scripts', array($this, 'enqueue_scripts'), 10, 2);
	}

	/**
	 * VueJS component of cart option
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_vue_component() {
		$settings_fields = Settings_Fields::get_instance('table-rate'); ?>
		<template id="shipqora-woocommerce-table-rate-component">
			<table class="table-shipqora-woocommerce-form table-shipqora-woocommerce-table-rate">
				<thead>
					<?php $this->output_heading_row(esc_html__('Table Rates #{{layerNo}}', 'shipqora-woocommerce'), array('table-rate')) ?>
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
			$settings_fields = Settings_Fields::get_instance('table-rate');
			$values['table_rate_models'] = $settings_fields->get_models();
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
		$settings_fields = Settings_Fields::get_instance('table-rate');

		$settings_fields->add_setting('shipping_rates', array(
			'priority' => 10,
			'model_key' => 'shipping_rates',
			'default_value' => array([]),
			'label' => esc_html__('Shipping Rates', 'shipqora-woocommerce'),
			'callback' => array($this, 'shipping_rates_setting_field'),
			'label_note' => esc_html__('Define the {{metric_label_short_lower}} thresholds and fee calculations for this shipping rates.', 'shipqora-woocommerce'),
			'option_note' => esc_html__("Define item {{metric_label_short_lower}} brackets and their corresponding calculation types. The system will match the exact bracket for the cart's item count to compute the final shipping cost.", 'shipqora-woocommerce'),
		), 'general');

		$settings_fields->add_setting('condition_groups', array(
			'priority' => 1000,
			'default_value' => array(),
			'model_key' => 'condition_groups',
			'callback' => array(Global_Settings_Fields::class, 'condition_group_setting_field'),
		), 'general');
	}

	/**
	 * Output setting field of shipping cost ranges tier
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_rates_setting_field($form_control) {
		$form_control->output_before_input_options(); ?>
		<table class="table-rate" v-if="<?php echo esc_attr($form_control->get_model_key()) ?>?.length">
			<thead>
				<tr>
					<th><?php esc_html_e('From ( > )', 'shipqora-woocommerce') ?></th>
					<th><?php esc_html_e('To ( <= )', 'shipqora-woocommerce') ?></th>
					<th><?php esc_html_e('Cost Type', 'shipqora-woocommerce') ?></th>
					<th>
						<?php
						printf(
							/* translators: %s for currency symbol */
							esc_html__('Cost (%s) or Percentage', 'shipqora-woocommerce'),
							esc_html(get_woocommerce_currency_symbol())
						) ?>
					</th>

					<th class="column-delete"></th>
				</tr>
			</thead>

			<tbody>
				<tr v-for="(shipping_rate, index) in <?php echo esc_attr($form_control->get_model_key()) ?>" :key="shipping_rate?.id" :class="error_classes(index)">
					<td><input type="number" placeholder="0" disabled :value="get_shipping_rate_minimum(index)"></td>
					<td>
						<input
							type="number"
							class="range-input-max"
							v-model="shipping_rate.max"
							placeholder="<?php esc_html_e('max', 'shipqora-woocommerce') ?>"
							title="<?php esc_attr_e('Leave empty or enter "max" to apply to any value above the lower bound', 'shipqora-woocommerce') ?>" />
					</td>
					<td>
						<select v-model="shipping_rate.type">
							<option value="fixed_amount"><?php esc_html_e('Fixed Amount', 'shipqora-woocommerce') ?></option>
							<option value="per_unit_or_percentage">{{calculation_type_label}}</option>
						</select>
					</td>

					<td><input v-model="shipping_rate.value" class="range-input-value" type="number" placeholder="0.00" min="0" step="0.001"></td>
					<td class="column-delete">
						<a @click.prevent="delete_shipping_rate(index)" class="btn-delete dashicons dashicons-remove" href="#"></a>
					</td>
				</tr>
			</tbody>

		</table>

		<a class="button button-small" @click.prevent="add_shipping_rate()" href="#"><?php esc_html_e('+ Add Shipping Rate', 'shipqora-woocommerce') ?></a>
<?php
		$form_control->output_after_input_options();
	}
}

Table_Rate::get_instance()->init_hook();

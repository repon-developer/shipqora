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
						<td colspan="2"><?php esc_html_e('Variation', 'shipflex') ?> #{{variation_no}}</td>
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

		$settings_fields->add_setting('target_amount', array(
			'priority' => 10,
			'label' => esc_html__('Minimum Subtotal', 'shipflex'),
			'callback' => array($this, 'sdfsadfasdfasfsf'),
			'related_models' => array(
				'unit_value1' => '',
				'unit_value2' => '',
				'unit_operator' => 'greater_than',
			)
		), 'general');

		$settings_fields->add_setting('sdfsfsfsf', array(
			'priority' => 10,
			'label' => esc_html__('Variation Cost', 'shipflex'),
			'callback' => array($this, 'sdfsfsfsfsfsdfsdf'),
			'related_models' => array(
				'sdfsf' => 'flat_rate',
			)
		), 'general');
	}

	/**
	 * Output setting field of product source
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function sdfsadfasdfasfsf($form_control) {
		$form_control->output_before_input_options(); ?>
		<div class="field-row">
			<select v-model="unit_operator">
				<?php Utils::get_operators_options(array('greater_than', 'less_than', 'greater_than_or_equal', 'less_than_or_equal', 'between')); ?>
			</select>
			<input v-model="unit_value1" type="number" min="0" placeholder="0.00">
			<input v-model="unit_value2" type="number" min="0" placeholder="0.00" v-if="unit_operator == 'between'">
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
	public function sdfsfsfsfsfsdfsdf($form_control) {
		$form_control->output_before_input_options(); ?>
		<div class="field-row">
			<select v-model="sdfsf">
				<option value="flat_rate"><?php esc_html_e('Flat Rate', 'shipflex') ?></option>
				<option value="cost_per_unit"><?php esc_html_e('Cost per kg', 'shipflex') ?></option>
				<option value="cost_ranges"><?php esc_html_e('Cost Ranges', 'shipflex') ?></option>
			</select>

			<input type="number" min="0" placeholder="0.00">
		</div>
<?php
		$form_control->output_after_input_options();
	}
}

Advanced_Shipping::get_instance();

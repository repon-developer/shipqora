<?php

namespace ShipFlex\Feature;

use ShipFlex\Feature;
use ShipFlex\Form_Control;
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
			'section_title' => esc_html__('Shipping Cost Adjustment Settings', 'shipflex'),
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
					<?php esc_html_e('Tier', 'shipflex') ?> #{{tierNo}}
				</div>
			</td>
		</tr>

		<template v-if="!collapse">
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
			'label' => esc_html__('Cost Adjustment', 'shipflex'),
			'callback' => array($this, 'shipping_cost_adjustment_setting_field'),
			'label_note' => esc_html__('Choose how the shipping cost should be adjusted and enter the value to apply.', 'shipflex'),
			'option_note' => esc_html__('Enter an amount or percentage based on the selected adjustment type.', 'shipflex'),
			'related_models' => array(
				'adjustment_amount' => '',
				'adjustment_type' => 'increase_percentage',
			)
		), 'tier-item');

		$settings_fields->add_setting('shipping_cost_limit', array(
			'priority' => 1000,
			'label' => esc_html__('Shipping Cost Limits', 'shipflex'),
			'callback' => array($this, 'shipping_cost_limit_setting_field'),
			'label_note' => esc_html__('Set the minimum and maximum allowed shipping cost after the adjustment is applied.', 'shipflex'),
			'option_note' => esc_html__('Leave either field empty to disable that limit.', 'shipflex'),
			'related_models' => array(
				'min_shipping_cost' => '',
				'max_shipping_cost' => '',
			)
		), 'tier-item');

		$settings_fields->add_setting('condition_groups', array(
			'priority' => 1000,
			'default_value' => array(),
			'model_key' => 'condition_groups',
			'callback' => array($this, 'condition_group_setting_field'),
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
			<select v-model="adjustment_type">
				<option value="increase_percentage"><?php esc_html_e('Increase by Percentage', 'shipflex') ?></option>
				<option value="decrease_percentage"><?php esc_html_e('Decrease by Percentage', 'shipflex') ?></option>
				<option value="increase_amount"><?php esc_html_e('Increase by Amount', 'shipflex') ?></option>
				<option value="decrease_amount"><?php esc_html_e('Decrease by Amount', 'shipflex') ?></option>
				<option value="fixed_amount"><?php esc_html_e('Set Fixed Cost', 'shipflex') ?></option>
			</select>
			<input type="number" v-model="adjustment_amount" placeholder="<?php esc_html_e('Amount', 'shipflex') ?>">
			<span v-if="'increase_percentage' == adjustment_type || 'decrease_percentage' == adjustment_type">%</span>

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
			<input type="number" v-model="min_shipping_cost" placeholder="<?php esc_html_e('Min', 'shipflex') ?>">
			<input type="number" v-model="max_shipping_cost" placeholder="<?php esc_html_e('Max', 'shipflex') ?>">
		</div>
	<?php
		$form_control->output_after_input_options();
	}

	/**
	 * Output condition group setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function condition_group_setting_field(Form_Control $form_control) {
		$form_control->output_open_row(); ?>
		<td colspan="2">
			<div class="shipflex-repeater shipflex-repeater-condition-groups" v-if="condition_groups?.length > 0">
				<template v-for="(group, index) in condition_groups" :key="group?.id">
					<div class="repeater-item repeater-item-separator" v-if="index > 0" data-text="<?php esc_attr_e('or', 'shipflex') ?>"></div>
					<div class="repeater-item">
						<condition-group
							:group="group"
							@delete="delete_condition(index)"
							@update="(group_data) => condition_groups[index] = group_data">
						</condition-group>
					</div>
				</template>
			</div>

			<button class="button" :class="{'button-large-dashed button-full-width': !condition_groups?.length}" @click.prevent="add_condition_group()">
				<?php esc_html_e('Add condition group', 'shipflex') ?>
			</button>
		</td>
<?php
		$form_control->output_close_row();
	}
}

Feature::add_feature(Shipping_Cost_Adjustment::class);

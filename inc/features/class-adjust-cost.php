<?php

namespace ShipFlex\Feature;

use ShipFlex\Feature;
use ShipFlex\Form_Control;
use ShipFlex\Settings_Fields;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Adjust Cost class
 */
final class Adjust_Cost extends Feature {

	/**
	 * Hold the feature id of this feature
	 * 
	 * @var string
	 */
	protected $feature_id = 'adjust-shipping-cost';

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
			'priority' => 10,
			'base_model' => 'adjust_shipping_cost',
			'name' => esc_html__('Adjust Shipping Cost', 'shipflex'),
			'editor_box_title' => esc_html__('Adjust Shipping Cost', 'shipflex'),
			'description' => esc_html__('Adjust Shipping Cost of exists', 'shipflex'),
		);
	}

	/**
	 * Modify shipping rate object
	 * 
	 * @since 1.0.0
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
		<table class="table-shipflex-form table-shipflex-adjust-shipping-cost-form">
			<thead>
				<tr>
					<td colspan="2">
						Line Number #{{lineNumber}}
					</td>
				</tr>
			</thead>

			<?php $settings_fields->output_fields('line-item-settings') ?>
		</table>
	<?php
	}

	/**
	 * Output settings fields of rule editor
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_rule_editor(Settings_Fields $settings_fields) { ?>
		<div class="shipflex-box">
			<div class="box-header">
				<h3><?php echo esc_html($this->get_configuration_value('editor_box_title')) ?></h3>
			</div>

			<div class="box-content">
				<?php $settings_fields->output_fields($this->get_id()); ?>
			</div>
		</div>
	<?php
	}

	/**
	 * Add settings field of rule editor of current feature
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_editor_settings_fields(Settings_Fields $settings_fields) {
		$settings_fields->add_setting('conditions', array(
			'priority' => 10,
			'default_value' => array([], []),
			'model_key' => $this->get_model_key('line_items'),
			'label' => esc_html__('Active Features', 'shipflex'),
			'callback' => array($this, 'line_item_setting_field'),
			'label_note' => esc_html__('Configure how this reward is calculated in the above "Product Settings".', 'shipflex'),
			'option_note' => esc_html__('These settings control the discount type, value, and how the discount is applied during checkout.', 'shipflex'),
		), $this->get_id());
	}

	/**
	 * Output line items setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function line_item_setting_field() { ?>
		<feature-adjust-shipping-cost
			v-for="(line_item, index) in adjust_shipping_cost.line_items">
		</feature-adjust-shipping-cost>

		<!-- <template
			line-data="<?php echo esc_attr($this->get_model_key('line_items')) ?>"
			is="vue:feature-adjust-shipping-cost"
			@update="(value) => free_shipping_line = value">
		</template> -->

	<?php
	}

	/**
	 * Add component settings field 
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_component_settings_fields(Settings_Fields $settings_fields) {

		$settings_fields->add_setting('adjust-shipping-cost', array(
			'priority' => 1000,
			'default_value' => array(),
			'model_key' => 'sdfsfsfsf',
			'label' => esc_html__('Active Features', 'shipflex'),
			'callback' => array($this, 'adjust_cost_setting_field'),
			'label_note' => esc_html__('Configure how this reward is calculated in the above "Product Settings".', 'shipflex'),
			'option_note' => esc_html__('These settings control the discount type, value, and how the discount is applied during checkout.', 'shipflex'),
		), 'line-item-settings');

		$settings_fields->add_setting('condition-groups', array(
			'priority' => 1000,
			'default_value' => array(),
			'model_key' => 'condition_groups',
			'label' => esc_html__('Active Features', 'shipflex'),
			'callback' => array($this, 'condition_group_setting_field'),
			'label_note' => esc_html__('Configure how this reward is calculated in the above "Product Settings".', 'shipflex'),
			'option_note' => esc_html__('These settings control the discount type, value, and how the discount is applied during checkout.', 'shipflex'),
		), 'line-item-settings');
	}

	/**
	 * Output adjust cost setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function adjust_cost_setting_field(Form_Control $form_control) {
		$form_control->output_before_input_options(); ?>
		<div class="field-row">
			<input type="number">
			sfsf
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
							@delete="delete_collection('condition_groups', index)"
							@update="(group_data) => condition_groups[index] = group_data">
						</condition-group>
					</div>
				</template>
			</div>

			<button class="button" :class="{'button-large-dashed button-full-width': !condition_groups?.length}" @click.prevent="add_collection('condition_groups')">
				<?php esc_html_e('Add condition group', 'shipflex') ?>
			</button>
		</td>
<?php
		$form_control->output_close_row();
	}
}

Feature::add_feature(Adjust_Cost::class);

<?php

namespace ShipFlex\Feature;

use ShipFlex\Feature;
use ShipFlex\Form_Control;
use ShipFlex\Condition\Main;
use ShipFlex\Settings_Fields;
use ShipFlex\Utils;

if (!defined('ABSPATH')) {
	exit;
}

final class Hide_Other_Shipping_Methods extends Feature {

	/**
	 * Hold the feature id of this feature
	 * 
	 * @var string
	 */
	protected $feature_id = 'hide-other-shipping-methods';

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
			'priority' => 20,
			'base_model' => 'hide_other_shipping_methods',
			'name' => esc_html__('Hide Other Shipping Methods', 'shipflex'),
			'section_title' => esc_html__('Hide Other Shipping Methods', 'shipflex'),
			'description' => esc_html__('If the selected shipping methods are available on the checkout page, hide the other selected shipping methods.', 'shipflex'),
		);
	}

	/**
	 * Get all hideable shipping rates
	 * 
	 * @since 1.0.0
	 * @return WC_Shipping_Rate
	 */
	public function get_shipping_rates() {
		$tier_items = apply_filters(Utils::get_hook_name('feature', $this->get_id(), 'hideable-shipping-rates'), array($this->lite_tier));

		$hideable_rates = array();
		foreach ($tier_items as $tier) {
			$shipping_methods = array();
			if (isset($this->lite_tier['shipping_methods']) && is_array($this->lite_tier['shipping_methods'])) {
				$shipping_methods = $this->lite_tier['shipping_methods'];
			}

			if (count($shipping_methods) == 0) {
				continue;
			}

			$condition_groups = array();
			if (isset($this->lite_tier['condition_groups']) && is_array($this->lite_tier['condition_groups'])) {
				$condition_groups = $this->lite_tier['condition_groups'];
			}

			$matched = Main::get_instance()->is_matched_conditions($condition_groups, $this);
			if (!$matched) {
				continue;
			}

			$hideable_rates = array_merge($hideable_rates, $shipping_methods);
		}

		return $hideable_rates;
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
				is="vue:feature-hide-other-shipping-methods"
				:feature-data="hide_other_shipping_methods?.lite_tier"
				@update="(value) => hide_other_shipping_methods.lite_tier = value">
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
		$settings_fields->add_setting('shipping_methods', array(
			'priority' => 10,
			'label' => esc_html__('Shipping Methods to Hide', 'shipflex'),
			'callback' => array($this, 'shipping_methods_setting_field'),
			'label_note' => esc_html__("Select the shipping methods that should be hidden when this rule's conditions are met.", 'shipflex'),
			'option_note' => esc_html__('Add one or more shipping methods. The selected shipping methods will be hidden.', 'shipflex'),
			'related_models' => array(
				'shipping_methods' => array(''),
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
	public function shipping_methods_setting_field(Form_Control $form_control) {
		$form_control->output_before_input_options(); ?>

		<ul class="shipflex-repeater" v-if="shipping_methods?.length" style="margin-bottom: 8px;">
			<li class="repeater-item" v-for="(shipping_method, index) in shipping_methods" :key="shipping_method">
				<shipping-method-input
					:shipping-method="shipping_method"
					@update="(value) => shipping_methods[index] = value"
					@delete="delete_shipping_method(index)">
				</shipping-method-input>
			</li>
		</ul>

		<a href="#" class="button" :class="add_shipping_method_button_class" @click.prevent="add_shipping_method()">
			<?php esc_html_e('Add Shipping Method', 'shipflex') ?>
		</a>

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
}

Feature::add_feature(Hide_Other_Shipping_Methods::class);

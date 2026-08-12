<?php

namespace ShipQora\Feature;

use ShipQora\Utils;
use ShipQora\Feature;
use ShipQora\Form_Control;
use ShipQora\Condition\Main;
use ShipQora\Settings_Fields;
use ShipQora\Component_Methods;

if (!defined('ABSPATH')) {
	exit;
}

final class Hide_Other_Shipping_Methods extends Feature {

	/**
	 * Provides common helper methods of component
	 *
	 * @since 1.0.0
	 */
	use Component_Methods;

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
			'standalone' => true,
			'feature_priority' => 2,
			'base_model' => 'hide_other_shipping_methods',
			'name' => esc_html__('Hide Other Shipping Methods', 'shipqora'),
			'section_title' => esc_html__('Hide Other Shipping Methods', 'shipqora'),
			'description' => esc_html__('If the selected shipping methods(s) are available on the checkout page, hide the other selected shipping methods.', 'shipqora'),
		);
	}

	/**
	 * Get all hideable shipping rates
	 * 
	 * @since 1.0.0
	 * @return WC_Shipping_Rate
	 */
	public function get_shipping_rates($shipping_rate) {
		$tier_items = apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
			Utils::get_hook_name('feature', $this->get_id(), 'layers'),
			array($this->lite_layer),
			$this
		);

		$hideable_rates = array();
		foreach ($tier_items as $tier_item) {
			$shipping_methods = array();
			if (isset($tier_item['shipping_methods']) && is_array($tier_item['shipping_methods'])) {
				$shipping_methods = $tier_item['shipping_methods'];
			}

			if (count($shipping_methods) == 0) {
				continue;
			}

			$condition_groups = array();
			if (isset($tier_item['condition_groups']) && is_array($tier_item['condition_groups'])) {
				$condition_groups = $tier_item['condition_groups'];
			}

			$matched = Main::get_instance()->is_matched_conditions($condition_groups);
			if (!$matched) {
				continue;
			}

			foreach ($shipping_methods as $shipping_method_id) {
				$this->add_shipping_rate_data($shipping_rate, $shipping_method_id);
			}
		}
	}

	/**
	 * Output component
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_component() {
		$settings_fields = Settings_Fields::get_instance($this->get_id());
		$action_contents = apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
			Utils::get_hook_name('component-heading-actions', $this->get_id()),
			null
		); ?>

		<?php $this->output_heading_row(esc_html__('Hide Tier #{{tierNo}}', 'shipqora'), array($this->get_id())) ?>
		<template v-if="!collapse">
			<?php $settings_fields->output_fields('tier-item') ?>
		</template>
	<?php
	}

	/**
	 * Add settings field of rule editor of current feature
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_editor_settings_fields(Settings_Fields $settings_fields) {
		$settings_fields->add_setting('lite_layer', array(
			'priority' => 10,
			'default_value' => (object) array(),
			'model_key' => $this->get_model_key('lite_layer'),
			'callback' => array($this, 'lite_layer_setting_field'),
		), $this->get_id());

		$settings_fields->add_setting('add_new_tier', array(
			'priority' => 100000,
			'row_attributes' => array('class' => 'shipqora-notice-row'),
			'callback' => array($this, 'add_new_tier_setting_field'),
		), $this->get_id());
	}

	/**
	 * Output line items setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function lite_layer_setting_field() { ?>
		<tbody>
			<template
				:draggable="false"
				is="vue:feature-hide-other-shipping-methods"
				:feature-data="hide_other_shipping_methods?.lite_layer"
				@update="(value) => hide_other_shipping_methods.lite_layer = value"
				<?php $this->output_component_attrs('hide-other-shipping-methods', array(':hide-heading' => 'true')) ?>>
			</template>
		</tbody>
	<?php
	}

	/**
	 * Add new adjustment tier notice
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_new_tier_setting_field(Form_Control $form_control) {
		$line_button_data = array('utm_source' => 'hide+other+shipping+methos+tier');
		$form_control->output_row(); ?>
		<td colspan="2">
			<div class="shipqora-notice-box">
				<h3>⚡ Need Multiple Hiding Tiers?</h3>
				<div class="description">Create complex combinations of conditions and stack multiple hiding tiers seamlessly with <strong>ShipQora Pro</strong>.</div>
				<div class="gap-10"></div>
				<?php Utils::get_lite_button($line_button_data) ?>
			</div>
		</td>
	<?php
		$form_control->output_row('close');
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
			'default_value' => array(''),
			'model_key' => 'shipping_methods',
			'type' => Form_Control::SHIPPING_METHODS,
			'label' => esc_html__('Shipping Methods to Hide', 'shipqora'),
			'label_note' => esc_html__("Select the shipping methods that should be hidden when this rule's conditions are met.", 'shipqora'),
			'option_note' => esc_html__('Add one or more shipping methods. The selected shipping methods will be hidden.', 'shipqora'),
		), 'tier-item');

		$settings_fields->add_setting('condition_groups', array(
			'priority' => 1000,
			'default_value' => array(),
			'model_key' => 'condition_groups',
			'callback' => array(General::class, 'condition_group_setting_field'),
		), 'tier-item');
	}
}

Feature::add_feature(Hide_Other_Shipping_Methods::class);

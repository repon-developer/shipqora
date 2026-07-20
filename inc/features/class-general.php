<?php

namespace ShipFlex\Feature;

use ShipFlex\Feature;
use ShipFlex\Form_Control;
use ShipFlex\Settings_Fields;
use ShipFlex\ShipFlex_Rule;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * General class
 */
final class General {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action('init', array($this, 'add_settings_fields'), 1);
		add_filter('woocommerce_package_rates', array($this, 'modify_shipping_rates'), 20, 2);
	}

	/**
	 * Modify shipping rates
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function modify_shipping_rates($rates, $package) {
		$features = Feature::get_features();

		if (isset($features['hide-shipping-methods'])) {
			unset($features['hide-shipping-methods']);
			$rates = array_filter($rates, function ($shipping_rate) {
				$shipflex_rule = ShipFlex_Rule::get_by_shipping_method($shipping_rate);
				if ($shipflex_rule->exists()) {
					if ($shipflex_rule->is_feature_enabled('hide-shipping-methods')) {
						$feature_instance = $shipflex_rule->get_feature_instance('hide-shipping-methods');
						if ($feature_instance) {
							return !$feature_instance->hide_shipping_methods();
						}
					}
				}

				return true;
			});
		}

		array_walk($rates, function (&$shipping_rate) use ($features) {
			$shipflex_rule = ShipFlex_Rule::get_by_shipping_method($shipping_rate);
			if (!$shipflex_rule->exists()) {
				return;
			}

			foreach ($features as $feature_id => $feature_instance) {
				if (!$shipflex_rule->is_feature_enabled($feature_id)) {
					continue;
				}

				$rule_feature = $shipflex_rule->get_feature_instance($feature_id);
				if ($rule_feature) {
					$shipping_rate = $rule_feature->manage_shipping_rate($shipping_rate);
				}
			}
		});



		//error_log(print_r($rates, true));

		return $rates;
	}

	/**
	 * Add settings fields of rule editor and features component
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_settings_fields() {
		$editor_settings_fields = Settings_Fields::get_instance('rule-editor');

		$editor_settings_fields->add_setting('shipping_methods', array(
			'priority' => 10,
			'default_value' => array(array()),
			'model_key' => 'shipping_methods',
			'callback' => array($this, 'shipping_methods_setting_field'),
			'type' => Form_Control::MULTIPLE_OPTIONS,
			'label' => esc_html__('Apply to Shipping Methods', 'shipflex'),
			'label_note' => esc_html__('Select the shipping methods this rule should apply to.', 'shipflex'),
			'option_note' => esc_html__('Add one or more shipping methods. This rule will only affect the selected methods.', 'shipflex'),
		), 'general');


		$registered_features = \ShipFlex\Feature::get_features();

		$registered_feature_options = array();
		foreach ($registered_features as $feature_id => $feature_instance) {
			$registered_feature_options[$feature_id] = array(
				'label' => $feature_instance->get_configuration_value('name'),
				'description' => $feature_instance->get_configuration_value('description'),
			);
		}

		$editor_settings_fields->add_setting('managed_features', array(
			'priority' => 10,
			'default_value' => array(),
			'model_key' => 'active_features',
			'option_type' => 'checkbox',
			'type' => Form_Control::MULTIPLE_OPTIONS,
			'options' => $registered_feature_options,
			'label' => esc_html__('Managed Features', 'shipflex'),
			'label_note' => esc_html__('Select the ShipFlex features that should be applied to the selected shipping methods.', 'shipflex'),
		), 'general');

		foreach ($registered_features as $feature_id => $feature_instance) {
			$feature_instance->add_editor_settings_fields($editor_settings_fields);

			if (method_exists($feature_instance, 'add_component_settings_fields')) {
				$component_settings_fields = Settings_Fields::get_instance($feature_id);
				$feature_instance->add_component_settings_fields($component_settings_fields);
			}
		}
	}

	/**
	 * Output shipping method setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_methods_setting_field(Form_Control $form_control) {
		$model_key = $form_control->get_model_key();
		$form_control->output_before_input_options(); ?>

		<ul class="shipflex-repeater" v-if="shipping_methods?.length" style="margin-bottom: 8px;">
			<li class="repeater-item" v-for="(shipping_method, index) in shipping_methods" :key="shipping_method">
				<shipping-method-input
					:shipping-method="shipping_method"
					@update="(value) => shipping_methods[index] = value"
					@delete="delete_collection('shipping_methods', index)">
				</shipping-method-input>
			</li>
		</ul>

		<a href="#" class="button" :class="{'button-small': shipping_methods?.length > 0, 'button-large-dashed': !shipping_methods?.length}" @click.prevent="add_collection('shipping_methods', '')">
			<?php esc_html_e('Add Shipping Method', 'shipflex') ?>
		</a>

<?php
		$form_control->output_after_input_options();
	}
}

new General();

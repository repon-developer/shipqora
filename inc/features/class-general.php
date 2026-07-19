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

		if (isset($features['visibility-condition'])) {
			unset($features['visibility-condition']);
			$rates = array_filter($rates, function ($shipping_rate) {
				$shipflex_rule = ShipFlex_Rule::get_from_instance($shipping_rate->get_instance_id());
				if ($shipflex_rule->exists()) {
					if ($shipflex_rule->is_feature_enabled('visibility-condition')) {
						$visibility_condition = $shipflex_rule->get_feature_instance('visibility-condition');
						if ($visibility_condition) {
							return $visibility_condition->visible_shipping_rate();
						}
					}
				}

				return true;
			});
		}

		array_walk($rates, function (&$shipping_rate) use ($features) {
			$shipflex_rule = ShipFlex_Rule::get_from_instance($shipping_rate->get_instance_id());
			if (!$shipflex_rule->exists()) {
				return;
			}

			foreach ($features as $feature_id => $feature_instance) {
				if (!$shipflex_rule->is_feature_enabled($feature_id)) {
					continue;
				}

				$rule_feature = $shipflex_rule->get_feature_instance($feature_id);
				if ($rule_feature) {
					$shipping_rate = $rule_feature->modify_shipping_rate($shipping_rate);
				}
			}
		});



		error_log(print_r($rates, true));

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

		$editor_settings_fields->add_setting('shipping_instances', array(
			'priority' => 10,
			'default_value' => array(),
			'model_key' => 'shipping_instances',
			'callback' => array($this, 'shipping_instance_setting_field'),
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

		$editor_settings_fields->add_setting('managed-features', array(
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
	 * Output shipping instance setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_instance_setting_field(Form_Control $form_control) {
		$model_key = $form_control->get_model_key();
		$form_control->output_before_input_options(); ?>

		<ul class="shipflex-repeater" v-if="shipping_instances?.length" style="margin-bottom: 8px;">
			<li class="repeater-item" v-for="(instance_id, instance_index) in shipping_instances" :key="instance_index">
				<select2-dropdown
					:multiple="false"
					type="shipping_instances"
					:initial-value="instance_id"
					@update="(value) => shipping_instances[instance_index] = value"
					placeholder="<?php esc_html_e('Choose a Shipping Method', 'shipflex') ?>">
				</select2-dropdown>

				<div class="tools">
					<a href="#" @click.prevent="delete_collection('shipping_instances', instance_index)" class="btn-delete-item dashicons dashicons-no-alt"></a>
				</div>
			</li>
		</ul>

		<a href="#" class="button" :class="{'button-small': shipping_instances?.length > 0, 'button-large-dashed': !shipping_instances?.length}" @click.prevent="add_collection('shipping_instances')">
			<?php esc_html_e('Add Shipping Method', 'shipflex') ?>
		</a>

<?php
		$form_control->output_after_input_options();
	}
}

new General();

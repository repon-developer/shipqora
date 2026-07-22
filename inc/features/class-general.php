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
	 * Global setting field for condition group
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function condition_group_setting_field(Form_Control $form_control) {
		$model_key = $form_control->get_model_key();

		$add_group_method = $form_control->get_extra_setting('add_group_method');
		$delete_group_method = $form_control->get_extra_setting('delete_group_method');

		$form_control->output_row(); ?>
		<td class="no-padding" colspan="2">
			<div class="shipflex-repeater shipflex-repeater-condition-groups" v-if="<?php echo esc_attr($model_key); ?>?.length > 0">
				<template v-for="(group, index) in <?php echo esc_attr($model_key); ?>" :key="group?.id">
					<div class="repeater-item repeater-item-separator" v-if="index > 0" data-text="<?php esc_attr_e('or', 'shipflex') ?>"></div>
					<div class="repeater-item">
						<condition-group
							:group="group"
							@delete="<?php echo esc_attr($delete_group_method) ?>"
							@update="(group_data) => <?php echo esc_attr($model_key); ?>[index] = group_data">
						</condition-group>
					</div>
				</template>
			</div>

			<button class="button button-large-dashed button-full-width" @click.prevent="<?php echo esc_attr($add_group_method) ?>">
				<?php esc_html_e('Add condition group', 'shipflex') ?>
			</button>
		</td>
	<?php
		$form_control->output_row('close');
	}

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
						$feature_object = $shipflex_rule->get_feature_object('hide-shipping-methods');
						if ($feature_object) {
							return !$feature_object->hide_shipping_methods();
						}
					}
				}

				return true;
			});
		}

		if (isset($features['hide-other-shipping-methods'])) {
			unset($features['hide-other-shipping-methods']);

			$hideable_rate_ids = array();
			foreach ($rates as $shipping_rate) {
				$shipflex_rule = ShipFlex_Rule::get_by_shipping_method($shipping_rate);
				if ($shipflex_rule->exists()) {
					if ($shipflex_rule->is_feature_enabled('hide-other-shipping-methods')) {
						$feature_object = $shipflex_rule->get_feature_object('hide-other-shipping-methods');
						if ($feature_object) {
							$hideable_rate_ids = array_merge($hideable_rate_ids, $feature_object->get_shipping_rates());
						}
					}
				}
			}

			$hideable_methods = array_filter($hideable_rate_ids, function ($rate_id) {
				$rate_information = explode(':', $rate_id);
				return !isset($rate_information[1]);
			});

			$rates = array_filter($rates, function ($shipping_rate) use ($hideable_methods, $hideable_rate_ids) {
				$matched = false;
				if (in_array($shipping_rate->get_method_id(), $hideable_methods)) {
					$matched = true;
				}

				if (in_array($shipping_rate->get_id(), $hideable_rate_ids)) {
					$matched = true;
				}

				return !$matched;
			});
		}

		array_walk($rates, function (&$shipping_rate) use ($features) {
			$shipflex_rule = ShipFlex_Rule::get_by_shipping_method($shipping_rate);
			if (!$shipflex_rule->exists()) {
				return;
			}

			foreach ($features as $feature_id => $feature_object) {
				if (!$shipflex_rule->is_feature_enabled($feature_id)) {
					continue;
				}

				$rule_feature_object = $shipflex_rule->get_feature_object($feature_id);
				if ($rule_feature_object) {
					$shipping_rate = $rule_feature_object->modify_shipping_rate($shipping_rate);
				}
			}
		});

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

		foreach ($registered_features as $feature_id => $feature_object) {
			$feature_object->add_editor_settings_fields($editor_settings_fields);

			if (method_exists($feature_object, 'add_component_settings_fields')) {
				$component_settings_fields = Settings_Fields::get_instance($feature_id);
				$feature_object->add_component_settings_fields($component_settings_fields);
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

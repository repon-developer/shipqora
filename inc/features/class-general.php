<?php

namespace ShipQora\Feature;

use ShipQora\Utils;
use ShipQora\Feature;
use ShipQora\Form_Control;
use ShipQora\ShipQora_Rule;
use ShipQora\Settings_Fields;

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
		add_filter('woocommerce_package_rates', array($this, 'set_feature_lines'), 30, 2);
		add_filter('woocommerce_package_rates', array($this, 'modify_shipping_rates'), 9999, 2);
		add_filter('woocommerce_package_rates', array($this, 'hide_shipping_methods'), 10000, 2);
		add_filter('woocommerce_available_payment_gateways', array($this, 'hide_payment_methods'));
	}

	/**
	 * Hide payment methods
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function hide_payment_methods($gateways) {
		if (WC()->session && method_exists(WC()->session, 'get')) {
			$shipping_methods = WC()->session->get('chosen_shipping_methods');
			if (is_array($shipping_methods) && count($shipping_methods) > 0) {
				$rules = array();
				foreach ($shipping_methods as $rate_id) {
					$rules = array_merge($rules, ShipQora_Rule::get_by_rate_id($rate_id));
				}

				if (count($rules) == 0) {
					return $gateways;
				}

				$hide_payment_methods = array();

				foreach ($rules as $rule) {
					if (!$rule->exists() || !$rule->is_feature_enabled('hide-payment-methods')) {
						continue;
					}

					$feature_object = $rule->get_feature_object('hide-payment-methods');
					if ($feature_object) {
						$hide_payment_methods = array_merge($hide_payment_methods, $feature_object->payment_methods());
					}
				}

				foreach ($hide_payment_methods as $gateway_id) {
					unset($gateways[$gateway_id]);
				}
			}
		}

		return $gateways;
	}

	/**
	 * Hide current shipping method
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function hide_current_shipping_method($rates) {
		$features = Feature::get_features();

		if (isset($features['hide-shipping-methods'])) {
			$rates = array_filter($rates, function ($shipping_rate) {
				$shipqora_rules = ShipQora_Rule::get_by_rate_id($shipping_rate->get_id());

				$hide_shipping_methos = array();
				foreach ($shipqora_rules as $key => $rule) {
					if ($rule->exists()) {
						if ($rule->is_feature_enabled('hide-shipping-methods')) {
							$feature_object = $rule->get_feature_object('hide-shipping-methods');
							if ($feature_object) {
								$hide_shipping_methos[] = $feature_object->hide_shipping_methods();
							}
						}
					}
				}

				return count(array_filter($hide_shipping_methos)) === 0;
			});
		}

		return $rates;
	}

	/**
	 * Set feature line before modify shipping rate
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function set_feature_lines($rates, $package) {
		$features = array_filter(Feature::get_features(), fn($feature) => true !== $feature->get_configuration('standalone'));

		$rates = $this->hide_current_shipping_method($rates);
		array_walk($rates, function (&$shipping_rate) use ($features) {
			$shipqora_rules = ShipQora_Rule::get_by_shipping_rate($shipping_rate);

			foreach ($features as $feature_id => $feature_object) {
				$rate_feature_object = clone $feature_object;

				foreach ($shipqora_rules as $rule) {
					if (!$rule->exists() || !$rule->is_feature_enabled($feature_id)) {
						continue;
					}

					$cost_configuration = $rule->get_feature_value($rate_feature_object->get_model_key('cost_configuration'));
					if (method_exists($rate_feature_object, 'set_line_item')) {
						$rate_feature_object->set_line_item($cost_configuration, $rule);
					}
				}

				$shipping_rate->{$feature_id} = $rate_feature_object;
			}
		});

		return $rates;
	}


	/**
	 * Modify shipping rates
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function modify_shipping_rates($rates, $package) {
		$features = array_filter(Feature::get_features(), fn($feature) => true !== $feature->get_configuration('standalone'));
		uasort($features, fn($a, $b) => $a->get_feature_priority() <=> $b->get_feature_priority());

		$feature_ids = array_keys($features);

		$rates = array_map(function ($shipping_rate) use ($feature_ids) {
			foreach ($feature_ids as $feature_id) {
				$feature_object = $shipping_rate->{$feature_id};
				if (!is_a($feature_object, Feature::class)) {
					continue;
				}

				$feature_object->modify_shipping_rate($shipping_rate);
			}

			return $shipping_rate;
		}, $rates);



		error_log(print_r($rates, true));

		return $rates;
	}

	/**
	 * Hide other shipping methods
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function hide_shipping_methods($rates, $package) {
		$features = Feature::get_features();
		if (!isset($features['hide-other-shipping-methods'])) {
			return $rates;
		}

		$rates = $this->hide_current_shipping_method($rates);




		$hide_shipping_methods = array();
		foreach ($rates as $shipping_rate) {
			$shipqora_rules = ShipQora_Rule::get_by_shipping_rate($shipping_rate);
			foreach ($shipqora_rules as $rule) {
				if (!$rule->exists() || !$rule->is_feature_enabled('hide-other-shipping-methods')) {
					continue;
				}

				$feature_object = $rule->get_feature_object('hide-other-shipping-methods');
				if ($feature_object) {






					$feature_object->get_shipping_rates($shipping_rate);
					$hide_shipping_methods = array_merge($hide_shipping_methods, $feature_object->get_shipping_rate_data($shipping_rate));
				}
			}
		}

		return $rates;

		return array_filter($rates, function ($current_rate) use ($hide_shipping_methods) {
			$search_data = array();
			$method_id = $current_rate->get_method_id();

			if ('pickup_location' !== $method_id) {
				$zone = \WC_Shipping_Zones::get_zone_by('instance_id', $current_rate->get_instance_id());
				$zone_id = $zone->get_id();

				$search_methods = array(
					$method_id,
					$method_id . ':' . $zone_id . '-0',
					$method_id . ':' . $zone_id . '-' . $current_rate->get_instance_id(),
				);
			}

			if ('pickup_location' == $method_id) {
				$search_methods = array($method_id, $current_rate->get_id());
			}

			return count(array_intersect($hide_shipping_methods, $search_methods)) == 0;
		});
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
			'default_value' => array(''),
			'model_key' => 'shipping_methods',
			'type' => Form_Control::SHIPPING_METHODS,
			'label' => esc_html__('Apply to Shipping Methods', 'shipqora'),
			'label_note' => esc_html__('Select the shipping methods this rule should apply to.', 'shipqora'),
			'option_note' => esc_html__('Add one or more shipping methods. This rule will only affect the selected methods.', 'shipqora'),
			'row_attributes' => array(
				'data-highlight-section' => 'general-shipping-methods'
			)
		), 'general');

		$registered_features = \ShipQora\Feature::get_features();

		$registered_feature_options = array();
		foreach ($registered_features as $feature_id => $feature_instance) {
			$registered_feature_options[$feature_id] = array(
				'label' => $feature_instance->get_configuration('name'),
				'description' => $feature_instance->get_configuration('description'),
			);
		}

		$editor_settings_fields->add_setting('active_features', array(
			'priority' => 10,
			'default_value' => array(),
			'model_key' => 'active_features',
			'option_type' => 'checkbox',
			'type' => Form_Control::MULTIPLE_OPTIONS,
			'options' => $registered_feature_options,
			'label' => esc_html__('Active Features', 'shipqora'),
			'callback' => array($this, 'active_features_setting_field'),
			'label_note' => esc_html__('Select the ShipQora features that should be applied to the selected shipping methods.', 'shipqora'),
		), 'general');

		foreach ($registered_features as $feature_id => $feature_object) {
			if (method_exists($feature_object, 'add_editor_settings_fields')) {
				$feature_object->add_editor_settings_fields($editor_settings_fields);
			}

			if (method_exists($feature_object, 'add_component_settings_fields')) {
				$component_settings_fields = Settings_Fields::get_instance($feature_id);
				$feature_object->add_component_settings_fields($component_settings_fields);
			}
		}

		$editor_settings_fields->add_setting('shipqora_rule_status', array(
			'priority' => 1000,
			'model_key' => 'status',
			'option_type' => 'radio',
			'default_value' => 'development',
			'options' => Utils::get_statuses(),
			'type' => Form_Control::MULTIPLE_OPTIONS,
			'label' => esc_html__('Rule Status', 'shipqora'),
			'callback' => array($this, 'status_setting_field'),
			'label_note' => esc_html__('Control the visibility and execution mode of this rule on your store.', 'shipqora'),
		), 'general');
	}

	/**
	 * Output shipping method setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function active_features_setting_field(Form_Control $form_control) {
		$form_control->output_before_input_options();
		$form_control->output_control(); ?>

		<div class="shipqora-notice-box shipqora-notice-box-left">
			<h3>💡 Looking for Additional Features?</h3>
			<div class="description">Missing a key feature for your workflow or any improvements? Reach out directly to <a href="mailto:support@shipqora.com?subject=ShipQora%20Feature%20Request">support@shipqora.com</a> and our team will help build it for you.</div>
			<div class="gap-10"></div>
			<a class="button" href="mailto:support@shipqora.com?subject=ShipQora%20Feature%20Request">Request a Feature</a>
		</div>
<?php
		$form_control->output_after_input_options();
	}

	/**
	 * Output status setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function status_setting_field(Form_Control $form_control) {
		$form_control->output_before_input_options();
		$form_control->output_control();
		do_action('shipqora/after_statuses_options');
		$form_control->output_after_input_options();
	}
}

new General();

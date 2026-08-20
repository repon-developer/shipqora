<?php

namespace ShipQora\Feature;

use ShipQora\Utils;
use ShipQora\Feature;
use ShipQora\Form_Control;
use ShipQora\ShipQora_Rule;
use ShipQora\Condition\Main;
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
		add_filter('woocommerce_package_rates', array($this, 'modify_shipping_rates'), 100, 2);
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
		$feature_object = Feature::get_feature('hide-shipping-methods');
		if (!$feature_object) {
			return $rates;
		}

		return array_filter($rates, function ($shipping_rate) use ($feature_object) {
			$shipqora_rules = ShipQora_Rule::get_by_rate_id($shipping_rate->get_id());

			$hide_shipping_methos = array();
			foreach ($shipqora_rules as $key => $rule) {
				if (!$rule->exists() || !$rule->is_feature_enabled('hide-shipping-methods')) {
					continue;
				}

				$condition_groups = $rule->get_feature_value($feature_object->get_model_key('condition_groups'));
				$hide_shipping_methos[] = Main::get_instance()->is_matched_conditions($condition_groups);
			}

			return count(array_filter($hide_shipping_methos)) === 0;
		});
	}

	/**
	 * Set feature line before modify shipping rate
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function modify_shipping_rates($rates, $package) {
		$rates = $this->hide_current_shipping_method($rates);

		$features = array_filter(Feature::get_features(), fn($feature) => true !== $feature->get_configuration('standalone'));
		uasort($features, fn($a, $b) => $a->get_feature_priority() <=> $b->get_feature_priority());

		array_walk($rates, function (&$shipping_rate) use ($features) {
			$shipqora_rules = ShipQora_Rule::get_by_shipping_rate($shipping_rate);

			foreach ($features as $feature_id => $feature_object) {
				$rate_feature_object = clone $feature_object;

				foreach ($shipqora_rules as $rule) {
					if (!$rule->exists() || !$rule->is_feature_enabled($feature_id)) {
						continue;
					}

					$primary_item_settings = $rule->get_feature_value($rate_feature_object->get_primary_settings_model());
					if (method_exists($rate_feature_object, 'arrange_feature_data')) {
						$rate_feature_object->arrange_feature_data($primary_item_settings, $rule);

						// 						/**
						//  * Arrange data of current feature
						//  * 
						//  * @since 1.0.0
						//  * @return void
						//  */
						// public function arrange_feature_data($rule) {

						// 	do_action(
						// 		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
						// 		$this->get_hook('set-line-item'),
						// 		$rule,
						// 		$rate_feature_object
						// 	);
						// }

					}
				}

				if (method_exists($rate_feature_object, 'modify_shipping_rate')) {
					$rate_feature_object->modify_shipping_rate($shipping_rate);
				}
			}
		});

		return $rates;
	}

	/**
	 * Hide other shipping methods
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function hide_shipping_methods($rates, $package) {
		$feature_object = Feature::get_feature('hide-other-shipping-methods');
		if (false === $feature_object) {
			return $rates;
		}

		$model_key = $feature_object->get_model_key('primary_hideable_shipping');
		foreach ($rates as $shipping_rate) {
			$shipqora_rules = ShipQora_Rule::get_by_shipping_rate($shipping_rate);
			foreach ($shipqora_rules as $rule) {
				$hideable_shippings = $rule->get_feature_value($model_key);
				$feature_object->set_line_item($hideable_shippings);

				do_action(
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
					$feature_object->get_hook('set-line-item'),
					$rule,
					$feature_object
				);
			}
		}

		return array_filter($rates, fn($current_rate) => !$feature_object->hide_shipping_rate($current_rate));
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

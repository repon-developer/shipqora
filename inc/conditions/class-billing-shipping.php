<?php

namespace ShipFlex\Condition;

use ShipFlex\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Billing & Shipping condition class
 */
final class Billing_Shipping {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter('shipflex/condition/types', array($this, 'add_condition_types'));
	}

	/**
	 * Add condition types
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function add_condition_types($condition_types) {
		$condition_types = array_merge($condition_types, array(
			'billing_shipping:billing_cities' => array(
				'priority' => 10,
				'model_key' => 'billing_cities',
				'template' => array($this, 'billing_shipping_cities'),
				'label' => esc_html__('Billing Cities', 'shipflex'),
				'validate_callback' => array($this, 'validate_billing_shipping'),
			),

			'billing_shipping:billing_states' => array(
				'priority' => 20,
				'default_value' => array(),
				'model_key' => 'billing_states',
				'template' => array($this, 'billing_shipping_states'),
				'validate_callback' => array($this, 'validate_billing_shipping'),
				'label' => esc_html__('Billing States', 'shipflex'),
				'extra_settings' => array(
					'placeholder' => esc_html__('Billing States', 'shipflex')
				)
			),

			'billing_shipping:billing_zipcodes' => array(
				'priority' => 30,
				'model_key' => 'billing_zipcodes',
				'template' => array($this, 'billing_zipcodes_template'),
				'validate_callback' => array($this, 'validate_billing_shipping'),
				'label' => esc_html__('Billing ZIP Codes', 'shipflex'),
			),

			'billing_shipping:billing_countries' => array(
				'priority' => 40,
				'default_value' => array(),
				'model_key' => 'billing_countries',
				'template' => array($this, 'billing_shipping_country'),
				'validate_callback' => array($this, 'validate_billing_shipping'),
				'label' => esc_html__('Billing Countries', 'shipflex'),
			),

			'billing_shipping:separator' => array(
				'priority' => 100,
				'type' => 'separator',
				'label' => '------------------------'
			),

			'billing_shipping:shipping_cities' => array(
				'priority' => 200,
				'model_key' => 'shipping_cities',
				'template' => array($this, 'billing_shipping_cities'),
				'label' => esc_html__('Shipping Cities', 'shipflex'),
				'validate_callback' => array($this, 'validate_billing_shipping'),
			),

			'billing_shipping:shipping_states' => array(
				'priority' => 210,
				'default_value' => array(),
				'model_key' => 'shipping_states',
				'template' => array($this, 'billing_shipping_states'),
				'validate_callback' => array($this, 'validate_billing_shipping'),
				'label' => esc_html__('Shipping States', 'shipflex'),
				'extra_settings' => array(
					'placeholder' => esc_html__('Shipping States', 'shipflex')
				)
			),

			'billing_shipping:shipping_countries' => array(
				'priority' => 220,
				'default_value' => array(),
				'model_key' => 'shipping_countries',
				'template' => array($this, 'billing_shipping_country'),
				'validate_callback' => array($this, 'validate_billing_shipping'),
				'label' => esc_html__('Shipping Countries', 'shipflex'),
			),
		));

		return $condition_types;
	}

	/**
	 * Match Billing & Shipping condition
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function validate_billing_shipping($matched, $condition) {
		$operator = 'any_in_list';
		if (!empty($condition['billing_shipping_operator'])) {
			$operator = $condition['billing_shipping_operator'];
		}

		if ('billing_shipping:billing_cities' === $condition['type'] || 'billing_shipping:shipping_cities' === $condition['type']) {
			$cities = $condition['billing_cities'] ?? '';
			$customer_city = strtolower(WC()->customer->get_billing_city());

			if ('shipping:city' === $condition['type']) {
				$cities = $condition['shipping_cities'] ?? '';
				$customer_city = strtolower(WC()->customer->get_shipping_city());
			}

			$cities = Utils::comma_separator_to_array($cities);
			if ('any_in_list' === $operator) {
				return in_array($customer_city, $cities);
			}

			if ('not_in_list' === $operator) {
				return !in_array($customer_city, $cities);
			}
		}

		if ('billing_shipping:billing_states' === $condition['type'] || 'billing_shipping:shipping_states' === $condition['type']) {
			$model_key = 'billing:billing_states' === $condition['type'] ? 'billing_states' : 'shipping_states';

			$states = array();
			if (isset($condition[$model_key]) && is_array($condition[$model_key])) {
				$states = $condition[$model_key];
			}

			$customer_state = WC()->customer->get_billing_state();
			if ('shipping:states' === $condition['type']) {
				$customer_state = WC()->customer->get_shipping_state();
			}

			if ('any_in_list' === $operator) {
				return in_array($customer_state, $states);
			}

			if ('not_in_list' === $operator) {
				return !in_array($customer_state, $states);
			}
		}

		if ('billing_shipping:billing_zipcodes' === $condition['type']) {
			$zipcodes = $condition['billing_zipcodes'] ?? '';
			$customer_postcode = strtolower(WC()->customer->get_billing_postcode());

			$matched = Main::get_instance()->get_matched_postal_codes($customer_postcode, $zipcodes);
			if ('any_in_list' === $operator) {
				return count($matched) > 0;
			}

			if ('not_in_list' === $operator) {
				return count($matched) === 0;
			}
		}

		if ('billing_shipping:billing_countries' === $condition['type'] || 'billing_shipping:shipping_countries' === $condition['type']) {
			$countries_model_key = 'billing_countries';
			if ('billing_shipping:shipping_countries' === $condition['type']) {
				$countries_model_key = 'shipping_countries';
			}

			$countries = isset($condition[$countries_model_key]) && is_array($condition[$countries_model_key]) ? $condition[$countries_model_key] : array();

			$customer_country = WC()->customer->get_shipping_country();
			if ('billing_shipping:billing_countries' === $condition['type']) {
				$customer_country = WC()->customer->get_billing_country();
			}

			if ('any_in_list' === $operator) {
				return in_array($customer_country, $countries);
			}

			if ('not_in_list' === $operator) {
				return !in_array($customer_country, $countries);
			}
		}

		return apply_filters(Utils::get_hook_name('condition', 'billing-shipping', 'matched'), $matched, $condition);
	}

	/**
	 * Add city template of condition
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function billing_shipping_cities($condition_settings, $type_key) {
		$model_key = !empty($condition_settings['model_key']) ? $condition_settings['model_key'] : 'billing_cities'; ?>
		<template v-if="type == '<?php echo esc_attr($type_key) ?>'">
			<select v-model="billing_shipping_operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>
			<input v-model="<?php echo esc_attr($model_key) ?>" style="width: 400px;" type="text" placeholder="<?php esc_attr_e('Example: Chicago, New York', 'shipflex'); ?>">
		</template>
	<?php
	}

	/**
	 * Add state of billing and shipping template of condition
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function billing_shipping_states($condition_settings, $type_key) {
		$model_key = !empty($condition_settings['model_key']) ? $condition_settings['model_key'] : 'billing_states';
		$placeholder = !empty($condition_settings['extra_settings']['placeholder']) ? $condition_settings['extra_settings']['placeholder'] : ''; ?>
		<template v-if="type == '<?php echo esc_attr($type_key) ?>'">
			<select v-model="billing_shipping_operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<select2-dropdown
				type="states"
				:initial-value="<?php echo esc_attr($model_key) ?>"
				@update="(value) => <?php echo esc_attr($model_key) ?> = value"
				placeholder="<?php echo esc_attr($placeholder) ?>"></select2-dropdown>
		</template>
	<?php
	}

	/**
	 * Add zipcode template of billing
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function billing_zipcodes_template($condition_settings, $type_key) {
		$model_key = !empty($condition_settings['model_key']) ? $condition_settings['model_key'] : 'billing_zipcodes'; ?>
		<template v-if="type == '<?php echo esc_attr($type_key) ?>'">
			<select>
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<input v-model="<?php echo esc_attr($model_key) ?>" style="width: 400px;" type="text" placeholder="<?php esc_attr_e('Example: 38632, 38???, 38*, T3B 0N3, T3B ???, T3B*', 'shipflex'); ?>">
			<div class="field-note" style="margin-top: 0;" v-if="type == 'billing_shipping:billing_zipcodes'">
				<?php
				printf(
					esc_html__('Enter one or more ZIP/postal codes separated by commas. Wildcards (* and ?) are supported. %s.', 'shipflex'),
					'<strong>' .  esc_html__('Example: T3B 0N3, T3B ???, T3B*', 'shipflex') . '</strong>'
				) ?>
			</div>
		</template>
	<?php
	}

	/**
	 * Add country template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function billing_shipping_country($condition_settings, $type_key) {
		$model_key = !empty($condition_settings['model_key']) ? $condition_settings['model_key'] : 'billing_countries'; ?>
		<template v-if="type == '<?php echo esc_attr($type_key) ?>'">
			<select v-model="billing_shipping_operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<select2-dropdown
				type="countries"
				:initial-value="<?php echo esc_attr($model_key) ?>"
				@update="(value) => <?php echo esc_attr($model_key) ?> = value"
				placeholder="<?php esc_html_e('Choose countries', 'shipflex'); ?>">
			</select2-dropdown>
		</template>
<?php
	}
}

new Billing_Shipping();

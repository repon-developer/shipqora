<?php

namespace ShipQora\Condition;

use ShipQora\Utils;

if (!defined('ABSPATH')) {
	exit;
}

final class Billing_Shipping {

	/**
	 * Hold condtion group id
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	public $group_id = 'billing_shipping';

	/**
	 * Condition models
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_name() {
		return esc_html__('Billing & Shipping', 'shipqora');
	}

	/**
	 * Get model keys of this group
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_model_keys() {
		return array(
			'billing_shipping_operator' => 'any_in_list',
		);
	}

	/**
	 * Register condition types
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function register_conditions($main_object) {
		$main_object->add_condition_types('billing_cities', array(
			'priority' => 10,
			'default_value' => array(),
			'model_key' => 'billing_cities',
			'template' => array($this, 'billing_shipping_cities'),
			'label' => esc_html__('Billing Cities', 'shipqora'),
			'validate_callback' => array($this, 'validate_billing_shipping'),
		));

		$main_object->add_condition_types('billing_states', array(
			'priority' => 20,
			'default_value' => array(),
			'model_key' => 'billing_states',
			'template' => array($this, 'billing_shipping_states'),
			'label' => esc_html__('Billing States', 'shipqora'),
			'placeholder' => esc_html__('Billing States', 'shipqora'),
			'validate_callback' => array($this, 'validate_billing_shipping'),
		));

		$main_object->add_condition_types('billing_postal_codes', array(
			'priority' => 30,
			'model_key' => 'billing_postal_codes',
			'template' => array($this, 'postal_code_template'),
			'validate_callback' => array($this, 'validate_billing_shipping'),
			'label' => esc_html__('Billing Postal Codes', 'shipqora'),
		));

		$main_object->add_condition_types('billing_countries', array(
			'priority' => 40,
			'default_value' => array(),
			'model_key' => 'billing_countries',
			'template' => array($this, 'billing_shipping_country'),
			'label' => esc_html__('Billing Countries', 'shipqora'),
			'validate_callback' => array($this, 'validate_billing_shipping'),
		));

		$main_object->add_condition_types('shipping_cities', array(
			'priority' => 200,
			'model_key' => 'shipping_cities',
			'label' => esc_html__('Shipping Cities', 'shipqora'),
			'template' => array($this, 'billing_shipping_cities'),
			'validate_callback' => array($this, 'validate_billing_shipping'),
		));

		$main_object->add_condition_types('shipping_states', array(
			'priority' => 210,
			'default_value' => array(),
			'model_key' => 'shipping_states',
			'label' => esc_html__('Shipping States', 'shipqora'),
			'template' => array($this, 'billing_shipping_states'),
			'placeholder' => esc_html__('Shipping States', 'shipqora'),
			'validate_callback' => array($this, 'validate_billing_shipping'),
		));

		$main_object->add_condition_types('shipping_states', array(
			'priority' => 230,
			'default_value' => array(),
			'model_key' => 'shipping_countries',
			'template' => array($this, 'billing_shipping_country'),
			'validate_callback' => array($this, 'validate_billing_shipping'),
			'label' => esc_html__('Shipping Countries', 'shipqora'),
		));
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

			$matched_zipcodes = Main::get_instance()->get_matched_postal_codes($customer_postcode, $zipcodes);
			if ('any_in_list' === $operator) {
				return count($matched_zipcodes) > 0;
			}

			if ('not_in_list' === $operator) {
				return count($matched_zipcodes) === 0;
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

		return $matched;
	}

	/**
	 * Add city template of condition
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function billing_shipping_cities($condition) { ?>
		<template v-if="type == '<?php echo esc_attr($condition->get_id()) ?>'">
			<select v-model="billing_shipping_operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>
			<input v-model="<?php echo esc_attr($condition->get_model_key()) ?>" style="width: 400px;" type="text" placeholder="<?php esc_attr_e('Example: Chicago, New York', 'shipqora'); ?>">
		</template>
	<?php
	}

	/**
	 * Add state of billing and shipping template of condition
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function billing_shipping_states($condition) { ?>
		<template v-if="type == '<?php echo esc_attr($condition->get_id()) ?>'">
			<select v-model="billing_shipping_operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<select2-dropdown
				type="states"
				placeholder="<?php echo esc_attr($condition->get_placeholder()) ?>"
				:initial-value="<?php echo esc_attr($condition->get_model_key()) ?>"
				@update="(value) => <?php echo esc_attr($condition->get_model_key()) ?> = value">
			</select2-dropdown>
		</template>
	<?php
	}

	/**
	 * Add postal code template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function postal_code_template($condition) { ?>
		<template v-if="type == '<?php echo esc_attr($condition->get_id()) ?>'">
			<select>
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<input v-model="<?php echo esc_attr($condition->get_model_key()) ?>" style="width: 400px;" type="text" placeholder="<?php esc_attr_e('Example: 38632, 38???, 38*, T3B 0N3, T3B ???, T3B*', 'shipqora'); ?>">
			<div class="field-note" style="margin-top: 0;">
				<?php
				printf(
					/* translators: %s: Postal Code guideline */
					esc_html__('Enter one or more ZIP/postal codes separated by commas. Wildcards (* and ?) are supported. %s.', 'shipqora'),
					'<strong>' .  esc_html__('Example: T3B 0N3, T3B ???, T3B*', 'shipqora') . '</strong>'
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
	public function billing_shipping_country($condition) { ?>
		<template v-if="type == '<?php echo esc_attr($condition->get_id()) ?>'">
			<select v-model="billing_shipping_operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<select2-dropdown
				type="countries"
				placeholder="<?php esc_html_e('Choose countries', 'shipqora'); ?>"
				:initial-value="<?php echo esc_attr($condition->get_model_key()) ?>"
				@update="(value) => <?php echo esc_attr($condition->get_model_key()) ?> = value">
			</select2-dropdown>
		</template>
<?php
	}
}

Main::register_condition_group(Billing_Shipping::class);

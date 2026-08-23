<?php

namespace ShipQora_WooCommerce\Condition;

use ShipQora_WooCommerce\Utils;

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
		return esc_html__('Billing & Shipping', 'shipqora-woocommerce');
	}

	/**
	 * Group Priority
	 * 
	 * @since 1.0.0
	 * @return float
	 */
	public function get_priority() {
		return 100;
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
			'label' => esc_html__('Billing Cities', 'shipqora-woocommerce'),
			'validate_callback' => array($this, 'validate_billing_shipping'),
		));

		$main_object->add_condition_types('billing_states', array(
			'priority' => 20,
			'default_value' => array(),
			'model_key' => 'billing_states',
			'template' => array($this, 'billing_shipping_states'),
			'label' => esc_html__('Billing States', 'shipqora-woocommerce'),
			'placeholder' => esc_html__('Billing States', 'shipqora-woocommerce'),
			'validate_callback' => array($this, 'validate_billing_shipping'),
		));

		$main_object->add_condition_types('billing_postal_codes', array(
			'priority' => 30,
			'model_key' => 'billing_postal_codes',
			'template' => array($this, 'postal_code_template'),
			'validate_callback' => array($this, 'validate_billing_shipping'),
			'label' => esc_html__('Billing Postal Codes', 'shipqora-woocommerce'),
		));

		$main_object->add_condition_types('billing_countries', array(
			'priority' => 40,
			'use_separator' => true,
			'default_value' => array(),
			'model_key' => 'billing_countries',
			'template' => array($this, 'billing_shipping_country'),
			'label' => esc_html__('Billing Countries', 'shipqora-woocommerce'),
			'validate_callback' => array($this, 'validate_billing_shipping'),
		));

		$main_object->add_condition_types('shipping_cities', array(
			'priority' => 200,
			'model_key' => 'shipping_cities',
			'label' => esc_html__('Shipping Cities', 'shipqora-woocommerce'),
			'template' => array($this, 'billing_shipping_cities'),
			'validate_callback' => array($this, 'validate_billing_shipping'),
		));

		$main_object->add_condition_types('shipping_states', array(
			'priority' => 210,
			'default_value' => array(),
			'model_key' => 'shipping_states',
			'label' => esc_html__('Shipping States', 'shipqora-woocommerce'),
			'template' => array($this, 'billing_shipping_states'),
			'placeholder' => esc_html__('Shipping States', 'shipqora-woocommerce'),
			'validate_callback' => array($this, 'validate_billing_shipping'),
		));

		$main_object->add_condition_types('shipping_countries', array(
			'priority' => 230,
			'default_value' => array(),
			'model_key' => 'shipping_countries',
			'template' => array($this, 'billing_shipping_country'),
			'validate_callback' => array($this, 'validate_billing_shipping'),
			'label' => esc_html__('Shipping Countries', 'shipqora-woocommerce'),
		));
	}

	/**
	 * Match Billing & Shipping condition
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function validate_billing_shipping($condition) {
		$condition_type_id = $condition->get_id();
		$current_value = $condition->get_value();
		$operator = $condition->get_value('billing_shipping_operator');

		if ('billing_cities' === $condition_type_id || 'shipping_cities' === $condition_type_id) {
			$customer_city = strtolower(WC()->customer->get_billing_city());
			if ('shipping_cities' === $condition_type_id) {
				$customer_city = strtolower(WC()->customer->get_shipping_city());
			}

			$cities = Utils::comma_separator_to_array($current_value);
			if ('any_in_list' === $operator) {
				return in_array($customer_city, $cities);
			}

			if ('not_in_list' === $operator) {
				return !in_array($customer_city, $cities);
			}
		}

		if ('billing_states' === $condition_type_id || 'shipping_states' === $condition_type_id) {
			$customer_state = WC()->customer->get_billing_state();
			if ('shipping_states' === $condition_type_id) {
				$customer_state = WC()->customer->get_shipping_state();
			}

			if (!is_array($current_value)) {
				$current_value = array();
			}

			if ('any_in_list' === $operator) {
				return in_array($customer_state, $current_value);
			}

			if ('not_in_list' === $operator) {
				return !in_array($customer_state, $current_value);
			}
		}

		if ('billing_postal_codes' === $condition_type_id) {
			$customer_postcode = strtolower(WC()->customer->get_billing_postcode());

			$matched_postal_codes = $condition->get_matched_postal_codes($customer_postcode, $current_value);
			if ('any_in_list' === $operator) {
				return count($matched_postal_codes) > 0;
			}

			if ('not_in_list' === $operator) {
				return count($matched_postal_codes) === 0;
			}
		}

		if ('billing_countries' === $condition_type_id || 'shipping_countries' === $condition_type_id) {
			if (!is_array($current_value)) {
				$current_value = array();
			}

			$customer_country = WC()->customer->get_shipping_country();
			if ('billing_countries' === $condition_type_id) {
				$customer_country = WC()->customer->get_billing_country();
			}

			if ('any_in_list' === $operator) {
				return in_array($customer_country, $current_value);
			}

			if ('not_in_list' === $operator) {
				return !in_array($customer_country, $current_value);
			}
		}

		return false;
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
			<input v-model="<?php echo esc_attr($condition->get_model_key()) ?>" style="width: 400px;" type="text" placeholder="<?php esc_attr_e('Example: Chicago, New York', 'shipqora-woocommerce'); ?>">
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

			<input v-model="<?php echo esc_attr($condition->get_model_key()) ?>" style="width: 400px;" type="text" placeholder="<?php esc_attr_e('Example: 38632, 38???, 38*, T3B 0N3, T3B ???, T3B*', 'shipqora-woocommerce'); ?>">
			<div class="field-note" style="margin-top: 0;">
				<?php
				printf(
					/* translators: %s: Postal Code guideline */
					esc_html__('Enter one or more ZIP/postal codes separated by commas. Wildcards (* and ?) are supported. %s.', 'shipqora-woocommerce'),
					'<strong>' .  esc_html__('Example: T3B 0N3, T3B ???, T3B*', 'shipqora-woocommerce') . '</strong>'
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
				placeholder="<?php esc_html_e('Choose countries', 'shipqora-woocommerce'); ?>"
				:initial-value="<?php echo esc_attr($condition->get_model_key()) ?>"
				@update="(value) => <?php echo esc_attr($condition->get_model_key()) ?> = value">
			</select2-dropdown>
		</template>
<?php
	}
}

Main::register_condition_group(Billing_Shipping::class);

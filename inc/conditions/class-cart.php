<?php

namespace ShipQora\Condition;

use ShipQora\Utils;
use ShipQora\Condition;
use ShipQora\Cart_Total;
use ShipQora\Component\Cart_Option;

if (!defined('ABSPATH')) {
	exit;
}


final class Cart {

	/**
	 * Hold condtion group id
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	public $group_id = 'cart';

	/**
	 * Condition models
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_name() {
		return esc_html__('Cart', 'shipqora');
	}

	/**
	 * Group Priority
	 * 
	 * @since 1.0.0
	 * @return float
	 */
	public function get_priority() {
		return 10;
	}

	/**
	 * Get model keys of this group
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_model_keys() {
		return array(
			'value' => '',
			'value2' => '',
			'cart_operator' => 'greater_than',
			'cart_cart_option' => array()
		);
	}

	/**
	 * Register condition types
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function register_conditions($main_object) {
		$weight_option_text = sprintf(
			/* translators: %s: weight unit of woocommerce */
			esc_html__('Total weight (%s)', 'shipqora'),
			get_option('woocommerce_weight_unit', 'kg')
		);

		$dimension_option_text = sprintf(
			/* translators: %s: volume unit of woocommerce */
			esc_html__('Total volume (%s)', 'shipqora'),
			get_option('woocommerce_dimension_unit', 'cm')
		);

		$main_object->add_condition_types('cart_subtotal', array(
			'priority' => 10,
			'template' => array($this, 'cart_common_templates'),
			'validate_callback' => array($this, 'validate_condition'),
			'label' => esc_html__('Subtotal', 'shipqora'),
		));

		$main_object->add_condition_types('cart_total_quantity', array(
			'priority' => 20,
			'label' => esc_html__('Total quantity', 'shipqora'),
			'template' => array($this, 'cart_common_templates'),
			'validate_callback' => array($this, 'validate_condition'),
		));

		$main_object->add_condition_types('cart_total_weight', array(
			'priority' => 30,
			'label' => $weight_option_text,
			'template' => array($this, 'cart_common_templates'),
			'validate_callback' => array($this, 'validate_condition'),
		));

		$main_object->add_condition_types('cart_total_volume', array(
			'priority' => 40,
			'label' => $dimension_option_text,
			'template' => array($this, 'cart_common_templates'),
			'validate_callback' => array($this, 'validate_condition'),
		));
	}

	/**
	 * Validate cart condition
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function validate_condition($condition) {
		$operator = $condition->get_value('cart_operator');
		$value_one = floatval($condition->get_value('value'));
		$value_two = floatval($condition->get_value('value2'));

		$cart_option = $condition->apply_filters(new Cart_Option($condition->get_value('cart_cart_option')), 'cart-option');
		$type_total_keys = array(
			'cart_subtotal' => null,
			'cart_total_quantity' => 'quantity',
			'cart_total_weight' => 'weight',
			'cart_total_volume' => 'volume',
		);

		$compare_value = 0.00;
		if (array_key_exists($condition->get_id(), $type_total_keys)) {
			$cart_total = new Cart_Total($cart_option->get_cart_items_keys());
			$compare_value = $cart_total->get_total($type_total_keys[$condition->get_id()]);
		}

		if ($compare_value <= 0) {
			return false;
		}

		if ('equal_to' === $operator) {
			return $compare_value == $value_one;
		}

		if ('less_than' === $operator) {
			return $compare_value < $value_one;
		}

		if ('less_than_or_equal' === $operator) {
			return $compare_value <= $value_one;
		}

		if ('greater_than_or_equal' === $operator) {
			return $compare_value >= $value_one;
		}

		if ('greater_than' === $operator) {
			return $compare_value > $value_one;
		}

		if ('between' === $operator) {
			return $compare_value >= $value_one && $compare_value <= $value_two;
		}

		return false;
	}

	/**
	 * Add common template of cart
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function cart_common_templates() { ?>
		<template v-if="['cart_subtotal', 'cart_total_quantity', 'cart_total_weight', 'cart_total_volume'].includes(type)">
			<select v-model="cart_operator">
				<?php Utils::get_operators_options(array('equal_to', 'less_than', 'less_than_or_equal', 'greater_than', 'greater_than_or_equal', 'between')); ?>
			</select>

			<input type="number" v-model="value" placeholder="<?php echo '0.00'; ?>" step="0.001">
			<input v-if="cart_operator == 'between'" type="number" v-model="value2" :min="value" placeholder="<?php echo '0.00'; ?>" step="0.001">

			<cart-option
				:cart-option-data="cart_cart_option"
				@on-update="(value) => cart_cart_option = value">
				<template v-slot:based-on-first-option>
					<option value=""><?php esc_html_e('of the cart items', 'shipqora') ?></option>
				</template>
			</cart-option>
		</template>
<?php
	}
}

Main::register_condition_group(Cart::class);

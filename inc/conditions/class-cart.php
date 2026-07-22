<?php

namespace ShipFlex\Condition;

use ShipFlex\Utils;
use ShipFlex\Cart_Total;
use ShipFlex\Component\Cart_Option;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Cart Condition class
 */
final class Cart {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter('shipflex/condition/models', array($this, 'condition_models'));
		add_filter('shipflex/condition/types', array($this, 'add_condition_types'));
	}

	/**
	 * Condition models
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function condition_models($values) {
		return array_merge($values, array(
			'value' => '',
			'value2' => '',
			'cart_operator' => 'greater_than',
			'cart_cart_option' => array()
		));
	}

	/**
	 * Add condition types
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function add_condition_types($condition_types) {
		$weight_option_text = sprintf(
			/* translators: %s: weight unit of woocommerce */
			esc_html__('Total weight (%s)', 'shipflex'),
			get_option('woocommerce_weight_unit', 'kg')
		);

		$dimension_option_text = sprintf(
			/* translators: %s: volume unit of woocommerce */
			esc_html__('Total volume (%s)', 'shipflex'),
			get_option('woocommerce_dimension_unit', 'cm')
		);

		$condition_types = array_merge($condition_types, array(
			'cart:subtotal' => array(
				'priority' => 10,
				'template' => array($this, 'cart_common_templates'),
				'validate_callback' => array($this, 'validate_cart_condition'),
				'label' => esc_html__('Subtotal', 'shipflex'),
			),

			'cart:total_quantity' => array(
				'priority' => 15,
				'template' => array($this, 'cart_common_templates'),
				'validate_callback' => array($this, 'validate_cart_condition'),
				'label' => esc_html__('Total quantity', 'shipflex'),
			),

			'cart:total_weight' => array(
				'priority' => 20,
				'label' => $weight_option_text,
				'template' => array($this, 'cart_common_templates'),
				'validate_callback' => array($this, 'validate_cart_condition'),
			),

			'cart:total_volume' => array(
				'priority' => 25,
				'label' => $dimension_option_text,
				'template' => array($this, 'cart_common_templates'),
				'validate_callback' => array($this, 'validate_cart_condition'),
			),
		));

		return $condition_types;
	}

	/**
	 * Cart related condition filters
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function validate_cart_condition($matched, $condition) {
		if (!in_array($condition['type'], array('cart:subtotal', 'cart:total_quantity', 'cart:total_weight', 'cart:total_volume'))) {
			return $matched;
		}

		$operator = $condition['cart_operator'];
		$value_one = floatval($condition['value']);
		$value_two = isset($condition['value2']) ? floatval($condition['value2']) : 0.00;
		$cart_option_data = isset($condition['cart_cart_option']) ? $condition['cart_cart_option'] : null;

		$cart_option = new Cart_Option($cart_option_data);

		$type_total_keys = array(
			'cart:subtotal' => null,
			'cart:total_quantity' => 'quantity',
			'cart:total_weight' => 'weight',
			'cart:total_volume' => 'volume',
		);

		$compare_value = 0.00;
		if (array_key_exists($condition['type'], $type_total_keys)) {
			$cart_total = new Cart_Total($cart_option->get_matched_cart_items_keys());
			$compare_value = $cart_total->get_total($type_total_keys[$condition['type']]);
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

		return $matched;
	}

	/**
	 * Add common template of cart
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function cart_common_templates() { ?>
		<template v-if="['cart:subtotal', 'cart:total_quantity', 'cart:total_weight', 'cart:total_volume'].includes(type)">
			<select v-model="cart_operator">
				<?php Utils::get_operators_options(array('equal_to', 'less_than', 'less_than_or_equal', 'greater_than', 'greater_than_or_equal', 'between')); ?>
			</select>

			<input type="number" v-model="value" placeholder="<?php echo '0.00'; ?>" step="0.001">
			<input v-if="cart_operator == 'between'" type="number" v-model="value2" :min="value" placeholder="<?php echo '0.00'; ?>" step="0.001">

			<cart-option
				:cart-option-data="cart_cart_option"
				@on-update="(value) => cart_cart_option = value">
				<template v-slot:based-on-first-option>
					<option value=""><?php esc_html_e('of the cart items', 'shipflex') ?></option>
				</template>
			</cart-option>
		</template>
<?php
	}
}


new Cart();

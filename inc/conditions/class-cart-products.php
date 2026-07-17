<?php

namespace ShipFlex\Condition;

use ShipFlex\Utils;
use ShipFlex\Cart_Total;
use ShipFlex\Session_Reward;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Cart products condition class
 */
final class Cart_Products {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter('shipflex/condition/types', array($this, 'add_condition_types'));
	}

	/**
	 * Add condition types of cart products
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function add_condition_types($types) {
		$types['cart_products:products'] = array(
			'priority' => 1,
			'default_value' => array(),
			'model_key' => 'cart_products_products',
			'template' => array($this, 'products_template'),
			'label' => esc_html__('Products', 'shipflex'),
			'validate_callback' => array($this, 'validate_condition'),
		);

		$types['cart_products:variation_products'] = array(
			'priority' => 2,
			'default_value' => array(),
			'model_key' => 'cart_products_variation_products',
			'template' => array($this, 'variation_product_template'),
			'label' => esc_html__('Variation products', 'shipflex'),
			'validate_callback' => array($this, 'validate_condition'),
		);

		foreach (Utils::get_product_taxonomies() as $tax_key => $taxonomy) {
			$types['cart_products:' . $tax_key] = wp_parse_args($taxonomy, array(
				'group' => 'cart_products',
				'default_value' => array(),
				'template' => array($this, 'taxonomy_templates'),
				'model_key' => 'cart_products_' . $taxonomy['model'],
				'validate_callback' => array($this, 'validate_condition'),
			));
		}

		return $types;
	}

	/**
	 * Validate condition
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function validate_condition($matched, $condition) {
		$session_reward = Session_Reward::get_instance();
		if ($session_reward->is_context('product')) {
			return true;
		}

		$cart_total = new Cart_Total();
		$cart_items = $cart_total->get_cart_items();

		$model_key = null;
		$current_cart_values = array();
		if ('cart_products:products' === $condition['type']) {
			$model_key = 'cart_products_products';
			$current_cart_values = array_map(fn($cart_item) => $cart_item['product_id'], $cart_items);
		}

		if ('cart_products:variation_products' === $condition['type']) {
			$model_key = 'cart_products_variation_products';
			$current_cart_values = array_map(fn($cart_item) => $cart_item['variation_id'], $cart_items);
			$current_cart_values = array_unique(array_filter($current_cart_values));
		}

		foreach (Utils::get_product_taxonomies() as $tax_key => $taxonomy) {
			if ('cart_products:' . $tax_key === $condition['type']) {
				$model_key = 'cart_products_' . $taxonomy['model'];
				$current_cart_values = $cart_total->get_terms($tax_key);
				break;
			}
		}

		if (!isset($condition[$model_key]) || !is_array($condition[$model_key])) {
			return false;
		}

		$cart_products_operator = 'any_in_list';
		if (!empty($condition['cart_products_operator'])) {
			$cart_products_operator = $condition['cart_products_operator'];
		}

		$matched_items = array_intersect($current_cart_values, $condition[$model_key]);
		if ('any_in_list' == $cart_products_operator) {
			return count($matched_items) > 0;
		}

		if ('all_in_list' == $cart_products_operator) {
			return count($condition[$model_key]) === count($matched_items);
		}

		if ('not_in_list' == $cart_products_operator) {
			return count($matched_items) === 0;
		}

		return $matched;
	}


	/**
	 * Products template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function products_template() { ?>
		<template v-if="type == 'cart_products:products'">
			<select v-model="cart_products_operator">
				<?php Utils::get_operators_options(array('any_in_list', 'all_in_list', 'not_in_list')); ?>
			</select>

			<select2-dropdown
				type="products"
				:initial-value="cart_products_products"
				@update="(value) => cart_products_products = value"
				placeholder="<?php esc_html_e('Products', 'shipflex'); ?>">
			</select2-dropdown>
		</template>
	<?php
	}

	/**
	 * Variation products template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function variation_product_template() { ?>
		<template v-if="type == 'cart_products:variation_products'">
			<select v-model="cart_products_operator">
				<?php Utils::get_operators_options(array('any_in_list', 'all_in_list', 'not_in_list')); ?>
			</select>

			<select2-dropdown
				type="variation_products"
				:initial-value="cart_products_variation_products"
				@update="(value) => cart_products_variation_products = value"
				placeholder="<?php esc_html_e('Variation products', 'shipflex'); ?>">
			</select2-dropdown>
		</template>
	<?php
	}

	/**
	 * Add templates for taxonomy
	 * 
	 * @since 1.0.4
	 * @return void
	 */
	public function taxonomy_templates($settings, $field_type_key) {?>
		<template v-if="type == '<?php echo esc_attr($field_type_key) ?>'">
			<select v-model="cart_products_operator">
				<?php Utils::get_operators_options(array('any_in_list', 'all_in_list', 'not_in_list')); ?>
			</select>

			<select2-dropdown
				type="<?php echo esc_attr($settings['type']); ?>"
				placeholder="<?php echo esc_attr($settings['label']); ?>"
				:initial-value="cart_products_<?php echo esc_attr($settings['model']); ?>"
				@update="(value) => set_value(value, 'cart_products_<?php echo esc_attr($settings['model']); ?>')">
			</select2-dropdown>
		</template>
<?php
	}
}

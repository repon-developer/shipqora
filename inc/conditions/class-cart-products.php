<?php

namespace ShipQora\Condition;

use ShipQora\Utils;
use ShipQora\Cart_Total;

if (!defined('ABSPATH')) {
	exit;
}

final class Cart_Products {

	/**
	 * Hold condtion group id
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	public $group_id = 'cart_products';

	/**
	 * Condition models
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_name() {
		return esc_html__('Cart Products', 'shipqora');
	}

	/**
	 * Get model keys of this group
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_model_keys() {
		return array('cart_products_operator' => 'any_in_list');
	}

	/**
	 * Register condition types
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function register_conditions($main_object) {
		foreach (Utils::get_product_taxonomies() as $tax_key => $taxonomy) {
			$main_object->add_condition_types('cart_products_' . $tax_key, wp_parse_args(array(
				'default_value' => array(),
				'data_type' => $taxonomy['type'],
				'template' => array($this, 'taxonomy_templates'),
				'model_key' => 'cart_products_' . $taxonomy['model'],
				'validate_callback' => array($this, 'validate_condition'),
			), $taxonomy));
		}
	}

	/**
	 * Validate condition
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function validate_condition($matched, $condition) {
		$cart_total = new Cart_Total();
		$cart_items = $cart_total->get_cart_items();

		$model_key = null;
		$current_cart_values = array();
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
				placeholder="<?php esc_html_e('Products', 'shipqora'); ?>">
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
				placeholder="<?php esc_html_e('Variation products', 'shipqora'); ?>">
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
	public function taxonomy_templates($condition) { ?>
		<template v-if="type == '<?php echo esc_attr($condition->get_id()) ?>'">
			<select v-model="cart_products_operator">
				<?php Utils::get_operators_options(array('any_in_list', 'all_in_list', 'not_in_list')); ?>
			</select>

			<select2-dropdown
				type="<?php echo esc_attr($condition->get_data_type()); ?>"
				placeholder="<?php echo esc_attr($condition->get_placeholder()); ?>"
				:initial-value="<?php echo esc_attr($condition->get_model_key()); ?>"
				@update="(value) => set_value(value, '<?php echo esc_attr($condition->get_model_key()); ?>')">
			</select2-dropdown>
		</template>
<?php
	}
}

Main::register_condition_group(Cart_Products::class);

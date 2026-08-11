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
	 * Group Priority
	 * 
	 * @since 1.0.0
	 * @return float
	 */
	public function get_priority() {
		return 20;
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
	public function validate_condition($condition) {
		$model_values = $condition->get_value($condition->get_model_key());
		if (empty($model_values) || !is_array($model_values)) {
			return false;
		}

		$data_type = $condition->get_data_type();
		if (empty($data_type)) {
			$data_type = '';
		}

		$object_info = explode(':', $data_type);
		if (!isset($object_info[1])) {
			$object_info[1] = '';
		}

		$cart_total = new Cart_Total();

		$cart_object_ids = array();
		if ('taxonomy' == $object_info[0]) {
			$cart_object_ids = $cart_total->get_terms($object_info[1]);
		}

		$cart_object_ids = apply_filters(
			Utils::get_hook_name('condition', 'cart-products', 'validate', 'cart-object-ids'),
			$cart_object_ids,
			$condition,
			$cart_total
		);

		$matched_object_ids = array_intersect($model_values, $cart_object_ids);

		$operator = $condition->get_value('cart_products_operator');
		if ('any_in_list' == $operator) {
			return count($matched_object_ids) > 0;
		}

		if ('all_in_list' == $operator) {
			return count($model_values) === count($matched_object_ids);
		}

		if ('not_in_list' == $operator) {
			return count($matched_object_ids) === 0;
		}

		return false;
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

<?php

namespace ShipQora_WooCommerce\Component;

use ShipQora_WooCommerce\Utils;
use ShipQora_WooCommerce\Cart_Total;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Cart Option class
 */
final class Cart_Option {

	/**
	 * Hold all cart settings options
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	private static $hold_options = array();

	/**
	 * Get options settings
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_options() {
		if (!empty(self::$hold_options)) {
			return self::$hold_options;
		}

		$product_settings_options = array();
		foreach (Utils::get_product_taxonomies() as $tax_key => $taxonomy) {
			$product_settings_options['taxonomy:' . $tax_key] = $taxonomy;
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
		$product_settings_options = apply_filters(Utils::get_hook_name('cart-option', 'options'), $product_settings_options);
		self::$hold_options = Utils::priority_rearrange($product_settings_options);
		return self::$hold_options;
	}

	/**
	 * Implement require styles and scripts of cart option
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function enqueue_scripts($values, $source) {
		if (Utils::is_plugin_screen('rule-editor') && 'localize' == $source) {
			$cart_options = self::get_options();
			$values['cart_options'] = $cart_options;

			foreach ($cart_options as $type_key => $option) {
				if (!empty($option['model'])) {
					$values['cart_option_models'][$option['model']] = array();
				}
			}
		}

		return $values;
	}

	/**
	 * VueJS component of cart option
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function output_component() { ?>
		<template id="shipqora-woocommerce-cart-option-component">
			<select ref="cart_option_dropdown" v-model="based_on" data-once-modal="cart-option-advanced">
				<slot name="based-on-first-option"></slot>
				<option
					:key="option_value"
					v-for="(option, option_value) in options"
					:value="option_value">{{get_option_label(option_value)}}</option>
			</select>

			<select v-model="operator" v-if="based_on?.length">
				<option value="any_in_list"><?php esc_html_e('Any in list', 'shipqora-woocommerce') ?></option>
				<option value="all_in_list"><?php esc_html_e('All in list', 'shipqora-woocommerce') ?></option>
				<option value="not_in_list"><?php esc_html_e('Not in the list', 'shipqora-woocommerce') ?></option>
			</select>

			<template v-for="(option, option_value) in options" :key="'dropdown_' + option_value">
				<select2-dropdown
					:type="based_on"
					v-if="based_on == option_value"
					:placeholder="option?.placeholder"
					:initial-value="get_value(option?.model)"
					@update="(value) => set_value(value, option.model)">
				</select2-dropdown>
			</template>
		</template>
<?php
	}

	/**
	 * Setting of Based on
	 * 
	 * @var string
	 */
	public $based_on = 'of_the_cart';

	/**
	 * Hold operator
	 * 
	 * @var string
	 */
	public $operator = 'any_in_list';

	/**
	 * Hold object group like- post_type, taxonomy, etc
	 * 
	 * @var string
	 */
	public $object_group = '';

	/**
	 * Hold object name like registered post type name,  product_cat
	 * 
	 * @var string
	 */
	public $object_name = '';

	/**
	 * Hold model values
	 * 
	 * @var array
	 */
	public $model_values = [];

	/**
	 * Hold compare values of all in list operator
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	private $all_in_list_compare_values = array();

	/**
	 * Extra data
	 * 
	 * @var array
	 */
	public $extra_data = [];

	/**
	 * Constructor.
	 */
	public function __construct($options) {
		if (!is_array($options)) {
			return;
		}

		if (empty($options['based_on'])) {
			$options['based_on'] = 'of_the_cart';
		}

		$options = wp_parse_args($options, array('based_on' => 'of_the_cart', 'operator' => 'any_in_list'));
		foreach ($options as $model => $value) {
			if (!empty($model)) {
				$this->{$model} = $value;
			}
		}

		$object_info = explode(':', $this->based_on);
		if (!empty($object_info[0])) {
			$this->object_group = trim($object_info[0]);
		}

		if (!empty($object_info[1])) {
			$this->object_name = $object_info[1];
		}

		$this->set_model_values();
	}

	/**
	 * isset magic method
	 * 
	 * @since 1.0.0
	 * @param string $key
	 * @param boolean
	 */
	public function __isset($key) {
		return isset($this->extra_data[$key]);
	}

	/**
	 * Set magic method
	 * 
	 * @since 1.0.0
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set($key, $value) {
		$this->extra_data[$key] = $value;
	}

	/**
	 * Get magic method
	 * 
	 * @since 1.0.0
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key) {
		return isset($this->extra_data[$key]) ? $this->extra_data[$key] : null;
	}

	/**
	 * Check if supported based on
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function is_supported_based_on() {
		$supported_option_keys = array_keys(self::get_options());
		$supported_option_keys[] = 'of_the_cart';
		return in_array($this->based_on, $supported_option_keys);
	}

	/**
	 * Set model values
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function set_model_values() {
		if (!$this->is_supported_based_on()) {
			return;
		}

		$options = self::get_options();
		if (!isset($options[$this->based_on]['model'])) {
			return;
		}

		$model_key = $options[$this->based_on]['model'];
		if (!isset($this->{$model_key}) || !is_array($this->{$model_key})) {
			return;
		}

		$this->model_values = $this->{$model_key};
	}

	/**
	 * Get terms of product item
	 * 
	 * @since 1.0.0
	 * @param integer $product_id
	 * @param string $taxonomy
	 * @return array
	 */
	public function get_terms_of_product($product_id, $taxonomy) {
		$terms = get_the_terms($product_id, $taxonomy);
		if (empty($terms) || is_wp_error($terms)) {
			$terms = array();
		}

		return array_map(fn($term) => $term->term_id, $terms);
	}

	/**
	 * Check if value matched with model values
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function is_matched_model_values() {
		$all_cart_items_values = array();
		foreach (Cart_Total::get_cart_items() as $cart_item_key => $cart_item) {
			$cart_items_values = array();
			if ('taxonomy' === $this->object_group && !empty($this->object_name)) {
				$cart_items_values = $this->get_terms_of_product($cart_item['product_id'], $this->object_name);
			}

			$cart_items_values = apply_filters(
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
				Utils::get_hook_name('cart-option', 'is-matched-model-values', 'cart-items-values'),
				$cart_items_values,
				$cart_item,
				$this
			);

			$all_cart_items_values = array_merge($all_cart_items_values, $cart_items_values);
		}

		$all_cart_items_values = array_unique($all_cart_items_values);
		$matched = array_intersect($all_cart_items_values, $this->model_values);
		if ('any_in_list' === $this->operator) {
			return count($matched) > 0;
		}

		if ('not_in_list' === $this->operator) {
			return count($matched) === 0;
		}

		if ('all_in_list' === $this->operator) {
			return count($matched) === count($this->model_values);
		}

		return false;
	}


	/**
	 * Check if condition has provided product id
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function is_eligible_product($product_id, $variation_id = 0) {
		if (!$this->is_supported_based_on()) {
			return false;
		}

		$configured_options = self::get_options();

		$eligible_product = false;
		if ('of_the_cart' == $this->based_on) {
			$eligible_product = true;
		} else {
			$compare_values = array();
			if ('taxonomy' === $this->object_group && !empty($this->object_name)) {
				$terms = get_the_terms($product_id, $this->object_name);
				if (is_array($terms)) {
					$compare_values = array_map(fn($term) => $term->term_id, $terms);
				}
			}

			$compare_values = apply_filters(
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
				Utils::get_hook_name('cart-option', 'is-eligible-product', 'compare-values'),
				$compare_values,
				$product_id,
				$variation_id,
				$this
			);

			$matched_values = array_intersect($this->model_values, $compare_values);
			$matched_model_values = $this->is_matched_model_values();

			if ($matched_model_values && count($matched_values) > 0) {
				$eligible_product = true;
			}

			if (!$matched_model_values && 'not_in_list' == $this->operator) {
				$eligible_product = count($matched_values) === 0;
			}
		}

		return apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
			Utils::get_hook_name('cart-option', 'is-eligible-product'),
			$eligible_product,
			$product_id,
			$variation_id,
			$this
		);
	}

	/**
	 * Get matched cart items keys
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_cart_items_keys() {
		$matched_cart_items_keys = array();
		foreach (Cart_Total::get_cart_items() as $cart_item_key => $cart_item) {
			$eligible_product = $this->is_eligible_product($cart_item['product_id'], $cart_item['variation_id']);
			if ($eligible_product) {
				$matched_cart_items_keys[] = $cart_item_key;
			}
		}

		return $matched_cart_items_keys;
	}
}

add_filter('shipqora/admin_enqueue_scripts', array(Cart_Option::class, 'enqueue_scripts'), 10, 2);

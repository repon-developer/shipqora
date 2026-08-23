<?php

namespace ShipQora;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Cart class
 */
final class Cart_Total {

	/**
	 * Hold session based cart items
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	private static $hold_cart_items = array();

	/**
	 * Get cart items
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_cart_items() {
		if (!WC()->cart) {
			return array();
		}

		$cart_hash = 'no_hash';
		if (WC()->cart && method_exists(WC()->cart, 'get_cart_hash')) {
			$cart_hash = WC()->cart->get_cart_hash();
		}

		if (!isset(self::$hold_cart_items[$cart_hash])) {
			self::$hold_cart_items[$cart_hash] = array();
			foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
				self::$hold_cart_items[$cart_hash][$cart_item_key] = $cart_item;
			}
		}

		return self::$hold_cart_items[$cart_hash];
	}

	/**
	 * Get cart item total
	 * 
	 * @since 1.0.0
	 * @return float
	 */
	public static function get_cart_item_subtotal($cart_item) {
		$amounts = array($cart_item['line_subtotal'], $cart_item['line_subtotal_tax']);
		return round(array_sum($amounts));
	}

	/**
	 * Hold cart items keys for return values
	 * 
	 * @var array
	 */
	private $cart_items_keys = [];

	/**
	 * Constructor
	 */
	public function __construct($cart_items_keys = null) {
		if (!is_array($cart_items_keys)) {
			$cart_items_keys = array_keys(self::get_cart_items());
		}

		$this->set_cart_items_keys($cart_items_keys);
	}

	/**
	 * Set cart items keys
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function set_cart_items_keys($cart_items_keys) {
		if (is_array($cart_items_keys)) {
			$this->cart_items_keys = $cart_items_keys;
		}
	}

	/**
	 * Get total volume of a product
	 * 
	 * @since 1.0.0
	 * @return float
	 */
	public function get_volume_of_product($_product, $quantity = 1) {
		if ($_product->has_dimensions()) {
			return $_product->get_length() * $_product->get_width() * $_product->get_height() * $quantity;
		}

		return 0;
	}

	/**
	 * Get filtered cart items of cart total
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_filtered_cart_items() {
		$cart_items = self::get_cart_items();
		foreach ($cart_items as $cart_item_key => $cart_item) {
			if (!in_array($cart_item_key, $this->cart_items_keys)) {
				unset($cart_items[$cart_item_key]);
			}
		}

		return $cart_items;
	}

	/**
	 * Get keys based total
	 * 
	 * @since 1.0.0
	 * @param string $total_type - subtotal, quantity, weight, volume
	 * @return array
	 */
	public function get_keys_based_total($total_type = null) {
		$key_based_total = array();

		if (empty($total_type)) {
			$total_type = 'subtotal';
		}

		$cart_items = $this->get_filtered_cart_items();
		if ('subtotal' == $total_type) {
			foreach ($cart_items as $cart_item_key => $cart_item) {
				$key_based_total[$cart_item_key] = self::get_cart_item_subtotal($cart_item);
			}
		}

		if ('quantity' == $total_type) {
			foreach ($cart_items as $cart_item_key => $cart_item) {
				$key_based_total[$cart_item_key] = $cart_item['quantity'];
			}
		}

		if ('weight' == $total_type) {
			foreach ($cart_items as $cart_item_key => $cart_item) {
				if ($cart_item['data']->has_weight()) {
					$key_based_total[$cart_item_key] = $cart_item['data']->get_weight() * $cart_item['quantity'];
				}
			}
		}

		if ('volume' == $total_type) {
			foreach ($cart_items as $cart_item_key => $cart_item) {
				$key_based_total[$cart_item_key] = $this->get_volume_of_product($cart_item['data'], $cart_item['quantity']);
			}
		}

		return $key_based_total;
	}

	/**
	 * Get total of total type
	 * 
	 * @since 1.0.0
	 * @param string $total_type
	 * @return float|int
	 */
	public function get_total($total_type = null) {
		if (empty($total_type)) {
			$total_type = 'subtotal';
		}

		$total = floatval(array_sum($this->get_keys_based_total($total_type)));
		if ('quantity' == $total_type) {
			$total = (int) $total;
		}

		return $total;
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
	 * Get terms of taxonomy
	 * 
	 * @since 1.0.0
	 * @param string $taxonomy
	 * @return array
	 */
	public function get_cart_item_terms($taxonomy) {
		$cart_items_terms = array();
		foreach ($this->get_filtered_cart_items() as $cart_item_key => $cart_item) {
			$cart_items_terms[$cart_item_key] = $this->get_terms_of_product($cart_item['product_id'], $taxonomy);
		}

		return $cart_items_terms;
	}

	/**
	 * Get all terms of cart items
	 * 
	 * @since 1.0.0
	 * @param string $taxonomy
	 * @return array
	 */
	public function get_terms($taxonomy) {
		$cart_terms = array();
		foreach ($this->get_cart_item_terms($taxonomy) as $terms) {
			$cart_terms = array_merge($cart_terms, $terms);
		}

		return $cart_terms;
	}
}

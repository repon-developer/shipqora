<?php

namespace ShipFlex\Condition;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Main Condition class
 */
class Main {

	/**
	 * Hold the current instance of condition
	 * 
	 * @since 1.0.0
	 * @var Main
	 */
	private static $instance = null;

	/**
	 * Get instance of current class
	 * 
	 * @since 1.0.0
	 * @return Main
	 */
	public static function get_instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hold instance of condition groups
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	public $condition_groups = array();

	/**
	 * Hold all condition types
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	public $condition_types = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		require_once ShipFlex_PATH . 'inc/conditions/class-cart.php';
		require_once ShipFlex_PATH . 'inc/conditions/class-user.php';
		require_once ShipFlex_PATH . 'inc/conditions/class-order-history.php';
		require_once ShipFlex_PATH . 'inc/conditions/class-cart-products.php';
		require_once ShipFlex_PATH . 'inc/conditions/class-billing-shipping.php';

		$this->condition_groups['cart'] = new Cart();
		$this->condition_groups['user'] = new User();
		$this->condition_groups['cart-products'] = new Cart_Products();
		$this->condition_groups['order-history'] = new Order_History();
		$this->condition_groups['billing-shipping'] = new Billing_Shipping();
	}

	/**
	 * Get condition types
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_condition_types() {
		if (empty($this->condition_types) || !is_array($this->condition_types)) {
			$this->condition_types = apply_filters('shipflex/condition/types', array());
		}

		return $this->condition_types;
	}

	/**
	 * Get condition models
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_models() {
		$models = array();
		foreach ($this->get_condition_types() as $condition_type) {
			if (!empty($condition_type['model_key'])) {
				$default_value = isset($condition_type['default_value']) ? $condition_type['default_value'] : null;
				$models[$condition_type['model_key']] = $default_value;
			}
		}

		return apply_filters('shipflex/condition/models', $models);
	}

	/**
	 * Group of conditions
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_groups() {
		return apply_filters('shipflex/condition/groups', array(
			'cart' => esc_html__('Cart', 'shipflex'),
			'cart_products' => esc_html__('Cart Products', 'shipflex'),
			'date' => esc_html__('Date', 'shipflex'),
			'billing' => esc_html__('Billing', 'shipflex'),
			'shipping' => esc_html__('Shipping', 'shipflex'),
			'user' => esc_html__('Customer', 'shipflex'),
			'order_history' => esc_html__('Order History', 'shipflex'),
			'others' => esc_html__('Others', 'shipflex'),
		));
	}



	/**
	 * Get condition types of group
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_types_by_group($group) {
		$condition_types = $this->get_condition_types();

		$group_condition_types = [];
		foreach ($condition_types as $key => $condition) {
			$field_type_info = explode(':', $key);
			if (empty($field_type_info[0]) || $group !== $field_type_info[0]) {
				continue;
			}

			$group_condition_types[$key] = $condition;
		}

		uasort($group_condition_types, fn($a, $b) => $a['priority'] > $b['priority'] ? 1 : -1);
		return $group_condition_types;
	}
}

Main::get_instance();

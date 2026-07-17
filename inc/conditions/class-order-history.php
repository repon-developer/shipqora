<?php

namespace ShipFlex\Condition;

use ShipFlex\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Order history class
 */
final class Order_History {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter('shipflex/condition/models', array($this, 'condition_models'));
		add_filter('shipflex/condition/types', array($this, 'add_condition_types'));
	}

	/**
	 * Condition values
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function condition_models($values) {
		return array_merge($values, array('first_purchase' => 'yes'));
	}

	/**
	 * Add condition types
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function add_condition_types($condition_types) {
		$condition_types['order_history:first_purchase'] = array(
			'priority' => 5,
			'template' => array($this, 'first_purchase_template'),
			'validate_callback' => array($this, 'validate_condition'),
			'label' => esc_html__('First Purchase', 'shipflex'),
		);

		return $condition_types;
	}

	/**
	 * Add first purchase template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function first_purchase_template() { ?>
		<template v-if="type == 'order_history:first_purchase'">
			<select v-model="first_purchase">
				<option value="yes"><?php esc_html_e('Yes', 'shipflex'); ?></option>
				<option value="no"><?php esc_html_e('No', 'shipflex'); ?></option>
			</select>
		</template>
<?php
	}

	/**
	 * Validate condition
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function validate_condition($matched, $condition) {
		if (!is_user_logged_in()) {
			return false;
		}

		$orders = wc_get_orders([
			'customer_id' => get_current_user_id(),
			'limit'       => 1,
			'status'      => ['wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed'],
			'return'      => 'ids',
		]);

		if ('yes' === $condition['first_purchase']) {
			return count($orders) === 0;
		}

		if ('no' === $condition['first_purchase']) {
			return count($orders) > 0;
		}

		return $matched;
	}
}

<?php

namespace ShipQora_WooCommerce\Condition;

use ShipQora_WooCommerce\Utils;

if (!defined('ABSPATH')) {
	exit;
}


final class Order_History {

	/**
	 * Hold condtion group id
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	public $group_id = 'order_history';

	/**
	 * Condition models
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_name() {
		return esc_html__('Order History', 'shipqora-woocommerce');
	}

	/**
	 * Group Priority
	 * 
	 * @since 1.0.0
	 * @return float
	 */
	public function get_priority() {
		return 40;
	}

	/**
	 * Register condition types
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function register_conditions($main_object) {
		$main_object->add_condition_types('first_purchase', array(
			'priority' => 10,
			'default_value' => 'yes',
			'model_key' => 'first_purchase',
			'template' => array($this, 'first_purchase_template'),
			'validate_callback' => array($this, 'validate_condition'),
			'label' => esc_html__('First Purchase', 'shipqora-woocommerce'),
		));
	}

	/**
	 * Add first purchase template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function first_purchase_template($condition) { ?>
		<template v-if="type == '<?php echo esc_attr($condition->get_id()) ?>'">
			<select v-model="<?php echo esc_attr($condition->get_model_key()) ?>">
				<option value="yes"><?php esc_html_e('Yes', 'shipqora-woocommerce'); ?></option>
				<option value="no"><?php esc_html_e('No', 'shipqora-woocommerce'); ?></option>
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
	public function validate_condition($condition) {
		if (!is_user_logged_in()) {
			return false;
		}

		$orders = wc_get_orders([
			'customer_id' => get_current_user_id(),
			'limit'       => 1,
			'status'      => ['wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed'],
			'return'      => 'ids',
		]);

		$first_purchase = $condition->get_value();
		if ('yes' === $first_purchase) {
			return count($orders) === 0;
		}

		if ('no' === $first_purchase) {
			return count($orders) > 0;
		}

		return false;
	}
}

Main::register_condition_group(Order_History::class);

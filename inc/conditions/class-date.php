<?php

namespace ShipQora_WooCommerce\Condition;

use ShipQora_WooCommerce\Utils;

if (!defined('ABSPATH')) {
	exit;
}

final class Date {

	/**
	 * Hold condtion group id
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	public $group_id = 'datetime';

	/**
	 * Condition models
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_name() {
		return esc_html__('Date & Time', 'shipqora-woocommerce');
	}

	/**
	 * Group Priority
	 * 
	 * @since 1.0.0
	 * @return float
	 */
	public function get_priority() {
		return 60;
	}

	/**
	 * Get model keys of this group
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_model_keys() {
		return array(
			'time_one' => '',
			'time_two' => '',
			'date_one' => '',
			'date_two' => '',
			'weekly_days' => array(),
			'date_operator' => 'before',
			'weekly_days_operator' => 'any_in_list',
		);
	}

	/**
	 * Register condition types
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function register_conditions($main_object) {
		$main_object->add_condition_types('datetime_time', array(
			'priority' => 10,
			'label' => esc_html__('Time', 'shipqora-woocommerce'),
			'template' => array($this, 'time_template'),
			'validate_callback' => array($this, 'validate_datetime'),
		));

		$main_object->add_condition_types('datetime_date', array(
			'priority' => 20,
			'label' => esc_html__('Date', 'shipqora-woocommerce'),
			'template' => array($this, 'date_template'),
			'validate_callback' => array($this, 'validate_datetime'),
		));

		$main_object->add_condition_types('weekly_days', array(
			'priority' => 30,
			'model_key' => 'weekly_days',
			'label' => esc_html__('Weekly Days', 'shipqora-woocommerce'),
			'template' => array($this, 'weekly_days_template'),
			'validate_callback' => array($this, 'validate_datetime'),
		));
	}

	/**
	 * Validate date and time
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function validate_datetime($condition) {
		$condition_type_id = $condition->get_id();

		if ('weekly_days' === $condition_type_id) {
			$weekly_days = $condition->get_value();
			if (!is_array($weekly_days)) {
				$weekly_days = array();
			}

			$current_day = strtolower(current_time('l'));

			$weekly_days_operator = $condition->get_value('weekly_days_operator');
			if ('any_in_list' == $weekly_days_operator) {
				return in_array($current_day, $weekly_days);
			}

			if ('not_in_list' == $weekly_days_operator) {
				return !in_array($current_day, $weekly_days);
			}
		}

		$operator = $condition->get_value('date_operator');

		if ('datetime_time' === $condition_type_id) {
			$time_one = strtotime($condition->get_value('time_one'));
			if (false === $time_one) {
				return false;
			}

			$current_time = current_time('timestamp');
			if ('before' === $operator) {
				return $current_time < $time_one;
			}

			if ('after' === $operator) {
				return $current_time > $time_one;
			}

			if ('between' === $operator || 'not_between' === $operator) {
				$time_two = strtotime($condition->get_value('time_two'));
				if (false === $time_two) {
					return false;
				}

				if ('between' === $operator) {
					return ($current_time >= $time_one && $current_time <= $time_two);
				}

				if ('not_between' === $operator) {
					return !($current_time >= $time_one && $current_time <= $time_two);
				}
			}
		}

		if ('datetime_date' === $condition_type_id) {
			$date_one = strtotime($condition->get_value('date_one'));
			if (false === $date_one) {
				return false;
			}

			$current_time = current_time('timestamp');
			if ('before' === $operator) {
				return $current_time < $date_one;
			}

			if ('after' === $operator) {
				return $current_time > $date_one;
			}

			if ('between' === $operator || 'not_between' === $operator) {
				$date_two = strtotime($condition->get_value('date_two'));
				if (false === $date_two) {
					return false;
				}

				if ('between' === $operator) {
					return ($current_time >= $date_one && $current_time <= $date_two);
				}

				if ('not_between' === $operator) {
					return !($current_time >= $date_one && $current_time <= $date_two);
				}
			}
		}

		return false;
	}

	/**
	 * Add time template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function time_template($condition) { ?>
		<template v-if="type == '<?php echo esc_attr($condition->get_id()) ?>'">
			<select v-model="date_operator">
				<?php Utils::get_operators_options(array('before', 'after', 'between', 'not_between')); ?>
			</select>

			<input type="time" v-model="time_one">
			<input type="time" v-model="time_two" v-if="date_operator == 'between' || date_operator == 'not_between'">
		</template>
	<?php
	}

	/**
	 * Add date template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function date_template($condition) { ?>
		<template v-if="type == '<?php echo esc_attr($condition->get_id()) ?>'">
			<select v-model="date_operator">
				<?php Utils::get_operators_options(array('before', 'after', 'between', 'not_between')); ?>
			</select>

			<input type="datetime-local" v-model="date_one">
			<input type="datetime-local" v-model="date_two" v-if="date_operator == 'between' || date_operator == 'not_between'">
		</template>
	<?php
	}

	/**
	 * Weekly days type template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function weekly_days_template($condition) { ?>
		<template v-if="type == '<?php echo esc_attr($condition->get_id()) ?>'">
			<select v-model="weekly_days_operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<select2-dropdown
				type="weekly_days"
				placeholder="<?php esc_attr_e('Select weekly days', 'shipqora-woocommerce'); ?>"
				:initial-value="<?php echo esc_attr($condition->get_model_key()) ?>"
				@update="(value) => <?php echo esc_attr($condition->get_model_key()) ?> = value">
			</select2-dropdown>
		</template>
<?php
	}
}

Main::register_condition_group(Date::class);

<?php

namespace ShipQora\Condition;

use ShipQora\Utils;

if (!defined('ABSPATH')) {
	exit;
}

final class User {

	/**
	 * Hold condtion group id
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	public $group_id = 'user';

	/**
	 * Condition models
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_name() {
		return esc_html__('Customer', 'shipqora');
	}

	/**
	 * Group Priority
	 * 
	 * @since 1.0.0
	 * @return float
	 */
	public function get_priority() {
		return 30;
	}

	/**
	 * Get model keys of this group
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_model_keys() {
		return array(
			'user_operator' => 'any_in_list',
		);
	}

	/**
	 * Register condition types
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function register_conditions($main_object) {
		$main_object->add_condition_types('user_users', array(
			'priority' => 10,
			'model_key' => 'user_users',
			'default_value' => array(),
			'label' => esc_html__('Users', 'shipqora'),
			'template' => array($this, 'users_condition_template'),
			'validate_callback' => array($this, 'validate_condition'),
		));

		$main_object->add_condition_types('user_logged_in', array(
			'priority' => 20,
			'default_value' => 'yes',
			'model_key' => 'user_logged_in',
			'template' => array($this, 'logged_in_template'),
			'validate_callback' => array($this, 'validate_condition'),
			'label' => esc_html__('Logged In', 'shipqora'),
		));
	}

	/**
	 * Condition filters
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function validate_condition($matched, $condition) {
		$operator = $condition['user_operator'];

		if ('user:users' === $condition['type']) {
			$users = isset($condition['users']) && is_array($condition['users']) ? $condition['users'] : array();
			if ('any_in_list' === $operator) {
				return in_array(get_current_user_id(), $users);
			}

			if ('not_in_list' === $operator) {
				return !in_array(get_current_user_id(), $users);
			}
		}

		if ('user:logged_in' === $condition['type'] && 'yes' == $condition['user_logged_in']) {
			return is_user_logged_in();
		}

		if ('user:logged_in' === $condition['type'] && 'no' == $condition['user_logged_in']) {
			return !is_user_logged_in();
		}

		return $matched;
	}

	/**
	 * Add users template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function users_condition_template($condition) { ?>
		<template v-if="type == '<?php echo esc_attr($condition->get_id()) ?>'">
			<select v-model="user_operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<select2-dropdown
				type="user:users"
				placeholder="<?php esc_attr_e('Choose users', 'shipqora'); ?>"
				:initial-value="<?php echo esc_attr($condition->get_model_key()) ?>"
				@update="(value) => <?php echo esc_attr($condition->get_model_key()) ?> = value">
			</select2-dropdown>
		</template>
	<?php
	}

	/**
	 * Add logged in template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function logged_in_template($condition) { ?>
		<template v-if="type == '<?php echo esc_attr($condition->get_id()) ?>'">
			<select v-model="<?php echo esc_attr($condition->get_model_key()) ?>">
				<option value="yes"><?php esc_html_e('Yes', 'shipqora'); ?></option>
				<option value="no"><?php esc_html_e('No', 'shipqora'); ?></option>
			</select>
		</template>
<?php
	}
}

Main::register_condition_group(User::class);

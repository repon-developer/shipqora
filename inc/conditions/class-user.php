<?php

namespace ShipFlex\Condition;

use ShipFlex\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * User Condition class
 */
final class User {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter('shipflex/condition/types', array($this, 'add_condition_types'));
	}

	/**
	 * Add condition types
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function add_condition_types($condition_types) {
		$condition_types = array_merge($condition_types, array(
			'user:users' => array(
				'priority' => 10,
				'model_key' => 'users',
				'default_value' => array(),
				'template' => array($this, 'users_condition_template'),
				'validate_callback' => array($this, 'validate_condition'),
				'label' => esc_html__('Users', 'shipflex'),
			),
			'user:roles' => array(
				'priority' => 15,
				'model_key' => 'user_roles',
				'default_value' => array(),
				'template' => array($this, 'user_roles_template'),
				'validate_callback' => array($this, 'validate_condition'),
				'label' => esc_html__('Customer Roles', 'shipflex'),
			),
			'user:logged_in' => array(
				'priority' => 20,
				'default_value' => 'yes',
				'model_key' => 'user_logged_in',
				'template' => array($this, 'logged_in_template'),
				'validate_callback' => array($this, 'validate_condition'),
				'label' => esc_html__('Logged In', 'shipflex'),
			),
		));

		return $condition_types;
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

		if ('user:roles' === $condition['type']) {
			$condition_roles = isset($condition['user_roles']) && is_array($condition['user_roles']) ? $condition['user_roles'] : array();
			if (count($condition_roles) === 0) {
				return false;
			}

			$user_roles = wp_get_current_user()->roles;
			if (!is_user_logged_in()) {
				$user_roles[] = 'guest';
			}

			$matched_roles = array_intersect($condition_roles, $user_roles);
			if ('any_in_list' === $operator) {
				return count($matched_roles) > 0;
			}

			if ('not_in_list' === $operator) {
				return count($matched_roles) == 0;
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
	public function users_condition_template() { ?>
		<template v-if="type == 'user:users'">
			<select v-model="user_operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<select2-dropdown
				type="user:users"
				:initial-value="users"
				@update="(value) => users = value"
				placeholder="<?php esc_attr_e('Choose users', 'shipflex'); ?>">
			</select2-dropdown>
		</template>
	<?php
	}

	/**
	 * Add user roles template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function user_roles_template() { ?>
		<template v-if="type == 'user:roles'">
			<select v-model="user_operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<select2-dropdown
				type="user_roles"
				:initial-value="user_roles"
				@update="(value) => user_roles = value"
				placeholder="<?php esc_attr_e('Customer Roles', 'shipflex'); ?>">
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
	public function logged_in_template() { ?>
		<template v-if="type == 'user:logged_in'">
			<select v-model="user_logged_in">
				<option value="yes"><?php esc_html_e('Yes', 'shipflex'); ?></option>
				<option value="no"><?php esc_html_e('No', 'shipflex'); ?></option>
			</select>
		</template>
<?php
	}
}

new User();
<?php

namespace ShipFlex\Component;

use ShipFlex\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Select2 Class
 */
final class Select2 {
	/**
	 * Nonce value
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	const NONCE_VALUE = 'shipflex/select2_dropdown_nonce';

	/**
	 * Hold the current instance
	 * 
	 * @since 1.0.0
	 * @var Select2
	 */
	private static $instance = null;

	/**
	 * Get instance of current class
	 * 
	 * @since 1.0.0
	 * @return Select2
	 */
	public static function get_instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action('admin_footer', array($this, 'output_component'));
		add_filter('shipflex/admin_enqueue_scripts', array($this, 'enqueue_scripts'), 10, 2);
		add_action('wp_ajax_shipflex/get_select2_dropdown_data', array($this, 'get_select2_data'));
	}

	/**
	 * Output select2 dropdown component
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_component() { ?>
		<template id="shipflex-select2-dropdown">
			<span class="select2-safety-span"><!-- dont-remove-this-line-otherwise-show-error --></span>

			<div class="shipflex-loading-spinner" v-if="loading"></div>

			<select
				v-else
				v-model="value"
				ref="select2_dropdown"
				:multiple="multiple">

				<slot v-if="$slots.options" name="options">

				<optgroup v-if="has_option_group" v-for="(group_label, group_code) in option_groups" :label="group_label" :key="group_code">
					<option v-for="(option_label, option_value) in get_group_options(group_code)" :value="option_value" v-html="option_label" :key="option_value"></option>
				</optgroup>
				<option v-else v-for="option in select_option_items" :value="option.id" :key="option.id" v-html="option.name"></option>
			</select>
		</template>
<?php
	}

	/**
	 * Add dependencies of style of select2 dropdown
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function enqueue_scripts($values, $source) {
		if ('styles' === $source) {
			$values[] = 'shipflex-select2';
		}

		if ('scripts' === $source) {
			$values[] = 'select2';
		}

		if ('localize' === $source) {
			$customer_roles = wp_roles()->role_names;
			$customer_roles['guest'] = esc_html__('Guest', 'shipflex');
			unset($customer_roles['administrator'], $customer_roles['author']);

			$values['select2'] = array(
				'nonce' => wp_create_nonce(self::NONCE_VALUE),
				'options' => array(
					'user_roles' => $customer_roles,
				)
			);
		}

		return $values;
	}

	/**
	 * Get select2 dropdown data
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function get_select2_data() {
		check_ajax_referer(self::NONCE_VALUE, 'security');

		$search_term = !empty($_POST['term']) ? sanitize_text_field(wp_unslash($_POST['term']))  : '';
		$meta_data['search_term'] = $search_term;

		$query_type = !empty($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';
		$query_type = explode(':', $query_type);

		$meta_data['object_type'] = !empty($query_type[0]) ? $query_type[0] : '';
		$meta_data['object_slug'] = !empty($query_type[1]) ? $query_type[1] : '';

		$meta_data['search_values'] = [];
		if (isset($_POST['values']) && is_array($_POST['values'])) {
			$meta_data['search_values'] = array_map('sanitize_text_field', wp_unslash($_POST['values']));
		}

		$results = array();

		$meta_data = wp_parse_args($meta_data, $_POST);
		$method_name = 'get_' . str_replace('-', '_', $meta_data['object_type']);

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
		$callback_method = apply_filters(Utils::get_hook_name('select2', 'method'), array($this, $method_name), $meta_data, $this);		
		if (method_exists(...$callback_method)) {
			$results = call_user_func($callback_method, $meta_data);
		}

		wp_send_json_success(apply_filters('shipflex/select2/results', $results, $meta_data));

		


		if ('user' == $meta_data['object_type'] && 'users' == $object_slug) {
			if (!empty($search_term)) {
				$search_args['search'] = $search_term;
			}

			if (count($values) > 0) {
				$search_args['include'] = $values;
			}

			$get_users = get_users($search_args);
			$results = array_map(function ($user) {
				return array('id' => $user->ID, 'name' => $user->display_name);
			}, $get_users);
		}
	}

	/**
	 * Get terms of taxonomy
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_taxonomy($meta_data) {
		if (empty($meta_data['object_slug'])) {
			return array();
		}

		$search_args = array('hide_empty' => false, 'taxonomy' => $meta_data['object_slug']);
		if (!empty($meta_data['search_term'])) {
			$search_args['search'] = $meta_data['search_term'];
		}

		$search_args['include'] = $meta_data['search_values'];
		$terms = get_terms($search_args);
		return array_map(fn($term) => array('id' => $term->term_id, 'name' => $term->name), $terms);
	}

	/**
	 * Get shipping instances
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_shipping_instances($meta_data) {
		$allow_shipping_method = false;
		if (isset($meta_data['shipping_method'])) {
			$allow_shipping_method = sanitize_text_field($meta_data['shipping_method']);
		}

		$shipping_zones = Utils::get_shipping_zones();

		$shipping_instances = array();
		foreach ($shipping_zones as $zone) {
			$zone_id = $zone->get_id();

			$zone_instances = array(
				$zone_id . '-0' => esc_html__('All rates', 'shipflex')
			);

			$shipping_methods = $zone->get_shipping_methods();
			foreach ($shipping_methods as $shipping_method) {
				if ($allow_shipping_method && $shipping_method->id !== $allow_shipping_method) {
					continue;
				}

				$option_slug = $zone_id . '-' . $shipping_method->instance_id;
				$zone_instances[$option_slug] = $shipping_method->get_title();
			}

			if (count($zone_instances) > 1) {
				$shipping_instances[$zone_id] = array(
					'id' => $zone_id,
					'name' => $zone->get_zone_name(),
					'instances' => $zone_instances
				);
			}
		}

		return $shipping_instances;
	}
}


Select2::get_instance();

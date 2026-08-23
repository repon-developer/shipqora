<?php

namespace ShipQora\Component;

use ShipQora\Utils;

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
	const NONCE_VALUE = 'shipqora/select2_dropdown_nonce';

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
		add_filter('shipqora/admin_enqueue_scripts', array($this, 'enqueue_scripts'), 10, 2);
		add_action('wp_ajax_shipqora/get_select2_dropdown_data', array($this, 'get_select2_data'));
	}

	/**
	 * Output select2 dropdown component
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_component() { ?>
		<template id="shipqora-select2-dropdown">
			<span class="select2-safety-span"><!-- dont-remove-this-line-otherwise-show-error --></span>

			<div class="shipqora-loading-spinner" v-if="loading"></div>
			<select
				v-else
				v-model="value"
				ref="select2_dropdown"
				:multiple="multiple">
				<template v-if="has_option_group" v-for="option in select_option_items" :key="option.id">
					<optgroup :label="option.name" v-if="option?.sub_options?.length">
						<option v-for="sub_option in option.sub_options" :value="sub_option.id" v-html="sub_option.name" :key="sub_option.id"></option>
					</optgroup>
				</template>
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
			$values[] = 'shipqora-select2';
		}

		if ('scripts' === $source) {
			$values[] = 'select2';
		}

		if ('localize' === $source) {
			$customer_roles = wp_roles()->role_names;
			$customer_roles['guest'] = esc_html__('Guest', 'shipqora');
			unset($customer_roles['administrator'], $customer_roles['author']);

			$values['select2'] = array(
				'nonce' => wp_create_nonce(self::NONCE_VALUE),
				'options' => array(
					'user_roles' => $customer_roles,
					'weekly_days' => array(
						'sunday' => esc_html__('Sunday', 'shipqora'),
						'monday' => esc_html__('Monday', 'shipqora'),
						'tuesday' => esc_html__('Tuesday', 'shipqora'),
						'wednesday' => esc_html__('Wednesday', 'shipqora'),
						'thursday' => esc_html__('Thursday', 'shipqora'),
						'friday' => esc_html__('Friday', 'shipqora'),
						'saturday' => esc_html__('Saturday', 'shipqora'),
					)
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

		$meta_data['values'] = [];
		if (isset($_POST['values']) && is_array($_POST['values'])) {
			$meta_data['values'] = array_map('sanitize_text_field', wp_unslash($_POST['values']));
		}

		$post_data = map_deep(wp_unslash($_POST), 'sanitize_text_field');
		$meta_data = wp_parse_args($meta_data, $post_data);

		$results = array();

		$method_name = 'get_' . str_replace('-', '_', $meta_data['object_type']);

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
		$callback_method = apply_filters(Utils::get_hook_name('select2', 'method'), array($this, $method_name), $meta_data, $this);
		if (method_exists(...$callback_method)) {
			$results = call_user_func($callback_method, $meta_data);
		}

		wp_send_json_success(apply_filters('shipqora/select2/results', $results, $meta_data));
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
			$search_args['search_columns'] = array('ID', 'user_login', 'user_email', 'display_name');
		}

		$search_args['include'] = $meta_data['values'];
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

		if ('pickup_location' == $meta_data['shipping_method']) {
			$pickup_locations = get_option('pickup_location_pickup_locations');

			if (is_array($pickup_locations)) {
				$locations = array_map(function ($location, $location_index) {
					return array('id' => $location_index, 'name' => $location['name']);
				}, $pickup_locations, array_keys($pickup_locations));

				return $locations;
			}
		}

		$shipping_zones = Utils::get_shipping_zones();

		$shipping_instances = array();
		foreach ($shipping_zones as $zone) {
			$zone_id = $zone->get_id();

			$zone_instances = array(
				$zone_id . '-0' => esc_html__('All rates', 'shipqora')
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
				$shipping_instances[] = array(
					'id' => $zone_id,
					'name' => $zone->get_zone_name(),
					'instances' => $zone_instances
				);
			}
		}

		return $shipping_instances;
	}

	/**
	 * Get customers
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_customers($meta_data) {
		$search_args = array();
		if (!empty($meta_data['search_term'])) {
			$search_args['search'] = $meta_data['search_term'];
		}

		if (count($meta_data['values']) > 0) {
			$search_args['include'] = $meta_data['values'];
		}

		$get_users = get_users($search_args);

		return array_map(function ($user) {
			return array('id' => $user->ID, 'name' => $user->display_name);
		}, $get_users);
	}
}


Select2::get_instance();

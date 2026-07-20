<?php

namespace ShipFlex\Component;

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
				<slot name="slot-options"></slot>
				<optgroup v-if="has_option_group" v-for="(group_label, group_code) in option_groups" :label="group_label" :key="group_code">
					<option v-for="(option_label, option_value) in get_group_options(group_code)" :value="option_value" v-html="option_label" :key="option_value"></option>
				</optgroup>
				<option v-else v-for="option in hold_options" :value="option.id" :key="option.id" v-html="option.name"></option>
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
					'countries' => (new \WC_Countries())->get_countries()
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

		$meta_data = wp_parse_args($meta_data, $_POST);

		$method_name_args = array(
			'get',
			str_replace('-', '_', $meta_data['object_type']),
			str_replace('-', '_', $meta_data['object_slug'])
		);

		$method_name = join('_', array_filter($method_name_args));

		$results = array();
		if (method_exists($this, $method_name)) {
			$results = call_user_func(array($this, $method_name), $meta_data);
		}

		wp_send_json_success(apply_filters('shipflex/select2/results', $results, $meta_data));

		if ('products' == $meta_data['object_type'] || ('post_type' === $meta_data['object_type'] && 'product' === $meta_data['object_slug'])) {
			$search_args['limit'] = 10;
			if (count($meta_data['search_values']) > 0) {
				$search_args['include'] = $meta_data['search_values'];
			}

			if (!empty($search_term)) {
				$search_args['s'] = $search_term;
			}

			$products = wc_get_products($search_args);

			$results = array_map(function ($product) {
				$data = array('id' => $product->get_id(), 'name' => $product->get_name());
				if ($product->is_type('variable')) {
					foreach ($product->get_children() as $variation_id) {
						$variation = wc_get_product($variation_id);
						$data['variations'][] = array('id' => $variation_id, 'name' => $variation->get_name() . ' (' . $variation_id . ')');
					}
				}

				return $data;
			}, $products);
		}

		if ('post_type' == $meta_data['object_type'] && !empty($object_slug)) {
			$search_args['post_type'] = $object_slug;
			if (!empty($search_term)) {
				$search_args['s'] = $search_term;
			}

			$posts = get_posts($search_args);
			$results = array_map(function ($item) {
				return array('id' => $item->ID, 'name' => $item->post_title);
			}, $posts);
		}

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

		$shipping_instances = array();

		$shipping_zones = \WC_Shipping_Zones::get_shipping_zones();
		$shipping_zones[] = new \WC_Shipping_Zone(0);

		foreach ($shipping_zones as $zone) {
			$shipping_methods = $zone->get_shipping_methods();
			foreach ($shipping_methods as $shipping_method) {
				if (!$shipping_method->enabled || ($allow_shipping_method && $shipping_method->id !== $allow_shipping_method)) {
					continue;
				}

				$zone_name = $zone->get_zone_name();
				if ($zone->get_id() == 0) {
					$zone_name = esc_html__('Rest of the world', 'shipflex');
				}

				$shipping_instances[] = array(
					'id' => $shipping_method->instance_id,
					'name' => sprintf('%s - %s', $zone_name, $shipping_method->get_title())
				);
			}
		}

		//error_log(print_r($shipping_instances, true));

		return $shipping_instances;
	}

	/**
	 * Get terms of taxonomy
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_taxonomy($values, $taxonomy, $search_term) {
		if (empty($taxonomy)) {
			return null;
		}

		$search_args = array('hide_empty' => false, 'taxonomy' => $taxonomy);
		if (!empty($search_term)) {
			$search_args['search'] = $search_term;
		}

		$search_args['include'] = $values;
		$terms = get_terms($search_args);
		return array_map(fn($term) => array('id' => $term->term_id, 'name' => $term->name), $terms);
	}

	/**
	 * Get variation products
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_variation_products($values, $object_slug, $search_term) {
		$variation_products = get_posts(array(
			's' => $search_term,
			'include' => $values,
			'posts_per_page' => 20,
			'post_type' => 'product_variation',
			'search_columns' => array('post_title'),
		));

		return array_map(function ($variation) {
			$product_title = get_the_title($variation->ID) . ' (' . $variation->ID . ')';
			return array('id' => $variation->ID, 'name' => html_entity_decode($product_title));
		}, $variation_products);
	}

	/**
	 * Get variable products
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_variable_products($values, $object_slug, $search_term) {
		$search_args = array('limit' => 15, 'type' => 'variable');
		if (count($values) > 0) {
			$search_args['include'] = $values;
		}

		if (!empty($search_term)) {
			$search_args['s'] = $search_term;
		}

		$products = wc_get_products($search_args);

		return array_map(function ($product) {
			$data = array('id' => $product->get_id(), 'name' => $product->get_name());
			foreach ($product->get_children() as $variation_id) {
				$variation = wc_get_product($variation_id);
				$data['variations'][] = array('id' => $variation_id, 'name' => $variation->get_name() . ' (' . $variation_id . ')');
			}

			return $data;
		}, $products);
	}
}


Select2::get_instance();

<?php

namespace ShipQora_WooCommerce;




if (!defined('ABSPATH')) {
	exit;
}

/**
 * Utilities class 
 */
class Utils {

	/**
	 * Get current version of this plugin
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_plugin_version() {
		return get_plugin_data(SHIPQORA_WOOCOMMERCE_FILE)['Version'];
	}

	/**
	 * Get available status of ShipQora rule
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_statuses() {
		return array(
			'active' => array(
				'label' => esc_html__('Active', 'shipqora-woocommerce'),
				'currently_text' => esc_html__('Currently Live', 'shipqora-woocommerce'),
				'description' => esc_html__('Live on checkout for all store customers and visitors.', 'shipqora-woocommerce'),
			),

			'development' => array(
				'label' => esc_html__('Test Mode', 'shipqora-woocommerce'),
				'currently_text' => esc_html__('Currently in Test Mode', 'shipqora-woocommerce'),
				'description' => esc_html__('Only active for logged-in administrators (ideal for testing rules safely on live sites).', 'shipqora-woocommerce')
			),

			'disabled' => array(
				'label' => esc_html__('Disabled', 'shipqora-woocommerce'),
				'currently_text' => esc_html__('Currently Disabled', 'shipqora-woocommerce'),
				'description' => esc_html__('Deactivated and hidden from checkout for all users.', 'shipqora-woocommerce')
			),
		);
	}

	/**
	 * JSON string to array
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function json_string_to_array($json_string) {
		if (!is_scalar($json_string)) {
			return (array) $json_string;
		}

		$data = json_decode($json_string, true);
		if (!is_array($data)) {
			$data = array();
		}

		return $data;
	}

	/**
	 * Convert comma sepator to array
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function comma_separator_to_array($string_value, $lower_case = true) {
		if ($lower_case) {
			$string_value = strtolower($string_value);
		}

		return array_filter(array_map('trim', explode(',', $string_value)));
	}

	/**
	 * Is supported screen of this plugin
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function is_plugin_screen($screen_name = 'plugin-screen') {
		if ('rule-editor' === $screen_name) {
			return strpos(get_current_screen()->id, 'shipqora-woocommerce-edit') !== false;
		}

		if ('rule-list-table' === $screen_name) {
			return strpos(get_current_screen()->id, 'shipqora-woocommerce') !== false;
		}

		return false;
	}

	/**
	 * Get Hook Name
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_hook_name(...$hook_slugs) {
		$hook_slugs = array_filter($hook_slugs);
		array_unshift($hook_slugs, 'shipqora-woocommerce');
		return join('/', $hook_slugs);
	}

	/**
	 * Rearrange array item by priority
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function priority_rearrange($values, $target_key = null) {
		if (empty($target_key)) {
			$target_key = 'priority';
		}

		$values = array_map(function ($option) use ($target_key) {
			if (!isset($option[$target_key])) {
				$option[$target_key] = 10;
			}

			$option[$target_key] = floatval($option[$target_key]);

			return $option;
		}, $values);

		uasort($values, fn($a, $b) => $a[$target_key] > $b[$target_key] ? 1 : -1);

		return $values;
	}

	/**
	 * Get split model key from model_keys
	 * 
	 * @since 1.0.0
	 * @param string $deep_model_keys
	 * @return array
	 */
	public static function get_deep_model_split_keys($deep_model_keys) {
		if (empty($deep_model_keys)) {
			return false;
		}

		$deep_keys = explode('.', $deep_model_keys);
		$last_key = array_pop($deep_keys);
		if (empty($deep_keys) && empty($last_key)) {
			return false;
		}

		return compact('deep_keys', 'last_key');
	}

	/**
	 * Get value of deep key from array
	 * 
	 * @since 1.0.0
	 * @param string $deep_model_key - free_shipping.hide_icon.more_level.deep_level
	 * @param string $default
	 * @return mixed
	 */
	public static function get_deep_key_value($deep_model_key, $array_data, $default = null) {
		$model_keys = self::get_deep_model_split_keys($deep_model_key);
		if (false === $model_keys) {
			return $default;
		}

		while ($current_key = current($model_keys['deep_keys'])) {
			if (isset($array_data[$current_key])) {
				$array_data = $array_data[$current_key];
			}

			next($model_keys['deep_keys']);
		}

		$last_key = $model_keys['last_key'];
		return isset($array_data[$last_key]) ? $array_data[$last_key] : $default;
	}

	/**
	 * Merge deep array item
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function deep_merge_arrays($array1, $array2) {
		foreach ($array2 as $key => $value) {
			if (is_array($value) && isset($array1[$key]) && is_array($array1[$key])) {
				$array1[$key] = self::deep_merge_arrays($array1[$key], $value); // recursive merge
			} else {
				$array1[$key] = $value; // overwrite or add
			}
		}
		return $array1;
	}

	/**
	 * Get condition operators
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_operators($operators = array()) {
		$supported_operators = array(
			'equal_to' => __('Equal To', 'shipqora-woocommerce'),
			'less_than' => __('Less than ( < )', 'shipqora-woocommerce'),
			'less_than_or_equal' => __('Less than or equal to ( <= )', 'shipqora-woocommerce'),
			'greater_than_or_equal' => __('Greater than or equal to ( >= )', 'shipqora-woocommerce'),
			'greater_than' => __('Greater than ( > )', 'shipqora-woocommerce'),
			'between' => __('Between', 'shipqora-woocommerce'),
			'any_in_list' => __('Any in list', 'shipqora-woocommerce'),
			'all_in_list' => __('All in list', 'shipqora-woocommerce'),
			'not_in_list' => __('Not in list', 'shipqora-woocommerce'),

			'before' => __('Before', 'shipqora-woocommerce'),
			'after' => __('After', 'shipqora-woocommerce'),
			'not_between' => __('Not Between', 'shipqora-woocommerce'),
		);

		$return_operators = [];
		while ($key = current($operators)) {
			if (isset($supported_operators[$key])) {
				$return_operators[$key] = $supported_operators[$key];
			}

			next($operators);
		}

		return $return_operators;
	}

	/**
	 * Get operators dropdown
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_operators_options($args = array()) {
		$operators = self::get_operators($args);

		$options = array_map(function ($label, $key) {
			return sprintf('<option value="%s">%s</option>', $key, $label);
		}, $operators, array_keys($operators));

		echo wp_kses(implode('', $options), array(
			'option' => array(
				'value' => true
			)
		));
	}

	/**
	 * Get registered taxonomies of product
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_product_taxonomies() {
		$taxonomies = array();
		$start_priority = 30;

		$product_taxonomies = get_object_taxonomies('product', 'objects');

		foreach ($product_taxonomies as $tax_slug => $taxonomy) {
			if (false === $taxonomy->public && 'product_shipping_class' !== $tax_slug) {
				continue;
			}

			$start_priority += 10;

			$taxonomy_lower_label = strtolower($taxonomy->label);

			$taxonomies[$tax_slug] = array(
				'slug' => $taxonomy->name,
				'priority' => $start_priority,
				'type' => 'taxonomy:' . $tax_slug,
				'model' => str_replace('-', '___', $taxonomy->name),
				'label' => ucwords(str_replace('Product ', '', $taxonomy->label)),
				'label_lower' => str_replace('product ', '', $taxonomy_lower_label),
				'placeholder' => sprintf(
					/* translators: %s for taxonomy label */
					esc_html__('Choose one or more %s', 'shipqora-woocommerce'),
					$taxonomy_lower_label
				)
			);
		}

		$taxonomy_priority = array('product_cat' => 5, 'product_tag' => 6, 'product_brand' => 7, 'product_shipping_class' => 1000);
		foreach ($taxonomy_priority as $tax_key => $priority) {
			if (isset($taxonomies[$tax_key])) {
				$taxonomies[$tax_key]['priority'] = $priority;
			}
		}

		return Utils::priority_rearrange($taxonomies);
	}

	/**
	 * Get all shipping zones
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_shipping_zones() {
		$shipping_zones = \WC_Shipping_Zones::get_shipping_zones();

		$global_zone = new \WC_Shipping_Zone(0);
		$global_zone->set_zone_name(esc_html__('Rest of the world', 'shipqora-woocommerce'));

		$shipping_zones[] = $global_zone;
		return $shipping_zones;
	}

	/**
	 * Get lite notice button
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_lite_button($button_data = null) {
		$button_data = wp_parse_args($button_data, array('utm_campaign' => 'shipqora-woocommerce', 'utm_medium' => 'shipqora+rule'));
		$button_attributes = array_map(fn($value, $attribute) => sprintf('%s="%s"', $attribute, $value), $button_data, array_keys($button_data));
		$button_link = 'https://shipqora.com/?' . implode('&', $button_attributes);
		$button = apply_filters('shipqora/lite_button', '<a class="button button-primary" target="_blank" href="' . esc_url($button_link) . '">' . esc_html__('Get Pro', 'shipqora-woocommerce') . '</a>');
		if (!empty($button)) {
			echo wp_kses_post($button);
		}
	}
}

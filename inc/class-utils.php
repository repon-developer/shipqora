<?php

namespace ShipFlex;

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
		return get_plugin_data(ShipFlex_FILE)['Version'];
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
			return strpos(get_current_screen()->id, 'shipflex-edit') !== false;
		}

		if ('rule-list-table' === $screen_name) {
			return strpos(get_current_screen()->id, 'shipflex') !== false;
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
		array_unshift($hook_slugs, 'shipflex');
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
			'equal_to' => __('Equal To', 'shipflex'),
			'less_than' => __('Less than ( < )', 'shipflex'),
			'less_than_or_equal' => __('Less than or equal to ( <= )', 'shipflex'),
			'greater_than_or_equal' => __('Greater than or equal to ( >= )', 'shipflex'),
			'greater_than' => __('Greater than ( > )', 'shipflex'),
			'between' => __('Between', 'shipflex'),
			'any_in_list' => __('Any in list', 'shipflex'),
			'all_in_list' => __('All in list', 'shipflex'),
			'not_in_list' => __('Not in list', 'shipflex'),

			'before' => __('Before', 'shipflex'),
			'after' => __('After', 'shipflex'),
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
			if (false === $taxonomy->public) {
				continue;
			}

			$start_priority += 10;

			$taxonomy_lower_label = strtolower($taxonomy->label);

			$taxonomies[$tax_slug] = array(
				'slug' => $taxonomy->name,
				'label' => $taxonomy->label,
				'priority' => $start_priority,
				'type' => 'taxonomy:' . $tax_slug,
				'label_lower' => $taxonomy_lower_label,
				'model' => str_replace('-', '___', $taxonomy->name),
				'placeholder' => sprintf(
					/* translate: %s for taxonomy label */
					esc_html__('Choose one or more %s', 'shipflex'),
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

		if (isset($taxonomies['product_shipping_class'])) {
			$taxonomies['product_shipping_class']['disabled_operators'] = array('all_in_list');
		}

		return Utils::priority_rearrange($taxonomies);
	}

	/**
	 * Supported VueJS attributes for table heading
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function table_header_action_vuejs_attr() {
		$allow_html_tags = wp_kses_allowed_html('post');
		$vuejs_attributes = array('v-if', ':class', '@click.prevent');

		$supported_tags = array('div', 'span', 'a');
		foreach ($supported_tags as $tag) {
			if (!isset($allow_html_tags[$tag])) {
				continue;
			}

			foreach ($vuejs_attributes as $attribute) {
				$allow_html_tags[$tag][$attribute] = true;
			}
		}

		return $allow_html_tags;
	}

	/**
	 * Get hook name of component heading actions
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_component_heading_actions_hook(...$args) {
		return Utils::get_hook_name('component-heading-actions', ...$args);
	}

	/**
	 * Get actions buttons of component heading
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_component_heading_actions() {
		return array(
			'duplicate' => array(
				'priority' => 5,
				'content' => '<a @click.prevent="duplicate_component()" class="button button-small" href="#"><span class="dashicons dashicons-admin-page"></span>' . esc_html__('Duplicate', 'shipflex') . '</a>'
			),

			'delete' => array(
				'priority' => 10,
				'content' => '<a v-if="tier_no &gt; 1" @click.prevent="delete_component()" class="button button-small" href="#"><span class="dashicons dashicons-trash"></span>' . esc_html__('Delete', 'shipflex') . '</a>'
			),

			'collapse' => array(
				'priority' => 1000,
				'content' => '<a v-if="tier_no &gt; 1" @click.prevent="collapse = !collapse" class="btn-collapse dashicons" :class="collapse_button_class" href="#"></a>'
			)
		);
	}

	/**
	 * Output action content of table heading
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function output_component_heading_actions($actions) {
		if (!is_array($actions) || count($actions) == 0) {
			return;
		}

		$html_contents = array_map(fn($item) => $item['content'], Utils::priority_rearrange($actions));
		$html = '<div class="component-heading-actions">' . join('', $html_contents) . '</div>';
		echo wp_kses($html, self::table_header_action_vuejs_attr());
	}

	/**
	 * Output heading row of component
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function output_component_heading_row($title, $action_contents) {
?>
		<tr class="row-group-heading">
			<td colspan="2">
				<span class="button-drag dashicons dashicons-menu-alt" v-if="draggable"></span>
				<div class="heading-line">
					<?php
					if (!empty($title)) {
						echo wp_kses_post($title);
					}

					Utils::output_component_heading_actions($action_contents); ?>
				</div>
			</td>
		</tr>
<?php
	}

	/**
	 * Get lite notice button
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_lite_button($button_data = null) {
		$button_data = wp_parse_args($button_data, array('utm_campaign' => 'shipflex', 'utm_medium' => 'rule+editor'));
		$button_attributes = array_map(fn($value, $attribute) => sprintf('%s="%s"', $attribute, $value), $button_data, array_keys($button_data));
		$button_link = 'https://shipflexpro.com/?' . implode('&', $button_attributes);
		$button = apply_filters('shipflex/lite_button', '<a class="button button-primary" target="_blank" href="' . esc_url($button_link) . '">' . esc_html__('Get Pro', 'shipflex') . '</a>');
		if (!empty($button)) {
			echo wp_kses_post($button);
		}
	}
}

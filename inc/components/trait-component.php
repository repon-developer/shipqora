<?php

namespace ShipFlex;

if (!defined('ABSPATH')) {
	exit;
}

trait Component_Methods {

	/**
	 * Supported VueJS attributes
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function vuejs_attr() {
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
	 * Get actions buttons of component heading
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_heading_actions() {
		return array(
			'duplicate' => array(
				'priority' => 5,
				'content' => '<a v-if="!hide_action(\'duplicate\')" @click.prevent="duplicate_tier()" class="button button-small" href="#"><span class="dashicons dashicons-admin-page"></span>' . esc_html__('Duplicate', 'shipflex') . '</a>'
			),

			'delete' => array(
				'priority' => 10,
				'content' => '<a v-if="!hide_action(\'delete\')" @click.prevent="delete_tier()" class="button button-small" href="#"><span class="dashicons dashicons-trash"></span>' . esc_html__('Delete', 'shipflex') . '</a>'
			),

			'collapse' => array(
				'priority' => 1000,
				'content' => '<a v-if="!hide_action(\'collapse\')" @click.prevent="collapse = !collapse" class="button btn-collapse dashicons" :class="collapse_button_class" href="#"></a>'
			)
		);
	}

	/**
	 * Output heading row of component
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	protected function output_heading_row($title, $filter_slugs = array()) {
		$action_contents = apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
			Utils::get_hook_name('component-heading-actions', ...$filter_slugs),
			$this->get_heading_actions()
		); ?>

		<tr class="row-group-heading" v-if="!hideHeading">
			<td colspan="2">
				<span :class="drag_button_classes" class="dashicons dashicons-menu-alt" v-if="draggable"></span>
				<div class="heading-line">
					<?php
					if (!empty($title)) {
						echo wp_kses_post($title);
					}

					if (is_array($action_contents) && count($action_contents) > 0) {
						$html_contents = array_map(fn($item) => $item['content'], Utils::priority_rearrange($action_contents));
						$html = '<div class="component-heading-actions">' . join('', $html_contents) . '</div>';
						echo wp_kses($html, $this->vuejs_attr());
					} ?>
				</div>
			</td>
		</tr>
<?php
	}

	/**
	 * Output attributes of component
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	protected function output_component_attrs($component_id, $attributes) {
		$attributes = apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
			Utils::get_hook_name('component', 'attributes'),
			$attributes,
			$component_id
		);
		
		foreach ($attributes as $key => $value) {
			if (is_array($value)) {
				$value = wp_json_encode($value);
			}

			echo esc_attr($key) . '="' . esc_attr($value) . '" ';
		}
	}

	/**
	 * Get shipping rate data
	 * 
	 * @since 1.0.0
	 * @param $shipping_rate
	 * @return array
	 */
	public function get_shipping_rate_data($shipping_rate) {
		$existed_data = $shipping_rate->{$this->get_id()};
		if (!is_array($existed_data)) {
			$existed_data = array();
		}

		return $existed_data;
	}

	/**
	 * Add data at provided shipping rate
	 * 
	 * @since 1.0.0
	 * @param $shipping_rate
	 * @param $data_items
	 * @return void
	 */
	protected function add_shipping_rate_data($shipping_rate, $new_data, $data_id = null) {
		$existed_data = $shipping_rate->{$this->get_id()};
		if (!is_array($existed_data)) {
			$existed_data = array();
		}

		if ($data_id) {
			$existed_data[$data_id] = $new_data;
		} else {
			$existed_data[] = $new_data;
		}

		$shipping_rate->{$this->get_id()} = $existed_data;
	}
}

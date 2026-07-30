<?php

namespace ShipFlex;

if (!defined('ABSPATH')) {
	exit;
}

trait Component_Methods {

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
				'content' => '<a @click.prevent="duplicate_tier()" class="button button-small" href="#"><span class="dashicons dashicons-admin-page"></span>' . esc_html__('Duplicate', 'shipflex') . '</a>'
			),

			'delete' => array(
				'priority' => 10,
				'content' => '<a v-if="!hide_action(\'delete\')" @click.prevent="delete_tier()" class="button button-small" href="#"><span class="dashicons dashicons-trash"></span>' . esc_html__('Delete', 'shipflex') . '</a>'
			),

			'collapse' => array(
				'priority' => 1000,
				'content' => '<a @click.prevent="collapse = !collapse" class="button btn-collapse dashicons" :class="collapse_button_class" href="#"></a>'
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
						echo wp_kses($html, Utils::table_header_action_vuejs_attr());
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
	protected function output_component_attrs($attributes = array(), $extra_data = null) {
		$attributes = apply_filters(Utils::get_hook_name('component', 'attributes'), $attributes, $extra_data);
		foreach ($attributes as $key => $value) {
			echo esc_attr($key) . '="' . esc_attr($value) . '" ';
		}
	}
}

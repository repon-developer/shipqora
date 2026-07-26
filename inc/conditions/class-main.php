<?php

namespace ShipFlex\Condition;

use ShipFlex\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Main Condition class
 */
class Main {

	/**
	 * VueJS component of cart option
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function output_component() {
		$main_condition = self::get_instance(); ?>
		<template id="shipflex-condition">
			<select class="condition-types" v-model="type">
				<?php
				foreach ($main_condition->get_groups() as $group_key => $group_label) {
					$conditions = $main_condition->get_types_by_group($group_key);
					if (count($conditions) == 0) {
						continue;
					}

					echo '<optgroup label="' . esc_attr($group_label) . '">';
					foreach ($conditions as $key => $condition) {
						printf('<option value="%s">%s</option>', esc_attr($key), esc_html($condition['label']));
					}
					echo '</optgroup>';
				} ?>
			</select>

			<?php
			$rendered_templates = array();
			foreach ($main_condition->get_types() as $type_key => $condition_type) {
				if (!isset($condition_type['template']) || !is_callable($condition_type['template'])) {
					continue;
				}

				$model_key = !empty($condition_type['model_key']) ? $condition_type['model_key'] : '';
				$template_id = md5($model_key . maybe_serialize($condition_type['template']));
				if (!in_array($template_id, $rendered_templates)) {
					$rendered_templates[] = $template_id;
					call_user_func($condition_type['template'], $condition_type, $type_key);
				}
			} ?>

			<div class="tools">
				<span @click="delete_condition()" class="btn-delete-item dashicons dashicons-no-alt"></span>
			</div>
		</template>

		<template id="shipflex-condition-group">
			<span @click="delete_group()" class="btn-delete-item btn-delete-group dashicons dashicons-trash"></span>

			<div class="shipflex-repeater" v-if="conditions?.length">
				<template v-for="(condition, index) in conditions" :key="condition.id">
					<div class="repeater-item repeater-item-separator" v-if="index > 0" data-text="<?php esc_attr_e('and', 'shipflex') ?>"></div>
					<div class="repeater-item">
						<condition :condition="condition" :number="index" :key="condition?.id"></condition>
					</div>
				</template>
			</div>

			<a class="button button-small" href="#" @click.prevent="add_condition()">
				<?php esc_html_e('Add condition', 'shipflex') ?>
			</a>
		</template>
<?php
	}

	/**
	 * Hold the current instance of condition
	 * 
	 * @since 1.0.0
	 * @var Main
	 */
	private static $instance = null;

	/**
	 * Get instance of current class
	 * 
	 * @since 1.0.0
	 * @return Main
	 */
	public static function get_instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hold all condition types
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	public $types = array();

	/**
	 * Hold results of conditions
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	private $condition_results = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		require_once ShipFlex_PATH . 'inc/conditions/class-cart.php';
		require_once ShipFlex_PATH . 'inc/conditions/class-user.php';
		require_once ShipFlex_PATH . 'inc/conditions/class-order-history.php';
		require_once ShipFlex_PATH . 'inc/conditions/class-cart-products.php';
		require_once ShipFlex_PATH . 'inc/conditions/class-billing-shipping.php';
	}

	/**
	 * Group of conditions
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_groups() {
		return apply_filters('shipflex/condition/groups', array(
			'cart' => esc_html__('Cart', 'shipflex'),
			'cart_products' => esc_html__('Cart Products', 'shipflex'),
			'date' => esc_html__('Date', 'shipflex'),
			'billing' => esc_html__('Billing', 'shipflex'),
			'shipping' => esc_html__('Shipping', 'shipflex'),
			'user' => esc_html__('Customer', 'shipflex'),
			'order_history' => esc_html__('Order History', 'shipflex'),
			'others' => esc_html__('Others', 'shipflex'),
		));
	}

	/**
	 * Get condition types
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_types() {
		if (empty($this->types) || !is_array($this->types)) {
			$this->types = apply_filters('shipflex/condition/types', array());
		}

		return $this->types;
	}

	/**
	 * Get condition models
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_models() {
		$models = array();
		foreach ($this->get_types() as $condition_type) {
			if (!empty($condition_type['model_key'])) {
				$default_value = isset($condition_type['default_value']) ? $condition_type['default_value'] : null;
				$models[$condition_type['model_key']] = $default_value;
			}
		}

		return apply_filters('shipflex/condition/models', $models);
	}

	/**
	 * Get condition types of group
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_types_by_group($group) {
		$condition_types = $this->get_types();

		$group_condition_types = [];
		foreach ($condition_types as $key => $condition) {
			$field_type_info = explode(':', $key);
			if (empty($field_type_info[0]) || $group !== $field_type_info[0]) {
				continue;
			}

			$group_condition_types[$key] = $condition;
		}

		uasort($group_condition_types, fn($a, $b) => $a['priority'] > $b['priority'] ? 1 : -1);
		return $group_condition_types;
	}

	/**
	 * Match group and conditions
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function is_matched_conditions($condition_groups, $feature_object) {
		if (empty($condition_groups) || !is_array($condition_groups)) {
			return true;
		}

		$hash = md5(wp_json_encode($condition_groups));
		if (array_key_exists($hash, $this->condition_results)) {
			return $this->condition_results[$hash];
		}

		$condition_types = $this->get_types();
		$condition_groups = array_filter($condition_groups, function ($group_data) use ($condition_types, $feature_object) {
			if (!isset($group_data['conditions']) || !is_array($group_data['conditions'])) {
				return true;
			}

			$conditions = array_filter($group_data['conditions'], function ($condition) use ($condition_types, $feature_object) {
				$condition = wp_parse_args($condition, array('type' => '', 'value' => '', 'value2' => ''));
				$current_type = $condition['type'];

				$validated_condition = false;
				if (isset($condition_types[$current_type]['validate_callback']) && is_callable($condition_types[$current_type]['validate_callback'])) {
					$validated_condition = call_user_func($condition_types[$current_type]['validate_callback'], false, $condition, $this);
				}

				$hook_name = Utils::get_hook_name('condition', $current_type, 'matched');
				return apply_filters($hook_name, $validated_condition, $condition, $feature_object);
			});

			return count($group_data['conditions']) === count($conditions);
		});

		$hook_name = Utils::get_hook_name('condition-groups', 'matched');
		$this->condition_results[$hash] = apply_filters($hook_name, count($condition_groups) > 0, $condition_groups, $feature_object);
		return $this->condition_results[$hash];
	}
}

Main::get_instance();

<?php

namespace ShipQora\Condition;

use ShipQora\Utils;
use ShipQora\Cart_Total;

if (!defined('ABSPATH')) {
	exit;
}

class Main {

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
	 * Hold all registered condition groups
	 * 
	 * @since 1.0.0
	 * @var array
	 */

	private $registerd_groups = array();

	/**
	 * Hold all condition types
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	private $condition_types = array();

	/**
	 * Hold results of conditions
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	private $condition_results = array();

	/**
	 * Hold current group_id of register condition type
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	private $add_condition_group_id = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action('init', array($this, 'register_condition_types'), 100); //Don't use priority less than 6, otherwise taxonomy will not return
	}

	/**
	 * VueJS component of cart option
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_component() { ?>
		<template id="shipqora-condition">
			<select class="condition-types" v-model="type" data-once-modal="advanced-condition-types">
				<?php
				foreach ($this->get_group_options() as $group_id => $group_data) {
					if (count($group_data['sub_options']) == 0) {
						continue;
					}

					echo '<optgroup label="' . esc_attr($group_data['label']) . '">';
					foreach ($group_data['sub_options'] as $condition_object) {
						printf('<option value="%s">%s</option>', esc_attr($condition_object->get_id()), esc_html($condition_object->get_label()));
						if (true == $condition_object->get_use_separator()) {
							echo '<option disabled>---------------</option>';
						}
					}
					echo '</optgroup>';
				} ?>
			</select>

			<?php
			$rendered_templates = array();
			foreach ($this->get_condition_types() as $object) {
				$model_key = $object->get_model_key();
				$template_id = md5($model_key . maybe_serialize($object->get_template()));

				if (!in_array($template_id, $rendered_templates)) {
					$rendered_templates[] = $template_id;
					$object->render_template();
				}
			} ?>

			<div class="tools">
				<span @click="delete_condition()" class="btn-delete-item dashicons dashicons-no-alt"></span>
			</div>
		</template>

		<template id="shipqora-condition-group">
			<span @click="delete_group()" class="btn-delete-item btn-delete-group dashicons dashicons-trash"></span>

			<div class="shipqora-repeater" v-if="conditions?.length">
				<template v-for="(condition, index) in conditions" :key="condition.id">
					<div class="repeater-item repeater-item-separator" v-if="index > 0" data-text="<?php esc_attr_e('and', 'shipqora') ?>"></div>
					<div class="repeater-item">
						<condition :condition="condition" :number="index" :key="condition?.id"></condition>
					</div>
				</template>
			</div>

			<a class="button button-small" href="#" @click.prevent="add_condition()"><?php esc_html_e('+ Add condition', 'shipqora') ?></a>
		</template>
<?php
	}

	/**
	 * Load all registered condition files
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function load_files() {
		require_once SHIPQORA_PATH . 'inc/conditions/class-condition.php';
		require_once SHIPQORA_PATH . 'inc/conditions/class-cart.php';
		require_once SHIPQORA_PATH . 'inc/conditions/class-user.php';
		require_once SHIPQORA_PATH . 'inc/conditions/class-date.php';
		require_once SHIPQORA_PATH . 'inc/conditions/class-order-history.php';
		require_once SHIPQORA_PATH . 'inc/conditions/class-cart-products.php';
		require_once SHIPQORA_PATH . 'inc/conditions/class-billing-shipping.php';
	}

	/**
	 * Register condition group
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function register_condition_group($group_class) {
		$group_object = new $group_class();

		if (!property_exists($group_object, 'group_id')) {
			throw new \Exception(sprintf(esc_html__('The %s class must have a public $group_id property.', 'shipqora'), $group_class));
		}

		$group_id = $group_object->group_id;
		if (empty($group_id)) {
			throw new \Exception(sprintf(esc_html__('The %s class must have a value for the $group_id property.', 'shipqora'), $group_class));
		}

		if (!method_exists($group_object, 'get_name')) {
			throw new \Exception(sprintf(esc_html__('The %s class must have a public get_name() method.', 'shipqora'), $group_class));
		}

		$group_name = $group_object->get_name();
		if (empty($group_name)) {
			throw new \Exception(sprintf(esc_html__('The get_name() method of the %s class should return a valid group name.', 'shipqora'), $group_class));
		}

		$this->registerd_groups[$group_id] = $group_object;
	}

	/**
	 * Group of conditions
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_groups() {
		$groups = $this->registerd_groups;
		uasort($groups, fn($a, $b) => $a->get_priority() <=> $b->get_priority());

		return $groups;

		return apply_filters('shipqora/condition/groups', array(
			'order_history' => esc_html__('Order History', 'shipqora'),
		));
	}

	/**
	 * Register condition types of condition group
	 */
	public function register_condition_types() {
		foreach ($this->get_groups() as $group_id => $group_object) {
			if (method_exists($group_object, 'register_conditions')) {
				$this->add_condition_group_id = $group_id;

				$group_object->register_conditions($this);

				do_action(
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
					Utils::get_hook_name('condition', 'register-type'),
					$group_id,
					$group_object
				);

				$this->add_condition_group_id = null;
			}
		}
	}

	/**
	 * Add condition types
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_condition_types($condition_id, $condition_data) {
		if (empty($this->add_condition_group_id)) {
			throw new \Exception(sprintf(
				esc_html__('You are trying to add a condition outside the register_condition() method or %s hook.', 'shipqora'),
				Utils::get_hook_name('condition', 'register-type')
			));
		}

		if (!isset($condition_data['template']) || isset($condition_data['template']) && !is_callable($condition_data['template'])) {
			throw new \Exception(esc_html__('Please add the template array key with a validation callback for this condition type.', 'shipqora'));
		}

		$condition_id = sanitize_key($condition_id);
		$this->condition_types[$condition_id] = new Condition($condition_id, array_merge($condition_data, array(
			'group_id' => $this->add_condition_group_id
		)));
	}

	/**
	 * Get all added condition types
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_condition_types() {
		return $this->condition_types;
	}

	/**
	 * Get models of all registered condition groups
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_models() {
		$model_keys = array();
		foreach ($this->get_groups() as $group_object) {
			if (method_exists($group_object, 'get_model_keys')) {
				$model_keys = wp_parse_args($model_keys, $group_object->get_model_keys());
			}
		}

		foreach ($this->get_condition_types() as $condition_id => $object) {
			$model_key = $object->get_model_key();
			if (!empty($model_key)) {
				$model_keys[$model_key] = $object->get_default_value();
			}
		}

		return apply_filters(Utils::get_hook_name('condition', 'models'), $model_keys);
	}

	/**
	 * Group of conditions
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_group_options() {
		$groups = array();
		foreach ($this->get_groups() as $group_id => $group_object) {
			$groups[$group_id] = array(
				'label' => $group_object->get_name(),
				'sub_options' => array_filter($this->condition_types, function ($item) use ($group_id) {
					return $item->get_group_id() == $group_id;
				})
			);
		}

		return $groups;
	}

	/**
	 * Match group and conditions
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function is_matched_conditions($condition_groups) {
		if (empty($condition_groups) || !is_array($condition_groups)) {
			return true;
		}

		$hash = md5(wp_json_encode($condition_groups) . wp_json_encode(Cart_Total::get_cart_items()));
		if (array_key_exists($hash, $this->condition_results)) {
			return $this->condition_results[$hash];
		}

		$condition_groups = array_filter($condition_groups, function ($group_data) {
			if (!isset($group_data['conditions']) || !is_array($group_data['conditions'])) {
				return true;
			}

			$conditions = array_filter($group_data['conditions'], function ($condition_data) {
				if (empty($condition_data['type'])) {
					return true;
				}

				$condition_id = sanitize_key($condition_data['type']);
				$condition_types = $this->get_condition_types();
				if (!isset($condition_types[$condition_id])) {
					return true;
				}

				$validated_condition = $condition_types[$condition_id]->validate_condition($condition_data);

				return apply_filters(
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
					Utils::get_hook_name('condition', $condition_id, 'matched'),
					$validated_condition,
					$condition_data
				);
			});

			return count($group_data['conditions']) === count($conditions);
		});

		$this->condition_results[$hash] = apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
			Utils::get_hook_name('condition-groups', 'matched'),
			count($condition_groups) > 0,
			$condition_groups
		);

		return $this->condition_results[$hash];
	}
}

Main::get_instance()->load_files();

add_action('wp_loadedd', function () {
	$data = Main::get_instance()->get_group_options();
	var_dump($data);
	exit;
}, 1000);

<?php

namespace ShipQora;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Feature class
 */
class Feature {

	/**
	 * Hold all registered features
	 * 
	 * @var array
	 */
	private static $features = array();

	/**
	 * Register feature
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function add_feature($feature_class) {
		$feature_instance = new $feature_class();
		self::$features[$feature_instance->get_id()] = $feature_instance;
	}

	/**
	 * Get all registered features
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_features() {
		uasort(self::$features, function ($a, $b) {
			return $a->get_configuration('priority') > $b->get_configuration('priority') ? 1 : -1;
		});

		return self::$features;
	}

	/**
	 * Hold the feature key of current feature
	 * 
	 * @var string
	 */
	protected $feature_id = '';

	/**
	 * Hold all extra value
	 * 
	 * @var array
	 */
	protected $meta_data = [];

	/**
	 * Hold all feature lines item of rules
	 * 
	 * @var array
	 */
	protected $line_items = [];

	/**
	 * isset magic method
	 * 
	 * @since 1.0.0
	 * @param string $key
	 * @param boolean
	 */
	public function __isset($key) {
		return isset($this->meta_data[$key]);
	}

	/**
	 * Set magic method
	 * 
	 * @since 1.0.0
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set($key, $value) {
		$this->meta_data[$key] = $value;
	}

	/**
	 * Get magic method
	 * 
	 * @since 1.0.0
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key) {
		return isset($this->meta_data[$key]) ? $this->meta_data[$key] : null;
	}

	/**
	 * Get feature id
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function get_id() {
		return $this->feature_id;
	}

	/**
	 * Get feature configuration
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_configuration_settings() {
		return array();
	}

	/**
	 * Get value of feature configuration
	 * 
	 * @since 1.0.0
	 * @return mixed
	 */
	public function get_configuration($key) {
		$configuration = $this->get_configuration_settings();
		return isset($configuration[$key]) ? $configuration[$key] : null;
	}

	/**
	 * Get priority for run feature
	 * 
	 * @since 1.0.0
	 * @return int
	 */
	public function get_feature_priority($shipqora_rule = null) {
		$feature_priority = $this->get_configuration('feature_priority');
		if (is_a($shipqora_rule, ShipQora_Rule::class)) {
			$priority = $shipqora_rule->get_feature_value($this->get_model_key('feature_priority'));
			if (strlen($priority)) {
				$feature_priority = $priority;
			}
		}

		return $feature_priority;
	}

	/**
	 * Get model key after add  base model as a prefix
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function get_model_key($model_key) {
		return $this->get_configuration('base_model') . '.' . $model_key;
	}

	/**
	 * Get hook of feature
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function get_hook(...$hooks) {
		return Utils::get_hook_name('feature', $this->get_id(), ...$hooks);
	}

	/**
	 * Order rule id and priority
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function order_priority($items) {
		$items = array_map(function ($item) {
			$item = wp_parse_args($item, array('rule_id' => 0, 'priority' => 10, 'calculated_shipping_cost' => 0.00));
			if (strlen($item['priority']) === 0) {
				$item['priority'] = 10;
			}

			return $item;
		}, $items);

		usort($items, fn($a, $b) => [$a['priority'], $a['rule_id'], $a['calculated_shipping_cost']] <=> [$b['priority'], $b['rule_id'], $b['calculated_shipping_cost']]);

		return $items;
	}

	/**
	 * Get wrapper attributes of current section
	 * 
	 * @since 1.0.0
	 * @return mixed
	 */
	public function get_wrapper_attributes() {
		return array();
	}

	/**
	 * Output wrapper attributes
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_wrapper_attributes() {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
		$wrapper_attributes = apply_filters(Utils::get_hook_name($this->get_id(), 'wrapper-attributes'), $this->get_wrapper_attributes());
		if (!is_array($wrapper_attributes) || count($wrapper_attributes) == 0) {
			return;
		}

		foreach ($wrapper_attributes as $key => $value) {
			echo esc_attr($key) . '="' . esc_attr($value) . '" ';
		}
	}
}

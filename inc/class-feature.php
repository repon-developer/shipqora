<?php

namespace ShipFlex;

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
	 * Get available reward types configurations
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function add_feature($feature_class) {
		$feature_instance = new $feature_class();

		$configuration = $feature_instance->get_configuration();
		$configuration['class_name'] = $feature_class;
		$configuration['instance'] = $feature_instance;

		self::$features[$feature_instance->get_id()] = $configuration;
	}

	/**
	 * Get all registered features
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_features() {
		uasort(self::$features, function ($a, $b) {
			if (!isset($a['priority'])) {
				$a['priority'] = 10;
			}

			if (!isset($b['priority'])) {
				$b['priority'] = 10;
			}

			return $a['priority'] > $b['priority'] ? 1 : -1;
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
	protected function get_configuration() {
		return array();
	}

	/**
	 * Get value of feature configuration
	 * 
	 * @since 1.0.0
	 * @return mixed
	 */
	public function get_configuration_value($key) {
		$configuration = $this->get_configuration();
		return isset($configuration[$key]) ? $configuration[$key] : null;
	}

	/**
	 * Add settings field of rule editor
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_editor_settings_fields(Settings_Fields $settings_fields) {
	}

	/**
	 * Output settings fields of rule editor
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_rule_editor(Settings_Fields $settings_fields) {
	}
}

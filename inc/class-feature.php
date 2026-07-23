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
			return $a->get_configuration_value('priority') > $b->get_configuration_value('priority') ? 1 : -1;
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
	 * Hold settings of lite tier 
	 * 
	 * @var array
	 */
	protected $lite_tier = [];

	/**
	 * Hold all extra value
	 * 
	 * @var array
	 */
	protected $meta_data = [];

	/**
	 * Constructor.
	 */
	public function __construct($data = null) {
		if (!is_array($data)) {
			return;
		}

		foreach ($data as $key => $value) {
			$this->{$key} = $value;
		}
	}

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
	 * Get model key after add  base model as a prefix
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function get_model_key($model_key) {
		return $this->get_configuration_value('base_model') . '.' . $model_key;
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

	/**
	 * Output action content of tier heading
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function get_form_table_header_action() {
		$actions = array(
			'duplicate' => array(
				'priority' => 5,
				'content' => '<a @click.prevent="duplicate_tier()" class="button button-small" href="#"><span class="dashicons dashicons-admin-page"></span>' . esc_html__('Duplicate', 'shipflex') . '</a>'
			),

			'delete' => array(
				'priority' => 10,
				'content' => '<a v-if="tier_no &gt; 1" @click.prevent="delete_tier()" class="button button-small" href="#"><span class="dashicons dashicons-trash"></span>' . esc_html__('Delete', 'shipflex') . '</a>'
			),

			'collapse' => array(
				'priority' => 1000,
				'content' => '<a v-if="tier_no &gt; 1" @click.prevent="collapse = !collapse" class="btn-collapse dashicons" :class="collapse_button_class" href="#"></a>'
			)
		);

		Utils::get_form_table_header_action($actions, $this->get_id());
	}
}

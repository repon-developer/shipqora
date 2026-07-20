<?php

namespace ShipFlex;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * ShipFlex_Rule class
 */
final class ShipFlex_Rule {

	/**
	 * Get rule by ID
	 * 
	 * @return ShipFlex_Rule
	 */
	public static function get($id) {
		global $wpdb;
		$rule_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", $wpdb->shipflex_rules_table, $id), ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return new self($rule_data);
	}

	/**
	 * Get rule by instance ID
	 * 
	 * @return ShipFlex_Rule
	 */
	public static function get_from_instance($instance_id, $status = null) {
		global $wpdb;

		$prepared_sql = $wpdb->prepare("SELECT * FROM %i WHERE 1 = 1", $wpdb->shipflex_rules_table);
		$prepared_sql .= $wpdb->prepare(" AND JSON_CONTAINS(shipping_methods, %s)", wp_json_encode((string) $instance_id));

		if (current_user_can('manage_woocommerce')) {
			$prepared_sql .= " AND status IN ('active', 'development')";
		} else {
			$prepared_sql .= " AND status = 'active'";
		}

		$rule_data = $wpdb->get_row($prepared_sql, ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return new self($rule_data);
	}

	/**
	 * ID of current item
	 * 
	 * @var int
	 */
	private $id = 0;

	/**
	 * Title of rule
	 * 
	 * @var string
	 */
	public $title = '';

	/**
	 * Hold all shipping methods
	 * 
	 * @var array
	 */
	private $shipping_methods = [];

	/**
	 * Hold all active features
	 * 
	 * @var array
	 */
	private $active_features = [];

	/**
	 * Hold all feature settings
	 * 
	 * @var array
	 */
	private $feature_settings = [];

	/**
	 * Status of rule
	 * 
	 * @var string
	 */
	private $status = 'development';

	/**
	 * Hold all data of current rule
	 * 
	 * @var array
	 */
	private $meta_data = [];

	/**
	 * Created time of this rule
	 * 
	 * @var stirng
	 */
	public $created_at = '';

	/**
	 * Get array properties
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_json_properties() {
		return array('shipping_methods', 'active_features', 'feature_settings', 'meta_data');
	}

	/**
	 * Constructor.
	 */
	public function __construct($data = array()) {
		$this->created_at = gmdate('Y-m-d H:i:s');
		if (!is_array($data)) {
			return;
		}

		$array_properties = $this->get_json_properties();
		while ($key = current($array_properties)) {
			if (array_key_exists($key, $data)) {
				$this->{$key} = Utils::json_string_to_array($data[$key]);
				unset($data[$key]);
			}

			next($array_properties);
		}

		$features_base_models = array_map(function ($feature) {
			return $feature->get_configuration_value('base_model');
		}, Feature::get_features());

		foreach ($data as $key => $value) {
			if (in_array($key, $features_base_models)) {
				$this->feature_settings[$key] = $value;
			} else {
				$this->{$key} = $value;
			}
		}

		$this->id = absint($this->id);
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
	 * Set current rule ID
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function set_id($rule_id) {
		$this->id = absint($rule_id);
	}

	/**
	 * Get current rule id
	 * 
	 * @since 1.0.0
	 * @return int
	 */
	public function get_id() {
		$new_id = absint($this->new_id);
		if ($new_id > 0) {
			return $new_id;
		}

		return absint($this->id);
	}

	/**
	 * Check if current item is new or not
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function is_new() {
		return absint($this->id) == 0;
	}

	/**
	 * Check if current item exists in database
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function exists() {
		return $this->get_id() > 0;
	}

	/**
	 * Save rule
	 * 
	 * @since 1.0.0
	 * @return integer
	 */
	public function save() {
		global $wpdb;

		$data = get_object_vars($this);
		unset($data['meta_data']);

		$array_properties = $this->get_json_properties();
		while ($key = current($array_properties)) {
			$data[$key] = wp_json_encode($this->{$key}, JSON_UNESCAPED_UNICODE);
			next($array_properties);
		}

		$result = $wpdb->replace($wpdb->shipflex_rules_table, $data); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if (false === $result) {
			wp_send_json_error(array(
				'message' => esc_html__('Unable to save your data. Please contact support if the issue persists.', 'shipflex')
			));
		}

		if ($this->is_new()) {
			$this->new_id = $wpdb->insert_id;
		}

		return $this->get_id();
	}

	/**
	 * Get models for vuejs app
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_models() {
		$rule_data = get_object_vars($this);
		unset($rule_data['created_at'], $rule_data['meta_data'], $rule_data['feature_settings']);
		foreach ($this->feature_settings as $feature_id => $feature_data) {
			$rule_data[$feature_id] = $feature_data;
		}

		$rule_editor_settings = Settings_Fields::get_instance('rule-editor');

		$rule_models = apply_filters('shipflex/rule_models', $rule_editor_settings->get_models());
		return (object) Utils::deep_merge_arrays($rule_models, array_merge($this->meta_data, $rule_data));
	}

	/**
	 * Check if provide feature is enabled or not
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function is_feature_enabled($feature_id) {
		return in_array($feature_id, $this->active_features);
	}

	/**
	 * Get feature instance of provided feature id
	 * 
	 * @since 1.0.0
	 * @return object
	 */
	public function get_feature_instance($feature_id) {
		$registered_features = Feature::get_features();
		if (!isset($registered_features[$feature_id])) {
			return false;
		}

		$feature_instance = $registered_features[$feature_id];
		$base_model = $feature_instance->get_configuration_value('base_model');

		if (!isset($this->feature_settings[$base_model]) || !is_array($this->feature_settings[$base_model])) {
			return false;
		}

		$class_name = get_class($feature_instance);
		return new $class_name($this->feature_settings[$base_model]);
	}
}

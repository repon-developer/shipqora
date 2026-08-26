<?php

namespace ShipQora;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * ShipQora_Rule class
 */
final class ShipQora_Rule {
	/**
	 * Hold all instance of ShipQora_Rule
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	private static $rule_instances = [];

	/**
	 * Get rule by ID
	 * 
	 * @since 1.0.0
	 * @param int $id
	 * @return ShipQora_Rule
	 */
	public static function get($shipqora_rule_id) {
		if (!isset(self::$rule_instances[$shipqora_rule_id])) {
			global $wpdb;
			$rule_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", $wpdb->shipqora_rules_table, $shipqora_rule_id), ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			self::$rule_instances[$shipqora_rule_id] = new self($rule_data);
		}

		return self::$rule_instances[$shipqora_rule_id];
	}



	/**
	 * Hold ShipQora Rules of instance id of shipping rate id
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	private static $shipping_rates = array();

	/**
	 * Get ShipQora Rules by instance ID of shipping method
	 * 
	 * @since 1.0.0
	 * @param int $rate_id - free_shipping:14
	 * @return array
	 */
	public static function get_by_rate_id($rate_id) {
		if (isset(self::$shipping_rates[$rate_id])) {
			return self::$shipping_rates[$rate_id];
		}

		$rate_id_data = explode(':', $rate_id);
		if (empty($rate_id_data[1])) {
			$rate_id_data[1] = 0;
		}

		$method_id = $rate_id_data[0];
		$instance_id = $rate_id_data[1];

		$json_search_data = null;
		if ('pickup_location' == $method_id) {
			$json_search_data = array('pickup_location', $rate_id);
		}

		if ('pickup_location' !== $method_id && $instance_id > 0) {
			$shipping_method = \WC_Shipping_Zones::get_shipping_method($instance_id);
			if (!is_a($shipping_method, 'WC_Shipping_Method')) {
				return array();
			}

			$current_zone = \WC_Shipping_Zones::get_zone_by('instance_id', $instance_id);
			if ($current_zone) {
				$zone_id = $current_zone->get_id();
				$json_search_data = array(
					$shipping_method->id,
					$shipping_method->id . ':' . $zone_id . '-0',
					$shipping_method->id . ':' . $zone_id . '-' . $instance_id,
				);
			}
		}

		if (empty($json_search_data)) {
			return array();
		}

		global $wpdb;
		$prepared_sql = $wpdb->prepare("SELECT * FROM %i WHERE 1 = 1", $wpdb->shipqora_rules_table);

		$shipping_method_sql = array();
		while ($shpping_method_id = current($json_search_data)) {
			$shipping_method_sql[] = $wpdb->prepare('JSON_CONTAINS(shipping_methods, %s)', wp_json_encode($shpping_method_id));
			next($json_search_data);
		}

		$prepared_sql .= " AND (" . implode(' OR ', $shipping_method_sql) . ")";
		if (current_user_can('manage_woocommerce')) {
			$prepared_sql .= " AND status IN ('active', 'development')";
		} else {
			$prepared_sql .= " AND status = 'active'";
		}

		$results = $wpdb->get_results($prepared_sql, ARRAY_A); // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		foreach ($results as $rule_data) {
			self::$shipping_rates[$rate_id][$rule_data['id']] = new ShipQora_Rule($rule_data);
		}

		if (isset(self::$shipping_rates[$rate_id])) {
			return self::$shipping_rates[$rate_id];
		}

		return array();
	}

	/**
	 * Get ShipQora Rules by instance ID of shipping method
	 * 
	 * @since 1.0.0
	 * @param int $instance_id
	 * @return array
	 */
	public static function get_by_instance_id($instance_id) {
		$shipping_method = \WC_Shipping_Zones::get_shipping_method($instance_id);
		if (!is_a($shipping_method, 'WC_Shipping_Method')) {
			return array();
		}

		return self::get_by_rate_id($shipping_method->id . ':' . $shipping_method->instance_id);
	}

	/**
	 * Get rule by shipping rate
	 * 
	 * @since 1.0.0
	 * @return ShipQora_Rule
	 */
	public static function get_by_shipping_rate($shipping_rate) {
		return self::get_by_rate_id($shipping_rate->get_id());
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
	public $status = 'development';

	/**
	 * Hold all data of current rule
	 * 
	 * @var array
	 */
	private $meta_data = [];

	/**
	 * Update time of this rule
	 * 
	 * @var stirng
	 */
	public $updated_at = '';

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
		$this->set_data($data);
		$this->id = absint($this->id);
	}

	/**
	 * Set ShipQora rule data
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function set_data($data) {
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

		$features_base_models = array_map(fn($feature) => $feature->get_configuration('base_model'), Feature::get_features());
		foreach ($data as $key => $value) {
			if (in_array($key, $features_base_models)) {
				$this->feature_settings[$key] = $value;
			} else {
				$this->{$key} = $value;
			}
		}

		if (!is_array($this->active_features)) {
			$this->active_features = array();
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

		$this->updated_at = gmdate('Y-m-d H:i:s');
		$data = get_object_vars($this);
		unset($data['meta_data']);

		$array_properties = $this->get_json_properties();
		while ($key = current($array_properties)) {
			$data[$key] = wp_json_encode($this->{$key}, JSON_UNESCAPED_UNICODE);
			next($array_properties);
		}

		$result = $wpdb->replace($wpdb->shipqora_rules_table, $data); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if (false === $result) {
			wp_send_json_error(array(
				'message' => esc_html__('Unable to save your data. Please contact support if the issue persists.', 'shipqora')
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
		unset($rule_data['updated_at'], $rule_data['created_at'], $rule_data['meta_data'], $rule_data['feature_settings']);
		foreach ($this->feature_settings as $feature_id => $feature_data) {
			$rule_data[$feature_id] = $feature_data;
		}

		$rule_editor_settings = Settings_Fields::get_instance('rule-editor');

		$rule_models = apply_filters('shipqora/rule_models', $rule_editor_settings->get_models());
		return (object) Utils::deep_merge_arrays($rule_models, array_merge($this->meta_data, $rule_data));
	}

	/**
	 * Get active features of current rule
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_active_features() {
		return $this->active_features;
	}

	/**
	 * Get added shipping methods
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_shipping_methods() {
		if (empty($this->shipping_methods)) {
			return array();
		}

		$shipping_rates = array();
		$shipping_zones = Utils::get_shipping_zones();

		foreach ($shipping_zones as $zone) {
			$shipping_methods = $zone->get_shipping_methods();
			foreach ($shipping_methods as $shipping_method) {
				$instance_id = $shipping_method->instance_id;
				$zone_slug = $shipping_method->id . ':' . $zone->get_id();
				$method_slug =  $zone_slug . '-' . $shipping_method->instance_id;
				$method_title = $shipping_method->get_title();

				if (!in_array($method_slug, $this->shipping_methods)) {
					$instance_id = 0;
					$method_slug = $shipping_method->id . ':' . $zone->get_id() . '-0';

					$method_title = sprintf(
						/* translators: %s: Zone name */
						esc_html__('%s - All rates', 'shipqora'),
						$zone->get_zone_name()
					);

					if (!in_array($method_slug, $this->shipping_methods)) {
						$method_slug = $shipping_method->id;
						$method_title = esc_html__('All rates', 'shipqora');
					}
				}

				if (!in_array($method_slug, $this->shipping_methods)) {
					continue;
				}

				$shipping_rates[$method_slug] = array(
					'id' => $instance_id,
					'zone_id' => $zone->get_id(),
					'name' => sprintf('%s - %s', $shipping_method->method_title, $method_title)
				);
			}
		}

		return $shipping_rates;
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
	 * Get feature value from model key
	 * 
	 * @since 1.0.0
	 * @return mixed
	 */
	public function get_feature_value($chain_models, $default = null) {
		if (empty($chain_models)) {
			return null;
		}

		$deep_keys = explode('.', $chain_models);
		$last_key = array_pop($deep_keys);
		if (empty($last_key)) {
			return null;
		}

		$feature_settings = $this->feature_settings;
		while ($current_key = current($deep_keys)) {
			if (isset($feature_settings[$current_key])) {
				$feature_settings = $feature_settings[$current_key];
			}

			next($deep_keys);
		}

		return isset($feature_settings[$last_key]) ? $feature_settings[$last_key] : $default;
	}

	// /**
	//  * Get feature object of provided feature id
	//  * 
	//  * @since 1.0.0
	//  * @return object
	//  */
	// public function get_feature_object($feature_id) {
	// 	$registered_features = Feature::get_features();
	// 	if (!isset($registered_features[$feature_id])) {
	// 		return false;
	// 	}

	// 	$feature_instance = $registered_features[$feature_id];
	// 	$base_model = $feature_instance->get_configuration('base_model');

	// 	if (!isset($this->feature_settings[$base_model]) || !is_array($this->feature_settings[$base_model])) {
	// 		return false;
	// 	}

	// 	$class_name = get_class($feature_instance);
	// 	return new $class_name($this->feature_settings[$base_model]);
	// }
}

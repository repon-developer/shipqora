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
	 * Hold all instance of ShipFlex_Rule
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
	 * @return ShipFlex_Rule
	 */
	public static function get($shipflex_rule_id) {
		if (!isset(self::$rule_instances[$shipflex_rule_id])) {
			global $wpdb;
			$rule_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", $wpdb->shipflex_rules_table, $shipflex_rule_id), ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			self::$rule_instances[$shipflex_rule_id] = new self($rule_data);
		}

		return self::$rule_instances[$shipflex_rule_id];
	}

	/**
	 * Hold ShipFlex Rules of instance id of shipping method
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	private static $instances_ids = array();

	/**
	 * Get ShipFlex Rules by instance ID of shipping method
	 * 
	 * @since 1.0.0
	 * @param int $instance_id
	 * @return array
	 */
	public static function get_by_instance_id($instance_id) {
		if (isset(self::$instances_ids[$instance_id])) {
			return self::$instances_ids[$instance_id];
		}

		$shipping_method = \WC_Shipping_Zones::get_shipping_method($instance_id);
		if (!is_a($shipping_method, 'WC_Shipping_Method')) {
			return array();
		}

		$zone_id = \WC_Shipping_Zones::get_zone_by('instance_id', $instance_id)->get_id();

		global $wpdb;
		$prepared_sql = $wpdb->prepare("SELECT * FROM %i WHERE 1 = 1", $wpdb->shipflex_rules_table);

		$json_search = array(
			$shipping_method->id,
			$shipping_method->id . ':' . $zone_id . '-0',
			$shipping_method->id . ':' . $zone_id . '-' . $instance_id,
		);

		$shipping_method_sql = array();
		while ($shpping_method_id = current($json_search)) {
			$shipping_method_sql[] = $wpdb->prepare('JSON_CONTAINS(shipping_methods, %s)', wp_json_encode($shpping_method_id));
			next($json_search);
		}

		$prepared_sql .= " AND (" . implode(' OR ', $shipping_method_sql) . ")";
		if (current_user_can('manage_woocommerce')) {
			$prepared_sql .= " AND status IN ('active', 'development')";
		} else {
			$prepared_sql .= " AND status = 'active'";
		}

		$results = $wpdb->get_results($prepared_sql, ARRAY_A);
		foreach ($results as $rule_data) {
			self::$instances_ids[$instance_id][$rule_data['id']] = new ShipFlex_Rule($rule_data);
		}

		if (isset(self::$instances_ids[$instance_id])) {
			return self::$instances_ids[$instance_id];
		}

		return array();
	}

	/**
	 * Hold ShipFlex rule id of shipping rate
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	private static $shipping_rate_ids = [];

	/**
	 * Get rule by shipping rate
	 * 
	 * @since 1.0.0
	 * @return ShipFlex_Rule
	 */
	public static function get_by_shipping_rate($shipping_rate) {
		$instance_id = $shipping_rate->get_instance_id();

		$zone = \WC_Shipping_Zones::get_zone_by('instance_id', $instance_id);
		$zone_id = $zone->get_id();

		if (!isset(self::$shipping_rate_ids[$instance_id])) {
			global $wpdb;
			$prepared_sql = $wpdb->prepare("SELECT id FROM %i WHERE 1 = 1", $wpdb->shipflex_rules_table);

			$json_search = array(
				$shipping_rate->method_id,
				$shipping_rate->method_id . ':' . $zone_id . '-0',
				$shipping_rate->method_id . ':' . $zone_id . '-' . $instance_id,
			);

			$shipping_method_sql = array();
			while ($shpping_method = current($json_search)) {
				$shipping_method_sql[] = $wpdb->prepare('JSON_CONTAINS(shipping_methods, %s)', wp_json_encode($shpping_method));
				next($json_search);
			}

			$prepared_sql .= " AND (" . implode(' OR ', $shipping_method_sql) . ")";

			if (current_user_can('manage_woocommerce')) {
				$prepared_sql .= " AND status IN ('active', 'development')";
			} else {
				$prepared_sql .= " AND status = 'active'";
			}

			$rule_ids = $wpdb->get_col($prepared_sql); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			self::$shipping_rate_ids[$instance_id] = $rule_ids;
		}

		return array_map(fn($rule_id) => self::get($rule_id), self::$shipping_rate_ids[$instance_id]);
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
	 * Set ShipFlex rule data
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

		$features_base_models = array_map(fn($feature) => $feature->get_configuration_value('base_model'), Feature::get_features());
		foreach ($data as $key => $value) {
			if (in_array($key, $features_base_models)) {
				$this->feature_settings[$key] = $value;
			} else {
				$this->{$key} = $value;
			}
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
		unset($rule_data['updated_at'], $rule_data['created_at'], $rule_data['meta_data'], $rule_data['feature_settings']);
		foreach ($this->feature_settings as $feature_id => $feature_data) {
			$rule_data[$feature_id] = $feature_data;
		}

		$rule_editor_settings = Settings_Fields::get_instance('rule-editor');

		$rule_models = apply_filters('shipflex/rule_models', $rule_editor_settings->get_models());
		return (object) Utils::deep_merge_arrays($rule_models, array_merge($this->meta_data, $rule_data));
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

					$method_title = sprintf(esc_html__('%s - All rates', 'shipflex'), $zone->get_zone_name());
					if (!in_array($method_slug, $this->shipping_methods)) {
						$method_slug = $shipping_method->id;
						$method_title = esc_html__('All rates', 'shipflex');
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
	 * Get feature object of provided feature id
	 * 
	 * @since 1.0.0
	 * @return object
	 */
	public function get_feature_object($feature_id) {
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

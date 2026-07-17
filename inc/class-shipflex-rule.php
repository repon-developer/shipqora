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
		$reward_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", $wpdb->shipflex_rules_table, $id), ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return new self($reward_data);
	}

	/**
	 * ID of current item
	 * 
	 * @var int
	 */
	public $id = 0;

	/**
	 * Title of rule
	 * 
	 * @var string
	 */
	public $title = '';

	/**
	 * Status of rule
	 * 
	 * @var string
	 */
	public $status = 'development';

	/**
	 * Conditions of this rule
	 * 
	 * @var array
	 */
	public $conditions = [];

	/**
	 * Settings of this rule
	 * 
	 * @var array
	 */
	public $settings = [];

	/**
	 * Created time of this rule
	 * 
	 * @var stirng
	 */
	public $created_at = '';

	/**
	 * Meta data
	 * 
	 * @var array
	 */
	public $meta_data = [];

	/**
	 * Get array properties
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_array_properties() {
		return array('conditions', 'settings');
	}

	/**
	 * Constructor.
	 */
	public function __construct($data = array()) {
		$this->created_at = gmdate('Y-m-d H:i:s');
		if (is_object($data)) {
			$data = (array) $data;
		}

		if (!is_array($data)) {
			return;
		}

		$array_properties = $this->get_array_properties();
		while ($key = current($array_properties)) {
			if (array_key_exists($key, $data)) {
				$this->{$key} = Utils::json_string_to_array($data[$key]);
				unset($data[$key]);
			}

			next($array_properties);
		}

		foreach ($data as $key => $value) {
			$this->{$key} = $value;
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
	 * Get current reward id
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
	 * Save reward
	 * 
	 * @since 1.0.0
	 * @return integer
	 */
	public function save() {
		global $wpdb;

		$data = get_object_vars($this);
		unset($data['meta_data']);

		$array_properties = $this->get_array_properties();
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
		$clean_properties = array('meta_data', 'created_at');
		while ($key = current($clean_properties)) {
			unset($rule_data[$key]);
			next($clean_properties);
		}

		$rule_models = apply_filters('shipflex/rule_models', array(
			'conditions' => array(),
			'condition_groups_match' => 'any',
		));

		return (object) Utils::deep_merge_arrays($rule_models, array_merge($this->meta_data, $rule_data));
	}
}

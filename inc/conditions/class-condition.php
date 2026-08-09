<?php

namespace ShipQora\Condition;

if (!defined('ABSPATH')) {
	exit;
}

class Condition {

	/**
	 * Hold id of condition type
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	private $id = '';

	/**
	 * Hold group id of condition type
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	private $group_id = '';

	/**
	 * Hold option label of this condition type
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	private $label = '';

	/**
	 * Hold priority of this condition
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	private $priority = 10;
	
	/**
	 * Hold model key of this condition
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	private $model_key = 'group';

	/**
	 * Hold template of this condition type
	 * 
	 * @since 1.0.0
	 * @var mixed
	 */
	private $template = null;

	/**
	 * Hold validate callback of this condition type
	 * 
	 * @since 1.0.0
	 * @var mixed
	 */
	private $validate_callback = null;

	/**
	 * Hold all condition data
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	private $condition_data = array();

	/**
	 * Hold extra data of condition type
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	private $extra_data = array();

	/**
	 * Constructor.
	 */
	public function __construct($condition_id, $data) {
		$this->id = $condition_id;
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
		return isset($this->extra_data[$key]);
	}

	/**
	 * Set magic method
	 * 
	 * @since 1.0.0
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set($key, $value) {
		$this->extra_data[$key] = $value;
	}

	/**
	 * Get magic method
	 * 
	 * @since 1.0.0
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key) {
		return isset($this->extra_data[$key]) ? $this->extra_data[$key] : null;
	}

	/**
	 * Call magic method
	 * 
	 * @since 1.0.0
	 * @return mixed
	 */
	public function __call($name, $arguments) {
		if (str_starts_with($name, 'get_')) {
			$property = substr($name, 4);
			return $this->{$property};
		}
	}

	/**
	 * Get priority
	 * 
	 * @since 1.0.0
	 * @return float
	 */
	public function get_priority() {
		return floatval($this->priority);
	}

	/**
	 * Get value of key
	 * 
	 * @since 1.0.0
	 * @return mixed
	 */
	public function get_value($key = null) {
		if (empty($key)) {
			$key = $this->model_key;
		}

		return isset($this->condition_data[$key]) ? $this->condition_data[$key] : '';
	}

	/**
	 * Render template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function render_template() {
		if ($this->template && is_callable($this->template)) {
			call_user_func($this->template, $this);
		}
	}

	/**
	 * Validate condition
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function validate_condition($condition_data) {
		$this->condition_data = $condition_data;
		if ($this->validate_callback && is_callable($this->validate_callback)) {
			return call_user_func($this->validate_callback, $this);
		}

		return false;
	}

	/**
	 * Get matched postal codes
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_matched_postal_codes($customer_postal_code, $postal_codes) {
		$postal_codes = Utils::comma_separator_to_array($postal_codes);

		return array_filter($postal_codes, function ($postal_code) use ($customer_postal_code) {
			$regex = str_replace(['\*', '\?'], ['.*', '.'], preg_quote($postal_code, '/'));
			return preg_match('/^' . $regex . '$/i', $customer_postal_code);
		});
	}
}

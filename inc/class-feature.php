<?php

namespace ShipFlex;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Feature class
 */
final class Feature {

	/**
	 * Get available reward types configurations
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_rewards_configuration($add_general = false) {
		$rewards_configuration = apply_filters('shipflex/rewards_configuration', array());

		if ($add_general) {
			$rewards_configuration['general'] = array(
				'priority' => 0,
				'base_model' => 'general_settings',
				'predefined_badge_templates' => array(
					'general-product-listing' => array(
						'model_key' => 'general_settings.product_listing_badge',
						'label' => __('General - Product Listing Badge', 'shipflex'),
					),

					'general-single-product' => array(
						'model_key' => 'general_settings.single_product_badge',
						'label' => __('General - Single Product Badge', 'shipflex'),
						'allow_badge_templates' => array(
							'general-product-listing'
						)
					)
				)
			);
		}

		uasort($rewards_configuration, function ($a, $b) {
			if (!isset($a['priority'])) {
				$a['priority'] = 10;
			}

			if (!isset($b['priority'])) {
				$b['priority'] = 10;
			}

			return $a['priority'] > $b['priority'] ? 1 : -1;
		});


		return $rewards_configuration;
	}

	/**
	 * Constructor.
	 */
	public function __construct($data = array()) {
		
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
	 * Get all condition groups
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_conditions() {
		$groups = $this->condition_groups;
		if (!is_array($groups)) {
			$groups = array();
		}

		return $groups;
	}
}

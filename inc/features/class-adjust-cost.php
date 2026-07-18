<?php

namespace ShipFlex\Feature;

use ShipFlex\Feature;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Adjust Cost class
 */
final class Adjust_Cost extends Feature {

	/**
	 * Hold the feature id of this feature
	 * 
	 * @var string
	 */
	protected $feature_id = 'adjust-shipping-cost';

	/**
	 * Constructor.
	 */
	public function __construct() {
	}

	/**
	 * Add reward configuration of order discount to the reward types list
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_configuration() {
		return array(
			'priority' => 5,
			'base_model' => 'adjust_shipping_cost',
			'name' => esc_html__('Adjust Shipping Cost', 'shipflex'),
			'description' => esc_html__('Adjust Shipping Cost of exists', 'shipflex'),
		);
	}
}

Feature::add_feature(Adjust_Cost::class);

<?php

namespace ShipQora_WooCommerce;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class for Shipping_Cost
 * 
 * @since 1.0.0
 */
class Shipping_Cost extends \Exception {

	/**
	 * Hold shipping cost
	 * 
	 * @var float
	 */
	private float $amount = 0.00;

	/**
	 * Constructor
	 */
	public function __construct(float $amount) {
		parent::__construct();
		$this->amount = $amount;
	}

	/**
	 * Get amount of shipping cost
	 * 
	 * @since 1.0.0
	 * @return float
	 */
	public function getAmount(): float {
		return $this->amount;
	}
}
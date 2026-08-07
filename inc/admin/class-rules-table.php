<?php

namespace ShipFlex;

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * WP List table of Rewards
 */
class Rule_List_Table extends \WP_List_Table {
	/**
	 * Entry per page
	 * 
	 * @since 1.0.0
	 */
	public $per_page = 15;

	/**
	 * Constructor.
	 * 
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->per_page = $this->get_items_per_page('shipflex_rules_per_page', 15);
		parent::__construct(array('singular' => 'shipflex_rule_table', 'plural' => 'shipflex_rules_table', 'ajax' => false));
	}

	/**
	 * Prepare the items for the table to process
	 * 
	 * @since 1.0.0
	 */
	public function prepare_items() {
		$this->_column_headers = array($this->get_columns());

		global $wpdb;

		$page_number = $this->get_pagenum();
		$offset = absint($page_number - 1) * $this->per_page;

		$prepared_sqls = array(
			'select' => $wpdb->prepare("SELECT * FROM %i", $wpdb->shipflex_rules_table),
			'order' => "ORDER BY id DESC",
			'limit' => $wpdb->prepare("LIMIT %d, %d", $offset, $this->per_page)
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rules = $wpdb->get_results(implode(' ', $prepared_sqls), ARRAY_A);

		$this->items = array_map(fn($item) => new ShipFlex_Rule($item), $rules);

		unset($prepared_sqls['limit']);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_rules = $wpdb->get_var(implode(' ', $prepared_sqls));

		$this->set_pagination_args(array(
			'per_page'    => $this->per_page,
			'total_items' => $total_rules,
		));
	}

	/**
	 * Set bulk action for table
	 * 
	 * @since 1.0.0
	 */
	public function get_bulk_actions() {
		return array('bulk-delete' => __('Delete', 'shipflex'));
	}

	/**
	 * Get all available column of table
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => esc_html__('Title', 'shipflex'),
			'shipping_methods' => esc_html__('Shipping Methods', 'shipflex'),
			'features' => esc_html__('Active Features', 'shipflex'),
			'status' => esc_html__('Status', 'shipflex'),
			'updated_at' => esc_html__('Updated', 'shipflex'),
			'created_at' => esc_html__('Created', 'shipflex'),
		);

		return $columns;
	}

	/**
	 * Define what data to show on each column of the table
	 * 
	 * @param  String $column_name - Current column name
	 * @since 1.0.0
	 */
	public function column_default($shipflex_rule, $column_name) {
	}

	/**
	 * Checkbox column 
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function column_cb($shipflex_rule) {
		printf('<input type="checkbox" name="rules[]" value="%d" />', esc_attr($shipflex_rule->get_id()));
	}

	/**
	 * Title column 
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function column_title($shipflex_rule) {
		$edit_url = add_query_arg(array(
			'id' => $shipflex_rule->get_id(),
		), menu_page_url('shipflex-edit', false));

		printf('<strong><a class="row-title" href="%s">%s</a></strong>', esc_url($edit_url), esc_html($shipflex_rule->title));

		$menu_page = menu_page_url('shipflex', false);

		$row_actions[] = sprintf('<a href="%s">%s</a>', esc_url($edit_url), __('Edit', 'shipflex'));

		$delete_url = add_query_arg(array('id' => $shipflex_rule->get_id(), 'delete' => wp_create_nonce('shipflex/rule_delete_nonce')), $menu_page);
		$row_actions[] = sprintf('<a href="%s" class="delete-rule">%s</a>', esc_url($delete_url), __('Delete', 'shipflex'));

		echo '<div class="row-actions">' . wp_kses_post(implode(' | ', $row_actions)) . '</div>';
	}

	/**
	 * Shipping Methods Column 
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function column_shipping_methods($shipflex_rule) {
		$shipping_methods = $shipflex_rule->get_shipping_methods();

		$html_lists = array();

		foreach ($shipping_methods as $shipping_method) {
			$url_args = array('page' => 'wc-settings', 'tab' => 'shipping');
			if ($shipping_method['zone_id'] > 0) {
				$url_args['zone_id'] = $shipping_method['zone_id'];
			}

			if ($shipping_method['id'] > 0) {
				unset($url_args['zone_id']);
				$url_args['instance_id'] = $shipping_method['id'];
			}

			$shipping_method_text = sprintf(
				'<a target="_blank" href="%s">%s</a>',
				add_query_arg($url_args),
				$shipping_method['name']
			);

			$html_lists[] = '<li>' . $shipping_method_text . '</li>';
		}

		echo wp_kses_post('<ul class="list-item">' . implode('', $html_lists) . '</ul>');
	}

	/**
	 * Active Features Column 
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function column_features($shipflex_rule) {
		$activated_features = array();
		foreach (Feature::get_features() as $feature_id => $feature_object) {
			if ($shipflex_rule->is_feature_enabled($feature_id)) {
				$activated_features[] = sprintf('<li>%s</li>', $feature_object->get_configuration_value('name'));
			}
		}

		echo '<ul class="list-item">' . wp_kses_post(implode('', $activated_features)) . '</ul>';
	}

	/**
	 * Updated at column 
	 * 
	 * @since 1.0.0
	 */
	public function column_updated_at($shipflex_rule) {
		$updated_timestamp = strtotime(wp_date('Y-m-d H:i:s', strtotime($shipflex_rule->updated_at)));
		$readable_diff_time = strtotime(wp_date('Y-m-d H:i:s', strtotime('-3days')));
		if ($updated_timestamp > $readable_diff_time) {
			echo wp_kses_post(human_time_diff($updated_timestamp, current_time('timestamp')) . ' ago<br>');
		}

		printf(
			'%s at %s',
			esc_html(gmdate(get_option('date_format'), $updated_timestamp)),
			esc_html(gmdate(get_option('time_format'), $updated_timestamp))
		);
	}

	/**
	 * Create at column 
	 * 
	 * @since 1.0.0
	 */
	public function column_created_at($shipflex_rule) {
		$created_timestamp = strtotime(wp_date('Y-m-d H:i:s', strtotime($shipflex_rule->created_at)));

		$readable_diff_time = strtotime(wp_date('Y-m-d H:i:s', strtotime('-3days')));
		if ($created_timestamp > $readable_diff_time) {
			echo wp_kses_post(human_time_diff($created_timestamp, current_time('timestamp')) . ' ago<br>');
		}

		printf(
			'%s at %s',
			esc_html(gmdate(get_option('date_format'), $created_timestamp)),
			esc_html(gmdate(get_option('time_format'), $created_timestamp))
		);
	}

	/**
	 * Rule status 
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function column_status($shipflex_rule) {
		$all_statuses = Utils::get_statuses();

		$status = !empty($all_statuses[$shipflex_rule->status]['label']) ? $all_statuses[$shipflex_rule->status]['label'] : $shipflex_rule->status; ?>
		<div class="shipflex-status-wrapper">
			<span class="shipflex-status shipflex-status-<?php echo esc_attr($shipflex_rule->status) ?>"></span>
			<?php echo esc_html($status); ?>
		</div>
<?php
	}
}

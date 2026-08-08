<?php

namespace ShipQora;

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * WP List table of Rules
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
		$this->per_page = $this->get_items_per_page('shipqora_rules_per_page', 15);
		parent::__construct(array('singular' => 'shipqora_rule_table', 'plural' => 'shipqora_rules_table', 'ajax' => false));
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
			'select' => $wpdb->prepare("SELECT * FROM %i", $wpdb->shipqora_rules_table),
			'order' => "ORDER BY id DESC",
			'limit' => $wpdb->prepare("LIMIT %d, %d", $offset, $this->per_page)
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rules = $wpdb->get_results(implode(' ', $prepared_sqls), ARRAY_A);

		$this->items = array_map(fn($item) => new SHIPQORA_Rule($item), $rules);

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
		return array('bulk-delete' => __('Delete', 'shipqora'));
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
			'title' => esc_html__('Title', 'shipqora'),
			'shipping_methods' => esc_html__('Shipping Methods', 'shipqora'),
			'features' => esc_html__('Active Features', 'shipqora'),
			'status' => esc_html__('Status', 'shipqora'),
			'updated_at' => esc_html__('Updated', 'shipqora'),
			'created_at' => esc_html__('Created', 'shipqora'),
		);

		return $columns;
	}

	/**
	 * Define what data to show on each column of the table
	 * 
	 * @param  String $column_name - Current column name
	 * @since 1.0.0
	 */
	public function column_default($shipqora_rule, $column_name) {
	}

	/**
	 * Checkbox column 
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function column_cb($shipqora_rule) {
		printf('<input type="checkbox" name="rules[]" value="%d" />', esc_attr($shipqora_rule->get_id()));
	}

	/**
	 * Title column 
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function column_title($shipqora_rule) {
		$edit_url = add_query_arg(array(
			'id' => $shipqora_rule->get_id(),
		), menu_page_url('shipqora-edit', false));

		printf('<strong><a class="row-title" href="%s">%s</a></strong>', esc_url($edit_url), esc_html($shipqora_rule->title));

		$menu_page = menu_page_url('shipqora', false);

		$row_actions[] = sprintf('<a href="%s">%s</a>', esc_url($edit_url), __('Edit', 'shipqora'));

		$delete_url = add_query_arg(array('id' => $shipqora_rule->get_id(), 'delete' => wp_create_nonce('shipqora/rule_delete_nonce')), $menu_page);
		$row_actions[] = sprintf('<a href="%s" class="delete-rule">%s</a>', esc_url($delete_url), __('Delete', 'shipqora'));

		echo '<div class="row-actions">' . wp_kses_post(implode(' | ', $row_actions)) . '</div>';
	}

	/**
	 * Shipping Methods Column 
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function column_shipping_methods($shipqora_rule) {
		$shipping_methods = $shipqora_rule->get_shipping_methods();

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
	public function column_features($shipqora_rule) {
		$activated_features = array();
		foreach (Feature::get_features() as $feature_id => $feature_object) {
			if ($shipqora_rule->is_feature_enabled($feature_id)) {
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
	public function column_updated_at($shipqora_rule) {
		$updated_timestamp = strtotime(wp_date('Y-m-d H:i:s', strtotime($shipqora_rule->updated_at)));
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
	public function column_created_at($shipqora_rule) {
		$created_timestamp = strtotime(wp_date('Y-m-d H:i:s', strtotime($shipqora_rule->created_at)));

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
	public function column_status($shipqora_rule) {
		$all_statuses = Utils::get_statuses();

		$status = !empty($all_statuses[$shipqora_rule->status]['label']) ? $all_statuses[$shipqora_rule->status]['label'] : $shipqora_rule->status; ?>
		<div class="shipqora-status-wrapper">
			<span class="shipqora-status shipqora-status-<?php echo esc_attr($shipqora_rule->status) ?>"></span>
			<?php echo esc_html($status); ?>
		</div>
<?php
	}
}

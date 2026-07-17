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

		$select_sql = $wpdb->prepare("SELECT * FROM %i", $wpdb->shipflex_rules_table);

		$where_sql = " WHERE 1 = 1";
		

		$order_sql = $wpdb->prepare(" ORDER BY id DESC LIMIT %d, %d", $offset, $this->per_page);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rewards = $wpdb->get_results($select_sql . $where_sql . $order_sql);

		$this->items = array_map(fn($item) => new ShipFlex_Rule($item), $rewards);

		$prepared_sql = $wpdb->prepare("SELECT count(*) FROM %i", $wpdb->shipflex_rules_table);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_rules = $wpdb->get_var($prepared_sql);

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
			'status' => esc_html__('Status', 'shipflex'),
		);

		$columns['created_at'] = esc_html__('Created Date', 'shipflex');

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
	 */
	public function column_cb($shipflex_rule) {
		return sprintf('<input type="checkbox" name="reward_rules[]" value="%d" />', $shipflex_rule->get_id());
	}

	/**
	 * Title column 
	 * 
	 * @since 1.0.0
	 */
	public function column_title($shipflex_rule) {
		$edit_url = add_query_arg(array(
			'id' => $shipflex_rule->get_id(),
		), menu_page_url('shipflex-edit', false));

		printf('<strong><a class="row-title" href="%s">%s</a></strong>', esc_url($edit_url), esc_html($shipflex_rule->title));

		$menu_page = menu_page_url('shipflex', false);

		$row_actions[] = sprintf('<a href="%s">%s</a>', esc_url($edit_url), __('Edit', 'shipflex'));

		$delete_url = add_query_arg(array('id' => $shipflex_rule->get_id(), 'delete' => wp_create_nonce('shipflex/reward_delete_nonce')), $menu_page);
		$row_actions[] = sprintf('<a href="%s" class="delete-reward">%s</a>', esc_url($delete_url), __('Delete', 'shipflex'));

		echo '<div class="row-actions">' . wp_kses_post(implode(' | ', $row_actions)) . '</div>';
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
		$all_statuses = array(
			'active' => esc_html__('Active', 'shipflex'),
			'disable' => esc_html__('Disabled', 'shipflex'),
			'development' => esc_html__('Development', 'shipflex'),
		);

		$status = !empty($all_statuses[$shipflex_rule->status]) ? $all_statuses[$shipflex_rule->status] : $shipflex_rule->status; ?>
		<div class="reward-status-wrapper">
			<span class="reward-status reward-status-<?php echo esc_attr($shipflex_rule->status) ?>"></span>
			<?php echo esc_html($status); ?>
		</div>
<?php
	}
}

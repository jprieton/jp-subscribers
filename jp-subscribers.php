<?php
/**
 * Plugin Name: JP Subscribe DB
 * Plugin URI:https://github.com/jprieton/jp-subscribe/
 * Description: Ajax suscribe form to DB.
 * Version: 0.1.0
 * Author: Javier Prieto
 * Author URI: https://github.com/jprieton/
 * License: GPL2
 */
defined('ABSPATH') or die("No script kiddies please!");

// Updates
if (is_admin()) {

	if (!class_exists('BFIGitHubPluginUpdater')) {
		require_once __DIR__ . '/updater/BFIGitHubPluginUpdater.php';
	}
	if (!class_exists('Parsedown')) {
		// We're going to parse the GitHub markdown release notes, include the parser
		require_once __DIR__ . '/updater/Parsedown.php';
	}
	// new BFIGitHubPluginUpdater(__FILE__, 'jprieton', 'jp-subscribe');
}

define('JPSDB_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('JPSDB_PLUGIN_URI', plugin_dir_url(__FILE__));

include_once JPSDB_PLUGIN_PATH . 'includes/class-subscribers.php';

//Our class extends the WP_List_Table class, so we need to make sure that it's there
if (!class_exists('WP_List_Table')) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Suscriber_List_Table extends WP_List_Table {

	function get_columns() {
		$columns = array(
//			'subscriber_id' => '<input type="checkbox" />',
				'subscriber_name' => __('Name'),
				'subscriber_email' => __('Email'),
				'subscriber_date' => __('Date')
		);
		return $columns;
	}

	function column_default($item, $column_name) {
		switch ($column_name) {
			case 'subscriber_date':
				return mysql2date(__('Y/m/d'), $item->suscriber_date);
			case 'subscriber_name':
			case 'subscriber_email':
				return $item->{$column_name};
			default:
				return print_r($item, true); //Show the whole array for troubleshooting purposes
		}
	}

	function column_subscriber_id($item) {
		return "<input type='checkbox' name='suscribers[]' id='suscriber_{$item->id}' class='$role' value='{$item->id}' />";
	}

	function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array($columns, $hidden, $sortable);
		global $wpdb, $_wp_column_headers;
		$screen = get_current_screen();
		$wpdb instanceof wpdb;

		/* -- Preparing your query -- */
		$query = "SELECT * FROM {$wpdb->prefix}subscribers";

		/* -- Ordering parameters -- */
		//Parameters that are going to be used to order the result
		$orderby = !empty($_GET["orderby"]) ? mysql_real_escape_string($_GET["orderby"]) : 'ASC';
		$order = !empty($_GET["order"]) ? mysql_real_escape_string($_GET["order"]) : '';
		if (!empty($orderby) && !empty($order)) {
			$query.=' ORDER BY ' . $orderby . ' ' . $order;
		} else {
			$query.=' ORDER BY subscriber_id DESC';
		}

		/* -- Pagination parameters -- */
		//Number of elements in your table?
		$totalitems = $wpdb->query($query); //return the total number of affected rows
		//How many to display per page?
		$perpage = 20;
		//Which page is this?
		$paged = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
		//Page Number
		if (empty($paged) || !is_numeric($paged) || $paged <= 0) {
			$paged = 1;
		}
		//How many pages do we have in total?
		$totalpages = ceil($totalitems / $perpage);
		//adjust the query to take pagination into account
		if (!empty($paged) && !empty($perpage)) {
			$offset = ($paged - 1) * $perpage;
			$query.=' LIMIT ' . (int) $offset . ',' . (int) $perpage;
		}

		/* -- Register the pagination -- */
		$this->set_pagination_args(array(
				"total_items" => $totalitems,
				"total_pages" => $totalpages,
				"per_page" => $perpage,
		));
		//The pagination links are automatically built according to those parameters

		/* -- Register the Columns -- */
		$columns = $this->get_columns();
		$_wp_column_headers[$screen->id] = $columns;

		/* -- Fetch the items -- */
		$this->items = $wpdb->get_results($query);
	}

	public function single_row($item) {
		static $row_class = '';
		$row_class = ( $row_class == '' ? ' class="alternate"' : '' );

		echo '<tr' . $row_class . '>';
		$this->jp_single_row_columns($item);
		echo '</tr>';
	}

	private function jp_single_row_columns($item) {
		list( $columns, $hidden ) = $this->get_column_info();

		foreach ($columns as $column_name => $column_display_name) {
			$class = "class='$column_name column-$column_name'";

			$style = '';
			if (in_array($column_name, $hidden)) $style = ' style="display:none;"';

			$attributes = "$class$style";

			if ('cb' == $column_name) {
				echo '<th scope="row" class="check-column">';
				echo $this->column_cb($item);
				echo '</th>';
			} elseif (method_exists($this, 'column_' . $column_name)) {
				echo "<td $attributes>";
				echo call_user_func(array($this, 'column_' . $column_name), $item);
				echo "</td>";
			} else {
				echo "<td $attributes>";
				echo $this->column_default($item, $column_name);
				echo "</td>";
			}
		}
	}

}

if (is_admin()) {
	add_action('admin_menu', function () {
		add_menu_page('JP Subscribers', 'JP Subscribers', 'edit_others_posts', 'jp-subscribers', 'my_plugin_options', 'dashicons-email-alt');
	});
}

function my_plugin_options() {
	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}
	?>
	<div class="wrap">
		<h2>Suscriptores</h2>
		<?php
		//Prepare Table of elements
		$wp_list_table = new Suscriber_List_Table();
		$wp_list_table->prepare_items();
		$wp_list_table->display();
		?>
	</div>
	<?php
}

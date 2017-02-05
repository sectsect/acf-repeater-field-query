<?php
/*
Plugin Name: ACF Repeater Field Query
Plugin URI: https://github.com/sectsect/acf-repeater-field-query
Description: Modify the Query to multiple dates in a post For Advanced Custom Field "Repeater Field".
Author: SECT INTERACTIVE AGENCY
Version: 1.0.1
Author URI: https://www.ilovesect.com/
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) || ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-acf-repeater-field-query-activator.php
 */
function activate_acf_repeater_field_query() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-acf-repeater-field-query-activator.php';
	Acf_Repeater_Field_Query_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-acf-repeater-field-query-deactivator.php
 */
function deactivate_acf_repeater_field_query() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-acf-repeater-field-query-deactivator.php';
	Acf_Repeater_Field_Query_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_acf_repeater_field_query' );
register_deactivation_hook( __FILE__, 'deactivate_acf_repeater_field_query' );

global $wpdb;
define('TABLE_NAME', $wpdb->prefix.'acf_repeater_field_query');
define('ACF_RFQ_POST_TYPE', get_option('acf_rfq_posttype'));
define('ACF_RFQ_TAXONOMY', get_option('acf_rfq_taxonomy'));
define('ACF_RFQ_ACF_REPEATER', get_option('acf_rfq_dategroup'));
define('ACF_RFQ_ACF_REPEATER_DATE', get_option('acf_rfq_datefield'));
define('ACF_RFQ_ACF_REPEATER_STARTTIME', get_option('acf_rfq_starttimefield'));
define('ACF_RFQ_ACF_REPEATER_FINISHTIME', get_option('acf_rfq_finishtimefield'));

require_once plugin_dir_path( __FILE__ ) . 'functions/composer/vendor/autoload.php';
require_once plugin_dir_path( __FILE__ ) . 'functions/functions.php';
require_once plugin_dir_path( __FILE__ ) . 'functions/custom-queries.php';
require_once plugin_dir_path( __FILE__ ) . 'functions/admin.php';
require_once plugin_dir_path( __FILE__ ) . 'functions/calendar.php';

if ( ! class_exists( 'ACF_RFQ' ) ) {
    class ACF_RFQ
    {
        function __construct() {
			add_action('acf/save_post', array( $this, 'save_event' ), 20);	// https://www.advancedcustomfields.com/resources/acf-save_post/
			add_action('before_delete_post', array( $this, 'delete_event' ));
			add_action('pre_get_posts', array( $this, 'acf_rfq_pre_get_posts' ));
			add_filter('query_vars', array( $this, 'acf_rfq_register_query_vars' ));
        }

		/*==================================================
		    Save to "wp_acf_repeater_field_query" table.
		================================================== */
		// Sorting by the value of the second dimension in the array of two-dimensional array.
		function sortArrayByKey(&$array, $sortKey, $sortType = SORT_ASC)
		{
		    $tmpArray = array();
		    foreach ($array as $key => $row) {
		        $tmpArray[$key] = $row[$sortKey];
		    }
		    array_multisort($tmpArray, $sortType, $array);
		    unset($tmpArray);
		}

		function save_event($post_id)
		{
		    if (get_post_type($post_id) == ACF_RFQ_POST_TYPE && get_field(ACF_RFQ_ACF_REPEATER, $post_id)) {
		        global $wpdb;
		        $fields = get_field(ACF_RFQ_ACF_REPEATER, $post_id);
		        $this->sortArrayByKey($fields, ACF_RFQ_ACF_REPEATER_DATE);  // sorting by "date"

		        $sql = 'DELETE FROM '.TABLE_NAME." WHERE post_id = $post_id;";
		        $sql = $wpdb->prepare($sql);
		        $result = $wpdb->query($sql);

		        foreach ($fields as $field) {
		            // $date = str_replace('-', '', $field[ACF_RFQ_ACF_REPEATER_DATE]);
					$date = intval($field[ACF_RFQ_ACF_REPEATER_DATE]);
		            if ($field[ACF_RFQ_ACF_REPEATER_STARTTIME]) {
		                $stime = str_replace(':', '', $field[ACF_RFQ_ACF_REPEATER_STARTTIME].':00');
		            } else {
		                $stime = 'null';
		            }
		            if ($field[ACF_RFQ_ACF_REPEATER_FINISHTIME]) {
		                $ftime = str_replace(':', '', $field[ACF_RFQ_ACF_REPEATER_FINISHTIME].':00');
		            } else {
		                $ftime = 'null';
		            }
		            $sql = 'INSERT INTO '.TABLE_NAME." (post_id, date, starttime, finishtime) VALUES ($post_id, $date, $stime, $ftime);";
		            $sql = $wpdb->prepare($sql);
		            $result = $wpdb->query($sql);
		        }
		    }
		}

		/*==================================================
		    Delete the data in "wp_acf_repeater_field_query" table.
		================================================== */
		function delete_event($post_id)
		{
		    if (get_post_type($post_id) == ACF_RFQ_POST_TYPE && get_field(ACF_RFQ_ACF_REPEATER, $post_id)) {
		        global $wpdb;
		        $sql = 'DELETE FROM '.TABLE_NAME." WHERE post_id = $post_id;";
		        $sql = $wpdb->prepare($sql);
		        $result = $wpdb->query($sql);
		    }
		}

		/*==================================================
		    Modify the Main Query
		================================================== */
		function acf_rfq_pre_get_posts($query)
		{
		    // if( !empty($query->query_vars['calendar']) ){
		    // 	$venue = $query->get('calendar');
		    // 	$query->set('calendar',$venue);
		    // }

		    if (is_admin() || !$query->is_main_query()) {
		        return;
		    }

		    // if(!is_user_logged_in()){	//	For "Preview" on status: future/draft/private
		    // 	$query->set('post_status', 'publish');
		    // }

		    if ($query->is_post_type_archive(ACF_RFQ_POST_TYPE)) {
		        $query->set('posts_per_page', -1);

		        add_filter('posts_fields', 'event_fields', 10, 2);
		        add_filter('posts_join', 'event_join', 10, 2);
		        add_filter('posts_where', 'event_where', 10, 2);
		        add_filter('posts_orderby', 'event_orderby', 10, 2);
		        // if (!is_date()) {
		        //     add_filter('posts_groupby', 'event_groupby', 10, 2);        // ========== Disabled the outputs to duplicate post on Page "post_type_archive". (It is sorted based on the last date to hold) ==========
		        // }
		    }

		    if (ACF_RFQ_TAXONOMY) {
		        if ($query->is_tax(ACF_RFQ_TAXONOMY)) {
		            add_filter('posts_fields', 'event_fields', 10, 2);
		            add_filter('posts_join', 'event_join', 10, 2);
		            add_filter('posts_where', 'event_where', 10, 2);
		            add_filter('posts_orderby', 'event_orderby', 10, 2);
		        //    add_filter('posts_groupby', 'event_groupby', 10, 2);        // ========== Disabled the outputs to duplicate post on Page "taxonomy". (It is sorted based on the last date to hold) ==========
		        }
		    }
		}

		/*==================================================
		    Add query_vars for calendar
		================================================== */
		function acf_rfq_register_query_vars($qvars)
		{
		    $qvars[] = 'calendar';

		    return $qvars;
		}
	}
	new ACF_RFQ();
}

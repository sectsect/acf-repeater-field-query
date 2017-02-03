<?php
/*
Plugin Name: ACF Repeater Field Query
Plugin URI: https://github.com/sectsect/acf-repeater-field-query
Description: Modify the Query to multiple dates in a post For Advanced Custom Field "Repeater Field".
Author: SECT INTERACTIVE AGENCY
Version: 1.0.1
Author URI: https://www.ilovesect.com/
*/

global $wpdb;
define('TABLE_NAME', $wpdb->prefix.'acf_repeater_field_query');

define('ACF_RFQ_POST_TYPE', get_option('acf_rfq_posttype'));
define('ACF_RFQ_TAXONOMY', get_option('acf_rfq_taxonomy'));
define('ACF_RFQ_ACF_REPEATER', get_option('acf_rfq_dategroup'));
define('ACF_RFQ_ACF_REPEATER_DATE', get_option('acf_rfq_datefield'));
define('ACF_RFQ_ACF_REPEATER_STARTTIME', get_option('acf_rfq_starttimefield'));
define('ACF_RFQ_ACF_REPEATER_FINISHTIME', get_option('acf_rfq_finishtimefield'));

if (is_admin()) {
    register_activation_hook(__FILE__, 'acf_rfq_activate');
}
function acf_rfq_activate()
{
    global $wpdb;
    $acf_rfq_db_version = '1.0';
    $installed_ver = get_option('acf_rfq_version');
    $charset_collate = $wpdb->get_charset_collate();
    if ($installed_ver != $acf_rfq_db_version) {
        $sql = 'CREATE TABLE '.TABLE_NAME." (
              event_id bigint(20) NOT NULL AUTO_INCREMENT,
              post_id bigint(20) NOT NULL,
              date date NOT NULL,
              starttime time,
              finishtime time,
              PRIMARY KEY  (event_id, post_id)
            ) $charset_collate;";
        require_once ABSPATH.'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        update_option('acf_rfq_version', $acf_rfq_db_version);
    }
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
        sortArrayByKey($fields, ACF_RFQ_ACF_REPEATER_DATE);  // sorting by "date"

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
add_action('acf/save_post', 'save_event', 20);	// https://www.advancedcustomfields.com/resources/acf-save_post/

/*==================================================
    Delete the data in "wp_acf_repeater_field_query" table.
================================================== */
add_action('before_delete_post', 'delete_event');
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
add_action('pre_get_posts', 'acf_rfq_pre_get_posts');

/*==================================================
    Add query_vars for calendar
================================================== */
function acf_rfq_register_query_vars($qvars)
{
    $qvars[] = 'calendar';

    return $qvars;
}
add_filter('query_vars', 'acf_rfq_register_query_vars');
/*==================================================
    Modify & Setting the Sub Query @ http://bradt.ca/blog/extending-wp_query/
================================================== */
class ACF_RFQ_Query extends WP_Query
{
    public function __construct($args = array())
    {
        $args = array_merge($args, array(
            'post_type' => ACF_RFQ_POST_TYPE,
        ));
        /*==================================================
            Remove the add_filter('pre_get_posts').
        ================================================== */
        remove_filter('posts_fields', 'event_fields', 10, 2);
        remove_filter('posts_join', 'event_join', 10, 2);
        remove_filter('posts_where', 'event_where', 10, 2);
        remove_filter('posts_where', 'calendar_where', 10, 2);
        remove_filter('posts_orderby', 'event_orderby', 10, 2);
        remove_filter('post_limits', 'event_limits', 10, 2);
        remove_filter('posts_groupby', 'event_groupby', 10, 2);

        add_filter('posts_fields', 'event_fields', 10, 2);
        add_filter('posts_join', 'event_join', 10, 2);
        if ($args['calendar']) {
            add_filter('posts_where', 'calendar_where', 10, 2);
        } else {
            add_filter('posts_where', 'event_where', 10, 2);
        }
        add_filter('posts_orderby', 'event_orderby', 10, 2);
        add_filter('post_limits', 'event_limits', 10, 2);
    //    add_filter('posts_groupby', 'event_groupby',10, 2);

        parent::__construct($args);

        // Make sure these filters don't affect any other queries
        remove_filter('posts_fields', 'event_fields', 10, 2);
        remove_filter('posts_join', 'event_join', 10, 2);
        remove_filter('posts_where', 'event_where', 10, 2);
        remove_filter('posts_where', 'calendar_where', 10, 2);
        remove_filter('posts_orderby', 'event_orderby', 10, 2);
        remove_filter('post_limits', 'event_limits', 10, 2);
    //    remove_filter('posts_groupby', 'event_groupby', 10, 2);
    }
}
/*==================================================
    functions
================================================== */
function event_fields($select)
{
    global $wpdb;
    $select = '* ';

    return $select;
}

function event_join($join)
{
    global $wpdb;
    $join = 'LEFT JOIN '.TABLE_NAME." ON {$wpdb->posts}.ID = ".TABLE_NAME.'.post_id';

    return $join;
}

function event_where($where)
{
    if (!is_date()) {
        // If you have set the 'finishtime', it does not appear that post when it passes your set time. (Default: the day full)
        if (!ACF_RFQ_ACF_REPEATER_FINISHTIME) {
            $today = date_i18n('Ymd');
            $where = " AND post_type = '".ACF_RFQ_POST_TYPE."' AND post_status = 'publish' AND date >= $today ";
        } else {
            $currenttime = date_i18n('YmdHis');
            $where = " AND post_type = '".ACF_RFQ_POST_TYPE."' AND post_status = 'publish' AND TIMESTAMP(date,finishtime) > $currenttime ";
        }
    } else {
        if (is_year()) {
            $theyaer = get_query_var('year');
            $startday = $theyaer.'-01-01';
            $finishday = $theyaer.'-12-31';
            $where = " AND post_type = '".ACF_RFQ_POST_TYPE."' AND post_status = 'publish' AND date BETWEEN '$startday' AND '$finishday' ";
        }
        if (is_month()) {
            $themonth = get_query_var('year').'-'.get_query_var('monthnum');
            $startday = $themonth.'-01';
            $finishday = $themonth.'-31';
            $where = " AND post_type = '".ACF_RFQ_POST_TYPE."' AND post_status = 'publish' AND date BETWEEN '$startday' AND '$finishday' ";
        }
        if (is_day()) {
            $thedate = get_query_var('year').'-'.get_query_var('monthnum').'-'.get_query_var('day');
            $theday = date_i18n('Ymd', strtotime($thedate));
            $where = " AND post_type = '".ACF_RFQ_POST_TYPE."' AND post_status = 'publish' AND date = $theday ";
        }
    }

    return $where;
}

function event_orderby($orderby)
{
    return 'date, post_id ASC';
}

function event_limits($limit)
{
    return $limit;
}

function event_groupby($groupby)
{
    global $wpdb;
    if (is_post_type_archive(ACF_RFQ_POST_TYPE)) {    // In the case of "is_post_type_archive()", it is summarized in groupby in order not to output a duplicate post.
        $groupby = "{$wpdb->posts}.ID";
    }

    return $groupby;
}

// ========== For calendar ========== (Date starts from first day in the month.)
function calendar_where($where)
{
    $today = date_i18n('Ym'.'01');
    $where = " AND post_type = '".ACF_RFQ_POST_TYPE."' AND post_status = 'publish' AND date >= $today ";

    return $where;
}

/*==================================================
    Add Menu Page
================================================== */
add_action('admin_menu', 'acf_rfq_menu');
function acf_rfq_menu()
{
    $page_hook_suffix = add_options_page('ACF Repeater Field Query', 'ACF Repeater Field Query', 8, 'acf_rfq_menu', 'acf_rfq_options_page');
    add_action('admin_print_styles-' . $page_hook_suffix, 'acf_rfq_admin_styles');
    add_action('admin_print_scripts-' . $page_hook_suffix, 'acf_rfq_admin_scripts');    // @ https://wpdocs.osdn.jp/%E9%96%A2%E6%95%B0%E3%83%AA%E3%83%95%E3%82%A1%E3%83%AC%E3%83%B3%E3%82%B9/wp_enqueue_script#.E3.83.97.E3.83.A9.E3.82.B0.E3.82.A4.E3.83.B3.E7.AE.A1.E7.90.86.E7.94.BB.E9.9D.A2.E3.81.AE.E3.81.BF.E3.81.A7.E3.82.B9.E3.82.AF.E3.83.AA.E3.83.97.E3.83.88.E3.82.92.E3.83.AA.E3.83.B3.E3.82.AF.E3.81.99.E3.82.8B
    add_action('admin_init', 'register_acf_rfq_settings');
}
function acf_rfq_admin_styles()
{
    wp_enqueue_style('select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css', array());
}
function acf_rfq_admin_scripts()
{
    wp_enqueue_script('select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', array('jquery'));
    wp_enqueue_script('script', plugin_dir_url(__FILE__) . 'admin/js/script.js', array('select2'));
}
function register_acf_rfq_settings()
{
    register_setting('acf_rfq-settings-group', 'acf_rfq_posttype');
    register_setting('acf_rfq-settings-group', 'acf_rfq_taxonomy');
    register_setting('acf_rfq-settings-group', 'acf_rfq_dategroup');
    register_setting('acf_rfq-settings-group', 'acf_rfq_datefield');
    register_setting('acf_rfq-settings-group', 'acf_rfq_starttimefield');
    register_setting('acf_rfq-settings-group', 'acf_rfq_finishtimefield');
}
function acf_rfq_options_page()
{
    require_once plugin_dir_path(__FILE__) . 'admin/index.php';
}

/*==================================================
    Add date column to only list-page for a Specific Post Type
================================================== */
    function acf_rfq_manage_posts_columns($columns)
    {
        $columns['eventdate'] = "Event Day<span style ='font-size: 11px; color: #999; margin-left: 12px;'>Today: " . date_i18n('Y-m-d') . "</span>";
        if (ACF_RFQ_ACF_REPEATER_STARTTIME || ACF_RFQ_ACF_REPEATER_FINISHTIME) {
            $columns['eventtime'] = "Time";
        }
        return $columns;
    }
    function acf_rfq_add_column($column_name, $postID)
    {
        if ($column_name == "eventdate") {
            $fields = get_field(ACF_RFQ_ACF_REPEATER, $postID);
            sort($fields);
            echo '<ul style="padding: 0; margin: 0;">';
            foreach ($fields as $field) {
				if (!$field[ACF_RFQ_ACF_REPEATER_FINISHTIME]) {
	                $today = date_i18n("Ymd");
	                $thedate = date('Ymd', strtotime($field['date']));
	                if ($thedate < $today) {
	                    $finish = ' class="finish"';
	                } elseif ($thedate == $today) {
	                    $finish = ' class="theday"';
	                } else {
	                    $finish = '';
	                }
				} else {
					$today = date_i18n("Ymd");
	                $thedate = date('Ymd', strtotime($field['date']));
					$rn = date_i18n("YmdHi");
	                $fintime = date('YmdHi', strtotime($field['date'] . $field[ACF_RFQ_ACF_REPEATER_FINISHTIME]));
	                if ($fintime < $rn) {
	                    $finish = ' class="finish"';
	                } elseif ($thedate == $today && $fintime >= $rn) {
	                    $finish = ' class="theday"';
	                } else {
	                    $finish = '';
	                }
				}
                $wd = date('D', strtotime($field['date']));
                if ($wd === "Sat") {
                    $wd = '<span style="color: #2ea2cc;">' . $wd . '</span>';
                } elseif ($wd === "Sun") {
                    $wd = '<span style="color: #a00;">' . $wd . '</span>';
                } else {
                    $wd = "<span>" . $wd . "</span>";
                }
                echo "<li".$finish.">" . date('Y-m-d', strtotime($field['date'])) . "（" . $wd . "）" . "</li>";
            }
            echo '</ul>';
        } elseif ($column_name == "eventtime") {
            $fields = get_field(ACF_RFQ_ACF_REPEATER, $postID);
            sort($fields);
            echo '<ul style="padding: 0; margin: 0;">';
            foreach ($fields as $field) {
                $today = date_i18n("Ymd");
                $thedate = date('Ymd', strtotime($field['date']));
                if ($thedate < $today) {
                    $finish = ' class="finish"';
                } elseif ($thedate == $today) {
                    $finish = ' class="theday"';
                } else {
                    $finish = '';
                }
                echo "<li".$finish.">" . date('H:i', strtotime($field['starttime'])) . " - " . date('H:i', strtotime($field['finishtime'])) . "</li>";
            }
            echo '</ul>';
        }
	}
    if(is_admin()){
        global $pagenow;
        if (isset($_GET['post_type']) && $_GET['post_type'] == ACF_RFQ_POST_TYPE && is_admin() && $pagenow == 'edit.php')  {
            add_filter('manage_posts_columns', 'acf_rfq_manage_posts_columns');
	        add_action('manage_posts_custom_column', 'acf_rfq_add_column', 10, 2);
        }
    }

/*==================================================
    Add CSS to edit.php
================================================== */
if (is_admin()) {
    global $pagenow;
    if (isset($_GET['post_type']) && $_GET['post_type'] == ACF_RFQ_POST_TYPE && is_admin() && $pagenow == 'edit.php') {
        wp_enqueue_style('admin-edit', plugin_dir_url(__FILE__) . 'admin/css/admin-edit.css', array());
    }
}

/*==================================================
    Get Custom post type day_link
================================================== */
function get_post_type_date_link($post_type, $year, $month = 0, $day = 0)
{
    global $wp_rewrite;
    $post_type_obj = get_post_type_object($post_type);
    $post_type_slug = $post_type_obj->rewrite['slug'] ? $post_type_obj->rewrite['slug'] : $post_type_obj->name;
    if ($day) { // day archive link
        // set to today's values if not provided
        if (!$year) {
            $year = gmdate('Y', current_time('timestamp'));
        }
        if (!$month) {
            $month = gmdate('m', current_time('timestamp'));
        }
        $link = $wp_rewrite->get_day_permastruct();
    } elseif ($month) { // month archive link
        if (!$year) {
            $year = gmdate('Y', current_time('timestamp'));
        }
        $link = $wp_rewrite->get_month_permastruct();
    } else { // year archive link
        $link = $wp_rewrite->get_year_permastruct();
    }
    if (!empty($link)) {
        $link = str_replace('%year%', $year, $link);
        $link = str_replace('%monthnum%', zeroise(intval($month), 2), $link);
        $link = str_replace('%day%', zeroise(intval($day), 2), $link);

        return home_url("$post_type_slug$link");
    }

    return home_url("$post_type_slug");
}

/*==================================================
    Load CalendR Class
================================================== */
require_once plugin_dir_path(__FILE__) . 'composer/vendor/autoload.php';
/*==================================================
    Event Calendar (archive)
================================================== */
function acf_rfq_calendar($args)
{
    if (ACF_RFQ_POST_TYPE):
		$defaults = array(
			'dates'        => array(),
			'months'       => array(),
			'weekdayLabel' => 'default',
			'weekdayBase'  => 0,	 // 0:sunday ～ 6:saturday
			'element'      => 'div',
			'class'        => ''
		);
		$d = wp_parse_args($args, $defaults);
		extract($d, EXTR_SKIP);

	    $locale = new WP_Locale();
		if($weekdayLabel == 'en'){
			$wd = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
		}else{
			$wd = array_values($locale->weekday_abbrev);
		}
	    $wd_en        = array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');
	    $today        = date_i18n('Ymd');
	    $factory      = new CalendR\Calendar();
		$num = 1;
	    foreach ($months as $month):
			$month = $month . "01";
	        $month = $factory->getMonth(date('Y', strtotime($month)), date('m', strtotime($month)));
?>
	<<?php echo esc_html($element); ?> class="calendar-<?php echo $num; ?> calendar-<?php echo date('Y', strtotime($month)) . "-" . date('m', strtotime($month)) ?><?php if($class): ?> <?php echo esc_html($class); ?><?php endif; ?>">
        <header>
        	<h4><?php echo $month->format('M'); ?></h4>
        </header>
        <table cellspacing="0" cellpadding="0" border="0">
            <thead>
				<tr>
					<?php
                        for ($i = 0; $i < 7; ++$i) {
                            $weekday     = ($weekdayBase + $i) % 7;
                            $weekdayText = $wd[$weekday];
                            $weekdayEn   = $wd_en[$weekday];
                            echo '<th class="dayweek ' . $weekdayEn . '">'. $weekdayText. '</th>';
                        }
					?>
				</tr>
        	</thead>
        	<tbody>
                <?php foreach ($month as $week): ?>
                    <tr>
                        <?php foreach ($week as $day): ?>
                            <td class="<?php echo mb_strtolower($day->format('D')); ?><?php if ($day->format('Ymd') === $today): ?> today<?php endif ?><?php if (!$month->includes($day)): ?> out-of-month<?php endif; ?>">
                                <?php
                                    if ($month->includes($day) && in_array($day->format('Ymd'), $dates)) {
                                        $href = get_post_type_date_link(ACF_RFQ_POST_TYPE, $day->format('Y'), $day->format('m'), $day->format('d'));
                                        $dayText = '<a href="' . $href . '"><span>' . $day->format('j') . '</span></a>';
                                    } else {
                                        $dayText = $day->format('j');
                                    }
                                    echo $dayText;
                                ?>
                            </td>
                        <?php endforeach ?>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
	</<?php echo esc_html($element); ?>>
<?php
			$num++;
    	endforeach;
    endif;
}
?>

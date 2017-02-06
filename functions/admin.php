<?php
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
	wp_enqueue_style('admin-options', plugin_dir_url(dirname(__FILE__)) . 'admin/css/admin-options.css', array());
}
function acf_rfq_admin_scripts()
{
    wp_enqueue_script('select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', array('jquery'));
    wp_enqueue_script('script', plugin_dir_url(dirname(__FILE__)) . 'admin/js/script.js', array('select2'));
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
    require_once plugin_dir_path(dirname(__FILE__)) . 'admin/index.php';
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
        wp_enqueue_style('admin-edit', plugin_dir_url(dirname(__FILE__)) . 'admin/css/admin-edit.css', array());
    }
}

<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.ilovesect.com/
 * @since      1.0.0
 *
 * @package    Acf_Repeater_Field_Query
 * @subpackage Acf_Repeater_Field_Query/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Acf_Repeater_Field_Query
 * @subpackage Acf_Repeater_Field_Query/includes
 * @author     SECT INTERACTIVE AGENCY <info@sectwebstudio.com>
 */
class Acf_Repeater_Field_Query_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;
	    $acf_rfq_db_version = '1.0';
	    $installed_ver = get_option('acf_rfq_version');
	    $charset_collate = $wpdb->get_charset_collate();
	    if ($installed_ver != $acf_rfq_db_version) {
	        $sql = 'CREATE TABLE '.ACF_RFQ_TABLE_NAME." (
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

}

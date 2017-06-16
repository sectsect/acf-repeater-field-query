# <img src="https://github-sect.s3-ap-northeast-1.amazonaws.com/logo.svg" width="28" height="auto"> ACF Repeater Field Query - For Events -

### Modify the Query to multiple dates in a post For [Advanced Custom Field](https://wordpress.org/plugins/advanced-custom-fields/) "Repeater Field"

## Features

For each `Date and Time` set in the `Repeater Field`, only the scheduled events are output to Archive Page.

- The `Date and Time` set in the `Loop Field` is outputted as `one event`.
- Displayed in order of the most recent event (`ASC`).
- Closed events is not outputted.
- Supply a `function` for calendar :date:

## Requirements

* PHP 5.3+
* Activation [Advanced Custom Field](https://wordpress.org/plugins/advanced-custom-fields/) Plugin.
* Create a `Repeater Field`, and `Date Field` in the Repeater Field w/ [Advanced Custom Field](https://wordpress.org/plugins/advanced-custom-fields/) Plugin.
* A 6-pack of beer🍺 (optional, I guess.)

## Installation

 1. `cd /path-to-your/wp-content/plugins/`
 2. `git clone git@github.com:sectsect/acf-repeater-field-query.git`
 3. Activate the plugin through the 'Plugins' menu in WordPress.  
 You can access the some setting by going to `Settings` -> `ACF Repeater Field Query`.
 4. Setting `Post Type Name`, `Repeater Field Name`, `Date Field Name` in Loop Feld".  
 ( Optional Field: `Taxonomy Name`, `StartTime Field`, `FinishTime Field` )  
 That's it:ok_hand:  
 The main query of your select post types will be modified.

## Fields Structure Example

 <img src="https://github-sect.s3-ap-northeast-1.amazonaws.com/acf-repeater-field-query/screen.png" width="800" height="auto">

## NOTES

* Tested on ACF `v5.5.5`
* If you want to apply to some existing posts, Resave the post.  
* Supports Page `is_date()` includes `is_year()` `is_month()` `is_day()`.
* If you have set the 'FinishTime', it does not appear that post when it passes your set time. (Default: The Day Full)

## Usage Example

You can get a sub query using the `new ACF_RFQ_Query()`

#### Example: Sub Query
```php
<?php
    $ary	 = array();
    $page    = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $perpage = 10;
    $offset  = ($page - 1) * $perpage;
    $args    = array(
        'posts_per_page' => $perpage
    );
    $query = new ACF_RFQ_Query($args);
?>
<?php if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post(); ?>
    // something
<?php endwhile; ?>
<?php endif;?>
<?php wp_reset_postdata(); ?>
```
#### Example: Sub Query For Calendar (Using `acf_rfq_calendar()`)  
```php
<?php
    $dates   = array();
    $args    = array(
        'posts_per_page' => -1,
        'calendar'       => true, // For get the data from not today but first day in this month.
    );
    $query = new ACF_RFQ_Query($args);
?>
<?php if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post(); ?>
<?php
    $date = date('Ymd', strtotime($post->date));
    array_push($dates, $date);
?>
<?php endwhile; endif; ?>
<?php wp_reset_postdata(); ?>

<?php
    // Passing array to acf_rfq Calendar Class.
    $dates  = array_unique($dates);	// Remove some Duplicate Values(Day)
    $date   = new DateTime();
    $months = array();
    for ($i = 0; $i < 3; ++$i) {	  // 3 months Calendar
        if ($i > 0) {
            $date->modify('first day of +1 month');
        } else {
            $date->modify('first day of this month');
        }
        array_push($months, $date->format('Ym'));
    }
	$args = array(
		'dates'        => $dates,		// (array) (required) Array of event Date ('Ymd' format)
		'months'       => $months,		// (array) (required) Array of month to generate calendar ('Ym' format)
		'weekdayLabel' => 'default',	// (string) (optional) Available value: 'default' or 'en' Note: 'default' is based on your wordpress locale setting.
		'weekdayBase'  => 0,			// (integer) (optional) The start weekday. 0:sunday ～ 6:saturday Default: 0
		'element'      => 'div',		// (string) (optional) The element for wraping. Default: 'div'
		'class'        => ''			// (string) (optional) The 'class' attribute value for wrap element. Default: ''
	);
	acf_rfq_calendar($args);
?>
```
#### Example: Sub Query For Calendar (Using Your Calendar Class)
```php
<?php
    $ary	 = array();
    $args    = array(
        'posts_per_page'    => -1,
        'calendar'          => true		// For get the data from not today but first day in this month.
    );
    $query = new ACF_RFQ_Query($args);
?>
<?php if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post(); ?>
<?php
    $date       = date('Ymd', strtotime($post->date));
    $post_id    = $post->ID;
    $perm       = get_the_permalink();
    $title      = get_the_title();
    array_push($ary, array('date' => $date, 'id' => $post_id, 'permlink' => $perm, 'title' => $title));
?>
<?php endwhile; endif; ?>
<?php wp_reset_postdata(); ?>

<?php
    // Passing array to your Calendar Class.
    require_once 'Calendar/Month/Weeks.php';
    calendar($ary, 0);
?>
```
#### Example: Get the "Date", "StartTime" and "FinishTime"
```php
<div id="date">
    <?php echo date('Y-m-d', strtotime($post->date)); ?>
</div>
<time>
    <?php echo date("H:i", strtotime($post->starttime)); ?> ~ <?php echo date("H:i", strtotime($post->finishtime)); ?>
</time>
```

## function

#### acf_rfq_calendar($args)  
##### Parameters

* **dates**
(array) (required) Array of event Date ('Ymd' format).

* **months**
(array) (required) Array of month to generate calendar ('Ym' format)

* **weekdayLabel**
(string) (optional) Available value: `'default'` or `'en'`.  
Default: `'default'`  
:memo: `'default'` is based on your wordpress locale setting.

* **weekdayBase**
(integer) (optional) The start weekday. 0:sunday ～ 6:saturday  
Default: `0`

* **element**
(string) (optional) The element for wraping.  
Default: `'div'`

* **class**
(string) (optional) The 'class' attribute value for wrap element.  
Default: `''`

##### Example

```php
<?php
$args = array(
	'dates'        => $dates,
	'months'       => $months,
	'weekdayLabel' => 'default',
	'weekdayBase'  => 0,
	'element'      => 'div',
	'class'        => 'myclass'
);
acf_rfq_calendar($args);
?>
```

## NOTES for Developer

* This Plugin does not hosting on the [wordpress.org](https://wordpress.org/) repo in order to prevent a flood of support requests from wide audience.

## Change log  
See [CHANGELOG](https://github.com/sectsect/acf-repeater-field-query/blob/master/CHANGELOG.md) file.

## License
See [LICENSE](https://github.com/sectsect/acf-repeater-field-query/blob/master/LICENSE) file.

## Related Plugin
I also have plugin with the same functionality for [Custom Field Suite](https://wordpress.org/plugins/custom-field-suite/) Plugin.  
#### <img src="https://github-sect.s3-ap-northeast-1.amazonaws.com/github.svg" width="18" height="auto"> [CFS Loop Field Query](https://github.com/sectsect/cfs-loop-field-query)

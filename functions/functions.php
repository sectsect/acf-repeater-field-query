<?php
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

function get_months_from_now($num)
{
    if (!$num) {
        $num = 1;
    }
    $date   = new DateTime();
    $months = array();
    for ($i = 0; $i < intval($num); ++$i) {
        if ($i > 0) {
            $date->modify('first day of +1 month');
        } else {
            $date->modify('first day of this month');
        }
        array_push($months, $date->format('Ym'));
    }

    return $months;
}

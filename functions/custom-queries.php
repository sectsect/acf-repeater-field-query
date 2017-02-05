<?php
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

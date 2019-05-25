<?php
if ( ! class_exists( 'ACF_RFQ_Query' ) ) {
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
}

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
			$factory->setFirstWeekday( $weekdayBase );
			$month = $month . '01';
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

<?php
/*
Plugin Name: Exchange Events
Plugin URI: http://exchange.uncc.edu
Description: Plugin to add events to WordPress site.
Version: 1.0.0
Author: Crystal Barton
Author URI: http://www.crystalbarton.com
GitHub Plugin URI: https://github.com/clas-web/exchange-events
*/

//error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

require_once( dirname(__FILE__).'/events-custom-post-type.php' );
//require_once( dirname(__FILE__).'/events-widget.php' );

add_filter( 'the_content', array('Exchange_Events', 'update_content_shortcode') );
add_action( 'wp_head', array('Exchange_Events', 'build_stylesheet_url') );

add_filter( 'template_include', array('Exchange_Events', 'events_template') );
add_filter( 'pre_get_posts', array('Exchange_Events', 'exchange_alter_event_query') );

//add_action( 'get_template_part_loop', array('Exchange_Events', 'events_loop'), 999, 2 );
//add_action( 'get_template_part_content', array('Exchange_Events', 'events_content'), 999, 2 );
//add_filter( 'the_content', array('Exchange_Events', 'update_event_content') );

/**
 *
 */
class Exchange_Events
{



	/**
	 * Constructor.
	 * Private.  Class only has static members.
	 * TODO: look up PHP abstract class implementation.
	 */
	private function __construct() { }



	/**
	 * 
	 */	
	public static function update_content_shortcode( $content )
	{
		//echo 'here i am';
		// [exchange-events
		//    start-date="mm-dd-yyyy"          default = "today"
		//    end-date="mm-dd-yyyy"            default = "2099-12-31"
		//    limit="#"                        default = "3"
		//    format="sidebar|archive"         default = "sidebar"
		//    class=""                         default = ""
		// ]
		
		$matches = NULL;
		$num_matches = preg_match_all("/\[exchange-events(.+)?\]/", $content, $matches, PREG_SET_ORDER);

		if( ($num_matches !== FALSE) && ($num_matches > 0) )
		{
			//echo '<pre> MATCHES: ';
			//var_dump($matches);
			//echo '</pre>';
			for( $i = 0; $i < $num_matches; $i++ )
			{
				$content = str_replace($matches[$i][0], Exchange_Events::process_shortcode($matches[$i][0]), $content);
			}
		}
		
		return $content;
	}



	/**
	 * 
	 */	
	public static function process_shortcode( $shortcode )
	{
		//echo '<pre> SHORTCODE: '.$shortcode.'</pre>';
		
		$content = '';
		
		$start_date = new DateTime();
		$start_date = $start_date->format('Y-m-d');
		$end_date = new DateTime('2099-12-31');
		$end_date = $end_date->format('Y-m-d');
		$limit = 3;
		$format = "sidebar";
		$class = 'exchange-events';

		$m = NULL;
	
		if( preg_match("/start-date=\"([^\"]+)\"/", $shortcode, $m) )
		{
			$date = DateTime::createFromFormat('m-d-Y', $m[1]);
			if( $date !== false ) $start_date = $date;
		}

		if( preg_match("/end-date=\"([^\"]+)\"/", $shortcode, $m) )
		{
			$date = DateTime::createFromFormat('m-d-Y', $m[1]);
			if( $date !== false ) $end_date = $date;
		}

		if( preg_match("/limit=\"([^\"]+)\"/", $shortcode, $m) )
		{
			if( is_numeric($m[1]) )
			{
				$limit = intval($m[1]);
				if( $limit < -1 ) $limit = -1;
			}
		}

		if( preg_match("/format=\"([^\"]+)\"/", $shortcode, $m) )
		{
			$format = $m[1];
			$class .= ' '.$format;
			$class = trim($class);
		}
		else
		{
			$class .= ' sidebar';
		}

		if( preg_match("/class=\"([^\"]+)\"/", $shortcode, $m) )
		{
			$class .= ' '.$m[1];
			$class = trim($class);
		}
		
		//
		// get events
		//
		$args = array(
			'number_of_posts' => $limit,
			'post_type' => Exchange_Events__Event_Custom_Post_Type::$name,
			'meta_key' => 'datetime',
			'orderby' => 'meta_value',
			'order' => 'ASC',
			'meta_query' => array(
				array(
					'key' => 'datetime',
					'value' => $start_date.' 00:00:00',
					'compare' => '>=',
				),
				array(
					'key' => 'datetime',
					'value' => $end_date.' 11:59:59',
					'compare' => '<=',
				)
			)
		);
		
		$events = new WP_Query( $args );

		ob_start();
		?>
		
		<pre>start of events</pre>
		
		<div class="<?php echo $class; ?>">
		
		<?php 
		if( !$events->have_posts() )
		{
			echo '<div class="no-events">No events found.</div>';
		}
		else
		{
			call_user_func( 'Exchange_Events::print_'.$format.'_events', $events );
		}
		?>

		</div>
		
		<pre>end of events</pre>
		
		<?php
		$content = ob_get_contents();
		ob_end_clean();

		wp_reset_postdata();
	
		return $content;
	}



	/**
	 * 
	 */	
	public static function sidebar_event( $event )
	{
		?>
		<div class="event">

			<h3><a href="<?php echo get_permalink($event->ID); ?>"><?php echo $event->post_title; ?></a></h3>
			<div class="contents">
			
				<div class="datetime"><?php echo $event->datetime; ?></div>
				<div class="location"><?php echo $event->location; ?></div>
			
			</div><!-- .contents -->
		
		</div><!-- .event -->
		<?php
	}



	/**
	 * 
	 */	
	public static function print_sidebar_events( $events )
	{
		while( $events->have_posts() )
		{
			$events->next_post();
			$event_post = $events->post;
			$event_post = Exchange_Events::populate_event_data( $event_post );
		
			Exchange_Events::print_event_excerpt( $event_post );
		}
	}



	/**
	 * 
	 */	
	public static function print_archive_events( $events )
	{
		$current_date = new DateTime('1900-01-01');
		$close_previous_day = false;

		while( $events->have_posts() )
		{
			$events->next_post();
			$event_post = $events->post;
			$event_post = Exchange_Events::populate_event_data( $event_post );

			//echo '<pre> EVENT: '; var_dump($event_post); echo '</pre>';

			$same_day = true;
			if( $event_post->dt->format('y-d-M') != $current_date->format('y-d-M') )
			{
				$same_day = false;
				$current_date = $event_post->dt;
				$month = $current_date->format('F');
				$day = $current_date->format('j');
				$weekday = $current_date->format('l');

				if( $close_previous_day )
				{
					?>
					</div>
					<?php
				}

				?>
				<div class="agenda-day">
					<div class="date-label">
						<div class="weekday"><?php echo $weekday; ?></div>
						<div class="month"><?php echo $month; ?></div>
						<div class="day"><?php echo $day; ?></div>
					</div>
				<?php
			
				$close_previous_day = true;
			}
			
			Exchange_Events::print_event_archive_excerpt( $event_post );
		}
		
		?>
		</div>
		<?php		
	}



	/**
	 * 
	 */	
	public static function populate_event_data( $event )
	{
		$dt = get_post_meta( $event->ID, 'datetime', true );
		$datetime = '';
		$date = '';
		$time = '';
		if( !empty($dt) )
		{
			$dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $dt );
			$datetime = $dt->format('F d, Y, g:i A');
			$date = $dt->format('F d, Y');
			$time = $dt->format('g:i A');
		}
		$location = get_post_meta( $event->ID, 'location', true );

		$event->dt = $dt;
		$event->date = $date;
		$event->time = $time;
		$event->datetime = $datetime;
		$event->location = $location;
		
		return $event;
	}



	/**
	 * 
	 */	
	public static function print_event_excerpt( $event )
	{
		?>
		<div class="event">

			<h3><a href="<?php echo get_permalink($event->ID); ?>"><?php echo $event->post_title; ?></a></h3>
			<div class="contents clearfix">
			
				<div class="datetime"><?php echo $event->datetime; ?></div>
				<div class="location"><?php echo $event->location; ?></div>
			
			</div><!-- .contents -->
		
		</div><!-- .event -->
		<?php
	}




	/**
	 * 
	 */	
	public static function print_event_archive_excerpt( $event )
	{
		?>
		<div class="event">

			<h3><a href="<?php echo get_permalink($event->ID); ?>"><?php echo $event->post_title; ?></a></h3>
			<div class="contents clearfix">
			
				<div class="event-info">
					<div class="datetime"><?php echo $event->time; ?></div>
					<div class="location"><?php echo $event->location; ?></div>
				</div>
			
				<div class="excerpt"><?php echo $event->post_excerpt; ?></div>
			
			</div><!-- .contents -->
		
		</div><!-- .event -->
		<?php
	}
	


	function events_template( $template )
	{
		if( is_post_type_archive(Exchange_Events__Event_Custom_Post_Type::$name) )
		{
			$theme_files = array(
				'archive-'.Exchange_Events__Event_Custom_Post_Type::$name.'.php',
				'plugins/archive-'.Exchange_Events__Event_Custom_Post_Type::$name.'.php'
			);
			
			$theme_file = locate_template( $theme_files, false );

			if( $theme_file != '' )
				return $theme_file;
			else
				return plugin_dir_path(__FILE__) . 'archive-event.php';
		}
		
		if( is_singular(Exchange_Events__Event_Custom_Post_Type::$name) )
		{
			$theme_files = array(
				'single-'.Exchange_Events__Event_Custom_Post_Type::$name.'.php',
				'plugins/single-'.Exchange_Events__Event_Custom_Post_Type::$name.'.php'
			);
			
			$theme_file = locate_template( $theme_files, false );
			
			if( $theme_file != '' )
				return $theme_file;
			else
				return plugin_dir_path(__FILE__) . 'single-event.php';
		}

		return $template;
	}



	/**
	 * 
	 */	
	public static function build_stylesheet_url()
	{
		echo '<link rel="stylesheet" href="' . plugin_dir_url( __FILE__ ) . 'style.css" />';
	}

	
	
	/**
	 * Alters the default query made when querying Event items.
	 */
	function exchange_alter_event_query( $wp_query )
	{
		if( $wp_query->query['post_type'] == Exchange_Events__Event_Custom_Post_Type::$name && !is_admin() )
		{
			$wp_query->query_vars['meta_key'] = 'datetime';
			$wp_query->query_vars['meta_compare'] = '>=';
			$wp_query->query_vars['meta_value'] = date('Y-m-d').' 00:00:00';
			$wp_query->query_vars['orderby'] = 'meta_value';
			$wp_query->query_vars['order'] = 'ASC';

			$wp_query->query_vars['where'] .= " AND datetime >= '" . date('Y-m-d') . " 00:00:00'";
		
			$wp_query->query_vars['posts_per_page'] = -1;
		}
	}
	
	

	/**
	 * 
	 */	
	public static function events_loop( $slug, $name )
	{
		if( !is_post_type_archive(Exchange_Events__Event_Custom_Post_Type::$name) ) return;
		//if( ('event' != $name) && ('index' != $name) && (!empty($name)) ) return;
		
		//echo 'slug: '.$slug.'<br/>';
		//echo 'name: '.$name.'<br/>';
		
		$slug = '';
		$name = '';

	}
	
	
	
	/**
	 * 
	 */	
	public static function events_content( $slug, $name )
	{
		if( !is_post_type_archive(Exchange_Events__Event_Custom_Post_Type::$name) ) return;
		//if( ('event' != $name) && ('index' != $name) && (!empty($name)) ) return;
		
		//echo 'slug: '.$slug.'<br/>';
		//echo 'name: '.$name.'<br/>';
		
		$slug = '';
		$name = '';
		
	}
	
	
	
	/**
	 * 
	 */	
	public static function update_event_content( $content )
	{
		
	}

}


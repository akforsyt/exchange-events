<?php
/**
 * Displays a single Event custom post type page.
 *
 * @package Exchange Events
 */
 
get_header();
$event = Exchange_Events::populate_event_data( $post );
?>

<div id="main" class="single-page events">

	<div id="primary" class="single">
	
		<h1><?php echo $event->post_title; ?></h1>

		<div class="event">

			<div class="contents">
			
				<div class="event-info">
					<div class="datetime"><label>Date/Time: </label><?php echo $event->datetime; ?></div>
					<div class="location"><label>Location: </label><?php echo $event->location; ?></div>
				</div>

				<div class="content"><?php echo $event->post_content; ?></div>			
			
			</div><!-- .contents -->
	
		</div><!-- .event -->

	</div><!-- #primary -->

</div><!-- #main -->

<?php get_footer(); ?>

<?php /*

<div id="main" class="wrapper">

    <div id="primary" class="site-content">
        <div id="content" role="main">
        
        
<div id="main" class="wrapper">

    <div id="main" class="single-page">
        <div id="primary" class="single">
                
        */ ?>
        
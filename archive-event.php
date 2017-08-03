<?php
/**
 * Displays the Event custom post type archive page.
 *
 * @package Exchange Events
 */

get_header();
?>

<div id="main" class="archive-page">

	<div id="primary" class="archive-list">
	
		<h1>Events</h1>
	
		<?php
		if( !have_posts() ):

			?>
			<p>No events found.</p>
			<?php

		else:
		
			?>
			<div class="exchange-events archive">
			<?php

			global $wp_query;
			//echo '<pre>';var_dump($wp_query);echo '</pre>';
			//echo '<pre>';var_dump($wp_query->posts);echo '</pre>';
			Exchange_Events::print_archive_events( $wp_query );
			
			?>
			</div>
			<?php
 			
		endif;
		?>
	
	</div><!-- #primary -->

</div><!-- #main -->

<?php get_footer(); ?>

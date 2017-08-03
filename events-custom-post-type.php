<?php
/**
 * 
 */

add_action( 'init', array( 'Exchange_Events__Event_Custom_Post_Type', 'create_custom_post' ) );
add_filter( 'post_updated_messages', array( 'Exchange_Events__Event_Custom_Post_Type', 'update_messages' ) );
add_action( 'add_meta_boxes', array( 'Exchange_Events__Event_Custom_Post_Type', 'info_box' ) );
add_action( 'save_post', array( 'Exchange_Events__Event_Custom_Post_Type', 'info_box_save' ) );

class Exchange_Events__Event_Custom_Post_Type
{
	public static $name = 'event';
	
	/**
	 * Constructor.
	 * Private.  Class only has static members.
	 * TODO: look up PHP abstract class implementation.
	 */
	private function __construct() { }



	/**
	 * Creates the custom Event post type.
	 */	
	public static function create_custom_post()
	{
		$labels = array(
			'name'               => 'Events',
			'singular_name'      => 'Event',
			'add_new'            => 'Add New',
			'add_new_item'       => 'Add New Event',
			'edit_item'          => 'Edit Event',
			'new_item'           => 'New Event',
			'all_items'          => 'All Events',
			'view_item'          => 'View Event',
			'search_items'       => 'Search Events',
			'not_found'          => 'No events found',
			'not_found_in_trash' => 'No events found in the Trash',
			'parent_item_colon'  => '',
			'menu_name'          => 'Events'
		);
		
		$args = array(
			'labels'        => $labels,
			'description'   => 'Holds our events and event specific data',
			'public'        => true,
			'menu_position' => 5,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'taxonomies'	=> array('category', 'post_tag'),
			'has_archive'   => true,
		);
		
		register_post_type( Exchange_Events__Event_Custom_Post_Type::$name, $args );	
	}
	
	
	
	/**
	 * Updates the messages displayed by the custom Event post type.
	 */
	public static function update_messages( $messages )
	{
		global $post, $post_ID;
		$messages[Exchange_Events__Event_Custom_Post_Type::$name] = array(
			0 => '', 
			1 => sprintf( __('Event updated. <a href="%s">View event</a>'), esc_url( get_permalink($post_ID) ) ),
			2 => __('Custom field updated.'),
			3 => __('Custom field deleted.'),
			4 => __('Event updated.'),
			5 => isset($_GET['revision']) ? sprintf( __('Event restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __('Event published. <a href="%s">View event</a>'), esc_url( get_permalink($post_ID) ) ),
			7 => __('Event saved.'),
			8 => sprintf( __('Event submitted. <a target="_blank" href="%s">Preview event</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			9 => sprintf( __('Event scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview event</a>'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			10 => sprintf( __('Event draft updated. <a target="_blank" href="%s">Preview event</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		);
		return $messages;
	}
	
	
	
	/**
	 * Sets up the custom meta box with special Event meta data tags.
	 */
	public static function info_box()
	{
		add_meta_box( 
			'event_info_box',
			'Event Info',
			array( 'Exchange_Events__Event_Custom_Post_Type', 'info_box_content' ),
			Exchange_Events__Event_Custom_Post_Type::$name,
			'side',
			'high'
		);
	}
	
	
	
	/**
	 * Writes the HTML code used to create the contents of the Event meta box.
	 * @param WP_Post The current post being displayed.
	 */
	public static function info_box_content( $post )
	{
		wp_nonce_field( plugin_basename( __FILE__ ), 'exchange-custom-event-post' );

		$datetime = get_post_meta( $post->ID, 'datetime', true );
		if( !empty($datetime) )
		{
			$datetime = DateTime::createFromFormat( 'Y-m-d H:i:s', $datetime );
			$date = $datetime->format('Y-m-d');
			$time = $datetime->format('h:i A');
		}
		
		$location = get_post_meta( $post->ID, 'location', true );

		?>
		<label for="clas-event-date">Date</label><br/>
		<input type="text" id="exchange-event-date" name="exchange-event-date" value="<?php echo esc_attr($date); ?>" size="32" /><br/>
		<label for="clas-event-time">Time</label><br/>
		<input type="text" id="exchange-event-time" name="exchange-event-time" value="<?php echo esc_attr($time); ?>" size="32" /><br/>
		<label for="clas-event-location">Location</label><br/>
		<input type="text" id="exchange-event-location" name="exchange-event-location" value="<?php echo esc_attr($location); ?>" size="32" /><br/>
		<?php
	}
	
	
	
	/**
	 * Saves the Event's custom meta data.
	 * @param int The current post's id.
	 */
	public static function info_box_save( $post_id )
	{
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
		return;
		
		if ( !wp_verify_nonce( $_POST['exchange-custom-event-post'], plugin_basename( __FILE__ ) ) )
		return;
		
		if ( !current_user_can( 'edit_page', $post_id ) )
		return;

		$datetime = DateTime::createFromFormat( 'Y-m-d h:i A', $_POST['exchange-event-date'].' '.$_POST['exchange-event-time'] );
		update_post_meta( $post_id, 'datetime', $datetime->format('Y-m-d H:i:s') );

		$location = $_POST['exchange-event-location'];
		update_post_meta( $post_id, 'location', $location );
	}
	
}

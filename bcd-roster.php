<?php
/*
	Plugin Name: BCD Roster
	Plugin URI: http://wordpress.org/extend/plugins/bcd-roster/
	Description: Adds a custom post type for roster members along with a custom taxonomy for assigning categories to the new post type.  Also provides a shortcode interface to list the roster in various ways.
	Author: Frank Jones
	Version: 1.0.0
	Author URI: http://www.duhjones.com/
*/

define ( 'BCDR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

bcdr_add_action_hooks();


// Add 'init' action hooks
function bcdr_add_action_hooks() {
	add_action( 'init', 'bcdr_cpt_create_member' );
	add_action( 'init', 'bcdr_tx_create_member_category' );
	add_action( 'admin_head', 'bcdr_styles_create_roster_icon' );
	add_shortcode( 'bcdroster', 'bcdr_sc_roster' );
}


// Add the custom post type 'bcd_cpt_member'
function bcdr_cpt_create_member() {
	register_post_type(
		'bcd_cpt_member',
		array(
			'labels' => array(
				'name' => __( 'Roster' ),
				'singular_name' => __('Roster'),
				'add_new_item' => __(''),
				'add_new' => __('Add New Member'),
				'edit_item' => __('Edit Member'),
				'new_item' => __('New Member'),
				'all_items' => __('All Members'),
				'view_item' => __('View Member'),
				'search_items' => __('Search Members'),
				'not_found' => __('No Members found'),
				'not_found_in_trash' => __('No Members found in Trash'),
				'parent_item_colon' => '',
				'menu_name' => 'Roster'
			),
			'hierarchical' => true,
			'description' => 'Roster members and descriptions',
			'supports' => array('title', 'editor', 'thumbnail', 'custom-fields', 'page-attributes'),
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'menu_position' => 25,
			'show_in_nav_menus' => true,
			'publicly_queryable' => true,
			'exclude_from_search' => false,
			'has_archive' => true,
			'query_var' => true,
			'can_export' => true,
			'rewrite' => true,
			'capability_type' => 'post'
		)
	);
}


// Add new taxonomy 'bcd_tx_member_category'
function bcdr_tx_create_member_category() {
	register_taxonomy(
		'bcd_tx_member_category',
		array('bcd_cpt_member'),
		array(
			'labels' => array(
				'name' => _x('Member Categories', 'taxonomy general name'),
				'singular_name' => _x('Member Category', 'taxonomy singular name'),
				'search_items' => __('Search Member Categories'),
				'all_items' => __('All Member Categories'),
				'parent_item' => __('Parent Member Category'),
				'parent_item_colon' => __('Parent Member Category:'),
				'edit_item' => __('Edit Member Category'),
				'update_item' => __('Update Member Category'),
				'add_new_item' => __('Add New Member Category'),
				'new_item_name' => __('New Member Category Name'),
				'menu name' => __('Member Category')
			),
			'hierarchical' => true,
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => array('slug' => 'bcd-tx-roster-category')
		)
	);
}


// Add an icon for the admin menu and screen
function bcdr_styles_create_roster_icon() {
	?>
	<style>
		/* Admin Menu - 16px */
		#menu-posts-bcd_cpt_member .wp-menu-image {
			background: url('<?php echo BCDR_PLUGIN_URL; ?>images/icon-roster-menu-16-sprite.png') no-repeat 6px 6px !important;
		}

		#menu-posts-bcd_cpt_member:hover .wp-menu-image, #menu-posts-bcd_cpt_member.wp-has-current-submenu .wp-menu-image {
			background-position: 6px -26px !important;
		}


		/* Post Screen - 32px */
		.icon32-posts-bcd_cpt_member {
			background: url('<?php echo BCDR_PLUGIN_URL; ?>images/icon-roster-page-32.png') no-repeat left top !important;
		}

		@media
		only screen and (-webkit-min-device-pixel-ratio: 1.5),
		only screen and (   min--moz-device-pixel-ratio: 1.5),
		only screen and (     -o-min-device-pixel-ratio: 3/2),
		only screen and (        min-device-pixel-ratio: 1.5) {
			/* Admin Menu - 16px @2x */
			#menu-posts-bcd_cpt_member .wp-menu-image {
				background-image: url('<?php echo BCDR_PLUGIN_URL; ?>images/icon-roster-menu-16-sprite@2x.png') !important;
				-webkit-background-size: 16px 48px;
				-moz-background-size: 16px 48px;
				background-size: 16px 48px;
			}

			/* Post Screen - 32px @2x */
			.icon32-posts-bcd_cpt_member {
				background-image: url('<?php echo BCDR_PLUGIN_URL; ?>images/icon-roster-page-32@2x.png') !important;
				-webkit-background-size: 32px 32px;
				-moz-background-size: 32px 32px;
				background-size: 32px 32px;
			}
		}
	</style>
<?php
}


// Add shortcode to display roster members
function bcdr_sc_roster( $atts ) {
	extract( shortcode_atts( array(
		'name' => 'all',
		'sortorder' => 'asc',
		'listtype' => ''
	), $atts ) );
	
	$rf_output = '';

	global $post;
	$tmp_post = $post;
	$qry_args = array( 'post_type' => 'bcd_cpt_member', 'orderby' => 'title', 'posts_per_page' => -1 );
	
	
	if ( $name != 'all' ) {
		$tx_query = array(
			array(
				'taxonomy' => 'bcd_tx_member_category',
				'field' => 'slug',
				'terms' => explode(',', $name),
				'operator' => 'AND',
				'include_children' => false
			)
		);
		$qry_args['tax_query'] = $tx_query;
	}
	
	
	if ( $sortorder == 'desc' )
		$qry_args['order'] = 'DESC';
	else
		$qry_args['order'] = 'ASC';
	
	
	if ( ( $listtype == 'ol' ) || ( $listtype == 'ul' ) )
	{
		$rf_output .= '<' . $listtype . '>';
	}
	
	
	$my_query = new WP_Query( $qry_args );
	
	while ( $my_query->have_posts() ) {
		$my_query->the_post();

		if ( ( $listtype == 'ol' ) || ( $listtype == 'ul' ) )
			$rf_output .= '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a><br /></li>';
		else
			$rf_output .= '<a href="' . get_permalink() . '">' . get_the_title() . '</a><br />';
	}
	wp_reset_query();
	
	
	if ( ( $listtype == 'ol' ) || ( $listtype == 'ul' ) )
	{
		$rf_output .= '</' . $listtype . '>';
	}
	
	
	return $rf_output;
}

?>

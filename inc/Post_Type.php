<?php
/**
 * Post Tpe Class.
 *
 * @package Codeinwp\Hyve
 */

namespace ThemeIsle\Hyve;

/**
 * Class Post_Type
 */
class Post_Type {
	/**
	 * Register the post type.
	 * 
	 * @return void
	 */
	public static function register() {
		$labels = array(
			'name'               => _x( 'Knowledge Base', 'post type general name', 'hyve' ),
			'singular_name'      => _x( 'Knowledge Base', 'post type singular name', 'hyve' ),
			'menu_name'          => _x( 'Knowledge Base', 'admin menu', 'hyve' ),
			'name_admin_bar'     => _x( 'Knowledge Base', 'add new on admin bar', 'hyve' ),
			'add_new'            => _x( 'Add New', 'Knowledge Base', 'hyve' ),
			'add_new_item'       => __( 'Add New Knowledge Base', 'hyve' ),
			'new_item'           => __( 'New Knowledge Base', 'hyve' ),
			'edit_item'          => __( 'Edit Knowledge Base', 'hyve' ),
			'view_item'          => __( 'View Knowledge Base', 'hyve' ),
			'all_items'          => __( 'All Knowledge Base', 'hyve' ),
			'search_items'       => __( 'Search Knowledge Base', 'hyve' ),
			'parent_item_colon'  => __( 'Parent Knowledge Base:', 'hyve' ),
			'not_found'          => __( 'No Knowledge Base found.', 'hyve' ),
			'not_found_in_trash' => __( 'No Knowledge Base found in Trash.', 'hyve' ),
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Knowledge Base.', 'hyve' ),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => false,
			'show_in_menu'       => false,
			'query_var'          => false,
			'rewrite'            => array( 'slug' => 'knowledge-base' ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'show_in_rest'       => false,
			'supports'           => array( 'title', 'editor', 'custom-fields' ),
		);

		register_post_type( 'hyve_docs', $args );
	}
}

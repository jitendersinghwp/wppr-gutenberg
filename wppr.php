<?php
/**
 * Plugin Name: WP Product Review Gutenberg Block Plugin
 * Plugin URI: https://themeisle.com/
 * Description: A Gutenberg block for WP Product Review.
 * Author: Hardeep Asrani
 * Author URI: https://themeisle.com/
 * Version: 1.0.0
 * License: GPL2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package WPPR
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue front end and editor JavaScript and CSS
 */
function hello_gutenberg_scripts() {
    $blockPath = '/dist/block.js';
    $stylePath = '/dist/block.css';
    // Enqueue the bundled block JS file
    wp_enqueue_script(
        'hello-gutenberg-block-js',
        plugins_url( $blockPath, __FILE__ ),
        [ 'wp-i18n', 'wp-edit-post', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-plugins', 'wp-edit-post', 'wp-api' ]
    );
    // Enqueue frontend and editor block styles
    wp_enqueue_style(
        'hello-gutenberg-block-css',
        plugins_url ($stylePath, __FILE__)
    );
}
// Hook scripts function into block editor hook
add_action('enqueue_block_assets', 'hello_gutenberg_scripts');

register_rest_field(
	array( 'post', 'wppr_review' ),
	'wppr_data', array(
		'get_callback'    => 'wppr_get_post_meta',
		'schema'          => null,
	)
);

function wppr_get_post_meta( $post ) {
	$data = array();
	$post_id = $post['id'];
	$post_type = $post['type'];
	$options = array(
				'cwp_meta_box_check',
				'cwp_rev_product_name',
				'_wppr_review_template',
				'cwp_rev_product_image',
				'cwp_image_link',
				'wppr_links',
				'cwp_rev_price',
				'wppr_pros',
				'wppr_cons',
				'wppr_rating',
				'wppr_options',
			 );
	foreach ( $options as $option ) {
		if ( ! empty( get_post_meta( $post_id, $option ) ) ) {
			$object = get_post_meta( $post_id, $option );
			$object = $object[0];
			$data[ $option ] = $object;
		}
	}

	return $data;
}

/**
* Add REST API support to an already registered post type.
*/
add_action( 'init', 'my_custom_post_type_rest_support', 25 );
function my_custom_post_type_rest_support() {
  	global $wp_post_types;

  	//be sure to set this to the name of your post type!
  	$post_type_name = 'wppr_review';
  	if( isset( $wp_post_types[ $post_type_name ] ) ) {
  		$wp_post_types[$post_type_name]->show_in_rest = true;
  		$wp_post_types[$post_type_name]->rest_base = $post_type_name;
  		$wp_post_types[$post_type_name]->rest_controller_class = 'WP_REST_Posts_Controller';
  	}  
}

add_action( 'rest_api_init', 'wp_api_add_posts_endpoints' );
function wp_api_add_posts_endpoints() {
  register_rest_route( 'wp-product-review', '/update-review', array(
        'methods'  => 'POST',
        'callback' => 'addPosts_callback',
		'args'     => array(
			'id' => array(
				'sanitize_callback' => 'absint',
			),
		),
    ));
}
function addPosts_callback( $data ) {
	if ( ! empty( $data['id'] ) ) {
		$review = new WPPR_Review_Model( $data['id'] );
		if ( $data['cwp_meta_box_check'] === 'Yes' ) {
			$review->activate();
			if ( ! empty( $data['cwp_rev_product_name'] ) ) {
				$review->set_name( $data['cwp_rev_product_name'] );
			}
			if ( ! empty( $data['_wppr_review_template'] ) ) {
				$review->set_template( $data['_wppr_review_template'] );
			}
			if ( ! empty( $data['cwp_rev_product_image'] ) ) {
				$review->set_image( $data['cwp_rev_product_image'] );
			}
			if ( $data['cwp_image_link'] === 'image' || $data['cwp_image_link'] === 'link' ) {
				$review->set_click( $data['cwp_image_link'] );
			}
			if ( ! empty( $data['wppr_links'] ) ) {
				$review->set_links( $data['wppr_links'] );
			}
			if ( ! empty( $data['cwp_rev_price'] ) ) {
				$review->set_price( $data['cwp_rev_price'] );
			}
			if ( ! empty( $data['wppr_options'] ) ) {
				$review->set_options( $data['wppr_options'] );
			}
			if ( ! empty( $data['wppr_pros'] ) ) {
				$review->set_pros( $data['wppr_pros'] );
			}
			if ( ! empty( $data['wppr_cons'] ) ) {
				$review->set_cons( $data['wppr_cons'] );
			}
		} else {
			$review->deactivate();
		}
	}
}

// Make it work with Pro version.
// Enable REST in wppr_review CPT.
<?php
namespace ZRDN;

use ZRDN\Recipe as RecipeModel;
use ZRDN\ZipRecipes;
/**
 * Gutenberg Recipe Block
 *
 * Add feature blocks using Gutenberg
 *
 * PHP version 5.3
 * Depends: WP REST API, Plugin Dependencies
 *
 * @package    Zip Recipes
 * @author     Mudassar Ali <sahil_bwp@yahoo.com>
 * @copyright  2017 Gezim Hoxha
 * @version 1.0
 */

class RecipeBlock {


    function __construct() {


	    $relativeScriptPath = 'gutenberg/build/recipe.min.js';

	    wp_register_script(
		    'recipe-block',
		    ZRDN_PLUGIN_DIRECTORY_URL . $relativeScriptPath, // File.
		    array( 'wp-components','wp-blocks', 'wp-i18n', 'wp-element', 'underscore' ), // Dependencies.
		    filemtime( ZRDN_PLUGIN_DIRECTORY . $relativeScriptPath ) // filemtime — Gets file modification time.
	    );

	    $relativeStylePath = 'gutenberg/assets/styles/bulma.css';
	    wp_register_style(
		    'recipe-block-editor',
		    ZRDN_PLUGIN_DIRECTORY_URL . $relativeStylePath,
		    array( 'wp-edit-blocks' ),
		    filemtime( ZRDN_PLUGIN_DIRECTORY . $relativeStylePath ) // filemtime — Gets file modification time.
	    );

	    $relativePathForBulmaMinireset = 'gutenberg/assets/styles/bulma-minireset-generic.css';
	    wp_register_style(
		    'recipe-block-editor-mini-reset',
		    ZRDN_PLUGIN_DIRECTORY_URL . $relativePathForBulmaMinireset,
		    array( 'wp-edit-blocks' ),
		    filemtime( ZRDN_PLUGIN_DIRECTORY . $relativePathForBulmaMinireset ) // filemtime — Gets file modification time.
	    );


	    register_block_type( 'zip-recipes/recipe-block', array(
	        'editor_script' => 'recipe-block',
	        'editor_style'  => array('recipe-block-editor-mini-reset', 'recipe-block-editor'),
            'render_callback' => array($this,  'gggb_render_shortcode'),
        ) );

    }

    function gggb_render_shortcode( $atts ) {
	    $recent_posts = wp_get_recent_posts( array(
		    'numberposts' => 1,
		    'post_status' => 'publish',
	    ) );
	    if ( count( $recent_posts ) === 0 ) {
		    return 'No posts';
	    }
	    $post = $recent_posts[ 0 ];
	    $post_id = $post['ID'];
	    return sprintf(
		    '<a class="wp-block-my-plugin-latest-post" href="%1$s">%2$s</a>',
		    esc_url( get_permalink( $post_id ) ),
		    esc_html( get_the_title( $post_id ) )
	    );
    }

}

new RecipeBlock();

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

        add_action( 'enqueue_block_editor_assets',  array($this, 'enqueue_block_editor_assets') );
        add_action( 'enqueue_block_assets',  array($this, 'enqueue_block_assets') );
       // add_filter( 'script_loader_tag', array($this, 'script_loader_tag'), 10, 3 );

        add_shortcode( 'github-gist', array($this, 'gggb_render_shortcode') );

        register_block_type( 'zip-recipes/recipe-block', array(
            //'render_callback' => array($this,  'render_callback_recipe_block'),
            'render_callback' => array($this,  'gggb_render_shortcode'),
        ) );

    }


    /**
     * Enqueue the block's assets for the editor.
     *
     * `wp-blocks`: Includes block type registration and related functions.
     * `wp-element`: Includes the WordPress Element abstraction for describing the structure of your blocks.
     * `wp-i18n`: To internationalize the block's text.
     *
     * @since 1.0.0
     */
    function enqueue_block_editor_assets() {

        // Add React files
        // wp_enqueue_script( 'react', 'https://cdnjs.cloudflare.com/ajax/libs/react/0.14.3/react.min.js', array(), null );
        // wp_enqueue_script( 'react-dom', 'https://cdnjs.cloudflare.com/ajax/libs/react/0.14.3/react-dom.min.js', array( 'react' ), null );

        // Add Babel file
        // wp_enqueue_script( 'babel', 'https://cdnjs.cloudflare.com/ajax/libs/babel-core/5.8.34/browser.min.js', array(), null );

        // Scripts.
        wp_enqueue_script(
            'recipe-block', // Handle.
            ZRDN_PLUGIN_DIRECTORY_URL . 'gutenberg/block/block.js', // File.
//            ZRDN_PLUGIN_DIRECTORY_URL . 'gutenberg/block/recipeblock.js', // File.
//            ZRDN_PLUGIN_DIRECTORY_URL . 'gutenberg/block.build.js', // File.
            array( 'wp-blocks', 'wp-i18n', 'wp-element', 'underscore' ), // Dependencies.
            filemtime( ZRDN_PLUGIN_DIRECTORY . 'gutenberg/block/block.js' ) // filemtime — Gets file modification time.
//            filemtime( ZRDN_PLUGIN_DIRECTORY . 'gutenberg/block/recipeblock.js' ) // filemtime — Gets file modification time.
//            filemtime( ZRDN_PLUGIN_DIRECTORY . 'gutenberg/block.build.js' ) // filemtime — Gets file modification time.
        );
        // Styles.
        wp_enqueue_style(
            'recipe-block-editor', // Handle.
            ZRDN_PLUGIN_DIRECTORY_URL . 'gutenberg/assets/css/editor.css', // File.
            array( 'wp-edit-blocks' ), // Dependency.
            filemtime( ZRDN_PLUGIN_DIRECTORY . 'gutenberg/assets/css/editor.css' ) // filemtime — Gets file modification time.
        );

        //wp_localize_script( 'wp-api', 'zrdn_recipe_block', array( 'root' => esc_url_raw( rest_url() ), 'nonce' => wp_create_nonce( 'wp_rest' ) ) );
    }

    /**
     * Enqueue the block's assets for the frontend.
     *
     * @since 1.0.0
     */
    function enqueue_block_assets() {
        wp_enqueue_style(
            'recipe-frontend', // Handle.
            ZRDN_PLUGIN_DIRECTORY_URL . 'gutenberg/assets/css/style.css', // File.
            array( 'wp-blocks' ), // Dependency.
            filemtime( ZRDN_PLUGIN_DIRECTORY . 'gutenberg/assets/css/style.css' ) // filemtime — Gets file modification time.
        );
    }

    /**
     * Change Script tag for reactjs
     *
     * @param $tag
     * @param $handle
     * @param $src
     * @return mixed
     */
    function script_loader_tag( $tag, $handle, $src ) {
        // Check that this is output of JSX file
        if ( 'recipe-block' == $handle ) {
            $tag = str_replace( "<script type='text/javascript'", "<script type='text/babel'", $tag );
        }

        return $tag;
    }

    function render_callback_recipe_block( $attributes ) {
        // var_dump($attributes);
        // var_dump($attributes);
        // $recipe_id = 45;
        // $recipe = RecipeModel::db_select($recipe_id);
        // $recipeHtml =  ZipRecipes::zrdn_format_recipe($recipe);
        $recipeHtml = <<<HTML
        <div class="wp-block-zip-recipes-recipe-block">
            <h2>4545545</h2>
            <ul class="ingredients"></ul>
            <div class="steps"></div>
        </div>
HTML;

         // return $recipeHtml;

       // var_dump($attributes);
      //  die('--');

    }

    function gggb_render_shortcode( $atts ) {
        if ( empty( $atts['url'] )
            || 'gist.github.com' !== parse_url( $atts['url'], PHP_URL_HOST ) ) {
            return '';
        }
        // die($atts['url']);
        return sprintf(
            '<script src="%s"></script>',
            esc_url( rtrim( $atts['url'], '/' ) . '.js' )
        );
    }



}

new RecipeBlock();

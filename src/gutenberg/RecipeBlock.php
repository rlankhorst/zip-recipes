<?php
namespace ZRDN;
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
        // Scripts.
        wp_enqueue_script(
            'recipe-block', // Handle.
            ZRDN_PLUGIN_DIRECTORY_URL . 'gutenberg/block/block.js', // File.
            array( 'wp-blocks', 'wp-i18n', 'wp-element', 'underscore' ), // Dependencies.
            filemtime( ZRDN_PLUGIN_DIRECTORY . 'gutenberg/block/block.js' ) // filemtime — Gets file modification time.
        );
        // Styles.
        wp_enqueue_style(
            'recipe-block-editor', // Handle.
            ZRDN_PLUGIN_DIRECTORY_URL . 'gutenberg/assets/css/editor.css', // File.
            array( 'wp-edit-blocks' ), // Dependency.
            filemtime( ZRDN_PLUGIN_DIRECTORY . 'gutenberg/assets/css/editor.css' ) // filemtime — Gets file modification time.
        );
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



}

new RecipeBlock();

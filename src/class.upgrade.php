<?php

namespace ZRDN;

/**
 * Dubugging 
 */
if (0) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

/**
 * Upgrade Base class
 *
 * Switch to a real shortcode
 *
 * PHP version 5.3
 *
 * @package Zip Recipes
 * @author     Mudassar Ali <sahil_bwp@yahoo.com>
 * @copyright  2017 Gezim Hoxha
 */
class ZipUpgrade extends ZipRecipes {

    /**
     * Defined Constants
     */
    const DESCRIPTION = "Upgrade recipes";
    const PAGE_ID = "zrdn-upgrade";
    const MAIN_JS_SCRIPT = "upgrade-js";
    const MAIN_CSS_SCRIPT = "upgrade-css";

    function __construct() {
        // check if not updated
        if (get_option('zrdn_plugin_is_upgrade', 'no') == 'yes') {
            return false;
        }
    }

    /**
     * Initializer for setting up action handler
     */
    public static function run() {
        /**
         * hook so we can add menus to our admin left-hand menu
         */
        add_action('admin_menu', array(get_called_class(), 'admin_menu'));

        /**
         * Register and enqueue a custom stylesheet in the WordPress admin.
         */
        add_action('admin_enqueue_scripts', array(get_called_class(), 'zrdn_enqueue_assets'));

        /**
         * Notice Hook
         */
        add_action('admin_notices', array(get_called_class(), 'zrdn_upgrade_notice_version'));

        /**
         * Xrh Ajax Call back WP hook
         */
        add_action('wp_ajax_upgrade_callback', array(get_called_class(), 'zrdn_ajax_upgrade_callback'));
    }

    /**
     * Admin Submenu defined
     */
    public static function admin_menu() {
        $pageTitle = "Upgrade";
        $menuTitle = $pageTitle;
        $menuSlug = self::PAGE_ID;
        $pageRenderer = __CLASS__ . '::zrdn_upgrade_shortcodes';
        add_submenu_page(
                'zrdn-settings', $pageTitle, $menuTitle, 'manage_options', $menuSlug, $pageRenderer
        );
    }

    /**
     * Render Import main view
     */
    public static function zrdn_upgrade_shortcodes() {
        Util::print_view("upgrade", array());
    }

    /**
     * Show Notice of upgrade
     */
    public static function zrdn_upgrade_notice_version() {
        echo '<div id="message" class="update-nag">'
        . 'Zip recipes required upgradation. '
        . '<a href="' . admin_url('admin.php?page=zrdn-upgrade') . '" aria-label="Please update WordPress now">Please update now</a>'
        . '</div>';
    }

    /**
     *  WP registers scripts and styles
     */
    public static function zrdn_enqueue_assets() {
        wp_register_script(self::MAIN_JS_SCRIPT, plugins_url('scripts/upgrade.js', __FILE__), array('jquery'), '1.0', true);
        wp_enqueue_script(self::MAIN_JS_SCRIPT);
        wp_localize_script(self::MAIN_JS_SCRIPT, 'zrdn_upgrade', array('settings' => admin_url('admin.php?page=zrdn-upgrade')));
        wp_register_style(self::MAIN_CSS_SCRIPT, plugins_url('styles/upgrade.css', __FILE__), array(), NULL, 'all');
        wp_enqueue_style(self::MAIN_CSS_SCRIPT);
    }

    /**
     * Implement Xhr callback
     * 
     */
    public static function zrdn_ajax_upgrade_callback() {
        $request = $_POST;
        $response = '';
        $action = $request['zrdn_action'];
        if (!empty($action)) {
            switch ($action) {
                case 'scan':
                    $response = self::zrdn_scan_deprecated();
                    break;
                case 'convert':
                    $response = self::zrdn_convert_deprecated($request);
                    break;
                case 'done':
                    $response = self::zrdn_convert_deprecated_done();
                    break;
                default:
                    break;
            }
        }
        header("Content-Type: application/json");
        echo json_encode($response);
        wp_die();
        exit();
    }

    /**
     * Get All posts and pages
     * 
     * @return Array
     */
    public static function zrdn_scan_deprecated() {
        return get_posts(array(
            'fields' => 'ids', // Only get post IDs
            'post_type' => array('post', 'page'),
            'numberposts' => -1,
            'orderby' => 'ID',
            'order' => 'ASC',
        ));
    }

    /**
     * 
     * @param Array $request
     * @return int
     */
    public static function zrdn_convert_deprecated($request) {
        $found = 0;
        $id = $request['zrdn_id'];
        $content = get_post($id);
        $post_content = ZipUpgrade::zrdn_find_replace($content->post_content);
        if (strlen($post_content) != strlen($post_content)) {
            $found = 1;
            $content->post_content = $post_content;
            wp_update_post($content);
        }
        return $found;
    }

    /**
     * Find and replace depricated shortcode
     * 
     * @param String $string
     * @return String
     */
    public static function zrdn_find_replace($string) {
        $string = preg_replace('/\[amd-zlrecipe-recipe:([0-9]+)\]/i', "[ziprecipes recipe id='$1']", $string);
        $string = preg_replace('/(id)=("(amd-zlrecipe-recipe-)[0-9^"]*")/i', "[ziprecipes recipe id='$1']", $string);
        return $string;
    }

    /**
     * Update done Option
     * 
     * @return Boolean
     */
    public static function zrdn_convert_deprecated_done() {
        return update_option('zrdn_plugin_is_upgrade', 'yes');
    }

}

ZipUpgrade::run();


/**
 *  end plugin here 
 */
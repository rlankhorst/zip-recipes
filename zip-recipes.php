<?php
/*
Plugin Name: Zip Recipes
Plugin URI: http://www.ziprecipes.net/
Plugin GitHub: https://github.com/hgezim/zip-recipes-plugin
Description: A plugin that adds all the necessary microdata to your recipes, so they will show up in Google's Recipe Search
Version: 4.1.0.16
Author: HappyGezim
Author URI: http://www.ziprecipes.net/
License: GPLv3 or later

Copyright 2014 Gezim Hoxha
This code is derived from the 2.6 version build of ZipList Recipe Plugin released by ZipList Inc.:
	http://get.ziplist.com/partner-with-ziplist/wordpress-recipe-plugin/ and licensed under GPLv3 or later
*/

/*
    This file is part of Zip Recipes Plugin.

    Zip Recipes Plugin is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Zip Recipes Plugin is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Zip Recipes Plugin. If not, see <http://www.gnu.org/licenses/>.
*/

// Make sure we don't expose any info if called directly
defined('ABSPATH') or die("Error! Cannot be called directly.");

if (!defined('ZRDN_VERSION_KEY'))
	define('ZRDN_VERSION_KEY', 'zrdn_version');

if (!defined('ZRDN_VERSION_NUM'))
	define('ZRDN_VERSION_NUM', '4.1.0.16');

if (!defined('ZRDN_PLUGIN_DIRECTORY'))
	define('ZRDN_PLUGIN_DIRECTORY', plugins_url() . '/' . dirname(plugin_basename(__FILE__)) . '/');

add_option(ZRDN_VERSION_KEY, ZRDN_VERSION_NUM);  // sort of useless as is never updated
add_option("amd_zlrecipe_db_version"); // used to store DB version - leaving as is name as legacy
add_option('zrdn_attribution_hide', '');
add_option('zlrecipe_printed_permalink_hide', '');
add_option('zlrecipe_printed_copyright_statement', '');
add_option('zlrecipe_stylesheet', 'zlrecipe-std');
add_option('recipe_title_hide', '');
add_option('zlrecipe_image_hide', '');
add_option('zlrecipe_image_hide_print', 'Hide');
add_option('zlrecipe_print_link_hide', '');
add_option('zlrecipe_ingredient_label', 'Ingredients');
add_option('zlrecipe_ingredient_label_hide', '');
add_option('zlrecipe_ingredient_list_type', 'l');
add_option('zlrecipe_instruction_label', 'Instructions');
add_option('zlrecipe_instruction_label_hide', '');
add_option('zlrecipe_instruction_list_type', 'ol');
add_option('zlrecipe_notes_label', 'Notes');
add_option('zlrecipe_notes_label_hide', '');
add_option('zlrecipe_prep_time_label', 'Prep Time:');
add_option('zlrecipe_prep_time_label_hide', '');
add_option('zlrecipe_cook_time_label', 'Cook Time:');
add_option('zlrecipe_cook_time_label_hide', '');
add_option('zlrecipe_total_time_label', 'Total Time:');
add_option('zlrecipe_total_time_label_hide', '');
add_option('zlrecipe_yield_label', 'Yield:');
add_option('zlrecipe_yield_label_hide', '');
add_option('zlrecipe_serving_size_label', 'Serving Size:');
add_option('zlrecipe_serving_size_label_hide', '');
add_option('zlrecipe_calories_label', 'Calories per serving:');
add_option('zlrecipe_calories_label_hide', '');
add_option('zlrecipe_fat_label', 'Fat per serving:');
add_option('zlrecipe_fat_label_hide', '');
add_option('zlrecipe_rating_label', 'Rating:');
add_option('zlrecipe_rating_label_hide', '');
add_option('zlrecipe_image_width', '');
add_option('zlrecipe_outer_border_style', '');
add_option('zlrecipe_custom_print_image', '');

register_activation_hook(__FILE__, 'zrdn_recipe_install' );
add_action('plugins_loaded', 'zrdn_recipe_install' );

add_action('admin_head', 'zrdn_add_recipe_button' );
add_action('admin_head', 'zrdn_js_vars' );

function zrdn_js_vars() {

	if (is_admin()) {
		?>
		<script type="text/javascript">
			var post_id = '<?php global $post; echo $post->ID; ?>';
		</script>
	<?php
	}
}

if (strpos($_SERVER['REQUEST_URI'], 'media-upload.php') && strpos($_SERVER['REQUEST_URI'], '&type=z_recipe') && !strpos($_SERVER['REQUEST_URI'], '&wrt='))
{
	zrdn_iframe_content($_POST, $_REQUEST);
	exit;
}

/* Send debug code to the Javascript console */
function zrdn_debug_to_console($data) {
	if(is_array($data) || is_object($data))
	{
		echo("<script>console.log('PHP: ".json_encode($data)."');</script>");
	} else {
		echo("<script>console.log('PHP: ".$data."');</script>");
	}
}

global $zrdn_db_version;
$zrdn_db_version = "3.2";	// This must be changed when the DB structure is modified

// Creates ZLRecipe tables in the db if they don't exist already.
// Don't do any data initialization in this routine as it is called on both install as well as
//   every plugin load as an upgrade check.
//
// Updates the table if needed
// Plugin Ver         DB Ver
//   1.0 - 1.3        3.0
//   1.4x - 2.6       3.1  Adds Notes column to recipes table
//   4.1.0.10 -       3.2  Adds primary key, collation

function zrdn_recipe_install() {
	global $wpdb;
	global $zrdn_db_version;

	$recipes_table = $wpdb->prefix . "amd_zlrecipe_recipes";
	$installed_db_ver = get_option("amd_zlrecipe_db_version");

	$charset_collate = get_charset_collate();

	if(strcmp($installed_db_ver, $zrdn_db_version) != 0) {				// An older (or no) database table exists
		$sql_command = "CREATE TABLE `$recipes_table` (
            recipe_id bigint(20) unsigned NOT NULL AUTO_INCREMENT  PRIMARY KEY,
            post_id bigint(20) unsigned NOT NULL,
            recipe_title text,
            recipe_image text,
            summary text,
            rating text,
            prep_time text,
            cook_time text,
            total_time text,
            yield text,
            serving_size varchar(50),
            calories varchar(50),
            fat varchar(50),
            ingredients text,
            instructions text,
            notes text,
            created_at timestamp DEFAULT NOW()
        	) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql_command);

		update_option("amd_zlrecipe_db_version", $zrdn_db_version);

	}
}

function get_charset_collate() {
	global $wpdb;

	$charset_collate = '';

	if ( ! empty( $wpdb->charset ) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	if ( ! empty( $wpdb->collate ) )
		$charset_collate .= " COLLATE $wpdb->collate";

	return $charset_collate;
}

add_action('admin_menu', 'zrdn_menu_pages' );

// Adds module to left sidebar in wp-admin for ZLRecipe
function zrdn_menu_pages() {
	// Add the top-level admin menu
	$page_title = 'Zip Recipes Settings';
	$menu_title = 'Zip Recipes';
	$capability = 'manage_options';
	$menu_slug = 'zrdn-settings';
	$function = 'zrdn_settings';
	add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, 'dashicons-carrot');
}

/**
 * Determine if server is running on HTTPS.
 * @return bool Return true if server is running on HTTPS otherwise return false.
 */
function isServerProtocolHttps()
{
	$is_server_https = false;
	if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
	    || $_SERVER['SERVER_PORT'] == 443) {
		$is_server_https = true;
	}

	return $is_server_https;
}

// Adds 'Settings' page to the ZipRecipe module
function zrdn_settings() {
	global $wp_version;

	if (!current_user_can('manage_options')) {
		wp_die('You do not have sufficient permissions to access this page.');
	}

	$zrdn_icon = ZRDN_PLUGIN_DIRECTORY . "images/zrecipes-icon.png";

	$registered = get_option('zrdn_registered');
	$zrecipe_attribution_hide = get_option('zrdn_attribution_hide');
	$printed_permalink_hide = get_option('zlrecipe_printed_permalink_hide');
	$printed_copyright_statement = get_option('zlrecipe_printed_copyright_statement');
	$stylesheet = get_option('zlrecipe_stylesheet');
	$recipe_title_hide = get_option('recipe_title_hide');
	$image_hide = get_option('zlrecipe_image_hide');
	$image_hide_print = get_option('zlrecipe_image_hide_print');
	$print_link_hide = get_option('zlrecipe_print_link_hide');
	$ingredient_label = get_option('zlrecipe_ingredient_label');
	$ingredient_label_hide = get_option('zlrecipe_ingredient_label_hide');
	$ingredient_list_type = get_option('zlrecipe_ingredient_list_type');
	$instruction_label = get_option('zlrecipe_instruction_label');
	$instruction_label_hide = get_option('zlrecipe_instruction_label_hide');
	$instruction_list_type = get_option('zlrecipe_instruction_list_type');
	$image_width = get_option('zlrecipe_image_width');
	$outer_border_style = get_option('zlrecipe_outer_border_style');
	$custom_print_image = get_option('zlrecipe_custom_print_image');

	// load other option values in to variables. These variables are used to load saved values through variable variables
	$notes_label = get_option('zlrecipe_notes_label');
	$notes_label_hide = get_option('zlrecipe_notes_label_hide');
	$prep_time_label = get_option('zlrecipe_prep_time_label');
	$prep_time_label_hide = get_option('zlrecipe_prep_time_label_hide');
	$cook_time_label = get_option('zlrecipe_cook_time_label');
	$cook_time_label_hide = get_option('zlrecipe_cook_time_label_hide');
	$total_time_label = get_option('zlrecipe_total_time_label');
	$total_time_label_hide = get_option('zlrecipe_total_time_label_hide');
	$yield_label = get_option('zlrecipe_yield_label');
	$yield_label_hide = get_option('zlrecipe_yield_label_hide');
	$serving_size_label = get_option('zlrecipe_serving_size_label');
	$serving_size_label_hide = get_option('zlrecipe_serving_size_label_hide');
	$calories_label = get_option('zlrecipe_calories_label');
	$calories_label_hide = get_option('zlrecipe_calories_label_hide');
	$fat_label = get_option('zlrecipe_fat_label');
	$fat_label_hide = get_option('zlrecipe_fat_label_hide');
	$rating_label = get_option('zlrecipe_rating_label');
	$rating_label_hide = get_option('zlrecipe_rating_label_hide');



	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		foreach ($_POST as $key=>$val) {
			$_POST[$key] = stripslashes($val);
		}

		if ($_POST['action'] === "register")
		{
			// if first, last name and email are provided, we assume that user is registering
			$registered = $_POST['first_name'] && $_POST['last_name'] && $_POST['email'];
			if ($registered)
			{
				update_option('zrdn_registered', true);
			}
		}
		else if ($_POST['action'] === "update_settings")
		{
			$zrecipe_attribution_hide = $_POST['zrecipe-attribution-hide'];
			$printed_permalink_hide = $_POST['printed-permalink-hide'];
			$printed_copyright_statement = $_POST['printed-copyright-statement'];
			$stylesheet = $_POST['stylesheet'];
			$recipe_title_hide = $_POST['recipe-title-hide'];
			$image_hide = $_POST['image-hide'];
			$image_hide_print = $_POST['image-hide-print'];
			$print_link_hide = $_POST['print-link-hide'];
			$ingredient_label = zrdn_strip_chars($_POST['ingredient-label']);
			$ingredient_label_hide = zrdn_strip_chars($_POST['ingredient-label-hide']);
			$ingredient_list_type = $_POST['ingredient-list-type'];
			$instruction_label = zrdn_strip_chars($_POST['instruction-label']);
			$instruction_label_hide = $_POST['instruction-label-hide'];
			$instruction_list_type = zrdn_strip_chars($_POST['instruction-list-type']);
			$notes_label = zrdn_strip_chars($_POST['notes-label']);
			$notes_label_hide = $_POST['notes-label-hide'];
			$prep_time_label = zrdn_strip_chars($_POST['prep-time-label']);
			$prep_time_label_hide = $_POST['prep-time-label-hide'];
			$cook_time_label = zrdn_strip_chars($_POST['cook-time-label']);
			$cook_time_label_hide = $_POST['cook-time-label-hide'];
			$total_time_label = zrdn_strip_chars($_POST['total-time-label']);
			$total_time_label_hide = $_POST['total-time-label-hide'];
			$yield_label = zrdn_strip_chars($_POST['yield-label']);
			$yield_label_hide = $_POST['yield-label-hide'];
			$serving_size_label = zrdn_strip_chars($_POST['serving-size-label']);
			$serving_size_label_hide = $_POST['serving-size-label-hide'];
			$calories_label = zrdn_strip_chars($_POST['calories-label']);
			$calories_label_hide = $_POST['calories-label-hide'];
			$fat_label = zrdn_strip_chars($_POST['fat-label']);
			$fat_label_hide = $_POST['fat-label-hide'];
			$rating_label = zrdn_strip_chars($_POST['rating-label']);
			$rating_label_hide = $_POST['rating-label-hide'];
			$image_width = $_POST['image-width'];
			$outer_border_style = $_POST['outer-border-style'];
			$custom_print_image = $_POST['custom-print-image'];

			update_option('zrdn_attribution_hide', $zrecipe_attribution_hide);
			update_option('zlrecipe_printed_permalink_hide', $printed_permalink_hide );
			update_option('zlrecipe_printed_copyright_statement', $printed_copyright_statement);
			update_option('zlrecipe_stylesheet', $stylesheet);
			update_option('recipe_title_hide', $recipe_title_hide);
			update_option('zlrecipe_image_hide', $image_hide);
			update_option('zlrecipe_image_hide_print', $image_hide_print);
			update_option('zlrecipe_print_link_hide', $print_link_hide);
			update_option('zlrecipe_ingredient_label', $ingredient_label);
			update_option('zlrecipe_ingredient_label_hide', $ingredient_label_hide);
			update_option('zlrecipe_ingredient_list_type', $ingredient_list_type);
			update_option('zlrecipe_instruction_label', $instruction_label);
			update_option('zlrecipe_instruction_label_hide', $instruction_label_hide);
			update_option('zlrecipe_instruction_list_type', $instruction_list_type);
			update_option('zlrecipe_notes_label', $notes_label);
			update_option('zlrecipe_notes_label_hide', $notes_label_hide);
			update_option('zlrecipe_prep_time_label', $prep_time_label);
			update_option('zlrecipe_prep_time_label_hide', $prep_time_label_hide);
			update_option('zlrecipe_cook_time_label', $cook_time_label);
			update_option('zlrecipe_cook_time_label_hide', $cook_time_label_hide);
			update_option('zlrecipe_total_time_label', $total_time_label);
			update_option('zlrecipe_total_time_label_hide', $total_time_label_hide);
			update_option('zlrecipe_yield_label', $yield_label);
			update_option('zlrecipe_yield_label_hide', $yield_label_hide);
			update_option('zlrecipe_serving_size_label', $serving_size_label);
			update_option('zlrecipe_serving_size_label_hide', $serving_size_label_hide);
			update_option('zlrecipe_calories_label', $calories_label);
			update_option('zlrecipe_calories_label_hide', $calories_label_hide);
			update_option('zlrecipe_fat_label', $fat_label);
			update_option('zlrecipe_fat_label_hide', $fat_label_hide);
			update_option('zlrecipe_rating_label', $rating_label);
			update_option('zlrecipe_rating_label_hide', $rating_label_hide);
			update_option('zlrecipe_image_width', $image_width);
			update_option('zlrecipe_outer_border_style', $outer_border_style);
			update_option('zlrecipe_custom_print_image', $custom_print_image);
		}
	}

	$printed_copyright_statement = esc_attr($printed_copyright_statement);
	$ingredient_label = esc_attr($ingredient_label);
	$instruction_label = esc_attr($instruction_label);
	$image_width = esc_attr($image_width);
	$custom_print_image = esc_attr($custom_print_image);

	$zrecipe_attribution_hide = (strcmp($zrecipe_attribution_hide, 'Hide') == 0 ? 'checked="checked"' : '');
	$printed_permalink_hide = (strcmp($printed_permalink_hide, 'Hide') == 0 ? 'checked="checked"' : '');
	$recipe_title_hide = (strcmp($recipe_title_hide, 'Hide') == 0 ? 'checked="checked"' : '');
	$image_hide = (strcmp($image_hide, 'Hide') == 0 ? 'checked="checked"' : '');
	$image_hide_print = (strcmp($image_hide_print, 'Hide') == 0 ? 'checked="checked"' : '');
	$print_link_hide = (strcmp($print_link_hide, 'Hide') == 0 ? 'checked="checked"' : '');

	// Stylesheet processing
	$stylesheet = (strcmp($stylesheet, 'zlrecipe-std') == 0 ? 'checked="checked"' : '');

	// Outer (hrecipe) border style
	$obs = '';
	$borders = array('None' => '', 'Solid' => '1px solid', 'Dotted' => '1px dotted', 'Dashed' => '1px dashed', 'Thick Solid' => '2px solid', 'Double' => 'double');
	foreach ($borders as $label => $code) {
		$obs .= '<option value="' . $code . '" ' . (strcmp($outer_border_style, $code) == 0 ? 'selected="true"' : '') . '>' . $label . '</option>';
	}

	$ingredient_label_hide = (strcmp($ingredient_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
	$ing_ul = (strcmp($ingredient_list_type, 'ul') == 0 ? 'checked="checked"' : '');
	$ing_ol = (strcmp($ingredient_list_type, 'ol') == 0 ? 'checked="checked"' : '');
	$ing_l = (strcmp($ingredient_list_type, 'l') == 0 ? 'checked="checked"' : '');
	$ing_p = (strcmp($ingredient_list_type, 'p') == 0 ? 'checked="checked"' : '');
	$ing_div = (strcmp($ingredient_list_type, 'div') == 0 ? 'checked="checked"' : '');
	$instruction_label_hide = (strcmp($instruction_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
	$ins_ul = (strcmp($instruction_list_type, 'ul') == 0 ? 'checked="checked"' : '');
	$ins_ol = (strcmp($instruction_list_type, 'ol') == 0 ? 'checked="checked"' : '');
	$ins_l = (strcmp($instruction_list_type, 'l') == 0 ? 'checked="checked"' : '');
	$ins_p = (strcmp($instruction_list_type, 'p') == 0 ? 'checked="checked"' : '');
	$ins_div = (strcmp($instruction_list_type, 'div') == 0 ? 'checked="checked"' : '');
	$other_options = '';
	$other_options_array = array('Rating', 'Prep Time', 'Cook Time', 'Total Time', 'Yield', 'Serving Size', 'Calories', 'Fat', 'Notes');

	foreach ($other_options_array as $option) {
		$name = strtolower(str_replace(' ', '-', $option));
		$value = strtolower(str_replace(' ', '_', $option)) . '_label';
		$value_hide = strtolower(str_replace(' ', '_', $option)) . '_label_hide';
		$value_hide_attr = ${$value_hide} == "Hide" ? 'checked="checked"' : '';
		$other_options .= '<tr valign="top">
            <th scope="row">\'' . $option . '\' Label</th>
            <td><input type="text" name="' . $name . '-label" value="' . ${$value} . '" class="regular-text" /><br />
            <label><input type="checkbox" name="' . $name . '-label-hide" value="Hide" ' . $value_hide_attr . ' /> Don\'t show ' . $option . ' label</label></td>
        </tr>';
	}

	$forms = 'For full customization options, see the <a href="http://www.ziprecipes.net/wp-content/uploads/2014/12/plugin-instructions-4.0.0.9.pdf" target="_blank">Instructions document</a>.
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                    	Custom Print Button
                    	<br />
                    	(Optional)
                    </th>
                    <td>
                    	<input type="hidden" name="action" value="update_settings" />
                        <input placeholder="URL to custom Print button image" type="text" name="custom-print-image" value="' . $custom_print_image . '" class="regular-text" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Zip Recipes Plugin Link</th>
                    <td><label><input type="checkbox" name="zrecipe-attribution-hide" value="Hide" ' . $zrecipe_attribution_hide . ' /> Don\'t show plugin link</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Printed Output: Recipe Permalink</th>
                    <td><label><input type="checkbox" name="printed-permalink-hide" value="Hide" ' . $printed_permalink_hide . ' /> Don\'t show permalink in printed output</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Printed Output: Copyright Statement</th>
                    <td><input type="text" name="printed-copyright-statement" value="' . $printed_copyright_statement . '" class="regular-text" /></td>
                </tr>
            </table>

            <hr />
			<h3>General</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Stylesheet</th>
                    <td><label><input type="checkbox" name="stylesheet" value="zlrecipe-std" ' . $stylesheet . ' /> Use Zip Recipes style</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Recipe Title</th>
                    <td><label><input type="checkbox" name="recipe-title-hide" value="Hide" ' . $recipe_title_hide . ' /> Don\'t show Recipe Title in post (still shows in print view)</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Print Button</th>
                    <td><label><input type="checkbox" name="print-link-hide" value="Hide" ' . $print_link_hide . ' /> Don\'t show Print Button</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Image Width</th>
                    <td><label><input type="text" name="image-width" value="' . $image_width . '" class="regular-text" /> pixels</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Image Display</th>
                    <td>
                    	<label><input type="checkbox" name="image-hide" value="Hide" ' . $image_hide . ' /> Don\'t show Image in post</label>
                    	<br />
                    	<label><input type="checkbox" name="image-hide-print" value="Hide" ' . $image_hide_print . ' /> Don\'t show Image in print view</label>
                    </td>
                </tr>
                <tr valign="top">
                	<th scope="row">Border Style</th>
                	<td>
						<select name="outer-border-style">' . $obs . '</select>
					</td>
				</tr>
            </table>
            <hr />
            <h3>Ingredients</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">\'Ingredients\' Label</th>
                    <td><input type="text" name="ingredient-label" value="' . $ingredient_label . '" class="regular-text" /><br />
                    <label><input type="checkbox" name="ingredient-label-hide" value="Hide" ' . $ingredient_label_hide . ' /> Don\'t show Ingredients label</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">\'Ingredients\' List Type</th>
                    <td>
                        <input type="radio" id="ingredient-list-type-l" name="ingredient-list-type" value="l" ' . $ing_l . ' />
                        <label for="ingredient-list-type-l">List</label>
                        <br />
                        <input type="radio" id="ingredient-list-type-ol" name="ingredient-list-type" value="ol" ' . $ing_ol . ' />
                        <label for="ingredient-list-type-ol">Numbered List</label>
                        <br />
                        <input type="radio" id="ingredient-list-type-ul" name="ingredient-list-type" value="ul" ' . $ing_ul . ' />
                        <label for="ingredient-list-type-ul">Bulleted List</label>
                        <br />
                        <input type="radio" id="ingredient-list-type-p" name="ingredient-list-type" value="p" ' . $ing_p . ' />
                        <label for="ingredient-list-type-p">Paragraphs</label>
                        <br />
                        <input type="radio" id="ingredient-list-type-div" name="ingredient-list-type" value="div" ' . $ing_div . ' />
                        <label for="ingredient-list-type-div">Divs</label>
	                </td>
                </tr>
            </table>

            <hr />

            <h3>Instructions</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">\'Instructions\' Label</th>
                    <td><input type="text" name="instruction-label" value="' . $instruction_label . '" class="regular-text" /><br />
                    <label><input type="checkbox" name="instruction-label-hide" value="Hide" ' . $instruction_label_hide . ' /> Don\'t show Instructions label</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">\'Instructions\' List Type</th>
                    <td>
                        <input type="radio" id="instruction-list-type-l" name="instruction-list-type" value="l" ' . $ins_l . ' />
                        <label for="instruction-list-type-l">List</label>
                        <br />
                        <input type="radio" id="instruction-list-type-ol" name="instruction-list-type" value="ol" ' . $ins_ol . ' />
                        <label for="instruction-list-type-ol">Numbered List</label>
                        <br />
                        <input type="radio" id="instruction-list-type-ul" name="instruction-list-type" value="ul" ' . $ins_ul . ' />
	                    <label for="instruction-list-type-ul">Bulleted List</label>
                        <br />
                        <input type="radio" id="instruction-list-type-p" name="instruction-list-type" value="p" ' . $ins_p . ' />
	                    <label for="instruction-list-type-p">Paragraphs</label>
                        <br />
                        <input type="radio" id="instruction-list-type-div" name="instruction-list-type" value="div" ' . $ins_div . ' />
                        <label for="instruction-list-type-div">Divs</label>
	                </td>
                </tr>
            </table>

            <hr />

            <h3>Other Options</h3>
            <table class="form-table">
                ' . $other_options . '
            </table>

            <p><input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes"></p>';

	if (! $registered && ! isServerProtocolHttps())
	{
		$forms = '
			<script type="text/javascript">
				var $form = jQuery("#zlrecipe_settings_form");

		         $form.on("submit", function (event)
		         {
	                 jQuery("#register_button").val("Registering...")
	                 jQuery("#register_button").attr("disabled", true);

	                 var postUrl = "http://api.ziprecipes.net/installation/register/";

		         	jQuery.post(postUrl, $form.serialize(), function(data)
		         	{
		         		var data = JSON.parse(data);

		         		if (data.status === "failure")
		         		{
		         			for (var elementID in data.errors)
		         			{
		         				if (data.errors.hasOwnProperty(elementID))
		         				{
		         					var errors = data.errors[elementID];
		         					for (var i = 0; i < errors.length; i++)
		         					{
		         						jQuery("#" + elementID).parent().append("<br/>"+errors[i]);
		         					}
		         				}
		         			}

			                jQuery("#register_button").val("Register")
			                jQuery("#register_button").attr("disabled", false);
		         		}
		         		else
		         		{
		         			// unbind current event hanlder and resubmit the form
		         			$form.off("submit");


		         			$form.get(0).submit();
		         		}
		         	});

		         	return false;
		         });
			</script>
			<h3>Register Zip Recipes Free</h3>
			Please register your plugin so we can notify you of any security updates or other fixes we make to Zip
			Recipes plugin. Registering also helps us troubleshoot any problems you may encounter. When you register, we will automatically
			receive your blog\'s web address, WordPress version, and names of installed plugins.
            <table class="form-table">
                <tr valign="top">
                    <th scope="row" style="padding: 0"><label for="first_name">First Name:</label></th>
                    <td style="padding: 0"><input type="text" id="first_name" name="first_name" class="regular-text" required autofocus /></td>
                </tr>
                <tr valign="top">
                    <th scope="row" style="padding: 0"><label for="last_name">Last Name:</label></th>
                    <td style="padding: 0"><input type="text" id="last_name" name="last_name" class="regular-text" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row" style="padding: 0"><label for="email">Email:</label></th>
                    <td style="padding: 0">
                    	<input type="email" id="email" name="email" class="regular-text" required />
                    	<input type="hidden" id="wp-version" name="wp_version" value="' . $wp_version . '" />
                    	<input type="hidden" id="plugins" name="plugins" value="' . zrdn_get_installed_plugins() . '" />
                    	<input type="hidden" id="blog-url" name="blog_url" value="' . home_url() . '" />
                    	<input type="hidden" name="action" value="register" />
                    </td>
                </tr>
            </table>
			<p><input type="submit" id="register_button" class="button-primary" value="Register"></p>
            ';
	}

	$output =  '<style>
        .form-table label { line-height: 2.5; }
        hr { border: none solid #DDD; border-top: 1px; margin: 30px 0; }
    </style>
    <div class="wrap">
        <form method="post" action="" id="zlrecipe_settings_form" name="zlrecipe_settings_form">
            <h2><img src="' . $zrdn_icon . '" /> Zip Recipes Settings</h2>' .
	           $forms .
	           '</form>
    </div>';

	echo $output;
}


/**
 * Get list of installed plugins as a string. Each plugin is separated with ;
 */
function zrdn_get_installed_plugins()
{
	$pluginsString = '';
	$plugins = get_plugins();
	foreach ($plugins as $path => $pluginData)
	{
		$pluginsString .= $pluginData['Name'] . "|";
	}

	return $pluginsString;
}

function zrdn_tinymce_plugin($plugin_array) {
	$plugin_array['zrdn_plugin'] = plugins_url( 'scripts/zlrecipe_editor_plugin.js?sver=' . ZRDN_VERSION_NUM, __FILE__ );
	return $plugin_array;
}

function zrdn_register_tinymce_button($buttons) {
	array_push($buttons, "zrdn_buttons");
	return $buttons;
}

function zrdn_add_recipe_button() {
	// check user permissions
	if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') ) {
		return;
	}

	// check if WYSIWYG is enabled
	if ( get_user_option('rich_editing') == 'true') {
		add_filter('mce_external_plugins', 'zrdn_tinymce_plugin' );
		add_filter('mce_buttons', 'zrdn_register_tinymce_button' );
	}
}

function zrdn_strip_chars( $val )
{
	return str_replace( '\\', '', $val );
}

// Content for the popup iframe when creating or editing a recipe
function zrdn_iframe_content($post_info = null, $get_info = null) {

	$recipe_id = 0;
	$recipe_title = "";
	$recipe_image = "";
	$prep_time_hours = 0;
	$prep_time_minutes = 0;
	$cook_time_hours = 0;
	$cook_time_minutes = 0;
	$total_time_hours = 0;
	$total_time_minutes = 0;
	$yield = "";
	$serving_size = 0;
	$calories = 0;
	$fat = 0;
	$ingredients = "";
	$instructions = "";
	$summary = "";
	$notes = "";
	$prep_time_input = '';
	$cook_time_input = '';
	$total_time_input = '';
	$submit = '';
	$ss = array();
	$iframe_title = '';
	$submitform = '';


	if ($post_info || $get_info) {

		if( $get_info["add-recipe-button"] || strpos($get_info["post_id"], '-') !== false ) {
			$iframe_title = "Update Your Recipe";
			$submit = "Update Recipe";
		} else {
			$iframe_title = "Add a Recipe";
			$submit       = "Add Recipe";
		}

		if ($get_info["post_id"] && !$get_info["add-recipe-button"] && strpos($get_info["post_id"], '-') !== false) {
			$recipe_id = preg_replace('/[0-9]*?\-/i', '', $get_info["post_id"]);
			$recipe = zrdn_select_recipe_db($recipe_id);
			$recipe_title = $recipe->recipe_title;
			$recipe_image = $recipe->recipe_image;
			$summary = $recipe->summary;
			$notes = $recipe->notes;
			$rating = $recipe->rating;
			$ss = array();
			$ss[(int)$rating] = 'selected="true"';
			$prep_time_input = '';
			$cook_time_input = '';
			$total_time_input = '';
			if (class_exists('DateInterval')) {
				try {
					$prep_time = new DateInterval($recipe->prep_time);
					$prep_time_minutes = $prep_time->i;
					$prep_time_hours = $prep_time->h;
				} catch (Exception $e) {
					if ($recipe->prep_time != null) {
						$prep_time_input = '<input type="text" name="prep_time" value="' . $recipe->prep_time . '"/>';
					}
				}

				try {
					$cook_time = new DateInterval($recipe->cook_time);
					$cook_time_minutes = $cook_time->i;
					$cook_time_hours = $cook_time->h;
				} catch (Exception $e) {
					if ($recipe->cook_time != null) {
						$cook_time_input = '<input type="text" name="cook_time" value="' . $recipe->cook_time . '"/>';
					}
				}

				try {
					$total_time = new DateInterval($recipe->total_time);
					$total_time_minutes = $total_time->i;
					$total_time_hours = $total_time->h;
				} catch (Exception $e) {
					if ($recipe->total_time != null) {
						$total_time_input = '<input type="text" name="total_time" value="' . $recipe->total_time . '"/>';
					}
				}
			} else {
				if (preg_match('(^[A-Z0-9]*$)', $recipe->prep_time) == 1) {
					preg_match('(\d*S)', $recipe->prep_time, $pts);
					preg_match('(\d*M)', $recipe->prep_time, $ptm, PREG_OFFSET_CAPTURE, strpos($recipe->prep_time, 'T'));
					$prep_time_minutes = str_replace('M', '', $ptm[0][0]);
					preg_match('(\d*H)', $recipe->prep_time, $pth);
					$prep_time_hours = str_replace('H', '', $pth[0]);
					preg_match('(\d*D)', $recipe->prep_time, $ptd);
					preg_match('(\d*M)', $recipe->prep_time, $ptmm);
					preg_match('(\d*Y)', $recipe->prep_time, $pty);
				} else {
					if ($recipe->prep_time != null) {
						$prep_time_input = '<input type="text" name="prep_time" value="' . $recipe->prep_time . '"/>';
					}
				}

				if (preg_match('(^[A-Z0-9]*$)', $recipe->cook_time) == 1) {
					preg_match('(\d*S)', $recipe->cook_time, $cts);
					preg_match('(\d*M)', $recipe->cook_time, $ctm, PREG_OFFSET_CAPTURE, strpos($recipe->cook_time, 'T'));
					$cook_time_minutes = str_replace('M', '', $ctm[0][0]);
					preg_match('(\d*H)', $recipe->cook_time, $cth);
					$cook_time_hours = str_replace('H', '', $cth[0]);
					preg_match('(\d*D)', $recipe->cook_time, $ctd);
					preg_match('(\d*M)', $recipe->cook_time, $ctmm);
					preg_match('(\d*Y)', $recipe->cook_time, $cty);
				} else {
					if ($recipe->cook_time != null) {
						$cook_time_input = '<input type="text" name="cook_time" value="' . $recipe->cook_time . '"/>';
					}
				}

				if (preg_match('(^[A-Z0-9]*$)', $recipe->total_time) == 1) {
					preg_match('(\d*S)', $recipe->total_time, $tts);
					preg_match('(\d*M)', $recipe->total_time, $ttm, PREG_OFFSET_CAPTURE, strpos($recipe->total_time, 'T'));
					$total_time_minutes = str_replace('M', '', $ttm[0][0]);
					preg_match('(\d*H)', $recipe->total_time, $tth);
					$total_time_hours = str_replace('H', '', $tth[0]);
					preg_match('(\d*D)', $recipe->total_time, $ttd);
					preg_match('(\d*M)', $recipe->total_time, $ttmm);
					preg_match('(\d*Y)', $recipe->total_time, $tty);
				} else {
					if ($recipe->total_time != null) {
						$total_time_input = '<input type="text" name="total_time" value="' . $recipe->total_time . '"/>';
					}
				}
			}

			$yield = $recipe->yield;
			$serving_size = $recipe->serving_size;
			$calories = $recipe->calories;
			$fat = $recipe->fat;
			$ingredients = $recipe->ingredients;
			$instructions = $recipe->instructions;
		} else {
			foreach ($post_info as $key=>$val) {
				$post_info[$key] = stripslashes($val);
			}

			$recipe_id = $post_info["recipe_id"];
			if( !$get_info["add-recipe-button"] )
				$recipe_title = get_the_title( $get_info["post_id"] );
			else
				$recipe_title = $post_info["recipe_title"];
			$recipe_image = $post_info["recipe_image"];
			$summary = $post_info["summary"];
			$notes = $post_info["notes"];
			$prep_time_minutes = $post_info["prep_time_minutes"];
			$prep_time_hours = $post_info["prep_time_hours"];
			$cook_time_minutes = $post_info["cook_time_minutes"];
			$cook_time_hours = $post_info["cook_time_hours"];
			$total_time_minutes = $post_info["total_time_minutes"];
			$total_time_hours = $post_info["total_time_hours"];
			$yield = $post_info["yield"];
			$serving_size = $post_info["serving_size"];
			$calories = $post_info["calories"];
			$fat = $post_info["fat"];
			$ingredients = $post_info["ingredients"];
			$instructions = $post_info["instructions"];
			if ($recipe_title != null && $recipe_title != '' && $ingredients != null && $ingredients != '') {
				$recipe_id = zrdn_insert_db($post_info);
			}
		}
	}

	$recipe_title = esc_attr($recipe_title);
	$recipe_image = esc_attr($recipe_image);
	$prep_time_hours = esc_attr($prep_time_hours);
	$prep_time_minutes = esc_attr($prep_time_minutes);
	$cook_time_hours = esc_attr($cook_time_hours);
	$cook_time_minutes = esc_attr($cook_time_minutes);
	$total_time_hours = esc_attr($total_time_hours);
	$total_time_minutes = esc_attr($total_time_minutes);
	$yield = esc_attr($yield);
	$serving_size = esc_attr($serving_size);
	$calories = esc_attr($calories);
	$fat = esc_attr($fat);
	$ingredients = esc_textarea($ingredients);
	$instructions = esc_textarea($instructions);
	$summary = esc_textarea($summary);
	$notes = esc_textarea($notes);

	$id = (int) $_REQUEST["post_id"];
	$plugindir = ZRDN_PLUGIN_DIRECTORY;
	if ($post_info != null) {
		$submitform .= "<script>window.onload = amdZLRecipeSubmitForm;</script>";
	}

	$form = <<< HTML

			<h3 class='amd-zlrecipe-title'>$iframe_title</h3>
	        <div id='amd-zlrecipe-form-items'>
            <input type='hidden' name='post_id' value='$id' />
            <input type='hidden' name='recipe_id' value='$recipe_id' />
            <p id='recipe-title'><label>Recipe Title <span class='required'>*</span></label> <input type='text' name='recipe_title' value='$recipe_title' /></p>
            <p id='recipe-image'><label>Recipe Image</label> <input type='text' name='recipe_image' value='$recipe_image' /></p>
            <p id='z_recipe_ingredients' class='cls'><label>Ingredients <span class='required'>*</span> <small>Put each ingredient on a separate line.  There is no need to use bullets for your ingredients.</small><small>You can also create labels, hyperlinks, bold/italic effects and even add images! <a href="http://www.ziprecipes.net/wp-content/uploads/2014/12/plugin-instructions-4.0.0.9.pdf" target="_blank">Learn how here</a></small></label><textarea name='ingredients'>$ingredients</textarea></label></p>
            <p id='amd-zlrecipe-instructions' class='cls'><label>Instructions <small>Press return after each instruction. There is no need to number your instructions.</small><small>You can also create labels, hyperlinks, bold/italic effects and even add images! <a href="http://www.ziprecipes.net/wp-content/uploads/2014/12/plugin-instructions-4.0.0.9.pdf" target="_blank">Learn how here</a></small></label><textarea name='instructions'>$instructions</textarea></label></p>
            <p><a href='#' id='more-options-toggle'>More options</a></p>
            <div id='more-options'>
                <p class='cls'><label>Summary</label> <textarea name='summary'>$summary</textarea></label></p>
                <p class='cls'><label>Rating</label>
                	<span class='rating'>
						<select name="rating">
							  <option value="0">None</option>
							  <option value="1" $ss[1]>1 Star</option>
							  <option value="2" $ss[2]>2 Stars</option>
							  <option value="3" $ss[3]>3 Stars</option>
							  <option value="4" $ss[4]>4 Stars</option>
							  <option value="5" $ss[5]>5 Stars</option>
						</select>
					</span>
				</p>
                <p class="cls"><label>Prep Time</label>
                    $prep_time_input
                    <span class="time">
                        <span><input type='number' min="0" max="24" name='prep_time_hours' value='$prep_time_hours' /><label>hours</label></span>
                        <span><input type='number' min="0" max="60" name='prep_time_minutes' value='$prep_time_minutes' /><label>minutes</label></span>
                    </span>
                </p>
                <p class="cls"><label>Cook Time</label>
                    $cook_time_input
                    <span class="time">
                    	<span><input type='number' min="0" max="24" name='cook_time_hours' value='$cook_time_hours' /><label>hours</label></span>
                        <span><input type='number' min="0" max="60" name='cook_time_minutes' value='$cook_time_minutes' /><label>minutes</label></span>
                    </span>
                </p>
                <p class="cls"><label>Total Time</label>
                    $total_time_input
                    <span class="time">
                        <span><input type='number' min="0" max="24" name='total_time_hours' value='$total_time_hours' /><label>hours</label></span>
                        <span><input type='number' min="0" max="60" name='total_time_minutes' value='$total_time_minutes' /><label>minutes</label></span>
                    </span>
                </p>
                <p><label>Yield</label> <input type='text' name='yield' value='$yield' /></p>
                <p><label>Serving Size</label> <input type='text' name='serving_size' value='$serving_size' /></p>
                <p><label>Calories</label> <input type='text' name='calories' value='$calories' /></p>
                <p><label>Fat</label> <input type='text' name='fat' value='$fat' /></p>
                <p class='cls'><label>Notes</label> <textarea name='notes'>$notes</textarea></label></p>
            </div>
            <input type='submit' value='$submit' name='add-recipe-button' />
        </div>
HTML;

	if (! get_option('zrdn_registered') && ! isServerProtocolHttps())
	{
		require_once(ABSPATH . 'wp-admin/includes/plugin.php');

		$settings_page_url = admin_url( 'admin.php?page=' . 'zrdn-settings' );

		$form              = sprintf('<h3 class="amd-zlrecipe-title">Register Zip Recipes Free</h3>
							<h4>Thank you for installing Zip Recipes plug. <a href="javascript:window.top.location = \'%s\';">Click here to go to the Zip Recipes plugin settings</a>
							to register the plugin (it\'s free!).</h4>', $settings_page_url);
	}

	$output = <<< HTML

<!DOCTYPE html>
<!--suppress HtmlUnknownTarget -->
<head>
		<link rel="stylesheet" href="$plugindir/styles/zlrecipe-dlog.css" type="text/css" media="all" />
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>
    <script type="text/javascript">//<!CDATA[

        function amdZLRecipeSubmitForm() {
            var title = document.forms['recipe_form']['recipe_title'].value;

            if (title==null || title=='') {
            	var jQrecipeTitle = $('#recipe-title');
                jQrecipeTitle.find('input').addClass('input-error');
                jQrecipeTitle.append('<p class="error-message">You must enter a title for your recipe.</p>');

                return false;
            }

            var jQingredients = $('#z_recipe_ingredients');
            var jQingredientsTextarea = jQingredients.find('textarea');
            var ingredients = jQingredientsTextarea.val();
            if (ingredients==null || ingredients=='' || ingredients==undefined) {
                jQingredientsTextarea.addClass('input-error');
                jQingredients.append('<p class="error-message">You must enter at least one ingredient.</p>');

                return false;
            }
            window.parent.amdZLRecipeInsertIntoPostEditor('$recipe_id');
            top.tinymce.activeEditor.windowManager.close(window);
        }

        $(document).ready(function() {
            $('#more-options').hide();
            $('#more-options-toggle').click(function() {
                $('#more-options').toggle(400);
                return false;
            });
        });
    //]]>
    </script>
    $submitform
</head>
<body id="amd-zlrecipe-uploader">
    <form enctype='multipart/form-data' method='post' action='' name='recipe_form'>
		$form
    </form>
</body>
HTML;



	echo $output;
}

// Inserts the recipe into the database
/**
 * @param $post_info
 *
 * @return mixed
 */
function zrdn_insert_db($post_info) {
	global $wpdb;

	$recipe_id = $post_info["recipe_id"];

	if ($post_info["prep_time_years"] || $post_info["prep_time_months"] || $post_info["prep_time_days"] || $post_info["prep_time_hours"] || $post_info["prep_time_minutes"] || $post_info["prep_time_seconds"]) {
		$prep_time = 'P';
		if ($post_info["prep_time_years"]) {
			$prep_time .= $post_info["prep_time_years"] . 'Y';
		}
		if ($post_info["prep_time_months"]) {
			$prep_time .= $post_info["prep_time_months"] . 'M';
		}
		if ($post_info["prep_time_days"]) {
			$prep_time .= $post_info["prep_time_days"] . 'D';
		}
		if ($post_info["prep_time_hours"] || $post_info["prep_time_minutes"] || $post_info["prep_time_seconds"]) {
			$prep_time .= 'T';
		}
		if ($post_info["prep_time_hours"]) {
			$prep_time .= $post_info["prep_time_hours"] . 'H';
		}
		if ($post_info["prep_time_minutes"]) {
			$prep_time .= $post_info["prep_time_minutes"] . 'M';
		}
		if ($post_info["prep_time_seconds"]) {
			$prep_time .= $post_info["prep_time_seconds"] . 'S';
		}
	} else {
		$prep_time = $post_info["prep_time"];
	}

	if ($post_info["cook_time_years"] || $post_info["cook_time_months"] || $post_info["cook_time_days"] || $post_info["cook_time_hours"] || $post_info["cook_time_minutes"] || $post_info["cook_time_seconds"]) {
		$cook_time = 'P';
		if ($post_info["cook_time_years"]) {
			$cook_time .= $post_info["cook_time_years"] . 'Y';
		}
		if ($post_info["cook_time_months"]) {
			$cook_time .= $post_info["cook_time_months"] . 'M';
		}
		if ($post_info["cook_time_days"]) {
			$cook_time .= $post_info["cook_time_days"] . 'D';
		}
		if ($post_info["cook_time_hours"] || $post_info["cook_time_minutes"] || $post_info["cook_time_seconds"]) {
			$cook_time .= 'T';
		}
		if ($post_info["cook_time_hours"]) {
			$cook_time .= $post_info["cook_time_hours"] . 'H';
		}
		if ($post_info["cook_time_minutes"]) {
			$cook_time .= $post_info["cook_time_minutes"] . 'M';
		}
		if ($post_info["cook_time_seconds"]) {
			$cook_time .= $post_info["cook_time_seconds"] . 'S';
		}
	} else {
		$cook_time = $post_info["cook_time"];
	}

	if ($post_info["total_time_years"] || $post_info["total_time_months"] || $post_info["total_time_days"] || $post_info["total_time_hours"] || $post_info["total_time_minutes"] || $post_info["total_time_seconds"]) {
		$total_time = 'P';
		if ($post_info["total_time_years"]) {
			$total_time .= $post_info["total_time_years"] . 'Y';
		}
		if ($post_info["total_time_months"]) {
			$total_time .= $post_info["total_time_months"] . 'M';
		}
		if ($post_info["total_time_days"]) {
			$total_time .= $post_info["total_time_days"] . 'D';
		}
		if ($post_info["total_time_hours"] || $post_info["total_time_minutes"] || $post_info["total_time_seconds"]) {
			$total_time .= 'T';
		}
		if ($post_info["total_time_hours"]) {
			$total_time .= $post_info["total_time_hours"] . 'H';
		}
		if ($post_info["total_time_minutes"]) {
			$total_time .= $post_info["total_time_minutes"] . 'M';
		}
		if ($post_info["total_time_seconds"]) {
			$total_time .= $post_info["total_time_seconds"] . 'S';
		}
	} else {
		$total_time = $post_info["total_time"];
	}

	$recipe = array (
		"recipe_title" =>  $post_info["recipe_title"],
		"recipe_image" => $post_info["recipe_image"],
		"summary" =>  $post_info["summary"],
		"rating" => $post_info["rating"],
		"prep_time" => $prep_time,
		"cook_time" => $cook_time,
		"total_time" => $total_time,
		"yield" =>  $post_info["yield"],
		"serving_size" =>  $post_info["serving_size"],
		"calories" => $post_info["calories"],
		"fat" => $post_info["fat"],
		"ingredients" => $post_info["ingredients"],
		"instructions" => $post_info["instructions"],
		"notes" => $post_info["notes"],
	);

	if (zrdn_select_recipe_db($recipe_id) == null) {
		$recipe["post_id"] = $post_info["post_id"];	// set only during record creation
		$wpdb->insert( $wpdb->prefix . "amd_zlrecipe_recipes", $recipe );
		$recipe_id = $wpdb->insert_id;
	} else {
		$wpdb->update( $wpdb->prefix . "amd_zlrecipe_recipes", $recipe, array( 'recipe_id' => $recipe_id ));
	}

	return $recipe_id;
}

// Inserts the recipe into the post editor
function zrdn_plugin_footer() {
	$url = site_url();
	$plugindir = ZRDN_PLUGIN_DIRECTORY;

	echo <<< HTML
    <style type="text/css" media="screen">
        #wp_editrecipebtns { position:absolute;display:block;z-index:999998; }
        #wp_editrecipebtn { margin-right:20px; }
        #wp_editrecipebtn,#wp_delrecipebtn { cursor:pointer; padding:12px;background:#010101; -moz-border-radius:8px;-khtml-border-radius:8px;-webkit-border-radius:8px;border-radius:8px; filter:alpha(opacity=80); -moz-opacity:0.8; -khtml-opacity: 0.8; opacity: 0.8; }
        #wp_editrecipebtn:hover,#wp_delrecipebtn:hover { background:#000; filter:alpha(opacity=100); -moz-opacity:1; -khtml-opacity: 1; opacity: 1; }
    </style>
    <script>//<![CDATA[
    var baseurl = '$url';          // This variable is used by the editor plugin
    var plugindir = '$plugindir';  // This variable is used by the editor plugin

        function amdZLRecipeInsertIntoPostEditor(rid) {
            tb_remove();

            var ed;

            var output = '<img id="amd-zlrecipe-recipe-';
            output += rid;
						output += '" class="amd-zlrecipe-recipe" src="' + plugindir + '/images/zrecipe-placeholder.png" alt="" />';

        	if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() && ed.id=='content') {  //path followed when in Visual editor mode
        		ed.focus();
        		if ( tinymce.isIE )
        			ed.selection.moveToBookmark(tinymce.EditorManager.activeEditor.windowManager.bookmark);

        		ed.execCommand('mceInsertContent', false, output);

        	} else if ( typeof edInsertContent == 'function' ) {  // path followed when in HTML editor mode
                output = '[amd-zlrecipe-recipe:';
                output += rid;
                output += ']';
                edInsertContent(edCanvas, output);
        	} else {
                output = '[amd-zlrecipe-recipe:';
                output += rid;
                output += ']';
        		jQuery( edCanvas ).val( jQuery( edCanvas ).val() + output );
        	}
        }
    //]]></script>
HTML;
}

add_action('admin_footer', 'zrdn_plugin_footer' );

// Converts the image to a recipe for output
function zrdn_convert_to_recipe($post_text) {
	$output = $post_text;
	$needle_old = 'id="amd-zlrecipe-recipe-';
	$preg_needle_old = '/(id)=("(amd-zlrecipe-recipe-)[0-9^"]*")/i';
	$needle = '[amd-zlrecipe-recipe:';
	$preg_needle = '/\[amd-zlrecipe-recipe:([0-9]+)\]/i';

	if (strpos($post_text, $needle_old) !== false) {
		// This is for backwards compatability. Please do not delete or alter.
		preg_match_all($preg_needle_old, $post_text, $matches);
		foreach ($matches[0] as $match) {
			$recipe_id = str_replace('id="amd-zlrecipe-recipe-', '', $match);
			$recipe_id = str_replace('"', '', $recipe_id);
			$recipe = zrdn_select_recipe_db($recipe_id);
			$formatted_recipe = zrdn_format_recipe($recipe);
			$output = str_replace('<img id="amd-zlrecipe-recipe-' . $recipe_id . '" class="amd-zlrecipe-recipe" src="' . plugins_url() . '/' . dirname(plugin_basename(__FILE__)) . '/images/zrecipe-placeholder.png?ver=1.0" alt="" />', $formatted_recipe, $output);
		}
	}

	if (strpos($post_text, $needle) !== false) {
		preg_match_all($preg_needle, $post_text, $matches);
		foreach ($matches[0] as $match) {
			$recipe_id = str_replace('[amd-zlrecipe-recipe:', '', $match);
			$recipe_id = str_replace(']', '', $recipe_id);
			$recipe = zrdn_select_recipe_db($recipe_id);
			$formatted_recipe = zrdn_format_recipe($recipe);
			$output = str_replace('[amd-zlrecipe-recipe:' . $recipe_id . ']', $formatted_recipe, $output);
		}
	}

	return $output;
}

add_filter('the_content', 'zrdn_convert_to_recipe' );

// Pulls a recipe from the db
function zrdn_select_recipe_db($recipe_id) {
	global $wpdb;

	$selectStatement = sprintf("SELECT * FROM `%samd_zlrecipe_recipes` WHERE recipe_id=%d", $wpdb->prefix, $recipe_id);
	$recipe = $wpdb->get_row($selectStatement);

	return $recipe;
}

// Format an ISO8601 duration for human readibility
function zrdn_format_duration($duration) {
	$date_abbr = array('y' => 'year', 'm' => 'month', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second');
	$result = '';

	if (class_exists('DateInterval')) {
		try {
			$result_object = new DateInterval($duration);

			foreach ($date_abbr as $abbr => $name) {
				if ($result_object->$abbr > 0) {
					$result .= $result_object->$abbr . ' ' . $name;
					if ($result_object->$abbr > 1) {
						$result .= 's';
					}
					$result .= ', ';
				}
			}

			$result = trim($result, ' \t,');
		} catch (Exception $e) {
			$result = $duration;
		}
	} else { // else we have to do the work ourselves so the output is pretty
		$arr = explode('T', $duration);
		$arr[1] = str_replace('M', 'I', $arr[1]); // This mimics the DateInterval property name
		$duration = implode('T', $arr);

		foreach ($date_abbr as $abbr => $name) {
			if (preg_match('/(\d+)' . $abbr . '/i', $duration, $val)) {
				$result .= $val[1] . ' ' . $name;
				if ($val[1] > 1) {
					$result .= 's';
				}
				$result .= ', ';
			}
		}

		$result = trim($result, ' \t,');
	}
	return $result;
}

// function to include the javascript for the Add Recipe button
function zrdn_process_head() {

	// Always add the print script
	$header_html='<script type="text/javascript" async="" src="' . ZRDN_PLUGIN_DIRECTORY . 'scripts/zlrecipe_print.js"></script>';

	// Recipe styling
	$css = get_option('zlrecipe_stylesheet');
	if (strcmp($css, '') != 0) {
		$header_html .= '<link charset="utf-8" href="' . ZRDN_PLUGIN_DIRECTORY . 'styles/zlrecipe-std.css" rel="stylesheet" type="text/css" />';
	}

	echo $header_html;
}
add_filter('wp_head', 'zrdn_process_head' );

// Replaces the [a|b] pattern with text a that links to b
// Replaces _words_ with an italic span and *words* with a bold span
function zrdn_richify_item($item, $class) {
	$output = preg_replace('/\[([^\]\|\[]*)\|([^\]\|\[]*)\]/', '<a href="\\2" class="' . $class . '-link" target="_blank">\\1</a>', $item);
	$output = preg_replace('/(^|\s)\*([^\s\*][^\*]*[^\s\*]|[^\s\*])\*(\W|$)/', '\\1<span class="bold">\\2</span>\\3', $output);
	return preg_replace('/(^|\s)_([^\s_][^_]*[^\s_]|[^\s_])_(\W|$)/', '\\1<span class="italic">\\2</span>\\3', $output);
}

function zrdn_break( $otag, $text, $ctag) {
	$output = "";
	$split_string = explode( "\r\n\r\n", $text, 10 );
	foreach ( $split_string as $str )
	{
		$output .= $otag . $str . $ctag;
	}
	return $output;
}

// Processes markup for attributes like labels, images and links
// !Label
// %image
function zrdn_format_item($item, $elem, $class, $itemprop, $id, $i) {

	if (preg_match("/^%(\S*)/", $item, $matches)) {	// IMAGE Updated to only pull non-whitespace after some blogs were adding additional returns to the output
		$output = '<img class = "' . $class . '-image" src="' . $matches[1] . '" />';
		return $output; // Images don't also have labels or links so return the line immediately.
	}

	if (preg_match("/^!(.*)/", $item, $matches)) {	// LABEL
		$class .= '-label';
		$elem = 'div';
		$item = $matches[1];
		$output = '<' . $elem . ' id="' . $id . $i . '" class="' . $class . '" >';	// No itemprop for labels
	} else {
		$output = '<' . $elem . ' id="' . $id . $i . '" class="' . $class . '" itemprop="' . $itemprop . '">';
	}

	$output .= zrdn_richify_item($item, $class);
	$output .= '</' . $elem . '>';

	return $output;
}

// Formats the recipe for output
function zrdn_format_recipe($recipe) {
	$output = "";
	$permalink = get_permalink();

	// Output main recipe div with border style
	$style_tag = '';
	$border_style = get_option('zlrecipe_outer_border_style');
	if ($border_style != null)
		$style_tag = 'style="border: ' . $border_style . ';"';
	$output .= '
    <div id="zlrecipe-container-' . $recipe->recipe_id . '" class="zlrecipe-container-border" ' . $style_tag . '>
    <div itemscope itemtype="http://schema.org/Recipe" id="zlrecipe-container" class="serif zlrecipe">
      <div id="zlrecipe-innerdiv">
        <div class="item b-b">';

	// Add the print button
	if (strcmp(get_option('zlrecipe_print_link_hide'), 'Hide') != 0) {
		$custom_print_image = get_option('zlrecipe_custom_print_image');
		$button_type = 'butn-link';
		$button_image = 'Print'; // NOT a button image in this case, but this is the legacy version
		if (strlen($custom_print_image) > 0) {
			$button_type = 'print-link';
			$button_image = '<img src="' . $custom_print_image . '">';
		}
		$output .= '<div class="zlrecipe-print-link fl-r"><a class="' . $button_type . '" title="Print this recipe" href="javascript:void(0);" onclick="zlrPrint(\'zlrecipe-container-' . $recipe->recipe_id . '\', \'' . ZRDN_PLUGIN_DIRECTORY . '\'); return false">' . $button_image . '</a></div>';
	}

	// add the title and close the item class
	$hide_tag = '';
	if (strcmp(get_option('recipe_title_hide'), 'Hide') == 0)
		$hide_tag = ' texthide';
	$output .= '<div id="zlrecipe-title" itemprop="name" class="b-b h-1 strong' . $hide_tag . '" >' . $recipe->recipe_title . '</div>
      </div>';

	// open the zlmeta and fl-l container divs
	$output .= '<div class="zlmeta zlclear">
      <div class="fl-l width-50">';

	if ($recipe->rating != 0) {
		$output .= '<p id="zlrecipe-rating" itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">';
		if (strcmp(get_option('zlrecipe_rating_label_hide'), 'Hide') != 0) {
			$output .= get_option('zlrecipe_rating_label') . ' ';
		}
		$output .= '<span class="rating rating-' . $recipe->rating . '"><span itemprop="ratingValue">' . $recipe->rating . '</span><span itemprop="reviewCount" style="display: none;">1</span></span>
       </p>';
	}

	// recipe timing
	if ($recipe->prep_time != null) {
		$prep_time = zrdn_format_duration($recipe->prep_time);

		$output .= '<p id="zlrecipe-prep-time">';
		if (strcmp(get_option('zlrecipe_prep_time_label_hide'), 'Hide') != 0) {
			$output .= get_option('zlrecipe_prep_time_label') . ' ';
		}
		$output .= '<span itemprop="prepTime" content="' . $recipe->prep_time . '">' . $prep_time . '</span></p>';
	}
	if ($recipe->cook_time != null) {
		$cook_time = zrdn_format_duration($recipe->cook_time);

		$output .= '<p id="zlrecipe-cook-time">';
		if (strcmp(get_option('zlrecipe_cook_time_label_hide'), 'Hide') != 0) {
			$output .= get_option('zlrecipe_cook_time_label') . ' ';
		}
		$output .= '<span itemprop="cookTime" content="' . $recipe->cook_time . '">' . $cook_time . '</span></p>';
	}
	if ($recipe->total_time != null) {
		$total_time = zrdn_format_duration($recipe->total_time);

		$output .= '<p id="zlrecipe-total-time">';
		if (strcmp(get_option('zlrecipe_total_time_label_hide'), 'Hide') != 0) {
			$output .= get_option('zlrecipe_total_time_label') . ' ';
		}
		$output .= '<span itemprop="totalTime" content="' . $recipe->total_time . '">' . $total_time . '</span></p>';
	}

	//!! close the first container div and open the second
	$output .= '</div>
      <div class="fl-l width-50">';

	//!! yield and nutrition
	if ($recipe->yield != null) {
		$output .= '<p id="zlrecipe-yield">';
		if (strcmp(get_option('zlrecipe_yield_label_hide'), 'Hide') != 0) {
			$output .= get_option('zlrecipe_yield_label') . ' ';
		}
		$output .= '<span itemprop="recipeYield">' . $recipe->yield . '</span></p>';
	}

	if ($recipe->serving_size != null || $recipe->calories != null || $recipe->fat != null) {
		$output .= '<div id="zlrecipe-nutrition" itemprop="nutrition" itemscope itemtype="http://schema.org/NutritionInformation">';
		if ($recipe->serving_size != null) {
			$output .= '<p id="zlrecipe-serving-size">';
			if (strcmp(get_option('zlrecipe_serving_size_label_hide'), 'Hide') != 0) {
				$output .= get_option('zlrecipe_serving_size_label') . ' ';
			}
			$output .= '<span itemprop="servingSize">' . $recipe->serving_size . '</span></p>';
		}
		if ($recipe->calories != null) {
			$output .= '<p id="zlrecipe-calories">';
			if (strcmp(get_option('zlrecipe_calories_label_hide'), 'Hide') != 0) {
				$output .= get_option('zlrecipe_calories_label') . ' ';
			}
			$output .= '<span itemprop="calories">' . $recipe->calories . '</span></p>';
		}
		if ($recipe->fat != null) {
			$output .= '<p id="zlrecipe-fat">';
			if (strcmp(get_option('zlrecipe_fat_label_hide'), 'Hide') != 0) {
				$output .= get_option('zlrecipe_fat_label') . ' ';
			}
			$output .= '<span itemprop="fatContent">' . $recipe->fat . '</span></p>';
		}
		$output .= '</div>';
	}

	//!! close the second container
	$output .= '</div>
      <div class="zlclear">
      </div>
    </div>';

	//!! create image and summary container
	if ($recipe->recipe_image != null || $recipe->summary != null) {
		$output .= '<div class="img-desc-wrap">';
		if ($recipe->recipe_image != null) {
			$style_tag = '';
			$class_tag = '';
			$image_width = get_option('zlrecipe_image_width');
			if ($image_width != null) {
				$style_tag = 'style="width: ' . $image_width . 'px;"';
			}
			if (strcmp(get_option('zlrecipe_image_hide'), 'Hide') == 0)
				$class_tag .= ' hide-card';
			if (strcmp(get_option('zlrecipe_image_hide_print'), 'Hide') == 0)
				$class_tag .= ' hide-print';
			$output .= '<p class="t-a-c' . $class_tag . '">
			  <img class="photo" itemprop="image" src="' . $recipe->recipe_image . '" title="' . $recipe->recipe_title . '" alt="' . $recipe->recipe_title . '" ' . $style_tag . ' />
			</p>';
		}
		if ($recipe->summary != null) {
			$output .= '<div id="zlrecipe-summary" itemprop="description">';
			$output .= zrdn_break( '<p class="summary italic">', zrdn_richify_item($recipe->summary, 'summary'), '</p>' );
			$output .= '</div>';
		}
		$output .= '</div>';
	}

	$ingredient_type= '';
	$ingredient_tag = '';
	$ingredient_list_type = get_option('zlrecipe_ingredient_list_type');
	$ingredientClassArray = array("ingredient");
	if (strcmp($ingredient_list_type, 'ul') == 0 || strcmp($ingredient_list_type, 'ol') == 0) {
		$ingredient_type = $ingredient_list_type;
		$ingredient_tag = 'li';
	}
	else if (strcmp($ingredient_list_type, 'l') == 0) {
		$ingredient_type = 'ul';
		$ingredient_tag = 'li';
		array_push($ingredientClassArray, "no-bullet");
	}
	else if (strcmp($ingredient_list_type, 'p') == 0 || strcmp($ingredient_list_type, 'div') == 0) {
		$ingredient_type = 'span';
		$ingredient_tag = $ingredient_list_type;
	}

	if (strcmp(get_option('zlrecipe_ingredient_label_hide'), 'Hide') != 0) {
		$output .= '<p id="zlrecipe-ingredients" class="h-4 strong">' . get_option('zlrecipe_ingredient_label') . '</p>';
	}

	$output .= '<' . $ingredient_type . ' id="zlrecipe-ingredients-list">';
	$i = 0;
	$ingredients = explode("\n", $recipe->ingredients);
	foreach ($ingredients as $ingredient) {
		$ingredientClassString = implode(' ', $ingredientClassArray);
		$output .= zrdn_format_item($ingredient, $ingredient_tag, $ingredientClassString, 'ingredients', 'zlrecipe-ingredient-', $i);
		$i++;
	}

	$output .= '</' . $ingredient_type . '>';

	// add the instructions
	if ($recipe->instructions != null) {

		$instruction_type= '';
		$instruction_tag = '';
		$instructionClassArray = array('instruction');
		$instruction_list_type_option = get_option('zlrecipe_instruction_list_type');
		if (strcmp($instruction_list_type_option, 'ul') == 0 || strcmp($instruction_list_type_option, 'ol') == 0) {
			$instruction_type = $instruction_list_type_option;
			$instruction_tag = 'li';
		}
		else if (strcmp($instruction_list_type_option, 'l') == 0) {
			$instruction_type = 'ul';
			$instruction_tag = 'li';
			array_push($instructionClassArray, 'no-bullet');
		}
		else if (strcmp($instruction_list_type_option, 'p') == 0 || strcmp($instruction_list_type_option, 'div') == 0) {
			$instruction_type = 'span';
			$instruction_tag = $instruction_list_type_option;
		}

		$instructions = explode("\n", $recipe->instructions);
		if (strcmp(get_option('zlrecipe_instruction_label_hide'), 'Hide') != 0) {
			$output .= '<p id="zlrecipe-instructions" class="h-4 strong">' . get_option('zlrecipe_instruction_label') . '</p>';
		}
		$output .= '<' . $instruction_type . ' id="zlrecipe-instructions-list" class="instructions">';
		$j = 0;
		foreach ($instructions as $instruction) {
			if (strlen($instruction) > 1) {
				$instructionClassString = implode(' ', $instructionClassArray);
				$output .= zrdn_format_item($instruction, $instruction_tag, $instructionClassString, 'recipeInstructions', 'zlrecipe-instruction-', $j);
				$j++;
			}
		}
		$output .= '</' . $instruction_type . '>';
	}

	//!! add notes section
	if ($recipe->notes != null) {
		if (strcmp(get_option('zlrecipe_notes_label_hide'), 'Hide') != 0) {
			$output .= '<p id="zlrecipe-notes" class="h-4 strong">' . get_option('zlrecipe_notes_label') . '</p>';
		}

		$output .= '<div id="zlrecipe-notes-list">';
		$output .= zrdn_break( '<p class="notes">', zrdn_richify_item($recipe->notes, 'notes'), '</p>' );
		$output .= '</div>';

	}

	// Zip Recipes attribution and version
	if (strcmp(get_option('zrdn_attribution_hide'), 'Hide') != 0)
		$output .= '<div class="zl-linkback">Schema/Recipe SEO Data Markup by <a title="Zip Recipes Plugin" href="http://www.ziprecipes.net" target="_blank">Zip Recipes Plugin</a></div>';
	$output .= '<div class="ziplist-recipe-plugin" style="display: none;">' . ZRDN_VERSION_NUM . '</div>';

	// Add permalink for printed output before closing the innerdiv
	if (strcmp(get_option('zlrecipe_printed_permalink_hide'), 'Hide') != 0) {
		$output .= '<a id="zl-printed-permalink" href="' . $permalink . '"title="Permalink to Recipe">' . $permalink . '</a>';
	}

	$output .= '</div>';

	// Add copyright statement for printed output (outside the dotted print line)
	$printed_copyright_statement = get_option('zlrecipe_printed_copyright_statement');
	if (strlen($printed_copyright_statement) > 0) {
		$output .= '<div id="zl-printed-copyright-statement" itemprop="copyrightHolder">' . $printed_copyright_statement . '</div>';
	}

	$output .= '</div></div>';


	return $output;
}

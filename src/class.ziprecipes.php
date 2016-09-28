<?php

namespace ZRDN;

require_once(ZRDN_PLUGIN_DIRECTORY . '_inc/class.ziprecipes.util.php');
require_once(ZRDN_PLUGIN_DIRECTORY . '_inc/class.ziprecipes.shortcodes.php');

class ZipRecipes {

	const TABLE_NAME = "amd_zlrecipe_recipes";
	const PLUGIN_OPTION_NAME = "zrdn__plugins";

	const registration_url = "https://api.ziprecipes.net/installation/register/";

	/**
	 * Init function.
	 */
	public static function init()
	{
		Util::log("Core init");

		// Instantiate plugin classes
		$parentPath =  dirname(__FILE__);
		$pluginsPath = "$parentPath/plugins";
		$pluginsDirHandle = opendir($pluginsPath);
		Util::log("Searching for plugins in:" . $pluginsPath);
		if ($pluginsDirHandle)
		{
			$originalDir = getcwd();
			chdir($pluginsPath);

			// loop through plugin dirs and require them
			while (false !== ($fileOrFolder = readdir($pluginsDirHandle)))
			{
				$notDir = ! is_dir($fileOrFolder);
                $invalidDir = $fileOrFolder === "." || $fileOrFolder === ".." || $fileOrFolder === '_internal';
				// we don't care about files inside `plugins` dir
				if ($notDir || $invalidDir)
				{
					continue;
				}

				// plugins classes will be in plugins/RecipeIndex/RecipeIndex.php
				$pluginName = $fileOrFolder;
				$pluginPath = "$fileOrFolder/$pluginName.php";
				Util::log("Attempting to load plugin:" . $pluginsPath);
				require_once($pluginPath);

				// instantiate class
				$namespace = __NAMESPACE__;
				$fullPluginName = "$namespace\\$pluginName"; // double \\ is needed because \ is an escape char
				$pluginInstance = new $fullPluginName;

				// add plugin to options if it's not already there
				// zrdn__plugins stores whether plugin is enabled or not:
				//	array("VisitorRating" => array("active" => false, "description" => "Stuff"),
				//				"RecipeIndex" => array("active" => true, "description" => "Stuff"))
				$pluginOptions = get_option(self::PLUGIN_OPTION_NAME, array());
				if (! array_key_exists($fullPluginName, $pluginOptions)) {
					$pluginOptions[$fullPluginName] = array("active" => true, "description" => $pluginInstance::DESCRIPTION);
				}
				update_option(self::PLUGIN_OPTION_NAME, $pluginOptions);
			}

			chdir($originalDir);
		}

		closedir($pluginsDirHandle);

		// Init shortcode so shortcodes can be used by any plugins
		$shortcodes = new __shortcode();

		// We need to call `zrdn__init_hooks` action before `init_hooks()` because some actions/filters registered
		//	in `init_hooks()` get called before plugins have a chance to register their hooks with `zrdn__init_hooks`
		do_action("zrdn__init_hooks"); // plugins can add an action to listen for this event and register their hooks

        self::init_hooks();
	}

	/**
	 * Function to hook to specific WP actions and filters.
	 */
	private static function init_hooks()
	{
		Util::log("I'm in init_hooks");

		add_action('admin_head', __NAMESPACE__ . '\ZipRecipes::zrdn_js_vars');
		add_action('admin_init', __NAMESPACE__ . '\ZipRecipes::zrdn_add_recipe_button');

		// `the_post` has no action/filter added on purpose. It doesn't work as well as `the_content`.
		add_filter('the_content', __NAMESPACE__ . '\ZipRecipes::zrdn_convert_to_full_recipe', 10);

		add_action('admin_menu', __NAMESPACE__ . '\ZipRecipes::zrdn_menu_pages');

		// Hook is called when recipe editor popup pops up in admin
		add_action('media_upload_z_recipe', __NAMESPACE__ . '\ZipRecipes::zrdn_load_admin_media');

		add_option("amd_zlrecipe_db_version"); // used to store DB version - leaving as is name as legacy
		add_option('zrdn_attribution_hide', '');
		add_option('zlrecipe_printed_permalink_hide', '');
		add_option('zlrecipe_printed_copyright_statement', '');
		add_option('zlrecipe_stylesheet', 'zlrecipe-std');
		add_option('recipe_title_hide', '');
		add_option('zlrecipe_image_hide', '');
		add_option('zlrecipe_image_hide_print', 'Hide');
		add_option('zlrecipe_print_link_hide', '');
		add_option('zlrecipe_ingredient_label_hide', '');
		add_option('zlrecipe_ingredient_list_type', 'l');
		add_option('zlrecipe_instruction_label_hide', '');
		add_option('zlrecipe_instruction_list_type', 'ol');
		add_option('zlrecipe_notes_label_hide', '');
		add_option('zlrecipe_prep_time_label_hide', '');
		add_option('zlrecipe_cook_time_label_hide', '');
		add_option('zlrecipe_total_time_label_hide', '');
		add_option('zlrecipe_yield_label_hide', '');
		add_option('zlrecipe_serving_size_label_hide', '');
		add_option('zlrecipe_calories_label_hide', '');
		add_option('zlrecipe_fat_label_hide', '');
		add_option('zlrecipe_carbs_label_hide', '');
		add_option('zlrecipe_protein_label_hide', '');
		add_option('zlrecipe_fiber_label_hide', '');
		add_option('zlrecipe_sugar_label_hide', '');
		add_option('zlrecipe_saturated_fat_label_hide', '');
		add_option('zlrecipe_sodium_label_hide', '');

		add_option('zlrecipe_image_width', '');
		add_option('zlrecipe_outer_border_style', '');
		add_option('zlrecipe_custom_print_image', '');

		add_filter('wp_head', __NAMESPACE__ . '\ZipRecipes::zrdn_process_head');

		// Deleting option that was added for WooCommerce conflict.
		//      This can be removed a few releases after 4.1.0.18
		delete_option('zrdn_woocommerce_active');


        add_action( 'admin_init', __NAMESPACE__. '\ZipRecipes::preload_check_registered');
		add_action('admin_footer', __NAMESPACE__ . '\ZipRecipes::zrdn_plugin_footer');

		self::zrdn_recipe_install();
	}

	/**
	 * This is used to get post title in recipe insertion iframe
	 */
	public static function zrdn_js_vars() {

		if (is_admin()) {
			?>
			<script type="text/javascript">
				var post_id = '<?php global $post; if(isset($post)) { echo $post->ID; } ?>';
			</script>
		<?php
		}
	}

	public static function zrdn_add_recipe_button() {
		// check user permissions
		if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') ) {
			return;
		}

		// check if WYSIWYG is enabled
		if ( get_user_option('rich_editing') == 'true') {
			add_filter('mce_external_plugins', __NAMESPACE__ . '\ZipRecipes::zrdn_tinymce_plugin');
			add_filter('mce_buttons', __NAMESPACE__ . '\ZipRecipes::zrdn_register_tinymce_button');
		}
	}

	/**
	 * Replace zip recipes shortcodes with actual, full, formatted recipe(s).
	 *
	 * @param $post_text String Text of the post which to replace shortcodes in
	 *
	 * @return String updated $post_text with formatted recipe(s)
	 */
	public static function zrdn_convert_to_full_recipe($post_text) {
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
				$recipe = self::zrdn_select_recipe_db($recipe_id);
				$formatted_recipe = self::zrdn_format_recipe($recipe);
				$output = str_replace('<img id="amd-zlrecipe-recipe-' . $recipe_id . '" class="amd-zlrecipe-recipe" src="' . plugins_url() . '/' . dirname(plugin_basename(__FILE__)) . '/images/zrecipe-placeholder.png?ver=1.0" alt="" />', $formatted_recipe, $output);
			}
		}

		if (strpos($post_text, $needle) !== false) {
			preg_match_all($preg_needle, $post_text, $matches);
			foreach ($matches[0] as $match) {
				$recipe_id = str_replace('[amd-zlrecipe-recipe:', '', $match);
				$recipe_id = str_replace(']', '', $recipe_id);
				$recipe = self::zrdn_select_recipe_db($recipe_id);
				$formatted_recipe = self::zrdn_format_recipe($recipe);
				$output = str_replace('[amd-zlrecipe-recipe:' . $recipe_id . ']', $formatted_recipe, $output);
			}
		}

		return $output;
	}

	// Formats the recipe for output
	public static function zrdn_format_recipe($recipe) {
		$nutritional_info = false;
		if ($recipe->serving_size != null || $recipe->calories != null || $recipe->fat != null || $recipe->carbs != null
		    || $recipe->protein != null || $recipe->fiber != null || $recipe->sugar != null || $recipe->saturated_fat != null
		    || $recipe->sodium != null)
		{
			$nutritional_info = true;
		}

		$ingredients = array();
		if ($recipe->ingredients != null) {
			$raw_ingredients = explode("\n", $recipe->ingredients);
			foreach ($raw_ingredients as $raw_ingredient) {
				array_push($ingredients, self::zrdn_format_item($raw_ingredient));
			}
		}

		// add the instructions
		$instructions = array();
		if ($recipe->instructions != null) {
			$raw_instructions = explode("\n", $recipe->instructions);
			foreach ($raw_instructions as $raw_instruction) {
				// not sure why this check is here and not in ingredients. Maybe ingredients can't be empty on client side?!
				if (strlen($raw_instruction) > 1) {
					array_push($instructions, self::zrdn_format_item($raw_instruction));
				}
			}
		}

		do_action('zrdn__usage_stats');

		$viewParams = array(
				'ZRDN_PLUGIN_URL' => ZRDN_PLUGIN_URL,
				'permalink' => get_permalink(),
				'border_style' => get_option('zlrecipe_outer_border_style'),
				'recipe_id' => $recipe->recipe_id,
				'custom_print_image' => get_option('zlrecipe_custom_print_image'),
				'print_hide' => get_option('zlrecipe_print_link_hide'),
				'title_hide' => get_option('recipe_title_hide'),
				'recipe_title' => $recipe->recipe_title,
				'ajax_url' => admin_url('admin-ajax.php'),
				'recipe_rating' => apply_filters('zrdn__ratings', '', $recipe->recipe_id),
				'prep_time' => self::zrdn_format_duration($recipe->prep_time),
				'prep_time_raw' => $recipe->prep_time,
				'prep_time_label_hide' => get_option('zlrecipe_prep_time_label_hide'),
				'cook_time' => self::zrdn_format_duration($recipe->cook_time),
				'cook_time_raw' => $recipe->cook_time,
				'cook_time_label_hide' => get_option('zlrecipe_cook_time_label_hide'),
				'total_time' => self::zrdn_format_duration($recipe->total_time),
				'total_time_raw' => $recipe->total_time,
				'total_time_label_hide' => get_option('zlrecipe_total_time_label_hide'),
				'yield' => $recipe->yield,
				'yield_label_hide' => get_option('zlrecipe_yield_label_hide'),
				'nutritional_info' => $nutritional_info,
				'serving_size' => $recipe->serving_size,
				'serving_size_label_hide' => get_option('zlrecipe_serving_size_label_hide'),
				'calories' => $recipe->calories,
				'calories_label_hide' => get_option('zlrecipe_calories_label_hide'),
				'fat' => $recipe->fat,
				'fat_label_hide' => get_option('zlrecipe_fat_label_hide'),
				'saturated_fat' => $recipe->saturated_fat,
				'saturated_fat_label_hide' => get_option('zlrecipe_saturated_fat_label_hide'),
				'carbs' => $recipe->carbs,
				'carbs_label_hide' => get_option('zlrecipe_carbs_label_hide'),
				'protein' => $recipe->protein,
				'protein_label_hide' => get_option('zlrecipe_protein_label_hide'),
				'fiber' => $recipe->fiber,
				'fiber_label_hide' => get_option('zlrecipe_fiber_label_hide'),
				'sugar' => $recipe->sugar,
				'sugar_label_hide' => get_option('zlrecipe_sugar_label_hide'),
				'sodium' => $recipe->sodium,
				'sodium_label_hide' => get_option('zlrecipe_sodium_label_hide'),
				'recipe_image' => $recipe->recipe_image,
				'summary' => $recipe->summary,
				'summary_rich' => self::zrdn_break('<p class="summary italic">', self::zrdn_richify_item($recipe->summary, 'summary'), '</p>' ),
				'image' => $recipe->recipe_image,
				'image_width' => get_option('zlrecipe_image_width'),
				'image_hide' => get_option('zlrecipe_image_hide'),
				'image_hide_print' => get_option('zlrecipe_image_hide_print'),
				'ingredient_label_hide' => get_option('zlrecipe_ingredient_label_hide'),
				'ingredient_list_type' => get_option('zlrecipe_ingredient_list_type'),
				'ingredients' => $ingredients,
				'instruction_label_hide' => get_option('zlrecipe_instruction_label_hide'),
				'instruction_list_type' => get_option('zlrecipe_instruction_list_type'),
				'instructions' => $instructions,
				'notes' => $recipe->notes,
				'formatted_notes' => self::zrdn_break('<p class="notes">', self::zrdn_richify_item($recipe->notes, 'notes'), '</p>'),
				'notes_label_hide' => get_option('zlrecipe_notes_label_hide'),
				'attribution_hide' => get_option('zrdn_attribution_hide'),
				'version' => ZRDN_VERSION_NUM,
				'print_permalink_hide' => get_option('zlrecipe_printed_permalink_hide'),
				'copyright' => get_option('zlrecipe_printed_copyright_statement'),
				'author_section' => apply_filters('zrdn__authors_render_author_for_recipe', $recipe)
		);
        $custom_template = apply_filters('zrdn__custom_templates_get_formatted_recipe', false, $viewParams);
        return $custom_template ?: Util::view('recipe', $viewParams);
	}

	/**
	 * Processes markup for attributes like labels, images and links.
	 * Changed behaviour in 4.5.2.7:
	 *  - links (like [margarine|http://margarine.com] no longer include an
	 *    'ingredient', 'ingredient-link', 'no-bullet', 'no-bullet-link' classes or a combination thereof
	 *  - images (like %http://example.com/logo.png no longer include an
	 *    'ingredient', 'ingredient-image', 'no-bullet', 'no-bullet-image' classes or a combination thereof
	 *  - ids are no longer added
	 * Syntax:
	 * !Label
	 * %image
	 * [link|http://example.com/link]
	 * @param string $item
	 *
	 * @return array {
	 *  @type string $type
	 *  @type string $content
	 * }
	 */
	public static function zrdn_format_item($item) {
		$formatted_item = $item;
		if (preg_match("/^%(\S*)/", $item, $matches)) {	// IMAGE Updated to only pull non-whitespace after some blogs were adding additional returns to the output
			// type: image
			// content: $matches[1]
			return array('type' => 'image', 'content' => $matches[1]); // Images don't also have labels or links so return the line immediately.
		}

		$retArray = array();
		if (preg_match("/^!(.*)/", $item, $matches)) {	// LABEL
			// type: subtitle
			// content: formatted $item
			$formatted_item = $matches[1];
			$retArray['type'] = 'subtitle';
		} else {
			// type: default
			// content: formatted $item
			$retArray['type'] = 'default';
		}

		$retArray['content'] = self::zrdn_richify_item($formatted_item);

		return $retArray;
	}

	// Adds module to left sidebar in wp-admin for ZLRecipe
	public static function zrdn_menu_pages() {
		// Add the top-level admin menu
		$page_title = 'Zip Recipes Settings';
		$menu_title = 'Zip Recipes';
		$capability = 'manage_options';
		$menu_slug = 'zrdn-settings';
		$function = __NAMESPACE__ . '\ZipRecipes::zrdn_settings';

        $is_registered = get_option('zrdn_registered');
        $reg_menu_slug = 'zrdn-register';
        $reg_function = __NAMESPACE__ . '\ZipRecipes::zrdn_registration';

        $parent_slug = $is_registered?$menu_slug:$reg_menu_slug;

		add_menu_page(
            $page_title,
            $menu_title,
            $capability,
            $parent_slug,
            $is_registered?$function:$reg_function,
            'dashicons-carrot'
        );

        if (!$is_registered) {
            // registration
            $page_reg_title = 'Zip Recipes Registration';

            $register_title = "Register";
            add_submenu_page(
                $parent_slug, // parent_slug
                $page_reg_title, // page_title
                $register_title, // menu_title
                $capability, // capability
                $reg_menu_slug, // menu_slug
                $reg_function // callback function
            );
        }

		$settings_title = "Settings";
		add_submenu_page(
            $parent_slug, // parent_slug
			$page_title, // page_title
			$settings_title, // menu_title
			$capability, // capability
			$menu_slug, // menu_slug
			$function // callback function
		);

        do_action("zrdn__menu_page", array(
            "capability" => $capability,
            "parent_slug" => $parent_slug,
        ));
	}

    /**
     * This is callback for admin init hook
     * we use it only in case when user registered in popup
     * if we do not do this iframe will be closed
     * instead of redirecting back to recipe
     */

    public static function preload_check_registered() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action']) && $_POST['action'] === "zrdn-register") {
                // if first, last name and email are provided, we assume that user is registering
                $registered = $_POST['first_name'] && $_POST['last_name'] && $_POST['email'];
                if ($registered) {
                    update_option('zrdn_registered', true);
                    if (isset($_POST['return-url'])) {
                        header('Location: '.$_POST['return-url'] );
                        exit;
                    }
                }
            }
        }
    }

    /**
     * Static function to show registration form
     */
    public static function zrdn_registration() {
        global $wp_version;

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        $settings_page_url = admin_url( 'admin.php?page=' . 'zrdn-settings' );

        $settingsParams = array(
            'settings_url' => $settings_page_url,
            'registration_url' => self::registration_url,
            'wp_version' => $wp_version,
            'installed_plugins' => Util::zrdn_get_installed_plugins(),
            'home_url' => home_url(),
            'return_to_url' => $settings_page_url,
        );

        Util::print_view('register', $settingsParams);
    }

	public static function zrdn_tinymce_plugin($plugin_array) {
		$plugin_array['zrdn_plugin'] = plugins_url( 'scripts/zlrecipe_editor_plugin.js?sver=' . ZRDN_VERSION_NUM, __FILE__ );
		return $plugin_array;
	}

	public static function zrdn_register_tinymce_button($buttons) {
		array_push($buttons, "zrdn_buttons");
		return $buttons;
	}

	// Adds 'Settings' page to the ZipRecipe module
	public static function zrdn_settings() {
		global $wp_version;

		if (!current_user_can('manage_options')) {
			wp_die('You do not have sufficient permissions to access this page.');
		}

		$zrdn_icon = ZRDN_PLUGIN_URL . "images/zrecipes-icon.png";

		$registered = get_option('zrdn_registered');
		$registered_clear = get_option('zrdn_registered');

        $register_url = admin_url( 'admin.php?page=' . 'zrdn-register' );
		$zrecipe_attribution_hide = get_option('zrdn_attribution_hide');
		$printed_permalink_hide = get_option('zlrecipe_printed_permalink_hide');
		$printed_copyright_statement = get_option('zlrecipe_printed_copyright_statement');
		$stylesheet = get_option('zlrecipe_stylesheet');
		$recipe_title_hide = get_option('recipe_title_hide');
		$image_hide = get_option('zlrecipe_image_hide');
		$image_hide_print = get_option('zlrecipe_image_hide_print');
		$print_link_hide = get_option('zlrecipe_print_link_hide');
		$ingredient_label_hide = get_option('zlrecipe_ingredient_label_hide');
		$ingredient_list_type = get_option('zlrecipe_ingredient_list_type');
		$instruction_label_hide = get_option('zlrecipe_instruction_label_hide');
		$instruction_list_type = get_option('zlrecipe_instruction_list_type');
		$image_width = get_option('zlrecipe_image_width');
		$outer_border_style = get_option('zlrecipe_outer_border_style');
		$custom_print_image = get_option('zlrecipe_custom_print_image');

		// load other option values in to variables. These variables are used to load saved values through variable variables
		$notes_label_hide = get_option('zlrecipe_notes_label_hide');
		$prep_time_label_hide = get_option('zlrecipe_prep_time_label_hide');
		$cook_time_label_hide = get_option('zlrecipe_cook_time_label_hide');
		$total_time_label_hide = get_option('zlrecipe_total_time_label_hide');
		$yield_label_hide = get_option('zlrecipe_yield_label_hide');
		$serving_size_label_hide = get_option('zlrecipe_serving_size_label_hide');
		$calories_label_hide = get_option('zlrecipe_calories_label_hide');
		$fat_label_hide = get_option('zlrecipe_fat_label_hide');
		$carbs_label_hide = get_option('zlrecipe_carbs_label_hide', '');
		$protein_label_hide = get_option('zlrecipe_protein_label_hide', '');
		$fiber_label_hide = get_option('zlrecipe_fiber_label_hide', '');
		$sugar_label_hide = get_option('zlrecipe_sugar_label_hide', '');
		$saturated_fat_label_hide = get_option('zlrecipe_saturated_fat_label_hide', '');
		$sodium_label_hide = get_option('zlrecipe_sodium_label_hide', '');

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			foreach ($_POST as $key=>$val) {
				$_POST[$key] = stripslashes($val);
			}

			if ($_POST['action'] === "zrdn-register")
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

				$zrecipe_attribution_hide = Util::get_array_value('zrecipe-attribution-hide', $_POST);
				$printed_permalink_hide = Util::get_array_value('printed-permalink-hide', $_POST);
				$printed_copyright_statement = Util::get_array_value('printed-copyright-statement', $_POST);
				$stylesheet = Util::get_array_value('stylesheet', $_POST);
				$recipe_title_hide = Util::get_array_value('recipe-title-hide', $_POST);
				$image_hide = Util::get_array_value('image-hide', $_POST);
				$image_hide_print = Util::get_array_value('image-hide-print', $_POST);
				$print_link_hide = Util::get_array_value('print-link-hide', $_POST);
				$ingredient_label_hide = self::zrdn_strip_chars(Util::get_array_value('ingredient-label-hide', $_POST));
				$ingredient_list_type = Util::get_array_value('ingredient-list-type', $_POST);
				$instruction_label_hide = Util::get_array_value('instruction-label-hide', $_POST);
				$instruction_list_type = self::zrdn_strip_chars(Util::get_array_value('instruction-list-type', $_POST));
				$notes_label_hide = Util::get_array_value('notes-label-hide', $_POST);
				$prep_time_label_hide = Util::get_array_value('prep-time-label-hide', $_POST);
				$cook_time_label_hide = Util::get_array_value('cook-time-label-hide', $_POST);
				$total_time_label_hide = Util::get_array_value('total-time-label-hide', $_POST);
				$yield_label_hide = Util::get_array_value('yield-label-hide', $_POST);
				$serving_size_label_hide = Util::get_array_value('serving-size-label-hide', $_POST);
				$calories_label_hide = Util::get_array_value('calories-label-hide', $_POST);
				$fat_label_hide = Util::get_array_value('fat-label-hide', $_POST);
				$carbs_label_hide = Util::get_array_value('carbs-label-hide', $_POST);
				$protein_label_hide = Util::get_array_value('protein-label-hide', $_POST);
				$fiber_label_hide = Util::get_array_value('fiber-label-hide', $_POST);
				$sugar_label_hide = Util::get_array_value('sugar-label-hide', $_POST);
				$saturated_fat_label_hide = Util::get_array_value('saturated-fat-label-hide', $_POST);
				$sodium_label_hide = Util::get_array_value('sodium-label-hide', $_POST);
				$image_width = Util::get_array_value('image-width', $_POST);
				$outer_border_style = Util::get_array_value('outer-border-style', $_POST);
				$custom_print_image = Util::get_array_value('custom-print-image', $_POST);


				update_option('zrdn_attribution_hide', $zrecipe_attribution_hide);
				update_option('zlrecipe_printed_permalink_hide', $printed_permalink_hide );
				update_option('zlrecipe_printed_copyright_statement', $printed_copyright_statement);
				update_option('zlrecipe_stylesheet', $stylesheet);
				update_option('recipe_title_hide', $recipe_title_hide);
				update_option('zlrecipe_image_hide', $image_hide);
				update_option('zlrecipe_image_hide_print', $image_hide_print);
				update_option('zlrecipe_print_link_hide', $print_link_hide);
				update_option('zlrecipe_ingredient_label_hide', $ingredient_label_hide);
				update_option('zlrecipe_ingredient_list_type', $ingredient_list_type);
				update_option('zlrecipe_instruction_label_hide', $instruction_label_hide);
				update_option('zlrecipe_instruction_list_type', $instruction_list_type);
				update_option('zlrecipe_notes_label_hide', $notes_label_hide);
				update_option('zlrecipe_prep_time_label_hide', $prep_time_label_hide);
				update_option('zlrecipe_cook_time_label_hide', $cook_time_label_hide);
				update_option('zlrecipe_total_time_label_hide', $total_time_label_hide);
				update_option('zlrecipe_yield_label_hide', $yield_label_hide);
				update_option('zlrecipe_serving_size_label_hide', $serving_size_label_hide);
				update_option('zlrecipe_calories_label_hide', $calories_label_hide);
				update_option('zlrecipe_fat_label_hide', $fat_label_hide);
				update_option('zlrecipe_carbs_label_hide', $carbs_label_hide);
				update_option('zlrecipe_protein_label_hide', $protein_label_hide);
				update_option('zlrecipe_fiber_label_hide', $fiber_label_hide);
				update_option('zlrecipe_sugar_label_hide', $sugar_label_hide);
				update_option('zlrecipe_saturated_fat_label_hide', $saturated_fat_label_hide);
				update_option('zlrecipe_sodium_label_hide', $sodium_label_hide);
				update_option('zlrecipe_image_width', $image_width);
				update_option('zlrecipe_outer_border_style', $outer_border_style);
				update_option('zlrecipe_custom_print_image', $custom_print_image);
                do_action('zrdn__custom_templates_save',$_POST);
			}
		}

		$printed_copyright_statement = esc_attr($printed_copyright_statement);
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
		$other_options_array = array('Prep Time', 'Cook Time', 'Total Time', 'Yield', 'Serving Size', 'Calories',
			'Fat', 'Saturated Fat', 'Carbs', 'Protein', 'Fiber', 'Sugar', 'Sodium', 'Notes');


		foreach ($other_options_array as $option) {
			$name = strtolower(str_replace(' ', '-', $option));
			$value_hide = strtolower(str_replace(' ', '_', $option)) . '_label_hide';
			$value_hide_attr = ${$value_hide} == "Hide" ? 'checked="checked"' : '';
			$other_options .= '<tr valign="top">
            <td>
            	<label>
            		<input type="checkbox" name="' . $name . '-label-hide" value="Hide" ' . $value_hide_attr . ' /> Don\'t show ' . $option . ' label
            	</label>
            </td>
        </tr>';
		}

		$settingsParams = array('zrdn_icon' => $zrdn_icon,
				'registered' => $registered,
				'custom_print_image' => $custom_print_image,
				'zrecipe_attribution_hide' => $zrecipe_attribution_hide,
				'printed_permalink_hide' => $printed_permalink_hide,
				'printed_copyright_statement' => $printed_copyright_statement,
				'stylesheet' => $stylesheet,
				'recipe_title_hide' => $recipe_title_hide,
				'print_link_hide' => $print_link_hide,
				'image_width' => $image_width,
				'image_hide' => $image_hide,
				'image_hide_print' => $image_hide_print,
				'obs' => $obs,
				'ingredient_label_hide' => $ingredient_label_hide,
				'ing_l' => $ing_l,
				'ing_ol' => $ing_ol,
				'ing_ul' => $ing_ul,
				'ing_p' => $ing_p,
				'ing_div' => $ing_div,
				'instruction_label_hide' => $instruction_label_hide,
				'ins_l' => $ins_l,
				'ins_ol' => $ins_ol,
				'ins_ul' => $ins_ul,
				'ins_p' => $ins_p,
				'ins_div' => $ins_div,
				'other_options' => $other_options,
				'registration_url' => self::registration_url,
				'wp_version' => $wp_version,
				'installed_plugins' => Util::zrdn_get_installed_plugins(),
                'extensions_settings' => apply_filters('zrdn__extention_settings_section', ''),
				'home_url' => home_url(),
				'author_section' => apply_filters('zrdn__authors_get_set_settings', $_POST),
                'register_url' => $register_url,
                'registered_clear' => $registered_clear
		);

		Util::print_view('settings', $settingsParams);
	}

	// Replaces the [a|b] pattern with text a that links to b
	// Replaces _words_ with an italic span and *words* with a bold span
	public static function zrdn_richify_item($item) {
		$output = preg_replace('/\[([^\]\|\[]*)\|([^\]\|\[]*)\]/', '<a href="\\2" target="_blank">\\1</a>', $item);
		$output = preg_replace('/(^|\s)\*([^\s\*][^\*]*[^\s\*]|[^\s\*])\*(\W|$)/', '\\1<span class="bold">\\2</span>\\3', $output);
		return preg_replace('/(^|\s)_([^\s_][^_]*[^\s_]|[^\s_])_(\W|$)/', '\\1<span class="italic">\\2</span>\\3', $output);
	}

	public static function zrdn_strip_chars( $val )
	{
		return str_replace( '\\', '', $val );
	}

	/**
	 * Run the install method when plugin is updated.
	 * This hook is called when any plugins are updated, so we need to ensure that Zip Recipes is updated
	 *   before the install method is called.
	 * @param $upgrader {Plugin_Upgrader}
	 * @param $data {array} Contains "type", "action", "plugins".
	 */
	public static function plugin_updated($upgrader, $data)
	{
		Util::log("In plugin_updated");

		// if this plugin is being updated, call zrdn_recipe_install method
		if (is_array($data) && $data['action'] === 'update' && $data['type'] === 'plugin' &&
		    is_array($data['plugins']) &&
		    in_array(ZRDN_PLUGIN_BASENAME, $data['plugins']))
		{
			self::init();
		}
	}

	/**
	 * Creates ZLRecipe tables in the db if they don't exist already.
	 * Don't do any data initialization in this routine as it is called on both install as well as
	 * every plugin load as an upgrade check.
	 *
	 * Updates the table if needed
	 *
	 * Plugin Ver       DB Ver
	 * 1.0 - 1.3        3.0
	 * 1.4x - 2.6       3.1  Adds Notes column to recipes table
	 * 4.1.0.10 -       3.2  Adds primary key, collation
	 * 4.2.0.20 -       3.3  Added carbs, protein, fiber, sugar, saturated fat, and sodium
	 */
	public static function zrdn_recipe_install() {
		global $wpdb;

		Util::log("In zrdn_recipe_install");

		$recipes_table = $wpdb->prefix . self::TABLE_NAME;

		$charset_collate = Util::get_charset_collate();

		// Each column for create table statement is an array element
		$columns = array(
			'recipe_id bigint(20) unsigned NOT NULL AUTO_INCREMENT  PRIMARY KEY',
			'post_id bigint(20) unsigned NOT NULL',
			'recipe_title text',
			'recipe_image text',
			'summary text',
			'prep_time text',
			'cook_time text',
			'total_time text',
			'yield text',
			'serving_size varchar(50)',
			'calories varchar(50)',
			'fat varchar(50)',
			'carbs varchar(50)',
			'protein varchar(50)',
			'fiber varchar(50)',
			'sugar varchar(50)',
			'saturated_fat varchar(50)',
			'sodium varchar(50)',
			'ingredients text',
			'instructions text',
			'notes text',
			'created_at timestamp DEFAULT NOW()'
		);

		$all_columns = apply_filters('zrdn__db_recipe_columns', $columns);

		// For dbDelta to detect different columns, they have to be split using \n (newline).
		// The comma is part of SQL syntax.
		$columns_string = implode(",\n", $all_columns);
		$sql_command = "CREATE TABLE `$recipes_table` ($columns_string) $charset_collate;";

		/**
		 * dbDelta is smart enough not to make changes if they're not needed so we don't need to have table
		 *  version checks.
		 * Also, dbDelta will not drop columns from a table, it only adds new ones.
		 */
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql_command); // run SQL script

		Util::log("Calling db_setup() action");

		do_action("zrdn__db_setup", self::TABLE_NAME);

		/**
		 * Loading translations
		 */
		$pluginLangDir = plugin_basename( dirname( __FILE__ ) ) . '/languages/';
		$globalLangDir = WP_LANG_DIR; // full path
		if (is_readable($globalLangDir)) {
			load_plugin_textdomain('zip-recipes', false, $globalLangDir);
		}
		load_plugin_textdomain('zip-recipes', false, $pluginLangDir);
	}

	// Content for the popup iframe when creating or editing a recipe
	public static function zrdn_iframe_content($post_info = null, $get_info = null) {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($_POST['action'] === "zrdn-register") {
                // if first, last name and email are provided, we assume that user is registering
                $registered = $_POST['first_name'] && $_POST['last_name'] && $_POST['email'];
                if ($registered) {
                    update_option('zrdn_registered', true);
                    if (isset($_POST['return-url'])) {
                        header('Location: '.$_POST['return-url'] );
                        exit;
                    }
                }
            }
        }

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
		$carbs = 0;
		$protein = 0;
		$fiber = 0;
		$sugar = 0;
		$saturated_fat = 0;
		$sodium = 0;
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
		$recipe = null;


		if ($post_info || $get_info) {

			if( isset($get_info["add-recipe-button"]) || strpos($get_info["recipe_post_id"], '-') !== false ) {
				$iframe_title = "Update Your Recipe";
				$submit = "Update Recipe";
			} else {
				$iframe_title = "Add a Recipe";
				$submit       = "Add Recipe";
			}

			if ( isset($get_info["recipe_post_id"]) &&
			     ! isset($get_info["add-recipe-button"]) &&
			     strpos($get_info["recipe_post_id"], '-') !== false
			) { // EDIT recipe
				$recipe_id = preg_replace('/[0-9]*?\-/i', '', $get_info["recipe_post_id"]);
				$recipe = self::zrdn_select_recipe_db($recipe_id);
				$recipe_title = $recipe->recipe_title;
				$recipe_image = $recipe->recipe_image;
				$summary = $recipe->summary;
				$notes = $recipe->notes;
				$ss = array();
				$prep_time_input = '';
				$cook_time_input = '';
				$total_time_input = '';
				if (class_exists('DateInterval')) {
					try {
						if ($recipe->prep_time) {
							$prep_time = new \DateInterval($recipe->prep_time);
							$prep_time_minutes = $prep_time->i;
							$prep_time_hours = $prep_time->h;
						}
					} catch (Exception $e) {
						if ($recipe->prep_time != null) {
							$prep_time_input = '<input type="text" name="prep_time" value="' . $recipe->prep_time . '"/>';
						}
					}

					try {
						if ($recipe->cook_time) {
							$cook_time = new \DateInterval($recipe->cook_time);
							$cook_time_minutes = $cook_time->i;
							$cook_time_hours = $cook_time->h;
						}
					} catch (Exception $e) {
						if ($recipe->cook_time != null) {
							$cook_time_input = '<input type="text" name="cook_time" value="' . $recipe->cook_time . '"/>';
						}
					}

					try {
						if ($recipe->total_time) {
							$total_time = new \DateInterval($recipe->total_time);
							$total_time_minutes = $total_time->i;
							$total_time_hours = $total_time->h;
						}
					} catch (Exception $e) {
						if ($recipe->total_time != null) {
							$total_time_input = '<input type="text" name="total_time" value="' . $recipe->total_time . '"/>';
						}
					}
				}
				else {
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
				$carbs = $recipe->carbs;
				$protein = $recipe->protein;
				$fiber = $recipe->fiber;
				$sugar = $recipe->sugar;
				$saturated_fat = $recipe->saturated_fat;
				$sodium = $recipe->sodium;
				$ingredients = $recipe->ingredients;
				$instructions = $recipe->instructions;
			}
			else { // SAVE/UPDATE recipe
				foreach ($post_info as $key=>$val) {
					$post_info[$key] = stripslashes($val);
				}

				$recipe_id = isset($post_info["recipe_id"]) ? $post_info["recipe_id"] : '';

				if( ! isset($get_info["add-recipe-button"])) {
					$recipe_title = get_the_title( $get_info["recipe_post_id"] );
				}
				else {
					$recipe_title = $post_info["recipe_title"];
				}
				$recipe_image = isset($post_info["recipe_image"]) ? $post_info["recipe_image"] : '';
				$summary = isset($post_info["summary"]) ? $post_info["summary"] : '';
				$notes = isset($post_info["notes"]) ? $post_info["notes"] : '';
				$prep_time_minutes = isset($post_info["prep_time_minutes"]) ? $post_info["prep_time_minutes"] : '';
				$prep_time_hours = isset($post_info["prep_time_hours"]) ? $post_info["prep_time_hours"] : '';
				$cook_time_minutes = isset($post_info["cook_time_minutes"]) ? $post_info["cook_time_minutes"] : '';
				$cook_time_hours = isset($post_info["cook_time_hours"]) ? $post_info["cook_time_hours"] : '';
				$total_time_minutes = isset($post_info["total_time_minutes"]) ? $post_info["total_time_minutes"] : '';
				$total_time_hours = isset($post_info["total_time_hours"]) ? $post_info["total_time_hours"] : '';
				$yield = isset($post_info["yield"]) ? $post_info["yield"] :'';
				$serving_size = isset($post_info["serving_size"]) ? $post_info["serving_size"] : '';
				$calories = isset($post_info["calories"]) ? $post_info["calories"] : '';
				$fat = isset($post_info["fat"]) ? $post_info["fat"] : '';
				$carbs = isset($post_info['carbs']) ? $post_info['carbs'] : '';
				$protein = isset($post_info['protein']) ? $post_info['protein'] : '';
				$fiber = isset($post_info['fiber']) ? $post_info['fiber'] : '';
				$sugar = isset($post_info['sugar']) ? $post_info['sugar'] : '';
				$saturated_fat = isset($post_info['saturated_fat']) ? $post_info['saturated_fat'] : '';
				$sodium = isset($post_info['sodium']) ? $post_info['sodium'] : '';
				$ingredients = isset($post_info["ingredients"]) ? $post_info["ingredients"] : '';
				$instructions = isset($post_info["instructions"]) ? $post_info["instructions"] : '';
				$author = apply_filters('zrdn__authors_get_author_from_post_data', $post_info);
				if ($author) {
					$post_info['author'] = $author;
				}
				if (isset($recipe_title) && $recipe_title != '' && isset($ingredients) && $ingredients != '') {
					// Save recipe to database
					$recipe_id = self::zrdn_insert_db($post_info);
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
		$carbs = esc_attr($carbs);
		$protein = esc_attr($protein);
		$fiber = esc_attr($fiber);
		$sugar = esc_attr($sugar);
		$saturated_fat = esc_attr($saturated_fat);
		$sodium = esc_attr($sodium);
		$ingredients = esc_textarea($ingredients);
		$instructions = esc_textarea($instructions);
		$summary = esc_textarea($summary);
		$notes = esc_textarea($notes);

		$id = (int) $_REQUEST["recipe_post_id"];

		$registration_required = ! get_option('zrdn_registered');

		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		$settings_page_url = admin_url( 'admin.php?page=' . 'zrdn-settings' );

        /*
         * Here is small trick
         * if user not registered we should redirect him to register form
         * and process if user registered
         */
        $registration_required = !get_option('zrdn_registered');
        if ($registration_required) {
            $cookie_live_period = time() + 60 * 60 * 24 * 7;

            if (isset($_GET['skip_registration']) && !isset($_COOKIE['skip-registration'])) {
                setcookie('skip-registration', 1, $cookie_live_period, '/');
            }

            $skip_registration = isset($_COOKIE['skip-registration']) || isset($_GET['skip_registration']);

            if (!$skip_registration || (isset($_GET['register']) && $_GET['register'] == 1)) {
                global $wp_version;
                $settings_page_url = admin_url('admin.php?page=' . 'zrdn-register');

                $settingsParams = array(
                    'settings_url' => $settings_page_url,
                    'registration_url' => self::registration_url,
                    'wp_version' => $wp_version,
                    'installed_plugins' => Util::zrdn_get_installed_plugins(),
                    'home_url' => home_url(),
                    'return_to_url' => str_replace('&register=1', '', "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"),
                    'plugin_url' => ZRDN_PLUGIN_URL,
                    'iframed_form' => true
                );

                Util::print_view('register', $settingsParams);
                return;
            }
        }

		Util::print_view('create-update-recipe', array(
			'pluginurl' => ZRDN_PLUGIN_URL,
			'recipe_id' => $recipe_id,
			'registration_required' => $registration_required,
			'settings_page_url' => $settings_page_url,
            'recipe_url' => "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
			'post_info' => $post_info,
			'ss' => $ss,
			'iframe_title' => $iframe_title,
			'id' => $id,
			'recipe_title' => $recipe_title,
			'recipe_image' => $recipe_image,
			'ingredients' => $ingredients,
			'instructions' => $instructions,
			'summary' => $summary,
			'prep_time_input' => $prep_time_input,
			'prep_time_hours' => $prep_time_hours,
			'prep_time_minutes' => $prep_time_minutes,
			'cook_time_input' => $cook_time_input,
			'cook_time_hours' => $cook_time_hours,
			'cook_time_minutes' => $cook_time_minutes,
			'total_time_input' => $total_time_input,
			'total_time_hours' => $total_time_hours,
			'total_time_minutes' => $total_time_minutes,
			'yield' => $yield,
			'serving_size' => $serving_size,
			'calories' => $calories,
			'carbs' => $carbs,
			'protein' => $protein,
			'fiber' => $fiber,
			'sugar' => $sugar,
			'sodium' => $sodium,
			'fat' => $fat,
			'saturated_fat' => $saturated_fat,
			'notes' => $notes,
			'submit' => $submit,
			'author_section' => apply_filters('zrdn__authors_recipe_create_update', '', $recipe, $post_info)
		));
	}

	// Inserts the recipe into the database
	/**
	 * @param $post_info
	 *
	 * @return mixed
	 */
	public static function zrdn_insert_db($post_info) {
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

		// Build array to be sent to db query call
		$clean_fields = array(
			'recipe_title', 'recipe_image', 'summary', 'yield',
			'serving_size', 'calories', 'fat', 'carbs',  'protein', 'fiber', 'sugar', 'saturated_fat', 'sodium',
			'ingredients', 'instructions', 'notes', 'author'
			);
		$recipe = array();
		foreach($post_info as $attr => $value) {
			if (in_array($attr, $clean_fields) && isset($post_info[$attr])) {
				$recipe[$attr] = $value;
			}
		}
		// Add fields that needed format change
		$recipe['prep_time'] = $prep_time;
		$recipe['cook_time'] = $cook_time;
		$recipe['total_time'] = $total_time;


		if (self::zrdn_select_recipe_db($recipe_id) == null) {
			$recipe["post_id"] = $post_info["recipe_post_id"];	// set only during record creation
			$wpdb->insert( $wpdb->prefix . self::TABLE_NAME, $recipe );
			$recipe_id = $wpdb->insert_id;
		} else {
			$wpdb->update( $wpdb->prefix . self::TABLE_NAME, $recipe, array( 'recipe_id' => $recipe_id ));
		}

		return $recipe_id;
	}

	// Pulls a recipe from the db
	public static function zrdn_select_recipe_db($recipe_id) {
		global $wpdb;

		$selectStatement = sprintf("SELECT * FROM `%s%s` WHERE recipe_id=%d", $wpdb->prefix, self::TABLE_NAME,$recipe_id);
		$recipe = $wpdb->get_row($selectStatement);

		return $recipe;
	}

	// function to include the javascript for the Add Recipe button
	public static function zrdn_process_head() {
		$css = get_option('zlrecipe_stylesheet');
		Util::print_view('header', array('ZRDN_PLUGIN_URL' => ZRDN_PLUGIN_URL, 'css' => $css));
	}

	public static function zrdn_break( $otag, $text, $ctag) {
		$output = "";
		$split_string = explode( "\r\n\r\n", $text, 10 );
		foreach ( $split_string as $str )
		{
			$output .= $otag . $str . $ctag;
		}
		return $output;
	}

	// Format an ISO8601 duration for human readibility
	public static function zrdn_format_duration($duration) {
		if ($duration == null) {
			return '';
		}

		$date_abbr = array(
			'y' => array('singular' => __('%d year', 'zip-recipes'), 'plural' => __('%d years', 'zip-recipes')),
			'm' => array('singular' => __('%d month', 'zip-recipes'), 'plural' => __('%d months', 'zip-recipes')),
			'd' => array('singular' => __('%d day', 'zip-recipes'), 'plural' => __('%d days', 'zip-recipes')),
			'h' => array('singular' => __('%d hour', 'zip-recipes'), 'plural' => __('%d hours', 'zip-recipes')),
			'i' => array('singular' => __('%d minute', 'zip-recipes'), 'plural' => __('%d minutes', 'zip-recipes')),
			's' => array('singular' => __('%d second', 'zip-recipes'), 'plural' => __('%d seconds', 'zip-recipes'))
		);

		$results_array = array();

		if (class_exists('DateInterval')) {
			try {
				$result_object = new \DateInterval($duration);
				foreach ($date_abbr as $abbr => $name_data) {
					$current_part = '';
					if ($result_object->$abbr > 0) {
						$current_part = sprintf( $name_data['singular'], $result_object->$abbr );
						if ($result_object->$abbr > 1) {
							$current_part = sprintf( $name_data['plural'], $result_object->$abbr );
						}
					}

					if ($current_part) {
						array_push( $results_array, $current_part );
					}
				}
			} catch (Exception $e) {
				$result = $duration;
			}
		} else { // else we have to do the work ourselves so the output is pretty
			$arr = explode('T', $duration);
			$arr[1] = str_replace('M', 'I', $arr[1]); // This mimics the DateInterval property name
			$duration = implode('T', $arr);

			foreach ($date_abbr as $abbr => $name_data) {
				if (preg_match('/(\d+)' . $abbr . '/i', $duration, $val)) {
					$current_part = sprintf($name_data['singular'], $val[1]);
					if ($val[1] > 1) {
						$current_part = sprintf($name_data['plural'], $val[1]);
					}
					array_push($results_array, $current_part);
				}
			}
		}

		return join(", ", $results_array);
	}


	// Inserts the recipe into the post editor
	public static function zrdn_plugin_footer() {
		wp_enqueue_script(
			'zrdn-admin-script',
			plugins_url('/scripts/admin.js', __FILE__),
			array( 'jquery' ), // deps
			false, // ver
			true // in_footer
		);

		Util::print_view('footer', array('url' => site_url(),
				'pluginurl' => ZRDN_PLUGIN_URL));
	}

	public static function zrdn_load_admin_media() {
		wp_enqueue_script('jquery');

		// This will enqueue the Media Uploader script
		wp_enqueue_script('media-upload');

		wp_enqueue_media();

		wp_enqueue_script('zrdn-admin-script');
	}
}

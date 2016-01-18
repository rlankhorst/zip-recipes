<?php

namespace ZRDN;

require_once(ZRDN_PLUGIN_DIRECTORY . '_inc/class.ziprecipes.util.php');
require_once(ZRDN_PLUGIN_DIRECTORY . '_inc/h2o/h2o.php');

class ZipRecipes {

	const TABLE_VERSION = "3.3"; // This must be changed when the DB structure is modified
	const TABLE_NAME = "amd_zlrecipe_recipes";

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
				$invalidDir = $fileOrFolder === "." || $fileOrFolder === "..";
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
				new $fullPluginName;
			}

			chdir($originalDir);
		}

		closedir($pluginsDirHandle);

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

		# HACK: register_activation_hook doesn't get called when plugin is updated, so we use `plugins_loaded` hook.
		add_action('plugins_loaded', __NAMESPACE__ . '\ZipRecipes::zrdn_recipe_install');

		add_action('admin_head', __NAMESPACE__ . '\ZipRecipes::zrdn_js_vars');
		add_action('admin_head', __NAMESPACE__ . '\ZipRecipes::zrdn_add_recipe_button');

		// `the_post` has no action/filter added on purpose. It doesn't work as well as `the_content`.
		// It's important for `get_the_excerpt` to have higher priority than `the_content` when hooked.
		//  (The third argument is $priority in `add_filter` function call. The lower the number, the higher the priority.)
		add_filter('get_the_excerpt', __NAMESPACE__ . '\ZipRecipes::zrdn_convert_to_summary_recipe', 9);
		add_filter('the_content', __NAMESPACE__ . '\ZipRecipes::zrdn_convert_to_full_recipe', 10);

		add_action('admin_menu', __NAMESPACE__ . '\ZipRecipes::zrdn_menu_pages');

		// Hook is called when recipe editor popup pops up in admin
		add_action('media_upload_z_recipe', __NAMESPACE__ . '\ZipRecipes::zrdn_load_admin_media');

		add_option("amd_zlrecipe_db_version"); // used to store DB version - leaving as is name as legacy
		add_option('zrdn_attribution_hide', '');
		add_option('zlrecipe_printed_permalink_hide', '');
		add_option('zlrecipe_printed_copyright_statement', '');
		add_option('zrdn_print_button_label', 'Print');
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
		add_option('zlrecipe_carbs_label', 'Carbs per serving:');
		add_option('zlrecipe_carbs_label_hide', '');
		add_option('zlrecipe_protein_label', 'Protein per serving:');
		add_option('zlrecipe_protein_label_hide', '');
		add_option('zlrecipe_fiber_label', 'Fiber per serving:');
		add_option('zlrecipe_fiber_label_hide', '');
		add_option('zlrecipe_sugar_label', 'Sugar per serving:');
		add_option('zlrecipe_sugar_label_hide', '');
		add_option('zlrecipe_saturated_fat_label', 'Saturated fat per serving:');
		add_option('zlrecipe_saturated_fat_label_hide', '');
		add_option('zlrecipe_sodium_label', 'Sodium per serving:');
		add_option('zlrecipe_sodium_label_hide', '');

		add_option('zlrecipe_rating_label', 'Rating:');
		add_option('zlrecipe_image_width', '');
		add_option('zlrecipe_outer_border_style', '');
		add_option('zlrecipe_custom_print_image', '');

		add_filter('wp_head', __NAMESPACE__ . '\ZipRecipes::zrdn_process_head');

		// Deleting option that was added for WooCommerce conflict.
		//      This can be removed a few releases after 4.1.0.18
		delete_option('zrdn_woocommerce_active');

		add_action('admin_footer', __NAMESPACE__ . '\ZipRecipes::zrdn_plugin_footer');

		self::zrdn_recipe_install();
	}

	public static function zrdn_js_vars() {

		if (is_admin()) {
			?>
			<script type="text/javascript">
				var post_id = '<?php global $post; if ($post instanceof WP_Post) { echo $post->ID; } ?>';
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

	/**
	 * If there is no existing post excerpt, and there is a recipe summary, use that as an excerpt.
	 *   If no recipe summary exists, use recipe instructions as an excerpt.
	 *
	 * Note: I didn't care to implement "old" shortcode search because most people won't have that type of shortcode.
	 *  Besides, we need to get rid of it.
	 *
	 * @param $excerpt_text String Text of excerpt.
	 *
	 * @return mixed New excerpt text.
	 */
	public static function zrdn_convert_to_summary_recipe($excerpt_text) {
		global $post;

		$output = $excerpt_text;

		if ($output == '') {
			$preg_needle = '/\[amd-zlrecipe-recipe:([0-9]+)\]/i';

			preg_match( $preg_needle, $post->post_content, $recipe_id_match );

			if ( isset( $recipe_id_match[1] ) && $recipe_id_match[1] != '' && $post->post_excerpt == '' ) {
				$zip_recipes_id = $recipe_id_match[1];
				$recipe         = self::zrdn_select_recipe_db( $zip_recipes_id );

				if ($recipe->summary != '') {
					$output = $recipe->summary;
				}
			}
		}

		return $output;
	}

	// Formats the recipe for output
	public static function zrdn_format_recipe($recipe) {
		$output = "";
		$permalink = get_permalink();

		// Output main recipe div with border style
		$style_tag = '';
		$border_style = get_option('zlrecipe_outer_border_style');
		if ($border_style != null) {
			$style_tag = 'style="border: ' . $border_style . ';"';
		}
		$output .= '
    <div id="zlrecipe-container-' . $recipe->recipe_id . '" class="zlrecipe-container-border" ' . $style_tag . '>
    <div itemscope itemtype="http://schema.org/Recipe" id="zlrecipe-container" class="serif zlrecipe">
      <div id="zlrecipe-innerdiv">
        <div class="item b-b">';

		// Add the print button
		if (strcmp(get_option('zlrecipe_print_link_hide'), 'Hide') != 0) {
			$custom_print_image = get_option('zlrecipe_custom_print_image');
			$button_type = 'butn-link';

			$print_button = get_option('zrdn_print_button_label');
			if (strlen($custom_print_image) > 0) {
				$button_type = 'print-link';
				$print_button = '<img src="' . $custom_print_image . '">';
			}
			$output .= '<div class="zlrecipe-print-link fl-r"><a class="' . $button_type .
			           '" title="Print this recipe" href="javascript:void(0);" onclick="zlrPrint(\'zlrecipe-container-' .
			           $recipe->recipe_id . '\', \'' . ZRDN_PLUGIN_URL . '\'); return false" rel="nofollow">' . $print_button .
			           '</a></div>';
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

		$output .= '<script type="text/javascript" > window.ajaxurl = "' . admin_url('admin-ajax.php') . '";</script>';

		$output .= do_action('zrdn__ratings', $recipe->recipe_id);

		// recipe timing
		if ($recipe->prep_time != null) {
			$prep_time = self::zrdn_format_duration($recipe->prep_time);

			$output .= '<p id="zlrecipe-prep-time">';
			if (strcmp(get_option('zlrecipe_prep_time_label_hide'), 'Hide') != 0) {
				$output .= get_option('zlrecipe_prep_time_label') . ' ';
			}
			$output .= '<span itemprop="prepTime" content="' . $recipe->prep_time . '">' . $prep_time . '</span></p>';
		}
		if ($recipe->cook_time != null) {
			$cook_time = self::zrdn_format_duration($recipe->cook_time);

			$output .= '<p id="zlrecipe-cook-time">';
			if (strcmp(get_option('zlrecipe_cook_time_label_hide'), 'Hide') != 0) {
				$output .= get_option('zlrecipe_cook_time_label') . ' ';
			}
			$output .= '<span itemprop="cookTime" content="' . $recipe->cook_time . '">' . $cook_time . '</span></p>';
		}
		if ($recipe->total_time != null) {
			$total_time = self::zrdn_format_duration($recipe->total_time);

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

		if ($recipe->serving_size != null || $recipe->calories != null || $recipe->fat != null || $recipe->carbs != null
		    || $recipe->protein != null || $recipe->fiber != null || $recipe->sugar != null || $recipe->saturated_fat != null
		    || $recipe->sodium != null) {
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
			if($recipe->saturated_fat != null)
			{
				$output .= '<p id="zlrecipe-saturated-fat">';
				if (strcmp(get_option('zlrecipe_saturated_fat_label_hide'), 'Hide') != 0)
				{
					$output .= get_option('zlrecipe_saturated_fat_label') . ' ';
				}
				$output .= '<span itemprop="saturatedFatContent">' . $recipe->saturated_fat . '</span></p>';
			}
			if($recipe->carbs != null)
			{
				$output .= '<p id="zlrecipe-carbs">';
				if (strcmp(get_option('zlrecipe_carbs_label_hide'), 'Hide') != 0)
				{
					$output .= get_option('zlrecipe_carbs_label') . ' ';
				}
				$output .= '<span itemprop="carbohydrateContent">' . $recipe->carbs . '</span></p>';
			}
			if($recipe->protein != null)
			{
				$output .= '<p id="zlrecipe-protein">';
				if (strcmp(get_option('zlrecipe_protein_label_hide'), 'Hide') != 0)
				{
					$output .= get_option('zlrecipe_protein_label') . ' ';
				}
				$output .= '<span itemprop="proteinContent">' . $recipe->protein . '</span></p>';
			}
			if($recipe->fiber != null)
			{
				$output .= '<p id="zlrecipe-fiber">';
				if (strcmp(get_option('zlrecipe_fiber_label_hide'), 'Hide') != 0)
				{
					$output .= get_option('zlrecipe_fiber_label') . ' ';
				}
				$output .= '<span itemprop="fiberContent">' . $recipe->fiber . '</span></p>';
			}
			if($recipe->sugar != null)
			{
				$output .= '<p id="zlrecipe-sugar">';
				if (strcmp(get_option('zlrecipe_sugar_label_hide'), 'Hide') != 0)
				{
					$output .= get_option('zlrecipe_sugar_label') . ' ';
				}
				$output .= '<span itemprop="sugarContent">' . $recipe->sugar . '</span></p>';
			}
			if ($recipe->sodium != null) {
				$output .= '<p id="zlrecipe-sodium">';
				if (strcmp(get_option('zlrecipe_sodium_label_hide'), 'Hide') != 0) {
					$output .= get_option('zlrecipe_sodium_label') . ' ';
				}
				$output .= '<span itemprop="sodiumContent">' . $recipe->sodium . '</span></p>';
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
				$output .= self::zrdn_break( '<p class="summary italic">', self::zrdn_richify_item($recipe->summary, 'summary'), '</p>' );
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
			$output .= self::zrdn_format_item($ingredient, $ingredient_tag, $ingredientClassString, 'ingredients', 'zlrecipe-ingredient-', $i);
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
					$output .= self::zrdn_format_item($instruction, $instruction_tag, $instructionClassString, 'recipeInstructions', 'zlrecipe-instruction-', $j);
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
			$output .= self::zrdn_break( '<p class="notes">', self::zrdn_richify_item($recipe->notes, 'notes'), '</p>' );
			$output .= '</div>';

		}

		// Zip Recipes attribution and version
		if (strcmp(get_option('zrdn_attribution_hide'), 'Hide') != 0)
			$output .= '<div class="zl-linkback">Schema/Recipe SEO Data Markup by <a title="Zip Recipes Plugin" href="http://www.ziprecipes.net" target="_blank">Zip Recipes Plugin</a></div>';
		$output .= '<div class="ziplist-recipe-plugin" style="display: none;">' . ZRDN_VERSION_NUM . '</div>';

		// Add permalink for printed output before closing the innerdiv
		if (strcmp(get_option('zlrecipe_printed_permalink_hide'), 'Hide') != 0) {
			$output .= '<a id="zl-printed-permalink" href="' . $permalink . '" title="Permalink to Recipe">' . $permalink . '</a>';
		}

		$output .= '</div>';

		// Add copyright statement for printed output (outside the dotted print line)
		$printed_copyright_statement = get_option('zlrecipe_printed_copyright_statement');
		if (strlen($printed_copyright_statement) > 0) {
			$output .= '<div id="zl-printed-copyright-statement" itemprop="copyrightHolder">' . $printed_copyright_statement . '</div>';
		}

		$output .= '</div></div>';

		// show piwik script
		wp_enqueue_script("zrdn_piwik", plugins_url('scripts/piwik.js', __FILE__), /*deps*/ array(), /*version*/ "1.0", /*in_footer*/ true);

		return $output;
	}

	// Processes markup for attributes like labels, images and links
	// !Label
	// %image
	public static function zrdn_format_item($item, $elem, $class, $itemprop, $id, $i) {

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

		$output .= self::zrdn_richify_item($item, $class);

		$output .= '</' . $elem . '>';

		return $output;
	}

	// Adds module to left sidebar in wp-admin for ZLRecipe
	public static function zrdn_menu_pages() {
		// Add the top-level admin menu
		$page_title = 'Zip Recipes Settings';
		$menu_title = 'Zip Recipes';
		$capability = 'manage_options';
		$menu_slug = 'zrdn-settings';
		$function = __NAMESPACE__ . '\ZipRecipes::zrdn_settings';
		add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, 'dashicons-carrot');

		$settings_title = "Settings";
		add_submenu_page(
			$settings_title, // parent_slug
			$page_title, // page_title
			$settings_title, // menu_title
			$capability, // capability
			$menu_slug, // menu_slug
			$function // callback function
		);

		do_action("zrdn__menu_page", array(
			"capability" => $capability,
			"parent_slug" => $menu_slug,
			));
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
		$zrecipe_attribution_hide = get_option('zrdn_attribution_hide');
		$printed_permalink_hide = get_option('zlrecipe_printed_permalink_hide');
		$printed_copyright_statement = get_option('zlrecipe_printed_copyright_statement');
		$print_button_label = get_option('zrdn_print_button_label');
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
		$carbs_label = get_option('zlrecipe_carbs_label', 'Carbs:');
		$carbs_label_hide = get_option('zlrecipe_carbs_label_hide', '');
		$protein_label = get_option('zlrecipe_protein_label', 'Protein:');
		$protein_label_hide = get_option('zlrecipe_protein_label_hide', '');
		$fiber_label = get_option('zlrecipe_fiber_label', 'Fiber:');
		$fiber_label_hide = get_option('zlrecipe_fiber_label_hide', '');
		$sugar_label = get_option('zlrecipe_sugar_label', 'Sugar:');
		$sugar_label_hide = get_option('zlrecipe_sugar_label_hide', '');
		$saturated_fat_label = get_option('zlrecipe_saturated_fat_label', 'Saturated fat:');
		$saturated_fat_label_hide = get_option('zlrecipe_saturated_fat_label_hide', '');
		$sodium_label = get_option('zlrecipe_sodium_label', 'Sodium:');
		$sodium_label_hide = get_option('zlrecipe_sodium_label_hide', '');
		$rating_label = get_option('zlrecipe_rating_label');



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
				$print_button_label = $_POST['print-button-label'];
				$stylesheet = $_POST['stylesheet'];
				$recipe_title_hide = $_POST['recipe-title-hide'];
				$image_hide = $_POST['image-hide'];
				$image_hide_print = $_POST['image-hide-print'];
				$print_link_hide = $_POST['print-link-hide'];
				$ingredient_label = self::zrdn_strip_chars($_POST['ingredient-label']);
				$ingredient_label_hide = self::zrdn_strip_chars($_POST['ingredient-label-hide']);
				$ingredient_list_type = $_POST['ingredient-list-type'];
				$instruction_label = self::zrdn_strip_chars($_POST['instruction-label']);
				$instruction_label_hide = $_POST['instruction-label-hide'];
				$instruction_list_type = self::zrdn_strip_chars($_POST['instruction-list-type']);
				$notes_label = self::zrdn_strip_chars($_POST['notes-label']);
				$notes_label_hide = $_POST['notes-label-hide'];
				$prep_time_label = self::zrdn_strip_chars($_POST['prep-time-label']);
				$prep_time_label_hide = $_POST['prep-time-label-hide'];
				$cook_time_label = self::zrdn_strip_chars($_POST['cook-time-label']);
				$cook_time_label_hide = $_POST['cook-time-label-hide'];
				$total_time_label = self::zrdn_strip_chars($_POST['total-time-label']);
				$total_time_label_hide = $_POST['total-time-label-hide'];
				$yield_label = self::zrdn_strip_chars($_POST['yield-label']);
				$yield_label_hide = $_POST['yield-label-hide'];
				$serving_size_label = self::zrdn_strip_chars($_POST['serving-size-label']);
				$serving_size_label_hide = $_POST['serving-size-label-hide'];
				$calories_label = self::zrdn_strip_chars($_POST['calories-label']);
				$calories_label_hide = $_POST['calories-label-hide'];
				$fat_label = self::zrdn_strip_chars($_POST['fat-label']);
				$fat_label_hide = $_POST['fat-label-hide'];
				$carbs_label = $_POST['carbs-label'];
				$carbs_label_hide = $_POST['carbs-label-hide'];
				$protein_label = $_POST['protein-label'];
				$protein_label_hide = $_POST['protein-label-hide'];
				$fiber_label = $_POST['fiber-label'];
				$fiber_label_hide = $_POST['fiber-label-hide'];
				$sugar_label = $_POST['sugar-label'];
				$sugar_label_hide = $_POST['sugar-label-hide'];
				$saturated_fat_label = $_POST['saturated-fat-label'];
				$saturated_fat_label_hide = $_POST['saturated-fat-label-hide'];
				$sodium_label = $_POST['sodium-label'];
				$sodium_label_hide = $_POST['sodium-label-hide'];
				$rating_label = self::zrdn_strip_chars($_POST['rating-label']);
				$image_width = $_POST['image-width'];
				$outer_border_style = $_POST['outer-border-style'];
				$custom_print_image = $_POST['custom-print-image'];

				update_option('zrdn_attribution_hide', $zrecipe_attribution_hide);
				update_option('zlrecipe_printed_permalink_hide', $printed_permalink_hide );
				update_option('zlrecipe_printed_copyright_statement', $printed_copyright_statement);
				update_option('zrdn_print_button_label', $print_button_label);
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
				update_option('zlrecipe_carbs_label', $carbs_label);
				update_option('zlrecipe_carbs_label_hide', $carbs_label_hide);
				update_option('zlrecipe_protein_label', $protein_label);
				update_option('zlrecipe_protein_label_hide', $protein_label_hide);
				update_option('zlrecipe_fiber_label', $fiber_label);
				update_option('zlrecipe_fiber_label_hide', $fiber_label_hide);
				update_option('zlrecipe_sugar_label', $sugar_label);
				update_option('zlrecipe_sugar_label_hide', $sugar_label_hide);
				update_option('zlrecipe_saturated_fat_label', $saturated_fat_label);
				update_option('zlrecipe_saturated_fat_label_hide', $saturated_fat_label_hide);
				update_option('zlrecipe_sodium_label', $sodium_label);
				update_option('zlrecipe_sodium_label_hide', $sodium_label_hide);
				update_option('zlrecipe_rating_label', $rating_label);
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
		$other_options_array = array('Rating', 'Prep Time', 'Cook Time', 'Total Time', 'Yield', 'Serving Size', 'Calories',
			'Fat', 'Saturated Fat', 'Carbs', 'Protein', 'Fiber', 'Sugar', 'Sodium', 'Notes');


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
                <tr valign="top">
                    <th scope="row">Print Button Label:</th>
                    <td><input type="text" name="print-button-label" value="' . $print_button_label . '" class="regular-text" /></td>
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

		if (! $registered)
		{
			$forms = '
			<script type="text/javascript">
				var $form = jQuery("#zlrecipe_settings_form");

		         $form.on("submit", function ()
		         {
		         	var $registerButton = jQuery("#register_button");
	                 $registerButton.val("Registering...");
	                 $registerButton.attr("disabled", true);

	                 var postUrl = "'. self::registration_url . '";

		         	jQuery.post(postUrl, $form.serialize(), function(data)
		         	{
		         		var jsonData = JSON.parse(data);

		         		if (jsonData.status === "failure")
		         		{
		         			for (var elementID in jsonData.errors)
		         			{
		         				if (jsonData.errors.hasOwnProperty(elementID))
		         				{
		         					var errors = jsonData.errors[elementID];
		         					for (var i = 0; i < errors.length; i++)
		         					{
		         						jQuery("#" + elementID).parent().append("<br/>"+errors[i]);
		         					}
		         				}
		         			}

			                $registerButton.val("Register");
			                $registerButton.attr("disabled", false);
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
                    	<input type="hidden" id="plugins" name="plugins" value="' . Util::zrdn_get_installed_plugins() . '" />
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
        hr { border: none; border-top: 1px; margin: 30px 0; }
    </style>
    <div class="wrap">
        <form method="post" action="" id="zlrecipe_settings_form" name="zlrecipe_settings_form">
            <h2><img src="' . $zrdn_icon . '" /> Zip Recipes Settings</h2>' .
		           $forms .
		           '</form>
    </div>';

		echo $output;
	}

	// Replaces the [a|b] pattern with text a that links to b
	// Replaces _words_ with an italic span and *words* with a bold span
	public static function zrdn_richify_item($item, $class) {
		$output = preg_replace('/\[([^\]\|\[]*)\|([^\]\|\[]*)\]/', '<a href="\\2" class="' . $class . '-link" target="_blank">\\1</a>', $item);
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
		$installed_db_ver = get_option("amd_zlrecipe_db_version");

		$charset_collate = Util::get_charset_collate();

		if($installed_db_ver !== self::TABLE_VERSION) {				// An older (or no) database table exists
			$sql_command = "CREATE TABLE `$recipes_table` (
            recipe_id bigint(20) unsigned NOT NULL AUTO_INCREMENT  PRIMARY KEY,
            post_id bigint(20) unsigned NOT NULL,
            recipe_title text,
            recipe_image text,
            summary text,
            prep_time text,
            cook_time text,
            total_time text,
            yield text,
            serving_size varchar(50),
            calories varchar(50),
            fat varchar(50),
			carbs varchar(50),
			protein varchar(50),
			fiber varchar(50),
			sugar varchar(50),
			saturated_fat varchar(50),
			sodium varchar(50),
            ingredients text,
            instructions text,
            notes text,
            created_at timestamp DEFAULT NOW()
        	) $charset_collate;";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql_command);

			update_option("amd_zlrecipe_db_version", self::TABLE_VERSION);
		}

		Util::log("Calling db_setup() action");

		do_action("zrdn__db_setup", self::TABLE_NAME);
	}

	// Content for the popup iframe when creating or editing a recipe
	public static function zrdn_iframe_content($post_info = null, $get_info = null) {

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


		if ($post_info || $get_info) {

			if( $get_info["add-recipe-button"] || strpos($get_info["recipe_post_id"], '-') !== false ) {
				$iframe_title = "Update Your Recipe";
				$submit = "Update Recipe";
			} else {
				$iframe_title = "Add a Recipe";
				$submit       = "Add Recipe";
			}

			if ($get_info["recipe_post_id"] && !$get_info["add-recipe-button"] && strpos($get_info["recipe_post_id"], '-') !== false) {
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
				$carbs = $recipe->carbs;
				$protein = $recipe->protein;
				$fiber = $recipe->fiber;
				$sugar = $recipe->sugar;
				$saturated_fat = $recipe->saturated_fat;
				$sodium = $recipe->sodium;
				$ingredients = $recipe->ingredients;
				$instructions = $recipe->instructions;
			} else {
				foreach ($post_info as $key=>$val) {
					$post_info[$key] = stripslashes($val);
				}

				$recipe_id = $post_info["recipe_id"];

				if( !$get_info["add-recipe-button"]) {
					$recipe_title = get_the_title( $get_info["recipe_post_id"] );
				}
				else {
					$recipe_title = $post_info["recipe_title"];
				}
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
				$carbs = $post_info['carbs'];
				$protein = $post_info['protein'];
				$fiber = $post_info['fiber'];
				$sugar = $post_info['sugar'];
				$saturated_fat = $post_info['saturated_fat'];
				$sodium = $post_info['sodium'];
				$ingredients = $post_info["ingredients"];
				$instructions = $post_info["instructions"];
				if ($recipe_title != null && $recipe_title != '' && $ingredients != null && $ingredients != '') {
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

		Util::view('create-update-recipe', array(
			'pluginurl' => ZRDN_PLUGIN_URL,
			'recipe_id' => $recipe_id,
			'registration_required' => $registration_required,
			'settings_page_url' => $settings_page_url,
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
			'submit' => $submit
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

		$recipe = array (
			"recipe_title" =>  $post_info["recipe_title"],
			"recipe_image" => $post_info["recipe_image"],
			"summary" =>  $post_info["summary"],
			"prep_time" => $prep_time,
			"cook_time" => $cook_time,
			"total_time" => $total_time,
			"yield" =>  $post_info["yield"],
			"serving_size" =>  $post_info["serving_size"],
			"calories" => $post_info["calories"],
			"fat" => $post_info["fat"],
			"carbs" => $post_info['carbs'],
			"protein" => $post_info['protein'],
			"fiber" => $post_info['fiber'],
			"sugar" => $post_info['sugar'],
			"saturated_fat" => $post_info['saturated_fat'],
			"sodium" => $post_info['sodium'],
			"ingredients" => $post_info["ingredients"],
			"instructions" => $post_info["instructions"],
			"notes" => $post_info["notes"],
		);

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

		// Always add the print script
		$header_html='<script type="text/javascript" async="" src="' . ZRDN_PLUGIN_URL . 'scripts/zlrecipe_print.js"></script>';

		// Recipe styling
		$css = get_option('zlrecipe_stylesheet');
		if (strcmp($css, '') != 0) {
			$header_html .= '<link href="' . ZRDN_PLUGIN_URL . 'styles/zlrecipe-std.css" rel="stylesheet" type="text/css" />';
		}

		echo $header_html;
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
		$date_abbr = array('y' => 'year', 'm' => 'month', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second');
		$result = '';

		if (class_exists('DateInterval')) {
			try {
				$result_object = new \DateInterval($duration);

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


	// Inserts the recipe into the post editor
	public static function zrdn_plugin_footer() {
		wp_enqueue_script(
			'zrdn-admin-script',
			plugins_url('/scripts/admin.js', __FILE__),
			array( 'jquery' ), // deps
			false, // ver
			true // in_footer
		);

		$url = site_url();
		$pluginurl = ZRDN_PLUGIN_URL;

		echo <<< HTML
    <style type="text/css" media="screen">
        #wp_editrecipebtns { position:absolute;display:block;z-index:999998; }
        #wp_editrecipebtn { margin-right:20px; }
        #wp_editrecipebtn,#wp_delrecipebtn { cursor:pointer; padding:12px;background:#010101; -moz-border-radius:8px;-khtml-border-radius:8px;-webkit-border-radius:8px;border-radius:8px; filter:alpha(opacity=80); -moz-opacity:0.8; -khtml-opacity: 0.8; opacity: 0.8; }
        #wp_editrecipebtn:hover,#wp_delrecipebtn:hover { background:#000; filter:alpha(opacity=100); -moz-opacity:1; -khtml-opacity: 1; opacity: 1; }
    </style>
    <script>//<![CDATA[
    var baseurl = '$url';          // This variable is used by the editor plugin
    var plugindir = '$pluginurl';  // This variable is used by the editor plugin

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

	public static function zrdn_load_admin_media() {
		wp_enqueue_script('jquery');

		// This will enqueue the Media Uploader script
		wp_enqueue_script('media-upload');

		wp_enqueue_media();

		wp_enqueue_script('zrdn-admin-script');
	}
}
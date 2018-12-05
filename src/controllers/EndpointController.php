<?php

namespace ZRDN;

/**
 * Zip Recipes API endpoint
 *
 * JSON base Restful API for Zip Recipes Operations.
 *
 * PHP version 5.3
 * Depends: WP REST API, Plugin Dependencies
 *
 * @package    Zip Recipes
 * @author     Mudassar Ali <sahil_bwp@yahoo.com>
 * @copyright  2017 Gezim Hoxha
 * @version 1.0
 * @link https://github.com/hgezim/zip-recipes-plugin Zip Recipes
 * @example  wp-json/zip-recipes/v1/recipe
 */
use WP_REST_Server;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WP_Http;
use ZRDN\ZRDN_REST_Response;
use ZRDN\Recipe as RecipeModel;

class ZRDN_API_Endpoint_Controller extends WP_REST_Controller {

    /**
     *  constant
     */

    protected $version = 1;
    protected $namespace = 'zip-recipes/v';
    protected $rest_base = 'recipe';

    public function __construct() {
        $this->namespace = $this->namespace . $this->version;
    }

    /**
     * Register our REST Server
     */
    public function boot_rest_server() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register routs
     */
    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_recipes'),
                'permission_callback' => array($this, 'is_logged_in_check'),
                'args' => array(),
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_recipe'),
                //'permission_callback' => array($this, 'create_item_permissions_check'),
                'permission_callback' => array($this, 'is_logged_in_check'),
                'args' => $this->get_endpoint_args_for_item_schema(true),
                'accept_json' => true,
            ),
        ));
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_recipe'),
                'permission_callback' => array($this, 'is_logged_in_check'),
                'args' => array(
                    'id' => array(
                        'validate_callback' => array($this, 'validate_numeric')
                    )
                ),
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_recipe'),
                'permission_callback' => array($this, 'is_logged_in_check'),
                'args' => $this->get_endpoint_args_for_item_schema(false),
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_recipe'),
                'permission_callback' => array($this, 'is_logged_in_check'),
                'args' => array(
                    'id' => array(
                        'validate_callback' => array($this, 'validate_numeric')
                    )
                ),
            ),
        ));

        // end routes functions    
    }

    /**
     * Get a collection of recipes
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_recipes(WP_REST_Request $request) {
        return ZRDN_REST_Response::error(__('Not Implemented Yet.', 'zip-recipes'),WP_Http::NOT_IMPLEMENTED);
    }

    /**
     * Get one recipe from the collection
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_recipe(WP_REST_Request $request) {
        $recipe_id = (int) $request['id'];
        $recipe = RecipeModel::db_select($recipe_id);
        if (empty($recipe_id) || !isset($recipe->recipe_id)) {
            return ZRDN_REST_Response::error(__('Invalid recipe ID or recipe not found', 'zip-recipes'));
        }
        $data = $this->prepare_item_for_response($recipe, $request);
        return ZRDN_REST_Response::success($data);
    }

    /**
     * Create one recipe from the collection
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function create_recipe(WP_REST_Request $request) {
        $parameters = $request->get_params();
        $post_id = Util::get_array_value('post_id', $parameters);
        $title = Util::get_array_value('title', $parameters);
        if (empty($post_id) || empty($title)) {
            return ZRDN_REST_Response::error(__('Invalid recipe fields', 'zip-recipes'));
        }
        $recipe = $this->prepare_item_for_database($request);
        $insert_id = RecipeModel::db_insert($recipe);
        if($insert_id){
            $recipe = RecipeModel::db_select($insert_id);
            $data = $this->prepare_item_for_response($recipe, $request);
            return ZRDN_REST_Response::success($data, WP_Http::CREATED);
        }else{
            return ZRDN_REST_Response::error(__('Can\'t create recipe', 'zip-recipes'));
        }
    }

    /**
     * Update one Recipe from the collection
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function update_recipe(WP_REST_Request $request) {
        $recipe_id = (int) $request['id'];
        $where = array('recipe_id' => $recipe_id);
        $recipe = $this->prepare_item_for_database($request);
        $is_updated = RecipeModel::db_update($recipe, $where);
        $recipe = RecipeModel::db_select($recipe_id);
        if($recipe) {
            $data = $this->prepare_item_for_response($recipe, $request);
            return ZRDN_REST_Response::success($data);
        }else{
            return ZRDN_REST_Response::error(__('Can\'t update recipe', 'zip-recipes'),WP_Http::NOT_IMPLEMENTED);
        }
    }

    /**
     * Delete one item from the collection
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function delete_recipe(WP_REST_Request $request) {
        $recipe_id = (int) $request['id'];

        $result = RecipeModel::db_delete(array('recipe_id' => $recipe_id));
        if (!$result) {
            return ZRDN_REST_Response::error(__('The resource cannot be deleted.', 'zip-recipes'));
        }
        return ZRDN_REST_Response::success(true);
    }


    /**
     * Checks that value is numeric.
     *
     * @param $param
     *
     * @return bool
     */
    public function validate_numeric($param) {
        return is_numeric($param);
    }

    /**
     * Prepare a single attachment for create or update.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_Error|stdClass $prepared_attachment Post object.
     */
    protected function prepare_item_for_database($request) {
        $parameters = $request->get_params();
        $nutrition = (isset($parameters['nutrition']) && !empty($parameters['nutrition'])) ? $parameters['nutrition'] : array();
        if(Util::get_array_value('post_id', $parameters)) {
            $sanitize['post_id'] = Util::get_array_value('post_id', $parameters);
        }
        if(Util::get_array_value('title', $parameters)) {
            $sanitize['recipe_title'] = Util::get_array_value('title', $parameters);
        }
        if(Util::get_array_value('image_url', $parameters)) {
            $sanitize['recipe_image'] = Util::get_array_value('image_url', $parameters);
        }
        if(Util::get_array_value('description', $parameters)) {
            $sanitize['summary'] = Util::get_array_value('description', $parameters);
        }
        if(Util::get_array_value('prep_time', $parameters)) {
            $sanitize['prep_time'] = Util::get_array_value('prep_time', $parameters);
        }
        if(Util::get_array_value('cook_time', $parameters)) {
            $sanitize['cook_time'] = Util::get_array_value('cook_time', $parameters);
        }
        if(Util::get_array_value('yield', $parameters)) {
            $sanitize['yield'] = Util::get_array_value('yield', $parameters);
        }
        if(Util::get_array_value('category', $parameters)) {
            $sanitize['category'] = Util::get_array_value('category', $parameters);
        }
        if(Util::get_array_value('cuisine', $parameters)) {
            $sanitize['cuisine'] = Util::get_array_value('cuisine', $parameters);
        }
        if(Util::get_array_value('ingredients', $parameters)) {
            $sanitize['ingredients'] = $this->format_array_to_text(Util::get_array_value('ingredients', $parameters));
        }
        if(Util::get_array_value('instructions', $parameters)) {
            $sanitize['instructions'] = $this->format_array_to_text(Util::get_array_value('instructions', $parameters));
        }
        if(Util::get_array_value('calories', $nutrition)) {
            $sanitize['calories'] = Util::get_array_value('calories', $nutrition);
        }
        if(Util::get_array_value('carbohydrateContent', $nutrition)) {
            $sanitize['carbs'] = Util::get_array_value('carbohydrateContent', $nutrition);
        }
        if(Util::get_array_value('cholesterolContent', $nutrition)) {
            $sanitize['cholesterol'] = Util::get_array_value('cholesterolContent', $nutrition);
        }
        if(Util::get_array_value('fiberContent', $nutrition)) {
            $sanitize['fiber'] = Util::get_array_value('fiberContent', $nutrition);
        }
        if(Util::get_array_value('proteinContent', $nutrition)) {
            $sanitize['protein'] = Util::get_array_value('proteinContent', $nutrition);
        }
        if(Util::get_array_value('fatContent', $nutrition)) {
            $sanitize['fat'] = Util::get_array_value('fatContent', $nutrition);
        }
        if(Util::get_array_value('saturatedFatContent', $nutrition)) {
            $sanitize['saturated_fat'] = Util::get_array_value('saturatedFatContent', $nutrition);
        }
        if(Util::get_array_value('servingSize', $nutrition)) {
            $sanitize['serving_size'] = Util::get_array_value('servingSize', $nutrition);
        }
        if(Util::get_array_value('sodiumContent', $nutrition)) {
            $sanitize['sodium'] = Util::get_array_value('sodiumContent', $nutrition);
        }
        if(Util::get_array_value('sugarContent', $nutrition)) {
            $sanitize['sugar'] = Util::get_array_value('sugarContent', $nutrition);
        }
        if(Util::get_array_value('transFatContent', $nutrition)) {
            $sanitize['trans_fat'] = Util::get_array_value('transFatContent', $nutrition);
        }

        return $sanitize;
    }


    /**
     * Prepares the item for the REST response.
     *
     * @since 4.7.0
     *
     * @param mixed $item WordPress representation of the item.
     * @param WP_REST_Request $request Request object.
     * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
     */
    public function prepare_item_for_response($item, $request) {
        $formatted = array(
            'id' => $item->recipe_id,
            'post_id' => $item->post_id,
            'title' => $item->recipe_title,
            'image_url' => $item->recipe_image,
            'description' => $item->summary,
            'prep_time' => $item->prep_time,
            'cook_time' => $item->cook_time,
            'yield' => $item->yield,
            'category' => $item->category,
            'cuisine' => $item->cuisine,
            'ingredients' => $this->format_text_to_array($item->ingredients),
            'instructions' => $this->format_text_to_array($item->instructions),
            'nutrition' => $this->format_nutrition_schema($item),
	        'notes' => $this->notes
        );
        return $formatted;
    }

    /**
     * Get Nutrition list
     * 
     * @example http://schema.org/NutritionInformation list of nutrition 
     * @return array
     */
    public function get_nutrition_schema_map_list() {
        return array(
            'calories' => 'calories',
            'carbohydrateContent' => 'carbs',
            'cholesterolContent' => 'cholesterol',
            'fatContent' => 'fat',
            'fiberContent' => 'fiber',
            'proteinContent' => 'protein',
            'saturatedFatContent' => 'saturated_fat',
            'servingSize' => 'serving_size',
            'sodiumContent' => 'sodium',
            'sugarContent' => 'sugar',
            'transFatContent' => 'trans_fat',
        );
    }

    /**
     * Format nutrition schema
     * 
     * @param object $item
     * @return array
     */
    public function format_nutrition_schema($item) {
        $nutritionList = $this->get_nutrition_schema_map_list();
        $map = array();
        foreach ($nutritionList as $key => $nutrition) {
            $map[$key] = $item->{$nutrition};
        }
        return $map;
    }


    /**
     * Text to array by line
     *
     * @help /\n|\r\n?/   /\R/
     * @param $text
     * @return array
     */
    public function format_text_to_array ($text){
        return preg_split('/\s*\R\s*/', trim($text), NULL, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Array to text by line
     *
     * @param $array
     * @return string
     */
    public function format_array_to_text($array){
        return implode("\n\r", $array);
    }

    /**
     * Callback User authenticated check
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function is_logged_in_check(WP_REST_Request $request) {
        return true;
//        $is_user_logged_in = false;
//        if (is_user_logged_in() == true) {
//            $is_user_logged_in = true;
//        }
//        return $is_user_logged_in;
    }


}

$server = new ZRDN_API_Endpoint_Controller();
$server->boot_rest_server();

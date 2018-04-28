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

class ZRDN_API_Endpoint_Controller extends WP_REST_Controller {

    /**
     *  constant
     */
    const TABLE_NAME = 'amd_zlrecipe_recipes';

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
                'callback' => [$this, 'get_recipe'],
                'permission_callback' => [$this, 'is_logged_in_check'],
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
    public function get_recipes($request) {
        $items = array(); //do a query, call another class, etc
        $data = array();
        foreach ($items as $item) {
            $itemdata = $this->prepare_item_for_response($item, $request);
            $data[] = $this->prepare_response_for_collection($itemdata);
        }

        return ZRDN_REST_Response::success($data);
    }

    /**
     * Get one recipe from the collection
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_recipe(WP_REST_Request $request) {
        $recipe_id = (int) $request['id'];
        $recipe = $this->db_select_recipe($recipe_id);
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
        $insert_id = $this->db_insert_recipe($recipe);
        if($insert_id) {
            return ZRDN_REST_Response::success($insert_id, 201);
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
        $is_updated = $this->db_update_recipe($recipe, $where);
        if($is_updated) {
            return ZRDN_REST_Response::success(true);
        }else{
            return ZRDN_REST_Response::error(__('Can\'t update recipe', 'zip-recipes'),500);
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
        // $recipe = $this->db_select_recipe($recipe_id);
        // $response = $this->prepare_item_for_response($recipe, $request);

        $result = $this->db_delete_recipe(['recipe_id' => $recipe_id]);
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
        if(Util::get_array_value('serving_size', $parameters)) {
            $sanitize['serving_size'] = Util::get_array_value('serving_size', $parameters);
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
        if(Util::get_array_value('saturatedFatContent', $nutrition)) {
            $sanitize['saturated_fat'] = Util::get_array_value('saturatedFatContent', $nutrition);
        }
        if(Util::get_array_value('sodiumContent', $nutrition)) {
            $sanitize['sodium'] = Util::get_array_value('sodiumContent', $nutrition);
        }
        if(Util::get_array_value('sugarContent', $nutrition)) {
            $sanitize['sugar'] = Util::get_array_value('sugarContent', $nutrition);
        }
        if(Util::get_array_value('unsaturatedFatContent', $nutrition)) {
            $sanitize['fat'] = Util::get_array_value('unsaturatedFatContent', $nutrition);
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

        $formated = [
            'id' => $item->recipe_id,
            'post_id' => $item->post_id,
            'title' => $item->recipe_title,
            'image_url' => $item->recipe_image,
            //'summary'=> $item->summary,
            'description' => $item->summary,
            'prep_time' => $item->prep_time,
            'cook_time' => $item->cook_time,
            //'total_time'=> $item->total_time,
            'yield' => $item->yield,
            'serving_size' => $item->serving_size,
            'category' => $item->category,
            'cuisine' => $item->cuisine,
            'ingredients' => $this->format_text_to_array($item->ingredients),
            'instructions' => $this->format_text_to_array($item->instructions),
            'nutrition' => $this->format_nutrition_schema($item),
        ];
        return $formated;
    }

    /**
     * Get Nutrition list
     * 
     * @example http://schema.org/NutritionInformation list of nutrition 
     * @return array
     */
    public function get_nutrition_schema_map_list() {
        return [
            'calories' => 'calories',
            'carbohydrateContent' => 'carbs',
            'cholesterolContent' => 'cholesterol',
            'fatContent' => 'fat', // fat
            'fiberContent' => 'fiber',
            'proteinContent' => 'protein',
            'saturatedFatContent' => 'saturated_fat', // fat
            // 'servingSize' => 'serving_size', // serving size
            'sodiumContent' => 'sodium',
            'sugarContent' => 'sugar',
            'transFatContent' => 'trans_fat', // fat
           // 'unsaturatedFatContent' => 'fat', // fat
        ];
    }

    /**
     * Format nutrition schema
     * 
     * @param object $item
     * @return array
     */
    public function format_nutrition_schema($item) {
        $nutritionList = $this->get_nutrition_schema_map_list();
        $map = [];
        foreach ($nutritionList as $key => $nutrition) {
            $map[$key] = $item->{$nutrition};
        }
        return $map;
    }


    /**
     * Get Table Name method
     * 
     * @global \ZRDN\Array $wpdb
     * @return type
     */
    private function getTableName() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME;
    }

    /**
     * Get a recipe from the db
     * 
     * @global \ZRDN\Array $wpdb
     * @param type $recipe_id
     * @return type
     */
    public function db_select_recipe($recipe_id) {
        global $wpdb;
        $table = $this->getTableName();
        $selectStatement = sprintf("SELECT * FROM `%s` WHERE recipe_id=%d", $table, $recipe_id);
        return $wpdb->get_row($selectStatement);
    }

    /**
     * Delete review row from table
     * 
     * @global \ZRDN\Array $wpdb
     * @param type $delete
     * @return boolean
     */
    public function db_delete_recipe($delete = array()) {
        if (!empty($delete)) {
            global $wpdb;
            return $wpdb->delete($this->getTableName(), $delete);
        }
        return FALSE;
    }

    /**
     * Insert Recipe in db
     *
     * @param $recipe
     * @return bool|int
     */
    public function db_insert_recipe($recipe){
        if(empty($recipe)){
            return FALSE;
        }
        global $wpdb;
        $wpdb->insert($this->getTableName(), $recipe);
        return $wpdb->insert_id;
    }

    /**
     * Recipe update db
     *
     * @param $recipe
     * @param $where
     * @return bool|false|int
     */
    public function db_update_recipe($recipe,$where=null){
        if(empty($recipe) || empty($where)){
            return FALSE;
        }
        global $wpdb;
        return $wpdb->update($this->getTableName(), $recipe, $where);
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
        $is_user_logged_in = false;
        if (is_user_logged_in() == true) {
            $is_user_logged_in = true;
        }
        return $is_user_logged_in;
    }


}

$server = new ZRDN_API_Endpoint_Controller();
$server->boot_rest_server();

class ZRDN_REST_Response {

    public static function response($code, $response) {
        return new WP_REST_Response($response, $code);
    }

    public static function error($response, $code = 404) {
        // return  new WP_Error('err', __('no id passed', 'zip-recipes'), array('status' => 500));
        return self::response($code, array(
                    'code' => $code,
                    'message' => $response,
                    'data' => null
        ));
    }

    public static function success($response, $code = 200) {
        return self::response($code, $response);
    }

}
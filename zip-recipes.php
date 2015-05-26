<?php
/*
Plugin Name: Zip Recipes
Plugin URI: http://www.ziprecipes.net/
Plugin GitHub: https://github.com/hgezim/zip-recipes-plugin
Description: A plugin that adds all the necessary microdata to your recipes, so they will show up in Google's Recipe Search
Version: 4.2.0.23
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

define('ZRDN_VERSION_NUM', '4.2.0.23');
define('ZRDN_PLUGIN_DIRECTORY', plugin_dir_path( __FILE__ ));
define('ZRDN_PLUGIN_URL', sprintf('%s/%s/', plugins_url(), dirname(plugin_basename(__FILE__))));

require_once(ZRDN_PLUGIN_DIRECTORY . 'class.ziprecipes.php');

add_action( 'init', array( 'ZipRecipes', 'init' ) );


if (strpos($_SERVER['REQUEST_URI'], 'media-upload.php') && strpos($_SERVER['REQUEST_URI'], '&type=z_recipe') && !strpos($_SERVER['REQUEST_URI'], '&wrt='))
{
	ZipRecipes::zrdn_iframe_content($_POST, $_REQUEST);
	exit;
}
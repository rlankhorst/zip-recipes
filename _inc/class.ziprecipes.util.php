<?php
/**
 * Created by PhpStorm.
 * User: gezimhoxha
 * Date: 15-05-25
 * Time: 12:59 AM
 */

namespace ZRDN;

class Util {
	/* Send debug code to the Javascript console */
	public static function zrdn_debug_to_console($data) {
		if(is_array($data) || is_object($data))
		{
			echo("<script>console.log('PHP: ".json_encode($data)."');</script>");
		} else {
			echo("<script>console.log('PHP: ".$data."');</script>");
		}
	}

	/**
	 * Render view and echo it.
	 *
	 * @param $name String name of html view to be found in views/ directory. Doesn't contain .html extension.
	 * @param array $args object View context parameters.
	 */
	public static function view($name, $args = array()) {
		$trace=debug_backtrace();
		$caller=$trace[1];

		if ($caller['class'])
		{
			$classComponents = explode("\\", $caller['class']);
			$class = $classComponents[count($classComponents) - 1];
			$plugin_name = $class;
		}

		$pluginDir = "";
		if ($plugin_name)
		{
			$pluginDir = "plugins/$plugin_name/";
		}

		$viewDir = ZRDN_PLUGIN_DIRECTORY . $pluginDir . 'views/';

		$file = $name . '.html';
		$cacheDir = sprintf('%s/cache', $viewDir);

		Util::log("Looking for plugin in dir:" . $viewDir);
		Util::log("Template name:" . $file);

		$h2o = new \H2o($file, array('searchpath' => $viewDir, 'cache'=> 'file', 'cache_dir' => $cacheDir));
		echo $h2o->render($args);
	}

	public static function get_charset_collate() {
		global $wpdb;

		$charset_collate = '';

		if ( ! empty( $wpdb->charset ) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty( $wpdb->collate ) )
			$charset_collate .= " COLLATE $wpdb->collate";

		return $charset_collate;
	}


	/**
	 * Get list of installed plugins as a string. Each plugin is separated with ;
	 */
	public static function zrdn_get_installed_plugins()
	{
		$pluginsString = '';
		$plugins = get_plugins();
		foreach ($plugins as $path => $pluginData)
		{
			// if you update the delimiter here, ensure the api.ziprecipes.net changes as well
			$pluginsString .= $pluginData['Name'] . "|";
		}

		return $pluginsString;
	}

	/**
	 * Log messages if WP_DEBUG is set.
	 * @param $message String Message to log.
	 */
	public static function log($message)
	{
		if (WP_DEBUG)
		{
			error_log("ZRDN: " . $message);
		}
	}
}
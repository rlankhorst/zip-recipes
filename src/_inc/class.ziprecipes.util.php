<?php
/**
 * Created by PhpStorm.
 * User: gezimhoxha
 * Date: 15-05-25
 * Time: 12:59 AM
 */

namespace ZRDN;

require_once(ZRDN_PLUGIN_DIRECTORY . 'vendor/autoload.php');
require_once("WP_I18n_Twig_Extension.php");

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
	 * @return string Rendered view.
	 */
	public static function _view($name, $args = array()) {
                $coreClasses = array("ZipRecipes","ZipUpgrade"); // core class array
		$trace=debug_backtrace();
		$caller=$trace[2]; // 0 here is direct caller of _view, 1 would be our Util class so we want 2

		$plugin_name = "";
		if ($caller['class'])
		{
			$classComponents = explode("\\", $caller['class']);
			$class = $classComponents[count($classComponents) - 1];
			$plugin_name = $class;
		}

		$pluginDir = "";
		// don't consider core class a plugin
		if ($plugin_name && !in_array($plugin_name, $coreClasses)) // TODO: ZipRecipes is hardcoded and needs to change
		{
			$pluginDir = "plugins/$plugin_name/";
		}
		
		$viewDir = ZRDN_PLUGIN_DIRECTORY . $pluginDir . 'views/';
		$file = $name . '.twig';

		$tempDir = get_temp_dir();
		$cacheDir = "${tempDir}zip-recipes/cache";

		// Prefer to write to temp dir, if possible
		// Update: nope, not good since more people can write to ./views but not /tmp
		// Perhaps in the future disable caching if neither is writable?!
		if (is_writable($viewDir)) {
			$cacheDir = "${viewDir}cache";
		}

		Util::log("Looking for template in dir:" . $viewDir);
		Util::log("Template name:" . $file);

		$loader = new \Twig_Loader_Filesystem($viewDir);
		$twig = new \Twig_Environment($loader, array(
				'cache' => $cacheDir,
				'autoescape' => true,
				'auto_reload' => true
		));

		$twig->addExtension(new WP_I18n_Twig_Extension());

		return $twig->render($file, $args);
	}

	public static function print_view($name, $args = array()) {
		echo self::_view($name, $args);
	}

	public static function view($name, $args = array()) {
		return self::_view($name, $args);
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

	// Get value of an array key
	// Used to suppress warnings if key doesn't exist
	public static function get_array_value($key, $array) {
		if (isset($array[$key])) {
			return $array[$key];
		}

		return null;
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
		if (! WP_DEBUG)
		{
			return;
		}

		$trace=debug_backtrace();

		$traceIndex = 1;
		$caller=$trace[$traceIndex];

		$output = "";

		do {
			$className = array_key_exists('class', $caller) ? $caller['class'] : "";
			$functionName = array_key_exists('function', $caller) ? $caller['function'] : "";
			$file = array_key_exists('file', $caller) ? $caller['file'] : "";
			$lineNumber = array_key_exists('line', $caller) ? $caller['line'] : "";

			$prefix = $traceIndex === 1 ? "ZRDN: " : "";
			$message = $traceIndex === 1 ? ": $message" : "";

			$output .= str_repeat("\t", $traceIndex - 1) . "$prefix$className $functionName" . $message . "\n";
			if ($file && $lineNumber) {
				$output .= str_repeat("\t", $traceIndex) . " from $file:$lineNumber" . "\n";
			}

			if (array_key_exists(++$traceIndex, $trace))
			{
				$caller = $trace[$traceIndex];
			}
			else
			{
				$caller = null;
			}
		} while($caller);

		//error_log($output);

	}
}
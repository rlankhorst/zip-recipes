<?php
/**
 * Created by PhpStorm.
 * User: gezimhoxha
 * Date: 15-05-25
 * Time: 12:59 AM
 */

namespace ZRDN;

class ZipRecipesUtil {
	/* Send debug code to the Javascript console */
	public static function zrdn_debug_to_console($data) {
		if(is_array($data) || is_object($data))
		{
			echo("<script>console.log('PHP: ".json_encode($data)."');</script>");
		} else {
			echo("<script>console.log('PHP: ".$data."');</script>");
		}
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
	 * @param $message Message to log.
	 */
	public static function log($message)
	{
		if (WP_DEBUG)
		{
			error_log("ZRDN: " . $message);
		}
	}
}
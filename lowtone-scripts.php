<?php
/*
 * Plugin Name: Lowtone Scripts Library
 * Plugin URI: http://wordpress.lowtone.nl/scripts
 * Plugin Type: lib
 * Description: An interface for including scripts.
 * Version: 1.0
 * Author: Lowtone <info@lowtone.nl>
 * Author URI: http://lowtone.nl
 * License: http://wordpress.lowtone.nl/license
 */

namespace lowtone\scripts {

	use lowtone\content\packages\Package,
		lowtone\io\File,
		lowtone\Util;

	// Includes
	
	if (!include_once WP_PLUGIN_DIR . "/lowtone-content/lowtone-content.php") 
		return trigger_error("Lowtone Content plugin is required", E_USER_ERROR) && false;

	$__i = Package::init(array(
			Package::INIT_PACKAGES => array("lowtone"),
			Package::INIT_SUCCESS => function() {return true;}
		));

	/**
	 * Register scripts.
	 * @param string|array $path Either the path for a file to register a single
	 * script or the path for a directory to register all scripts inside.
	 * @param array $dependencies The dependencies for the registered scripts. 
	 * If $path is a directory $dependencies could provide dependencies per file 
	 * using filenames as keys and a lists of individual dependencies as values.
	 * @param boolean $version The version for the registered scripts. Like 
	 * $dependencies versions can be defined per file by providing an array with 
	 * filenames as keys.
	 * @param boolean $footer Whether the scripts should be included in the 
	 * footer. Like $dependencies and $version the footer setting can be defined 
	 * per file by providing an array with filenames as keys.
	 * @return bool|array|NULL When a single file is registered TRUE is returned
	 * on success or FALSE on failure. When multiple files are registered list 
	 * of handles for successfully registered scripts is returned. If nothing 
	 * happened (bad input) NULL is returned.
	 */
	function register($path, $dependencies = array(), $version = false, $footer = false) {
		$result = NULL;

		// If path is a directory register all scripts inside

		if (is_dir($path)) {
			$result = array();

			$perFileDependencies = is_array(reset($dependencies));
			$perFileVersion = is_array($version);
			$perFileFooter = is_array($footer);

			foreach (glob($path . "/*.js") as $file) {
				if (!is_file($file))
					continue;

				$__filename = NULL;

				/**
				 * Determine filename once.
				 * @return string Returns the filename for the current file.
				 */
				$filename = function() use ($file, &$__filename) {
					return $__filename ?: ($__filename = basename(basename($file, ".js"), ".min"));
				};

				// Define file dependencies

				$fileDependencies = array();

				if ($perFileDependencies) {

					if (isset($dependencies[$filename()]))
						$fileDependencies = $dependencies[$filename()];

				} else
					$fileDependencies = $dependencies;

				// Define file version
				
				$fileVersion = false;

				if ($perFileVersion) {

					if (isset($version[$filename()]))
						$fileVersion = $version[$filename()];

				} else
					$fileVersion = $version;

				// Define file in footer
				
				$fileFooter = false;

				if ($perFileFooter) {

					if (isset($footer[$filename()]))
						$fileFooter = $footer[$filename()];

				} else
					$fileFooter = $footer;

				if (false === ($handle = register($file, $fileDependencies, $fileVersion, $fileFooter)))
					continue;
				
				$result[] = $handle;
				
			}

			$result = array_unique($result);

		} 

		// Register script if path is a file

		else if (is_file($path)) {
			$relPath = File::relPath($path);

			$url = site_url(preg_replace("#[/\\\\]+#", "/", $relPath));

			$filename = basename($path, ".js");

			if (".min" == substr($filename, -4)) {
				$filename = basename($filename, ".min");

				if (Util::isScriptDebug() && wp_script_is($filename, "registered"))
					return false;

			}

			wp_register_script($filename, $url, $dependencies, $version, $footer);

			$result = $filename;
		}

		return $result;
	}
	
}
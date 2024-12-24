<?php

namespace Valet\Drivers\Custom;

use Valet\Drivers\ValetDriver;

class WordPressMultisiteSubdirectoryValetDriver extends ValetDriver
{

	public $wp_root = false; // "wp"

	/**
	 * Determine if the driver serves the request.
	 * Specifically we are looking to see if the wp-config file includes the 'SUBDOMAIN_INSTALL' constant and that its value is false
	 */
	public function serves(string $sitePath, string $siteName, string $uri): bool
	{
		$filePath = $sitePath . '/wp-config.php';
		$constantName = 'SUBDOMAIN_INSTALL';
		$expectedValue = false;

		if (file_exists($filePath)) {
			$isMatch = $this->checkConstantValue($filePath, $constantName, $expectedValue);
			if ( $isMatch ){
				return true;
			}
		}

		return false;
	}


	/**
	 * Determine if the incoming request is for a static file (such as an image or stylesheet).
	 *
	 * @param string $sitePath
	 * @param string $siteName
	 * @param string $uri
	 * @return string|false
	 */
	public function isStaticFile(string $sitePath, string $siteName, string $uri)
	{
		// If the URI contains one of the main WordPress directories and it doesn't end with a slash,
		// drop the subdirectory from the URI and check if the file exists. If it does, return the new uri.
		if ( stripos($uri, 'wp-admin') !== false || stripos($uri, 'wp-content') !== false || stripos($uri, 'wp-includes') !== false ) {
			if ( substr($uri, -1, 1) == "/" ) return false;

				$new_uri = substr($uri, stripos($uri, '/wp-') );

			if ( $this->wp_root !== false && file_exists($sitePath . "/{$this->wp_root}/wp-admin") ) {
				$new_uri = "/{$this->wp_root}" . $new_uri;
			}

				if ( file_exists( $sitePath . $new_uri ) ) {
					return $sitePath . $new_uri;
				}
			}

		return false;
	}


	/**
	 * Get the fully resolved path to the application's front controller.
	 */
	public function frontControllerPath(string $sitePath, string $siteName, string $uri): string
	{
		//return $sitePath.'/index.php';

		$_SERVER['PHP_SELF']    = $uri;
		$_SERVER['SERVER_ADDR'] = '127.0.0.1';
		$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];

		// If URI contains one of the main WordPress directories, and it's not a request for the Network Admin,
		// drop the subdirectory segment before routing the request
		if ( ( stripos($uri, 'wp-admin') !== false || stripos($uri, 'wp-content') !== false || stripos($uri, 'wp-includes') !== false ) ) {

			if ( stripos($uri, 'wp-admin/network') === false ) {
				$uri = substr($uri, stripos($uri, '/wp-') );
			}

			if ( $this->wp_root !== false && file_exists($sitePath . "/{$this->wp_root}/wp-admin") ) {
				$uri = "/{$this->wp_root}" . $uri;
			}
		}

		// Handle wp-cron.php properly
		if ( stripos($uri, 'wp-cron.php') !== false ) {
			$new_uri = substr($uri, stripos($uri, '/wp-') );

			if ( file_exists( $sitePath . $new_uri ) ) {
				return $sitePath . $new_uri;
			}
		}

		return $sitePath.'/index.php';
	}


	/**
	 * Check if a constant in a PHP file is defined and has a specific value.
	 *
	 * @param string $filePath The path to the PHP file.
	 * @param string $constantName The name of the constant to check.
	 * @param mixed $expectedValue The value to compare against.
	 * @return bool True if the constant is defined and matches the expected value; otherwise, false.
	 */
	public function checkConstantValue($filePath, $constantName, $expectedValue)
	{

		// Include the file in an isolated scope
		ob_start();
		try {
			include $filePath;
		} finally {
			ob_end_clean(); // Suppress any output from the file
		}

		// Check if the constant is defined and matches the expected value
		return defined($constantName) && constant($constantName) === $expectedValue;
	}

}

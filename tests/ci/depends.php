#!/usr/bin/env php
<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

$opcodeCache = getenv('OPCODE_CACHE');
$db = getenv('DB');

if ($opcodeCache == 'apc' && PHP_VERSION_ID < 50500) {
	PhpExtensions::install('apc');
} elseif ($opcodeCache == 'xcache') {
	PhpExtensions::install('xcache');
}
if ($db == 'mongodb') {
	PhpExtensions::install('mongo');
}

/**
 * Class to install native PHP extensions mainly
 * for preparing test runs.
 */
class PhpExtensions {

	/**
	 * Holds build, configure and install instructions for PHP extensions.
	 *
	 * @var array Extensions to build keyed by extension name.
	 */
	protected static $_extensions = array(
		'memcached' => array(
			'ini' => array(
				'extension=memcached.so'
			)
		),
		'apc' => array(
			'ini' => array(
				'extension=apc.so',
				'apc.enabled=1',
				'apc.enable_cli=1'
			)
		),
		'xcache' => array(
			'require' => array(
				'php' => array('<', '5.4')
			),
			'build' => array(
				'url' => 'http://xcache.lighttpd.net/pub/Releases/1.3.2/xcache-1.3.2.tar.gz',
				'configure' => array('--enable-xcache'),
			),
			'ini' => array(
				'extension=xcache.so',
				'xcache.cacher=false',
				'xcache.admin.enable_auth=0',
				'xcache.var_size=1M'
			)
		),
		'mongo' => array(
			'ini' => array(
				'extension=mongo.so'
			)
		)
	);

	/**
	 * Install extension by given name.
	 *
	 * Uses configration retrieved as per `php_ini_loaded_file()`.
	 *
	 * @see http://php.net/php_ini_loaded_file
	 * @param string $name The name of the extension to install.
	 * @return void
	 */
	public static function install($name) {
		if (!isset(static::$_extensions[$name])) {
			return;
		}
		$extension = static::$_extensions[$name];
		echo $name;

		if (isset($extension['require']['php'])) {
			$version = $extension['require']['php'];

			if (!version_compare(PHP_VERSION, $version[1], $version[0])) {
				$message = " => not installed, requires a PHP version %s %s (%s installed)\n";
				printf($message, $version[0], $version[1], PHP_VERSION);
				return;
			}
		}
		if (isset($extension['build'])) {
			static::_system(sprintf('wget %s > /dev/null 2>&1', $extension['build']['url']));
			$file = basename($extension['build']['url']);

			static::_system(sprintf('tar -xzf %s > /dev/null 2>&1', $file));
			$folder = basename($file, '.tgz');
			$folder = basename($folder, '.tar.gz');

			$message  = 'sh -c "cd %s && phpize && ./configure %s ';
			$message .= '&& make && sudo make install" > /dev/null 2>&1';
			static::_system(sprintf($message, $folder, implode(' ', $extension['build']['configure'])));
		}

		foreach ($extension['ini'] as $ini) {
			static::_system(sprintf("echo %s >> %s", $ini, php_ini_loaded_file()));
		}
		printf("=> installed (%s)\n", $name);
	}

	/**
	 * Executes given command, reports and exits in case it fails.
	 *
	 * @param string $command The command to execute.
	 * @return void
	 */
	protected static function _system($command) {
		$return = 0;
		system($command, $return);

		if (0 !== $return) {
			printf("=> Command '%s' failed !", $command);
			exit($return);
		}
	}
}

?>
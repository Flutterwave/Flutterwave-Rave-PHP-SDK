<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

/**
 * This autoloader is only added to help load classes from the Rave namespace.
 * It is not required if you installed the Rave package in your project using composer.
 * It is used only for this example.
 */
// spl_autoload_register(function($class) {
// 	if (preg_match("/^Rave\\\(.+)$/", $class, $matches)) {
// 		$raveDir = dirname(__DIR__).'/src/Rave';
// 		$file = realpath("{$raveDir}/{$matches[1]}.php");

// 		if ($file && is_file($file)) {
// 			require_once $file;
// 		}
// 	}
// });

use Rave\Rave;

$rave = Rave::init()->run();

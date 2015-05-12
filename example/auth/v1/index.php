<?php
	error_reporting(0);
	error_reporting(E_ALL ^ E_NOTICE);
	ini_set('display_errors','On');

	// See http://www.slimframework.com/news/how-to-organize-a-large-slim-framework-application
	require_once('../vendor/autoload.php');

	// We will need to switch to production at some point...
	$options = [
		"mode" => "development",
	];
	$app = new \Slim\Slim($options);

	// Options and functionality tweaks
	require_once('config.php');

	$app->contentType('application/json'); // Shortcut to response->headers->set('Content-Type', $type);
	require_once('routes/user.php');
	$app->run();
?>
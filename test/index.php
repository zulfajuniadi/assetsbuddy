<?php

require_once __DIR__ . '/../vendor/autoload.php';

use AssetsBuddy\AssetsBuddy as AssetsBuddy;

AssetsBuddy::configure(array(
	"baseUrl" => "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}/generated/assets/",
	"assetDirectory" => "generated/assets",
	"cacheDirectory" => "generated/cache"
));

// Enqueue Javascript
AssetsBuddy::enqueue('minifier/js/jquery.js',
					 'minifier/js/handlebars.js',
					 'minifier/js/minifier.js');

// Enqueue CSS
AssetsBuddy::enqueue('minifier/css/bootstrap.css',
					 'minifier/css/minifier.css');

// Enqueue Template
AssetsBuddy::enqueue('minifier/tmpl/minifier.tmpl');

// Render assets tag
$js   = AssetsBuddy::render('js','minifyexample');
$css  = AssetsBuddy::render('css','minifyexample');
$tmpl = AssetsBuddy::render('tmpl','minifyexample');

include_once( __DIR__ . '/minifier/app/minifier.php' );
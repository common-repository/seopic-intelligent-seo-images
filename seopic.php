<?php

/**
 * Plugin Name: Seopic
 * Plugin URI: https://randbe.com/seopic-intelligent-seo-images-for-wordpress/
 * Description: Intelligent plugin which helps you automate image file renaming for better SEO.
 * Version: 1.0.0
 * Author: randbe
 * Author URI:  https://randbe.com/
 * Text Domain: seopic
 * Requires at least: 5.4
 * Requires PHP: 7.0
 */

defined('ABSPATH') || exit;

/**
 * Defining some global variables used all over the plugin.
 * @var string $seopic_image_size : this variable store current image size to be used after new image source created
 * @var int $seopic_all_media_count : this variable store all media number that will be renamed
 */
$seopic_image_size = "";
$seopic_all_media_count = 0;
$seopic_plugin_url = plugin_dir_url(__FILE__);

// Load files with functions of plugin.
require __DIR__ . "/includes/process-media.php";
require __DIR__ . "/includes/simple-html-dom.php";
require __DIR__ . "/includes/post-content.php";
require __DIR__ . "/includes/featured-image.php";
require __DIR__ . "/includes/helpers.php";
require __DIR__ . "/includes/assets.php";
require __DIR__ . "/includes/update-status.php";
require __DIR__ . "/includes/clear-media.php";

// Set up localisation.
load_plugin_textdomain("seopic", false, dirname(plugin_basename(__FILE__)) . '/languages');
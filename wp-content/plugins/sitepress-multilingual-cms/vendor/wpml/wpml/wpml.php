<?php

use WPML\CompositionRoot;
use WPML\Infrastructure\Dic;
use WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config\Config;
use WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config\Parser;
use WPML\UserInterface\Web\Infrastructure\WordPress\CompositionRoot\Config\AdminPage;
use WPML\UserInterface\Web\Infrastructure\WordPress\CompositionRoot\Config\Api;
use WPML\UserInterface\Web\Infrastructure\WordPress\CompositionRoot\Config\ConfigEvents;

if ( defined( 'WPML_VERSION' ) ) {
  // Already loaded.
  return;
}

require_once __DIR__ . '/src/constants.php';

$dic = new Dic();
$configArray = require_once __DIR__ . '/src/config.php';
$configParser = new Parser( $configArray );
$api = new Api();
$page = new AdminPage();

$compositionRoot = new CompositionRoot(
  $dic,
  new Config( $configParser, $dic, $api, $page ),
  new ConfigEvents( $dic )
);

// Load event listeners.
$compositionRoot->loadEventListeners();

// Admin Pages.
add_action(
  'admin_menu',
  function () use ( $compositionRoot ) {
      $compositionRoot->registerAdminPages();
  },
  1 // We must run this before legacy is doing the menu.
);

// REST Api.
add_action(
  'rest_api_init',
  function () use ( $compositionRoot ) {
      $compositionRoot->loadRESTEndpoints();
  }
);


// REST Api.
add_action(
  'admin_init',
  function () use ( $compositionRoot ) {
    $compositionRoot->loadAjaxEndpoints();
  }
);


// Scripts for admin.
add_action(
  'admin_enqueue_scripts',
  function () use ( $compositionRoot ) {
    $compositionRoot->loadAdminScripts();
  }
);

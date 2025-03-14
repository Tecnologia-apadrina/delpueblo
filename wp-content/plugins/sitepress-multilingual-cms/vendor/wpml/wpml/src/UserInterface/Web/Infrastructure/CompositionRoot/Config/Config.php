<?php

namespace WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config;

use WPML\ConfigInterface;
use WPML\DicInterface;
use WPML\PHP\Exception\Exception;
use WPML\UserInterface\Web\Core\Port\Script\ScriptDataProviderInterface;
use WPML\UserInterface\Web\Core\Port\Script\ScriptPrerequisitesInterface;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Endpoint\Endpoint;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Page;
use WPML\UserInterface\Web\Core\SharedKernel\Config\PageRequirementsInterface;

class Config implements ConfigInterface {

  /** @var Parser $parser */
  private $parser;

  /** @var DicInterface $dic */
  private $dic;

  /** @var ApiInterface $api */
  private $api;

  /** @var PageInterface $page */
  private $page;


  public function __construct(
    Parser $config,
    DicInterface $dic,
    ApiInterface $api,
    PageInterface $page
  ) {
    $this->parser = $config;
    $this->dic = $dic;
    $this->api = $api;
    $this->page = $page;
  }


  /** @return void */
  public function loadRESTEndpoints() {
    $config = $this->parser->parseAllRESTEndpoints();

    foreach ( $config->endpoints() as $endpoint ) {
      new EndpointLoader( $endpoint, $this->dic, $this->api );
    }
  }


  /** @return void */
  public function loadAjaxEndpoints() {
    $config = $this->parser->parseAllAjaxEndpoints();

    foreach ( $config->endpoints() as $endpoint ) {
      new EndpointLoader( $endpoint, $this->dic, $this->api );
    }
  }


  /** @return array<class-string,class-string> */
  public function getInterfaceMappings() {
    return $this->parser->parseInterfaceMappings();
  }


  /** @return array<class-string,array<string,string>> */
  public function getClassDefinitions() {
    return $this->parser->parseClassDefinitions();
  }


  /**
   * @throws Exception|\InvalidArgumentException
   * @return void
   */
  public function registerAdminPages() {
    $config = $this->parser->parseAdminPages();
    foreach ( $config->adminPages() as $adminPage ) {
      if ( $this->pageRequirementsMet( $adminPage ) ) {
        $this->page->register( $adminPage, [ $this, 'onLoadPage' ] );
      } else {
        // If requirements are not matched, remove the Page from config
        // to avoid registering Ajax & REST endpoints later.
        $this->parser->removeAdminPageConfig( $adminPage->id() );
      }
    }
  }


  /**
   * @throws Exception
   * @return void
   */
  public function loadAdminScripts() {
    $config = $this->parser->parseScripts();
    foreach ( $config->scripts() as $script ) {
      if ( ! $script->usedOnAdmin() ) {
        continue;
      }
      $script->onlyRegister()
        ? $this->page->registerScript( $script )
        : $this->page->loadScript( $script );
    }
  }


  /** @return void */
  public function onLoadPage( Page $page ) {
    $this->initPage( $page );
    $this->loadScripts( $page );
    $this->provideEndpoints( $page );
    $this->loadStyles( $page );
  }


  /** @return ?object */
  private function initPage( Page $page ) {
    $controllerClassName = $page->controllerClassName();
    if ( ! $controllerClassName ) {
      // Nothing to init.
      return null;
    }

    $controller = $this->dic->make( $controllerClassName );
    $page->setController( $controller );

    return $controller;
  }


  /** @return void */
  private function loadStyles( Page $page ) {
    foreach ( $page->styles() as $style ) {
      $this->page->loadStyle( $style );
    }
  }


  /** @return void */
  private function loadScripts( Page $page ) {
    foreach ( $page->scripts() as $script ) {
      if ( $scriptPrerequisitesClass = $script->prerequisites() ) {
        /** @var ScriptPrerequisitesInterface $ */
        $scriptPrerequisites = $this->dic->make( $scriptPrerequisitesClass );

        if ( ! $scriptPrerequisites instanceof ScriptPrerequisitesInterface ) {
          throw new \InvalidArgumentException(
            'Invalid script prerequisites. It must implement ' .
            ScriptPrerequisitesInterface::class
          );
        }

        if ( ! $scriptPrerequisites->scriptPrerequisitesMet() ) {
          // Skip loading the script as prerequisites are not met.
          continue;
        }
      }

      $this->page->loadScript( $script );

      // Check if there is a dataProvider defined in the config.
      if ( $dataProviderClass = $script->dataProvider() ) {
        /** @var ScriptDataProviderInterface $dataProvider */
        $dataProvider = $this->dic->make( $dataProviderClass );

        if ( ! $dataProvider instanceof ScriptDataProviderInterface ) {
          throw new \InvalidArgumentException(
            'Invalid data provider. It must implement ' .
                    ScriptDataProviderInterface::class
          );
        }

        $this->page->provideDataForScript(
          $script,
          $dataProvider->jsWindowKey(),
          $dataProvider->initialScriptData()
        );
      }
    }
  }


  /** @return void */
  private function provideEndpoints( Page $page ) {
    // Provide endpoints in wpmlEndpoints.
    $wpmlEndpoints = [
      'route' => [],
      'nonce' => $this->api->nonce()
    ];

    $config = $this->parser->parseGeneralEndpoints();
    /** @var array<Endpoint> $endpoints */
    $endpoints = array_merge(
      $config->endpoints(),
      $page->endpoints()
    );

    foreach ( $endpoints as $endpoint ) {
      $endpointToArray = [];
      $endpointToArray['url'] = $this->api->getFullUrl( $endpoint );
      $wpmlEndpoints['route'][ $endpoint->id() ] = $endpointToArray;
    }

    if ( count( $wpmlEndpoints['route'] ) === 0 ) {
      return;
    }

    $this->page->provideDataForPage(
      $page,
      'wpmlEndpoints',
      $wpmlEndpoints
    );
  }


  /**
   * @throws \InvalidArgumentException
   */
  private function pageRequirementsMet( Page $page ): bool {
    if ( $requirementsClassName = $page->requirementsClassName() ) {
      $requirements = $this->dic->make( $requirementsClassName );

      if ( ! $requirements instanceof PageRequirementsInterface ) {
        throw new \InvalidArgumentException(
          'Invalid Page Requirements Class. It must implements ' . PageRequirementsInterface::class
        );
      }

      return $requirements->requirementsMet();
    }

    return true;
  }


}

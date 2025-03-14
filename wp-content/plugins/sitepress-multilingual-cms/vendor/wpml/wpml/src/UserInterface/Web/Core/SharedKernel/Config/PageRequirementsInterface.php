<?php

namespace WPML\UserInterface\Web\Core\SharedKernel\Config;

/**
* Interface PageRequirementsInterface
*
* If the 'controller' of a page implements this interface, the controller will
* can do some extra checks before the page is loaded.
*/
interface PageRequirementsInterface {


  /** @return bool */
  public function requirementsMet();


}

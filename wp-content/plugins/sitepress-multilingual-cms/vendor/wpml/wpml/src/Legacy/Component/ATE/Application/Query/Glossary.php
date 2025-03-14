<?php

namespace WPML\Legacy\Component\ATE\Application\Query;

use WPML\Core\Component\ATE\Application\Query\GlossaryException;
use WPML\Core\Component\ATE\Application\Query\GlossaryInterface;

class Glossary implements GlossaryInterface
{


    /**
     * @inheritDoc
     */
  public function getGlossaryCount(): int {
      $glossaryApi = \WPML\Container\make( \WPML\TM\API\ATE\Glossary::class );

      $apiResponse = $glossaryApi->getGlossaryCount();

    /**
     * @psalm-suppress MissingClosureReturnType
     * @psalm-suppress MissingClosureParamType
     */
      $errorHandler = function ( $error ) {
          throw new GlossaryException(
            $error['error'] ?? __( 'Error getting glossary data', 'wpml' )
          );
      };

      /**
       * @psalm-suppress MissingClosureReturnType
       * @psalm-suppress MissingClosureParamType
       */
      $identity = function ( array $result ) {
          return $result;
      };

      $apiResult = $apiResponse->bimap( $errorHandler, $identity )->getOrElse( [] );

      return $apiResult['glossary_entries_count'] ?? 0;
  }


}

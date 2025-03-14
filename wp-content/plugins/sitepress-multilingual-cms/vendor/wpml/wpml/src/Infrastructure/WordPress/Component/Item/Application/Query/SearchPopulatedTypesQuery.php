<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Application\Query;

use WPML\Core\Component\Post\Application\Query\Criteria\SearchPopulatedTypesCriteria as SearchCriteria;
use WPML\Core\Component\Post\Application\Query\SearchPopulatedTypesQueryInterface;
use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;
use WPML\Core\Port\Persistence\QueryHandlerInterface;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\Builder\SearchPopulatedTypesCriteriaQueryBuilder;

class SearchPopulatedTypesQuery implements SearchPopulatedTypesQueryInterface {

  /** @var SearchPopulatedTypesCriteriaQueryBuilder $queryBuilder */
  private $queryBuilder;

  /** @var QueryHandlerInterface<int, string> $queryHandler */
  private $queryHandler;


  /**
   *
   * @param SearchPopulatedTypesCriteriaQueryBuilder $queryBuilder
   * @param QueryHandlerInterface<int, string> $queryHandler
   */
  public function __construct(
    SearchPopulatedTypesCriteriaQueryBuilder $queryBuilder,
    QueryHandlerInterface $queryHandler
  ) {
    $this->queryBuilder = $queryBuilder;
    $this->queryHandler = $queryHandler;
  }


  /**
   * @throws DatabaseErrorException
   */
  public function get( SearchCriteria $criteria ): array {
    // We're going to run the query, per post type.
    $postTypes = $criteria->getPostTypeIds();
    foreach ( $postTypes as $postTypeIndex => $postType ) {
      $query = $this->queryBuilder->build( $criteria, $postType );
      if ( ! $this->queryHandler->querySingle( $query ) ) {
        unset( $postTypes[ $postTypeIndex ] );
      }
    }

    return $postTypes;
  }


}

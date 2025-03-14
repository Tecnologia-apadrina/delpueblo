<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPosts;

use WPML\Core\Component\Post\Application\Query\Criteria\SearchCriteria;
use WPML\Core\Component\Post\Application\Query\SearchQueryInterface;
use WPML\Core\Port\Endpoint\EndpointInterface;
use WPML\PHP\Exception\Exception;
use WPML\PHP\Exception\InvalidArgumentException;


class GetPostsCountController implements EndpointInterface {

  /** @var SearchQueryInterface */
  private $findBySearchCriteriaQuery;


  public function __construct(
    SearchQueryInterface $findBySearchCriteriaQueryInterface
  ) {
    $this->findBySearchCriteriaQuery = $findBySearchCriteriaQueryInterface;
  }


  /**
   * @param array<string,mixed> $requestData
   *
   * @return array<string, int>
   * @throws InvalidArgumentException The requestData was not valid.
   *
   * @throws Exception Some system related error.
   */
  public function handle( $requestData = null ): array {
    $requestData = $requestData ?: [];

    try {
      $criteria = SearchCriteria::fromArray( $requestData );

      return [
        'totalCount' => $this->findBySearchCriteriaQuery->count( $criteria )
      ];
    } catch ( InvalidArgumentException $e ) {
      throw new InvalidArgumentException(
        'The request data for GetPostsCount is not valid.'
      );
    }
  }


}

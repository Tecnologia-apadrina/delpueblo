<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetPopulatedItemSections;

use WPML\Core\Component\Post\Application\Query\Criteria\SearchPopulatedTypesCriteria;
use WPML\Core\Component\Post\Application\Query\SearchPopulatedTypesQueryInterface;
use WPML\Core\Port\Endpoint\EndpointInterface;
use WPML\PHP\Exception\Exception;
use WPML\PHP\Exception\InvalidArgumentException;

/**
 * @phpstan-type RequestData=array{
 *   itemSectionIds: string[],
 *   sourceLanguageCode: string,
 *   targetLanguageCode?: string,
 *   translationStatuses?: string[],
 *   publicationStatus?: string,
 *  }
 */
class GetPopulatedItemSectionsController implements EndpointInterface {

  /**
   * @var SearchPopulatedTypesQueryInterface
   */
  private $searchPopulatedTypesQuery;

  /**
   * @var PopulatedItemSectionsFilterInterface
   */
  private $populatedItemSectionsFilter;


  public function __construct(
    SearchPopulatedTypesQueryInterface $searchPopulatedTypesQuery,
    PopulatedItemSectionsFilterInterface $populatedItemSectionsFilter
  ) {
    $this->searchPopulatedTypesQuery = $searchPopulatedTypesQuery;
    $this->populatedItemSectionsFilter = $populatedItemSectionsFilter;
  }


  /**
   * @param array<string,mixed> $requestData
   *
   * @return array<string,mixed>
   * @throws InvalidArgumentException|Exception
   */
  public function handle( $requestData = null ): array {

    $requestData = $this->validateRequestData( $requestData ?? [] );

    $globalFilters = SearchPopulatedTypesCriteria::fromArray( $requestData );

    $itemSectionIds = $requestData['itemSectionIds'];

    $populatedPostItems = $this->searchPopulatedTypesQuery->get( $globalFilters );

    foreach ( $itemSectionIds as $key => $itemSectionId ) {
      if (
        strpos( $itemSectionId, 'post/' ) === 0 &&
        ! in_array( str_replace( 'post/', '', $itemSectionId ), $populatedPostItems )
      ) {
        // If it's a post type, and not populated, remove it.
        unset( $itemSectionIds[ $key ] );
      }
    }

    $itemSectionIds = array_values( $itemSectionIds );

    $itemSectionIds = $this->populatedItemSectionsFilter->filter( $itemSectionIds, $globalFilters );

    return [
      'itemSectionIds'      => $itemSectionIds,
    ];
  }


  /**
   * @param array<string,mixed> $requestData
   *
   * @return RequestData
   * @throws InvalidArgumentException
   */
  private function validateRequestData( array $requestData ): array {
    if (
      ! isset( $requestData['itemSectionIds'] ) ||
      ! is_array( $requestData['itemSectionIds'] )
    ) {
      throw new InvalidArgumentException( 'itemSectionIds is required' );
    }
    if ( ! isset( $requestData['sourceLanguageCode'] ) ||
      ! is_string( $requestData['sourceLanguageCode'] )
    ) {
      throw new InvalidArgumentException( 'sourceLanguageCode is required' );
    }

    /**
     * @var RequestData $requestData
     */
    return $requestData;
  }


}

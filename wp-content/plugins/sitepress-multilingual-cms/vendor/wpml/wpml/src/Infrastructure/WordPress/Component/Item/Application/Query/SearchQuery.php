<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Application\Query;

use WPML\Core\Component\Post\Application\Query\Criteria\SearchCriteria;
use WPML\Core\Component\Post\Application\Query\SearchQueryInterface;
use WPML\Core\Port\Persistence\Exception\DatabaseErrorException;
use WPML\Core\Port\Persistence\QueryHandlerInterface;
use WPML\Core\Port\Persistence\ResultCollection;
use WPML\Core\Port\Persistence\ResultCollectionInterface;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\Builder\SearchCriteriaQueryBuilder;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\Mapper\ItemWithTranslationStatusDtoMapper;
use WPML\PHP\Exception\InvalidArgumentException;

/**
 * @phpstan-import-type RawData from ItemWithTranslationStatusDtoMapper
 * @phpstan-type SearchQueryAggregatedJob array{
 *    rid:int,
 *    job_id:string,
 *    automatic:string,
 *    translation_service:string,
 *    review_status:string,
 *    editor:string,
 *    translator_id:string
 *  }
 */
class SearchQuery implements SearchQueryInterface {

  /** @var SearchCriteriaQueryBuilder $queryBuilder */
  private $queryBuilder;

  /** @var QueryHandlerInterface<int, array<string,mixed>> $queryHandler */
  private $queryHandler;

  /** @var ItemWithTranslationStatusDtoMapper $mapper */
  private $mapper;


  /**
   *
   * @param SearchCriteriaQueryBuilder                $queryBuilder
   * @param QueryHandlerInterface<int, array<string,mixed>> $queryHandler
   */
  public function __construct(
    SearchCriteriaQueryBuilder $queryBuilder,
    QueryHandlerInterface $queryHandler,
    ItemWithTranslationStatusDtoMapper $mapper
  ) {
    $this->queryBuilder = $queryBuilder;
    $this->queryHandler = $queryHandler;
    $this->mapper       = $mapper;
  }


  /**
   * @throws DatabaseErrorException
   * @throws InvalidArgumentException
   */
  public function get( SearchCriteria $criteria ) {
    $query = $this->queryBuilder->build( $criteria );

    /** @var ResultCollectionInterface<int, RawData> $posts */
    $posts = $this->queryHandler->query( $query );

    $jobsQuery = $this->queryBuilder->buildJobsQuery( $posts );
    if ( ! $jobsQuery ) {
      // There are no jobs to map.
      return $this->mapper->mapCollection( $posts, new ResultCollection( [] ) );
    }

    /** @var ResultCollectionInterface<int, SearchQueryAggregatedJob> $jobs */
    $jobs = $this->queryHandler->query( $jobsQuery );
    return $this->mapper->mapCollection( $posts, $jobs );
  }


  /**
   * @throws DatabaseErrorException
   */
  public function count( SearchCriteria $criteria ): int {
    $query = $this->queryBuilder->buildCount( $criteria );

    /** @var string $count */
    $count = $this->queryHandler->querySingle( $query );

    return (int) $count;
  }


}

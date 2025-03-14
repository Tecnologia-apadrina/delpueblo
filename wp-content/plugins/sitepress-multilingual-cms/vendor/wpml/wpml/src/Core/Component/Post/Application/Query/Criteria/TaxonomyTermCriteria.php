<?php

namespace WPML\Core\Component\Post\Application\Query\Criteria;

use WPML\PHP\ConstructableFromArrayInterface;
use WPML\PHP\ConstructableFromArrayTrait;

/**
 * @implements ConstructableFromArrayInterface<TaxonomyTermCriteria>
 */
final class TaxonomyTermCriteria implements ConstructableFromArrayInterface {

  /** @use ConstructableFromArrayTrait<TaxonomyTermCriteria> */
  use ConstructableFromArrayTrait;

  /**
   * @var string
   */
  private $taxonomyId;

  /**
   * @var string|null
   */
  private $search;

  /**
   * @var int|null
   */
  private $limit;

  /**
   * @var int|null
   */
  private $offset;


  /**
   * @param string $taxonomyId
   * @param string|null $search
   * @param int|null $limit
   * @param int|null $offset
   */
  public function __construct(
    string $taxonomyId,
    string $search = null,
    int $limit = null,
    int $offset = null
  ) {
    $this->taxonomyId = $taxonomyId;
    $this->search = $search;
    $this->limit = $limit;
    $this->offset = $offset;
  }


  public function getTaxonomyId(): string {
    return $this->taxonomyId;
  }


  public function getSearch(): ?string {
    return $this->search;
  }


  public function getLimit(): ?int {
    return $this->limit;
  }


  public function getOffset(): ?int {
    return $this->offset;
  }


}

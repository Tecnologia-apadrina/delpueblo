<?php

namespace WPML\Core\Component\Post\Application\Query\Criteria;

use WPML\PHP\ConstructableFromArrayInterface;
use WPML\PHP\ConstructableFromArrayTrait;

/**
 * @implements ConstructableFromArrayInterface<SearchPopulatedTypesCriteria>
 */
final class SearchPopulatedTypesCriteria implements ConstructableFromArrayInterface {
  /** @use ConstructableFromArrayTrait<SearchPopulatedTypesCriteria> */
  use ConstructableFromArrayTrait;

  /**
   * @var string[]
   */
  private $itemSectionIds = [];

  /** @var string|null */
  private $publicationStatus;

  /** @var string */
  private $sourceLanguageCode;

  /** @var string|null */
  private $targetLanguageCode;

  /** @var int[] */
  private $translationStatuses = [];


  /**
   * @param string $sourceLanguageCode
   * @param array<string> $itemSectionIds
   * @param string|null $publicationStatus
   * @param string|null $targetLanguageCode
   * @param array<int> $translationStatuses
   */
  public function __construct(
    string $sourceLanguageCode,
    array $itemSectionIds = [],
    string $publicationStatus = null,
    string $targetLanguageCode = null,
    array $translationStatuses = []
  ) {
    $this->sourceLanguageCode  = $sourceLanguageCode;
    $this->itemSectionIds     = $itemSectionIds;
    $this->publicationStatus   = $publicationStatus;
    $this->targetLanguageCode  = $targetLanguageCode;
    $this->translationStatuses = $translationStatuses;
  }


  /** @return ?string */
  public function getPublicationStatus() {
    return $this->publicationStatus;
  }


  /** @return ?string */
  public function getSourceLanguageCode() {
    return $this->sourceLanguageCode;
  }


  /** @return ?string */
  public function getTargetLanguageCode() {
    return $this->targetLanguageCode;
  }


  /** @return int[] */
  public function getTranslationStatuses() {
    return $this->translationStatuses;
  }


  /** @return string[] */
  public function getItemSectionIds() {
    return $this->itemSectionIds;
  }


  /**
   * @return string[]
   */
  public function getPostTypeIds(): array {
    return array_map(
      function ( $itemSectionId ) {
        return str_replace( 'post/', '', $itemSectionId );
      },
      array_filter(
        $this->itemSectionIds,
        function ( $itemSectionId ) {
          return strpos( $itemSectionId, 'post/' ) === 0;
        }
      )
    );
  }


}

<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Application\Query\Builder;

use WPML\Core\Component\Post\Application\Query\Criteria\SearchPopulatedTypesCriteria as SearchCriteria;
use WPML\Core\Port\Persistence\QueryPrepareInterface;
use WPML\Core\SharedKernel\Component\Language\Application\Query\Dto\LanguageDto;
use WPML\Core\SharedKernel\Component\Language\Application\Query\LanguagesQueryInterface;

class SearchPopulatedTypesCriteriaQueryBuilder {
  use SearchQueryBuilderTrait;

  /** @var QueryPrepareInterface $queryPrepare */
  private $queryPrepare;

  /** @var LanguagesQueryInterface */
  private $languagesQuery;


  public function __construct(
    QueryPrepareInterface $queryPrepare,
    LanguagesQueryInterface $languages
  ) {
    $this->queryPrepare = $queryPrepare;
    $this->languagesQuery = $languages;
  }


  public function build( SearchCriteria $criteria, string $postTypeId ): string {
    $sourceLanguage = $criteria->getSourceLanguageCode()
      ?: $this->languagesQuery->getDefaultCode();

    $languageCodes = $criteria->getTargetLanguageCode() ?
      [$criteria->getTargetLanguageCode()] :
      array_map(
        function( LanguageDto $languageDto ) {
          return $languageDto->getCode();
        },
        $this->languagesQuery->getSecondary( true, $sourceLanguage )
      );

    $escapedLanguageCodes = array_map(
      function( $languageCode ) {
        return $this->queryPrepare->prepare( $languageCode );
      },
      $languageCodes
    );

    $elementType = $this->queryPrepare->prepare( $postTypeId );
    $sql = "
      SELECT p.post_type 
      FROM {$this->queryPrepare->prefix()}posts p
      INNER JOIN {$this->queryPrepare->prefix()}icl_translations source_t
        ON source_t.element_id = p.ID
          AND source_t.element_type = CONCAT('post_', p.post_type)
          AND source_t.language_code = '{$sourceLanguage}'
      {$this->buildTargetLanguageJoins( $escapedLanguageCodes )}
      WHERE
          {$this->buildTranslationStatusConditionWrapper( $criteria, $escapedLanguageCodes )}
          {$this->buildPostStatusCondition( $criteria->getPublicationStatus() )} 
          AND p.post_type = '{$elementType}'
      LIMIT 0,1
    ";

    return $sql;
  }


  /**
   * @param array<string> $targetLanguageCodes
   * @return string
   */
  private function buildTargetLanguageJoins( array $targetLanguageCodes ): string {
    $joins = [];
    foreach ( $targetLanguageCodes as $languageCode ) {
      $slugLanguageCode =  $this->getLanguageJoinColumName( $languageCode );

      $joins[] = "
        LEFT JOIN {$this->queryPrepare->prefix()}icl_translations target_t_{$slugLanguageCode}
          ON target_t_{$slugLanguageCode}.trid = source_t.trid
              AND target_t_{$slugLanguageCode}.language_code = '{$languageCode}'
        LEFT JOIN {$this->queryPrepare->prefix()}icl_translation_status target_ts_{$slugLanguageCode}
          ON target_ts_{$slugLanguageCode}.translation_id = target_t_{$slugLanguageCode}.translation_id
      ";
    }
    return implode( ' ', $joins );
  }


  /**
   * @param SearchCriteria $criteria
   * @param array<string> $targetLanguageCodes
   * @return string
   */
  private function buildTranslationStatusConditionWrapper(
    SearchCriteria $criteria,
    array $targetLanguageCodes
  ): string {
    return $this->buildTranslationStatusCondition( $criteria, $targetLanguageCodes );
  }


}

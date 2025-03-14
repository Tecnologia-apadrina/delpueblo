<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Application\Query\Builder;

use WPML\Core\Component\Post\Application\Query\Criteria\SearchCriteria;
use WPML\Core\Port\Persistence\QueryPrepareInterface;
use WPML\Core\Port\Persistence\ResultCollectionInterface;
use WPML\Core\SharedKernel\Component\Language\Application\Query\Dto\LanguageDto;
use WPML\Core\SharedKernel\Component\Language\Application\Query\LanguagesQueryInterface;
use WPML\Infrastructure\WordPress\Component\Item\Application\Query\Mapper\ItemWithTranslationStatusDtoMapper;

/**
 * @phpstan-import-type RawData from ItemWithTranslationStatusDtoMapper
 */
class SearchCriteriaQueryBuilder {
  use SearchQueryBuilderTrait;

  const WORD_COUNT_META_KEY = '_wpml_word_count';
  const TRANSLATOR_NOTE_META_KEY = '_icl_translator_note';

  /** @var QueryPrepareInterface $queryPrepare */
  private $queryPrepare;

  /** @var LanguagesQueryInterface */
  private $languagesQuery;

  /** @var SortingCriteriaQueryBuilder */
  private $sortingQueryBuilder;

  const POST_COLUMNS = "
    p.ID,
    p.post_title,
    p.post_status,
    p.post_date,
    p.post_type
  ";


  public function __construct(
    QueryPrepareInterface $queryPrepare,
    LanguagesQueryInterface $languages,
    SortingCriteriaQueryBuilder $sortingQueryBuilder
  ) {
    $this->queryPrepare        = $queryPrepare;
    $this->languagesQuery      = $languages;
    $this->sortingQueryBuilder = $sortingQueryBuilder;
  }


  /**
   * @param string[] $languageCodes
   * @return string
   */
  private function getTranslationStatuesColumns( $languageCodes ): string {
    $columns = [];

    foreach ( $languageCodes as $languageCode ) {
      $slugLanguageCode = $this->getLanguageJoinColumName( $languageCode );
      $languageCode = $this->queryPrepare->prepare( $languageCode );

      $columns[] = "CONCAT(
        'languageCode:{$languageCode},status:',
        CASE
          WHEN target_ts_{$slugLanguageCode}.rid IS NULL
            AND target_t_{$slugLanguageCode}.source_language_code IS NOT NULL
          THEN 0
          WHEN target_ts_{$slugLanguageCode}.needs_update = 1 THEN 3
          WHEN target_t_{$slugLanguageCode}.trid IS NOT NULL  
            AND (
              target_t_{$slugLanguageCode}.source_language_code IS NULL 
                OR target_ts_{$slugLanguageCode}.status = 0
                OR target_ts_{$slugLanguageCode}.status IS NULL
            ) THEN 10
          ELSE IFNULL(target_ts_{$slugLanguageCode}.status, 0)
        END,
        ', reviewStatus:', 
        IFNULL(target_ts_{$slugLanguageCode}.review_status, ''),
        ', element_id:', 
        IFNULL(target_t_{$slugLanguageCode}.element_id, ''),
        ',rid:', 
        IFNULL(target_ts_{$slugLanguageCode}.rid, '')
      )";
    }
    return 'CONCAT(' . implode( ", '; ', ", $columns ) . ') AS translation_statuses';
  }


  /*
   *
   * This will generate a query in the following structure:
   * SELECT
      p.ID,
      p.post_title,
      p.post_status,
      p.post_date,
      IFNULL(meta_wc.meta_value, 0) AS word_count,
      meta_tn.meta_value  AS translator_note,
      CONCAT(CONCAT(
       'languageCode:fr,status:',
       CASE
        WHEN target_ts_fr.needs_update = 1 THEN 3
        WHEN target_t_fr.trid IS NOT NULL
        AND target_t_fr.element_id IS NOT NULL
        AND (
         target_t_fr.source_language_code IS NULL
          OR target_ts_fr.status = 0
         ) THEN 10
        ELSE IFNULL(target_ts_fr.status, 0)
        END,
       ', reviewStatus:',
       IFNULL(target_ts_fr.review_status, ''),
       ',rid:',
       IFNULL(target_ts_fr.rid, '')
      ), '; ', CONCAT(
       'languageCode:es,status:',
       CASE
        WHEN target_ts_es.needs_update = 1 THEN 3
        WHEN target_t_es.trid IS NOT NULL
        AND target_t_es.element_id IS NOT NULL
        AND (
         target_t_es.source_language_code IS NULL
          OR target_ts_es.status = 0
         ) THEN 10
        ELSE IFNULL(target_ts_es.status, 0)
        END,
       ', reviewStatus:',
     IFNULL(target_ts_es.review_status, ''),
     ',rid:',
     IFNULL(target_ts_es.rid, '')
      ))  AS translation_statuses
      FROM wp_posts p
       INNER JOIN wp_icl_translations source_t
        ON source_t.element_id = p.ID
         AND source_t.element_type = CONCAT('post_', p.post_type)
         AND source_t.language_code = 'en'
       LEFT JOIN wp_icl_translations target_t_fr
         ON target_t_fr.trid = source_t.trid
          AND target_t_fr.language_code = 'fr'
       LEFT JOIN wp_icl_translation_status target_ts_fr
         ON target_ts_fr.translation_id = target_t_fr.translation_id
       LEFT JOIN wp_icl_translations target_t_es
         ON target_t_es.trid = source_t.trid
          AND target_t_es.language_code = 'es'
       LEFT JOIN wp_icl_translation_status target_ts_es
         ON target_ts_es.translation_id = target_t_es.translation_id
       LEFT JOIN wp_postmeta meta_wc
         ON meta_wc.post_id = p.ID
          AND meta_wc.meta_key = '_wpml_word_count'
       LEFT JOIN wp_postmeta meta_tn
         ON meta_tn.post_id = p.ID
          AND meta_tn.meta_key = '_icl_translator_note'
      WHERE p.post_type = 'post'
      AND p.post_status NOT IN ('auto-draft', 'trash')
      AND ((
       target_t_fr.trid IS NULL
        OR (target_ts_fr.status = 0 AND target_t_fr.element_id IS NULL)
        OR target_ts_fr.needs_update = 1
        OR target_ts_fr.status IN (30, 1, 2)
       ) OR (
       target_t_es.trid IS NULL
        OR (target_ts_es.status = 0 AND target_t_es.element_id IS NULL)
        OR target_ts_es.needs_update = 1
        OR target_ts_es.status IN (30, 1, 2)
       ))
    ORDER BY p.post_date DESC
    LIMIT 26 OFFSET 0
   *
   *
   * @param SearchCriteria $criteria
   *
   * @return string
   */
  public function build( SearchCriteria $criteria ): string {
    return $this->buildQueryWithFields( $criteria );
  }


  /**
   * @param ResultCollectionInterface<int,RawData> $posts
   * @return string|null
   */
  public function buildJobsQuery( ResultCollectionInterface $posts ) {
    $rids = [];

    foreach ( $posts->getResults() as $postId => $post ) {
      $translationStatuses = $post['translation_statuses'];
      $languageStatuses = explode( ';', $translationStatuses );
      foreach ( $languageStatuses as $languageStatus ) {
        $rid = $this->getRidFromTranslationStatus( $languageStatus );
        if ( $rid ) {
          $rids[] = $rid;
        }
      }
    }

    $rids = array_unique( $rids );
    if ( empty( $rids ) ) {
      return null;
    }
    return "
  SELECT
    tj.rid,
    tj.job_id,
    tj.translator_id,
    ts.status,
    ts.review_status,
    ts.needs_update,
    tj.automatic,
    ts.translation_service,
    tj.editor
  FROM {$this->queryPrepare->prefix()}icl_translate_job tj
  LEFT JOIN {$this->queryPrepare->prefix()}icl_translation_status ts
        ON tj.rid = ts.rid
  INNER JOIN (
    SELECT
      rid,
      MAX(job_id) AS max_job_id
    FROM {$this->queryPrepare->prefix()}icl_translate_job
    WHERE rid IN (" . implode( ',', $rids ) . ")
    GROUP BY rid
  ) latest_jobs
  ON tj.rid = latest_jobs.rid
  AND tj.job_id = latest_jobs.max_job_id";
  }


  private function getRidFromTranslationStatus( string $translationStatus ): int {
    $matches = [];
    preg_match( '/rid:(\d+)/', $translationStatus, $matches );

    return count( $matches ) > 0 ? intval( $matches[1] ) : 0;
  }


  /**
   * @param array<string> $escapedLanguageCodes
   * @return string
   */
  private function getFields( array $escapedLanguageCodes ): string {
    $postColumns    = self::POST_COLUMNS;
    $translationStatusesColumns = $this->getTranslationStatuesColumns( $escapedLanguageCodes );

    return "
        {$postColumns},
        IFNULL(meta_wc.meta_value, 0) AS word_count,
        meta_tn.meta_value AS translator_note,
        {$translationStatusesColumns}
		";
  }


  private function buildQueryWithFields( SearchCriteria $criteria, bool $withPagination = true ): string {
    $sourceLanguage = $criteria->getSourceLanguageCode()
      ?: $this->languagesQuery->getDefaultCode();

    $escapedLanguageCodes = $this->getEscapedLanguageCodes( $criteria, $sourceLanguage );

    $fields = $this->getFields( $escapedLanguageCodes );

    $sql = "
      SELECT
          {$fields}
      FROM {$this->queryPrepare->prefix()}posts p
      INNER JOIN {$this->queryPrepare->prefix()}icl_translations source_t
      ON source_t.element_id = p.ID
        AND source_t.element_type = CONCAT('post_', p.post_type)
        AND source_t.language_code = '{$sourceLanguage}'
      {$this->buildTargetLanguageJoins( $escapedLanguageCodes )}
        
  
      LEFT JOIN {$this->queryPrepare->prefix()}postmeta meta_wc
        ON meta_wc.post_id = p.ID
        AND meta_wc.meta_key = '" . self::WORD_COUNT_META_KEY . "'
      LEFT JOIN {$this->queryPrepare->prefix()}postmeta meta_tn
        ON meta_tn.post_id = p.ID
        AND meta_tn.meta_key = '" . self::TRANSLATOR_NOTE_META_KEY . "'
      WHERE p.post_type = '{$criteria->getType()}'
          {$this->buildPostStatusCondition( $criteria->getPublicationStatus() )}
          {$this->buildPostTitleCondition( $criteria )}
          {$this->buildTaxonomyTermCondition( $criteria )}
          {$this->buildParentCondition( $criteria )}
          {$this->buildTranslationStatusConditionWrapper( $criteria, $escapedLanguageCodes )}
          {$this->buildSortingQueryPart( $criteria )}
    "; // @codingStandardsIgnoreEnd

    if ( $withPagination ) {
        $sql .= ' ' . $this->buildPagination( $criteria );
    }

    return $sql;
  }


  public function buildCount( SearchCriteria $criteria ): string {
    $sql = $this->buildQueryWithFields( $criteria, false );

    $sql = "
      SELECT COUNT(t.ID)
      FROM (
        $sql
      ) t
    ";

    return $sql;
  }


  private function buildSortingQueryPart( SearchCriteria $criteria ): string {
    return $this->sortingQueryBuilder->build( $criteria->getSortingCriteria() );
  }


  private function buildPostTitleCondition(
    SearchCriteria $criteria
  ): string {
    $searchKeyword = $this->queryPrepare->escLike( $criteria->getTitle() );
    if ( $searchKeyword !== '' ) {
      return $this->queryPrepare->prepare(
        'AND p.post_title LIKE %s',
        '%' . $searchKeyword . '%'
      );
    }

    return '';
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
    return 'AND ' . $this->buildTranslationStatusCondition( $criteria, $targetLanguageCodes );
  }


  private function buildTaxonomyTermCondition( SearchCriteria $criteria ): string {
    if ( ! $criteria->getTaxonomyId() || ! $criteria->getTermId() ) {
      return '';
    }

    $where = $this->queryPrepare->prepare(
      "WHERE tax.taxonomy = %s AND tax.term_id = %d",
      $criteria->getTaxonomyId(),
      $criteria->getTermId()
    );

    return " AND p.ID IN (
      SELECT object_id
      FROM {$this->queryPrepare->prefix()}term_relationships rel
      JOIN {$this->queryPrepare->prefix()}term_taxonomy tax " .
      "ON rel.term_taxonomy_id = tax.term_taxonomy_id
      $where
      ) ";
  }


  private function buildParentCondition(
    SearchCriteria $criteria
  ): string {
    if ( $criteria->getParentId() ) {
      return $this->queryPrepare->prepare(
        'AND p.post_parent = %d',
        $criteria->getParentId()
      );
    }

    return '';
  }


  private function buildPagination( SearchCriteria $criteria ): string {
    return $this->queryPrepare->prepare(
      'LIMIT %d OFFSET %d',
      $criteria->getLimit(),
      $criteria->getOffset()
    );
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
   * @param string $sourceLanguage
   * @return array|string[]
   */
  private function getEscapedLanguageCodes( SearchCriteria $criteria, string $sourceLanguage ): array {
    $languageCodes = $criteria->getTargetLanguageCode() ?
      [$criteria->getTargetLanguageCode()] :
      array_map(
        function ( LanguageDto $languageDto ) {
          return $languageDto->getCode();
        },
        $this->languagesQuery->getSecondary()
      );

    if ( $sourceLanguage !== $this->languagesQuery->getDefaultCode() ) {
      $languageCodes[] = $this->languagesQuery->getDefaultCode();
      $languageCodes = array_filter(
        $languageCodes,
        function ( $languageCode ) use ( $sourceLanguage ) {
          return $languageCode !== $sourceLanguage;
        }
      );
    }

    $languageCodes = array_unique( $languageCodes );

    $escapedLanguageCodes = array_map(
      function ( $languageCode ) {
        return $this->queryPrepare->prepare( $languageCode );
      },
      $languageCodes
    );
    return $escapedLanguageCodes;
  }


}

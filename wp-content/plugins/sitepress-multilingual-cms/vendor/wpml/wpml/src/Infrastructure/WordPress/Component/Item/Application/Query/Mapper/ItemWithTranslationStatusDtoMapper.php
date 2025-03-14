<?php

namespace WPML\Infrastructure\WordPress\Component\Item\Application\Query\Mapper;

use WPML\Core\Component\Post\Application\Query\Dto\PostWithTranslationStatusDto;
use WPML\Core\Component\Post\Application\Query\Dto\TranslationStatusDto;
use WPML\Core\Port\Persistence\ResultCollection;
use WPML\Core\Port\Persistence\ResultCollectionInterface;
use WPML\Core\SharedKernel\Component\Translation\Domain\ReviewStatus;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationEditorType;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationMethod\TargetLanguageMethodType;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationStatus;
use WPML\PHP\Exception\InvalidArgumentException;
use function WPML\PHP\array_keys_exists;


/**
 * @phpstan-import-type TargetLanguageMethodTypeValues from TargetLanguageMethodType
 * @phpstan-type SearchQueryAggregatedJob array{
 * rid:int,
 * job_id:string,
 * automatic:string,
 * translation_service:string,
 * review_status:string,
 * editor:string,
 * translator_id:string
 * }
 * @phpstan-type SearchQueryJobsArray array<int, SearchQueryAggregatedJob>
 * @phpstan-type RawData array{
 *    ID:int,
 *    post_title:string,
 *    post_status:string,
 *    post_date:string,
 *    post_type:string,
 *    translation_statuses:string,
 *    word_count:string,
 *    translator_note:string
 *   }
 */
class ItemWithTranslationStatusDtoMapper {

  const REQUIRED_RAW_KEYS = [
    'ID',
    'post_title',
    'post_status',
    'post_date',
    'post_type',
    'translation_statuses',
    'word_count',
    'translator_note'
  ];


  /**
   * @param RawData $rawData
   * @param array<int, SearchQueryAggregatedJob> $jobs
   *
   * @return PostWithTranslationStatusDto
   * @throws InvalidArgumentException One of the required keys is missing.
   *
   */
  public function mapSingle( array $rawData, array $jobs ) {
    if ( ! array_keys_exists( self::REQUIRED_RAW_KEYS, $rawData ) ) {
      throw new InvalidArgumentException(
        'Invalid raw data for ItemWithTranslationStatusDtoMapper.' .
        'Required keys: ' . implode( ', ', self::REQUIRED_RAW_KEYS )
      );
    }

    $translationStatuses = $this->mapTranslationStatuses( $rawData, $jobs );

    return new PostWithTranslationStatusDto(
      $rawData['ID'],
      $rawData['post_title'],
      $rawData['post_status'],
      $rawData['post_date'],
      $rawData['post_type'],
      $translationStatuses,
      is_numeric( $rawData['word_count'] ) ? (int) $rawData['word_count'] : null,
      $rawData['translator_note']
    );
  }


  /**
   * @param ResultCollectionInterface<int,RawData> $items
   * @param ResultCollectionInterface<int,SearchQueryAggregatedJob> $jobs
   *
   * @psalm-suppress ArgumentTypeCoercion Validation on mapSingle().
   *
   * @return ResultCollectionInterface<int,PostWithTranslationStatusDto>
   * @throws InvalidArgumentException
   */
  public function mapCollection( ResultCollectionInterface $items, ResultCollectionInterface $jobs ) {
    $mappedItems = [];

    foreach ( $items->getResults() as $rawData ) {
      $indexedJobs = [];
      foreach ( $jobs->getResults() as $job ) {
        $jobRID = intval( $job['rid'] );
        if ( $jobRID === 0 ) {
          continue;
        }
        $indexedJobs[ $jobRID ] = $job;
      }
      $mappedItems[] = $this->mapSingle( $rawData, $indexedJobs );
    }

    return new ResultCollection( $mappedItems );
  }


  /**
   * translation_statuses has the following format:
   * "languageCode:fr,status:0,reviewStatus:NULL,jobId:1,automatic:0,translationService:local,editor:none;
   *  languageCode:de,status:10,reviewStatus:NEEDS_REVIEW,jobId:2,automatic:1,translationService:local,editor:ate"
   *
   * Where the values represent:
   *  - languageCode: language code
   *  - status: translation status as integer
   *  - reviewStatus: NULL | NEEDS_REVIEW | EDITING | ACCEPTED
   *  - jobId: int | NULL
   *  - automatic: 1 | 0 | NULL
   *  - translationService: local, int, NULL
   *  - editor: wp, wpml, ate, none, NULL
   *  - element_id: int | NULL
   *
   *
   * @param RawData $rawData
   * @param SearchQueryJobsArray $jobs
   *
   * @return array<string, TranslationStatusDto>
   */
  private function mapTranslationStatuses( array $rawData, array $jobs ): array {

    $translationStatuses = [];
    foreach ( explode( ';', $rawData['translation_statuses'] ) as $row ) {
      if ( trim( $row ) === '' ) {
        continue;
      }

      $values = $this->getRowValues( $row, $jobs );

      $langCode = $values['languageCode'];
      $status = $this->mapStatus( $values );
      $reviewStatus = empty( $values['reviewStatus'] ) || $values['reviewStatus'] === 'NULL' ?
        null : ( new ReviewStatus( $values['reviewStatus'] ) )->getValue();
      $jobId = isset( $values['jobId'] ) && $values['jobId'] !== 'NULL' ?
              (int) $values['jobId'] : null;
      $translatorId = isset( $values['translatorId'] ) ? (int) $values['translatorId'] : null;
      $isTranslated = isset( $values['element_id'] ) && $values['element_id'] > 0;
      $automatic = isset( $values['automatic'] ) && $values['automatic'] !== 'NULL' && (int) $values['automatic'] > 0;
      $translationService = $values['translationService'] ?? 'NULL';
      $editor = $values['editor'] ?? null;

      $method = $this->getMethod( $status, $translationService, $automatic, $jobId );

      $editor = $this->parseEditor( $editor );

      // Construct the nested array
      $translationStatuses[ $langCode ] = new TranslationStatusDto(
        $status,
        $reviewStatus,
        $jobId,
        $method,
        $editor,
        $isTranslated,
        $translatorId
      );

    }

    return $translationStatuses;
  }


  /**
   * @param string|null $editor
   *
   * @return TranslationEditorType::*
   */
  private function parseEditor( string $editor = null ) {
    if ( ! $editor || $editor === 'NULL' ) {
      return TranslationEditorType::NONE;
    }

    if ( $editor === 'wp' ) {
      return TranslationEditorType::WORDPRESS;
    }

    if ( $editor === 'wpml' ) {
      return TranslationEditorType::CLASSIC;
    }

    if ( in_array( $editor, TranslationEditorType::getTypes(), true ) ) {
        return $editor;
    }

    return TranslationEditorType::NONE;
  }


  /**
   * @param int $status
   * @param string $translationService
   * @param bool $automatic
   * @param int|null $jobId
   * @return TargetLanguageMethodTypeValues
   */
  private function getMethod( int $status, string $translationService, bool $automatic, ?int $jobId ): ?string {
    $method = null;
    if ( $status === TranslationStatus::DUPLICATE ) {
      $method = TargetLanguageMethodType::DUPLICATE;
    } else if ( $translationService !== 'local' && $translationService !== '' && $translationService !== 'NULL' ) {
      $method = TargetLanguageMethodType::TRANSLATION_SERVICE;
    } else if ( $automatic ) {
      $method = TargetLanguageMethodType::AUTOMATIC;
    } else if ( $jobId ) {
      $method = TargetLanguageMethodType::LOCAL_TRANSLATOR;
    }
    return $method;
  }


  /**
   * @param string $row
   * @param array<int, SearchQueryAggregatedJob> $jobs
   *
   * @psalm-suppress LessSpecificReturnStatement,MoreSpecificReturnType
   * @return array{
   *   languageCode:string,
   *   status:string,
   *   reviewStatus?:string,
   *   jobId?:string,
   *   automatic?:string,
   *   translationService?:string,
   *   editor?:string,
   *   element_id:int|null,
   *   translatorId?:string
   * }
   */
  private function getRowValues( string $row, array $jobs ): array {
    // Split the row by comma and map the values to an associative array
    $values = [];
    foreach ( explode( ',', $row ) as $pair ) {
      $kv = explode( ':', $pair );
      if ( count( $kv ) !== 2 ) {
        continue;
      }
      [$key, $value] = $kv;
      $values[trim( $key )] = trim( $value );
    }

    $rid = (int) $values['rid'];
    if ( $rid > 0 && isset( $jobs[$rid] ) ) {
      $job = $jobs[$rid];
      $values['jobId'] = $job['job_id'];
      $values['automatic'] = $job['automatic'];
      $values['translationService'] = $job['translation_service'];
      $values['editor'] = $job['editor'];
      $values['reviewStatus'] = $job['review_status'];
      $values['translatorId'] = $job['translator_id'];
    }
    /** @phpstan-ignore-next-line  */
    return $values;
  }


  /**
   * @param array{
   *    languageCode:string,
   *    status:string,
   *    reviewStatus?:string,
   *    jobId?:string,
   *    automatic?:string,
   *    translationService?:string,
   *    editor?:string,
   *    translatorId?:string
   *  } $values
   * @return int
   */
  private function mapStatus( array $values ): int {
    $status = (int) $values['status'];
    if ( $status === TranslationStatus::ATE_CANCELED ) {
      return TranslationStatus::NOT_TRANSLATED;
    }

    if ( isset( $values['reviewStatus'] ) && $values['reviewStatus'] === 'NEEDS_REVIEW' ) {
      return TranslationStatus::NEEDS_REVIEW;
    }

    return $status;
  }


}

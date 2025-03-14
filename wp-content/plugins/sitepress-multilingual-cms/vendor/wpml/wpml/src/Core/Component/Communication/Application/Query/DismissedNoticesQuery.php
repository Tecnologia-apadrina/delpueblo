<?php

namespace WPML\Core\Component\Communication\Application\Query;

use WPML\Core\Component\Communication\Domain\NoticesOption;
use WPML\Core\Port\Persistence\OptionsInterface;

class DismissedNoticesQuery {

  /** @var OptionsInterface */
  private $options;


  public function __construct( OptionsInterface $options ) {
    $this->options = $options;
  }


  /**
   * @param string[] $noticeIdsToCheck
   *
   * @return string[]
   */
  public function getDismissed( array $noticeIdsToCheck = [] ): array {
    /**
     * @var array{dismissed?: string[]} $noticeOptions
     */
    $noticeOptions = $this->options->get( NoticesOption::OPTION_NAME, [] );

    $dismissed = $noticeOptions['dismissed'] ?? [];

    if ( ! empty( $noticeIdsToCheck ) ) {
      $dismissed = array_intersect( $dismissed, $noticeIdsToCheck );
    }

    return $dismissed;
  }


}

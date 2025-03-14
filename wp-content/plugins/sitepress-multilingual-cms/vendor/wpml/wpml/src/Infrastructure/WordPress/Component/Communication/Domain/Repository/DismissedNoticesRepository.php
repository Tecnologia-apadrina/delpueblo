<?php

namespace WPML\Infrastructure\WordPress\Component\Communication\Domain\Repository;

use WPML\Core\Component\Communication\Domain\NoticesOption;
use WPML\Core\Component\Communication\Domain\Repository\DismissedNoticesRepositoryInterface;
use WPML\Core\Port\Persistence\OptionsInterface;

class DismissedNoticesRepository implements DismissedNoticesRepositoryInterface {

  /** @var OptionsInterface */
  private $options;


  public function __construct( OptionsInterface $options ) {
    $this->options = $options;
  }


  /**
   * @param string $noticeId
   *
   * @return void
   */
  public function dismiss( string $noticeId ) {
    /**
     * @var array{dismissed?: string[]} $noticeOptions
     */
    $noticeOptions = $this->options->get( NoticesOption::OPTION_NAME, [] );

    if ( ! isset( $noticeOptions['dismissed'] ) ) {
      $noticeOptions['dismissed'] = [];
    }

    if ( ! in_array( $noticeId, $noticeOptions['dismissed'] ) ) {
      $noticeOptions['dismissed'][] = $noticeId;
      $this->options->save( NoticesOption::OPTION_NAME, $noticeOptions );
    }
  }


}

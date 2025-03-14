<?php

namespace WPML\Core\Component\Communication\Application\Service;

use WPML\Core\Component\Communication\Domain\Repository\DismissedNoticesRepositoryInterface;

class DismissNoticeService {

  /** @var DismissedNoticesRepositoryInterface */
  private $repository;


  public function __construct( DismissedNoticesRepositoryInterface $repository ) {
    $this->repository = $repository;
  }


  /**
   * @param string $noticeId
   *
   * @return void
   */
  public function dismiss( string $noticeId ) {
    $this->repository->dismiss( $noticeId );
  }


}

<?php

namespace WPML\Core\Component\Communication\Domain\Repository;

interface DismissedNoticesRepositoryInterface {


  /**
   * @param string $noticeId
   *
   * @return void
   */
  public function dismiss( string $noticeId );


}

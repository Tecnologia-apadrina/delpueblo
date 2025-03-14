<?php

namespace WPML\Core\Port\Persistence;

interface QueryPrepareInterface {


  public function prefix(): string;


  /**
   * @param string $sql
   * @param array<scalar>|scalar $args
   * @return string
   */
  public function prepare( $sql, ...$args ): string;


  /**
   * @param string|null $text
   * @return string
   */
  public function escLike( $text ): string;


}

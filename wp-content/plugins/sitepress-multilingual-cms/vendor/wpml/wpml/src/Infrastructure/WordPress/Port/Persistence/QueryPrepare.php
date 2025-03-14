<?php

namespace WPML\Infrastructure\WordPress\Port\Persistence;

use WPML\Core\Port\Persistence\QueryPrepareInterface;

class QueryPrepare implements QueryPrepareInterface {

  /** @var \wpdb $wpdb */
  private $wpdb;


  /**
   * @param \wpdb $wpdb Type defined here to allow injecting the global.
   */
  public function __construct( $wpdb ) {
    $this->wpdb = $wpdb;
  }


  public function prefix(): string {
    return $this->wpdb->prefix;
  }


  /**
   * @param string $sql
   * @param array<scalar>|scalar $args
   * @return string
   */
  public function prepare( $sql, ...$args ): string {
    // @phpstan-ignore-next-line
    $prepared = $this->wpdb->prepare( $sql, $args );
    // Get rid of the possible void return of wpdb::prepare().
    return is_string( $prepared ) ? $prepared : '';
  }


  /**
   * Alias of wpdb::esc_like()
   *
   * @param string|null $text
   * @return string
   */
  public function escLike( $text ): string {
    if ( ! is_null( $text ) && trim( $text ) !== '' ) {
      return $this->wpdb->esc_like( $text );
    }

    return '';
  }


}

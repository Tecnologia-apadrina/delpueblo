<?php

namespace WPML\Legacy\Component\Post\Application\Query;

use WPML\Core\Component\Post\Application\Query\Criteria\TaxonomyCriteria;
use WPML\Core\Component\Post\Application\Query\Criteria\TaxonomyTermCriteria;
use WPML\Core\Component\Post\Application\Query\Dto\PostTaxonomyDto;
use WPML\Core\Component\Post\Application\Query\Dto\PostTermDto;
use WPML\Core\Component\Post\Application\Query\TaxonomyQueryInterface;

class TaxonomyQuery implements TaxonomyQueryInterface {

  /** @var \SitePress */
  private $sitepress;


  public function __construct( \SitePress $sitepress ) {
    $this->sitepress = $sitepress;
  }


  public function getTaxonomies( TaxonomyCriteria $criteria ): array {

    $originalLanguage = $this->sitepress->get_current_language();
    $this->sitepress->switch_lang( $criteria->getSourceLanguageCode() );
    $wpTaxonomies = get_taxonomies( [], 'objects' );

    $this->sitepress->switch_lang( $originalLanguage );

    return array_map(
      function ( $wpTaxonomy ) {
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        return new PostTaxonomyDto( $wpTaxonomy->name, $wpTaxonomy->label, array_values( $wpTaxonomy->object_type ) );
      },
      $wpTaxonomies
    );
  }


  /**
   * @param TaxonomyTermCriteria $taxonomyTermCriteria
   * @return PostTermDto[]
   * @throws \Exception
   */
  public function getTerms( TaxonomyTermCriteria $taxonomyTermCriteria ): array {
    /** @var array<\WP_Term>|\WP_Error $wpTerms */
    $wpTerms = get_terms(
      array(
        'orderby'       => 'name',
        'taxonomy'      => $taxonomyTermCriteria->getTaxonomyId(),
        'hide_empty' => true,
        'search'        => $taxonomyTermCriteria->getSearch() ?? '',
        'number'        => $taxonomyTermCriteria->getLimit() ?? '',
        'offset'        => (int) $taxonomyTermCriteria->getOffset(),
      )
    );

    if ( is_wp_error( $wpTerms ) ) {
      throw new \Exception( 'Could not retrieve terms.' );
    }

    return array_map(
      function ( $wpTerm ) {
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        return new PostTermDto( $wpTerm->term_id, $wpTerm->name );
      },
      $wpTerms
    );
  }


}

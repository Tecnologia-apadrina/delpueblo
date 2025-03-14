<?php

namespace WPML\Legacy\Component\TranslationProxy\Domain\Query;

use WPML\Core\SharedKernel\Component\TranslationProxy\Domain\Query\FetchRemoteTranslationServiceException;
use WPML\Core\SharedKernel\Component\TranslationProxy\Domain\Query\RemoteTranslationServiceQueryInterface;
use WPML\Core\SharedKernel\Component\TranslationProxy\Domain\RemoteTranslationServiceDomain;
use WPML\Core\SharedKernel\Component\TranslationProxy\Domain\RemoteTranslationServiceExtraField;

class RemoteTranslationServiceQuery implements RemoteTranslationServiceQueryInterface {

  /** @var \TranslationProxy */
  private $translationProxy;

  /** @var \TranslationProxy_Basket */
  private $translationProxyBasket;


  public function __construct(
    \TranslationProxy $translationProxy,
    \TranslationProxy_Basket $translationProxyBasket
  ) {
    $this->translationProxy       = $translationProxy;
    $this->translationProxyBasket = $translationProxyBasket;
  }


  /** @return \TranslationProxy_Service|\WP_Error|\stdClass|false */
  private function getCurrentLegacy() {
    return $this->translationProxy::get_current_service();
  }


  /**
   * @param bool $forceRefreshExtraFields
   *
   * @return RemoteTranslationServiceDomain|null
   * @throws FetchRemoteTranslationServiceException
   */
  public function getCurrent( bool $forceRefreshExtraFields = false ) {
    /** @var \TranslationProxy_Service|\WP_Error|\stdClass|false $currentTranslationService */
    $currentTranslationService = $this->getCurrentLegacy();

    if ( is_wp_error( $currentTranslationService ) ) {
      throw new FetchRemoteTranslationServiceException(
        $currentTranslationService->get_error_message()
      );
    }

    if ( ! $currentTranslationService ) {
      return null;
    }

    $serviceRequiresAuthentication
      = $this->translationProxy::service_requires_authentication( $currentTranslationService );

    // When the maximumJobsPerBatch is not set or it's 0 this means that we don't need to chunk the translation service jobs separately
    $maximumJobsPerBatch = isset( $currentTranslationService->maximumJobsPerBatch )
                           && $currentTranslationService->maximumJobsPerBatch > 0
      ? $currentTranslationService->maximumJobsPerBatch
      : null;

    $translationServiceDomain = new RemoteTranslationServiceDomain(
      $currentTranslationService->id,
      $currentTranslationService->name,
      $serviceRequiresAuthentication,
      $currentTranslationService->description,
      $currentTranslationService->url,
      $currentTranslationService->logo_url,
      (array) $currentTranslationService->custom_fields,
      (array) $currentTranslationService->custom_fields_data,
      [],
      $maximumJobsPerBatch
    );

    // Try to get the extra fields ONLY if the translation service is authenticated
    if ( $translationServiceDomain->isAuthenticated() ) {
      $translationServiceDomain->setExtraFields( $this->getExtraFields( $forceRefreshExtraFields ) );
    }

    return $translationServiceDomain;
  }


  /**
   * @param bool $forceRefreshExtraFields
   *
   * @return RemoteTranslationServiceExtraField[]
   */
  public function getExtraFields( bool $forceRefreshExtraFields = false ): array {
    $localExtraFields = $this->translationProxy::get_extra_fields_local();

    $forceRefresh = false;

    if ( ! $localExtraFields || $forceRefreshExtraFields ) {
      $forceRefresh = true;
    }

    try {
      return array_map(
        function ( \WPML_TP_Extra_Field $field ) {
          return new RemoteTranslationServiceExtraField(
            $field->type,
            $field->label,
            $field->name,
            $field->items
          );
        },
        $this->translationProxyBasket::get_basket_extra_fields_array( $forceRefresh )
      );
    } catch ( \Throwable $e ) { // Handling any general exception coming from Legacy
      return [];
    }
  }


}

<?php

namespace WPML\Legacy\Component\ATE\Application\Query;

use WPML\Core\Component\ATE\Application\Query\AccountException;
use WPML\Core\Component\ATE\Application\Query\AccountInterface;
use WPML\Core\Component\ATE\Application\Query\Dto\AccountBalanceDto;
use WPML\Core\Component\ATE\Application\Query\Dto\CreditInfoDto;

class Account implements AccountInterface {


  /**
   * @return CreditInfoDto
   * @throws AccountException
   */
  public function getCredits(): CreditInfoDto {
    $apiResult = \WPML\TM\API\ATE\Account::getCredits();

    /**
     * @psalm-suppress MissingClosureReturnType
     * @psalm-suppress MissingClosureParamType
     */
    $errorHandler = function ( $error ) {
      throw new AccountException(
        $error['error'] ?? __( 'Error getting credits', 'wpml' )
      );
    };

    /**
     * @psalm-suppress MissingClosureReturnType
     * @psalm-suppress MissingClosureParamType
     */
    $identity = function ( array $result ) {
      return $result;
    };

    $apiResult = $apiResult->bimap( $errorHandler, $identity )->getOrElse( [] );

    return new CreditInfoDto(
      $apiResult['free_credits_amount'] ?? 0,
      $apiResult['active_subscription'] ?? false,
      $apiResult['subscription_usage'] ?? 0,
      $apiResult['available_balance'] ?? 0,
      $apiResult['total_credits_deposited'] ?? 0,
      $apiResult['total_credits_spent'] ?? 0,
      $apiResult['pay_as_you_go'] ?? false,
      $apiResult['subscription_max_limit'] ?? null
    );
  }


  public function getAccountBalances(): AccountBalanceDto {
    $apiResult = \WPML\TM\API\ATE\Account::getAccountBalances();

    /**
     * @psalm-suppress MissingClosureReturnType
     * @psalm-suppress MissingClosureParamType
     */
    $errorHandler = function ( $error ) {
      throw new AccountException(
        $error['error'] ?? __( 'Error getting account balances', 'wpml' )
      );
    };

    /**
     * @psalm-suppress MissingClosureReturnType
     * @psalm-suppress MissingClosureParamType
     */
    $identity = function ( array $result ) {
      return $result;
    };

    $apiResult = $apiResult->bimap( $errorHandler, $identity )->getOrElse( [] );

    return new AccountBalanceDto(
      $apiResult['account_balance'] ?? 0,
      $apiResult['redirect_url'] ?? ''
    );
  }


}

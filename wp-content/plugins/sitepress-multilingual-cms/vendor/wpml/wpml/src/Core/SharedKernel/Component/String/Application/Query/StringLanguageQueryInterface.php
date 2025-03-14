<?php

namespace WPML\Core\SharedKernel\Component\String\Application\Query;

interface StringLanguageQueryInterface {


  /**
   * @param int[] $strings
   *
   * @return array<int, string> [stringId => language]
   */
  public function getStringLanguages( array $strings ): array;


}

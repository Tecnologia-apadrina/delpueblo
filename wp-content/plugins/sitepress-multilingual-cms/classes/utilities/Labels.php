<?php

namespace WPML\Utilities;

use WPML\LIB\WP\Hooks;

use function WPML\FP\spreadArgs;

class Labels implements \IWPML_Frontend_Action, \IWPML_Backend_Action {

	public function add_hooks() {
		Hooks::onFilter( 'wpml_labelize_string' )
			->then( spreadArgs( [ $this, 'labelize' ] ) );
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	public static function labelize( $string ) {
		return ucwords(
			strtr(
				$string,
				[
					'-' => ' ',
					'_' => ' ',
				]
			)
		);
	}
}

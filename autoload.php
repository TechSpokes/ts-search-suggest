<?php
/**
 * Created by PhpStorm.
 * User: serge
 * Date: 4/5/2019
 * Time: 11:37 AM
 */

/**
 * PSR-4 Autoloader.
 */
spl_autoload_register( function ( $class ) {

	// validate namespace
	if (
		strncmp(
			$namespace = 'TechSpokes\SearchSuggest',
			$class,
			$namespace_length = strlen( $namespace )
		) !== 0
	) {
		return;
	};

	// try to load the file
	if ( file_exists(
		$file = join(
			        DIRECTORY_SEPARATOR,
			        array(
				        __DIR__,
				        'src',
				        str_replace( '\\', DIRECTORY_SEPARATOR, trim( substr( $class, $namespace_length ), '\\' ) )
			        )
		        ) . '.php'
	) ) {
		include $file;
	}
} );

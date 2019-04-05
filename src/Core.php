<?php
/**
 * Created by PhpStorm.
 * User: serge
 * Date: 4/5/2019
 * Time: 11:45 AM
 */

namespace TechSpokes\SearchSuggest;


/**
 * Class Core
 *
 * @package TechSpokes\SearchSuggest
 */
class Core {

	/**
	 * @var \TechSpokes\SearchSuggest\Core $instance
	 */
	protected static $instance;

	/**
	 * @return \TechSpokes\SearchSuggest\Core
	 */
	public static function getInstance() {

		if ( ! ( self::$instance instanceof Core ) ) {
			self::setInstance( new self() );
		}

		return self::$instance;
	}

	/**
	 * @param \TechSpokes\SearchSuggest\Core $instance
	 */
	protected static function setInstance( Core $instance ) {

		self::$instance = $instance;
	}

	/**
	 * Core constructor.
	 */
	protected function __construct() {
	}

}

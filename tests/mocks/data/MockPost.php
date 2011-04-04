<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD(http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace slicedup_scaffold\tests\mocks\data;

class MockPost extends \lithium\tests\mocks\data\MockBase {

	public static function flush() {
		static::$_instances = array();
	}

}

?>
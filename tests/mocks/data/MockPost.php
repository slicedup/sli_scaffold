<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD(http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace slicedup_scaffold\tests\mocks\data;

class MockPost extends \lithium\data\Model {

	protected $_schema = array(
		'id' => array('type' => 'integer'),
		'author_id' => array('type' => 'integer'),
		'title' => array('type' => 'string'),
		'body' => array('type' => 'text'),
		'created' => array('type' => 'datetime'),
		'updated' => array('type' => 'datetime')
	);

	public static function flush() {
		static::$_instances = array();
	}

}

?>
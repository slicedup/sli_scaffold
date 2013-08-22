<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD(http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_scaffold\tests\mocks\data;

class MockPost extends \lithium\data\Model {

	protected $_meta = array('connection' => 'mock-source');

	public static $scaffoldFields;

	public static $summaryFields;

	public static $detailFields;

	public static $scaffoldFormFields;

	public static $createFormFields;

	public static $updateFormFields;

	public static $anyOtherFields;

	protected $_schema = array(
		'id' => array('type' => 'integer'),
		'author_id' => array('type' => 'integer'),
		'title' => array('type' => 'string'),
		'body' => array('type' => 'text'),
		'created' => array('type' => 'datetime'),
		'updated' => array('type' => 'datetime')
	);
}

?>
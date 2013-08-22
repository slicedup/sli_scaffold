<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_scaffold\tests\cases\models;

use sli_scaffold\core\Scaffold;
use sli_scaffold\models\Scaffolds as Model;
use lithium\data\Connections;

class ScaffoldsTest extends \lithium\test\Unit {

	protected $_model = 'sli_scaffold\tests\mocks\data\MockPost';

	public function testUnsetScaffoldFields() {
		$post = $this->_model;
		$schema = $post::schema();
		$fields = Model::getFields($post);
		$expected = array_keys($schema->fields());
		$result = array_keys($fields);
		$this->assertEqual($expected, $result);

		$scaffoldFields = Model::getScaffoldFields($post);
		$this->assertEqual($fields, $scaffoldFields);
		$scaffoldFields = Model::getFields($post, 'scaffold');
		$this->assertEqual($fields, $scaffoldFields);

		$summaryFields = Model::getSummaryFields($post);
		$this->assertEqual($fields, $summaryFields);
		$summaryFields = Model::getFields($post, 'summary');
		$this->assertEqual($fields, $summaryFields);

		$detailFields = Model::getDetailFields($post);
		$this->assertEqual($fields, $detailFields);
		$detailFields = Model::getFields($post, 'detail');
		$this->assertEqual($fields, $detailFields);

		$anyOtherFields = Model::getAnyOtherFields($post);
		$this->assertEqual($fields, $anyOtherFields);
		$anyOtherFields = Model::getFields($post, 'AnyOther');
		$this->assertEqual($fields, $anyOtherFields);
	}

	public function testScaffoldFields() {
		$post = $this->_model;
		$setScaffoldFields = array('id', 'title', 'body', 'status');
		$post::$scaffoldFields = $setScaffoldFields;

		$fields = Model::getFields($post);
		$expected = $setScaffoldFields;
		$result = array_keys($fields);
		$this->assertEqual($expected, $result);

		$summaryFields = Model::getSummaryFields($post);
		$this->assertEqual($fields, $summaryFields);
		$summaryFields = Model::getFields($post, 'summary');
		$this->assertEqual($fields, $summaryFields);

		$setSummaryFields = array('id', 'title', 'status', 'created');
		$post::$summaryFields = $setSummaryFields;
		$summaryFields = Model::getSummaryFields($post);
		$this->assertEqual($setSummaryFields, array_keys($summaryFields));
		$summaryFields = Model::getFields($post, 'summary');
		$this->assertEqual($setSummaryFields, array_keys($summaryFields));

		$detailFields = Model::getDetailFields($post);
		$this->assertEqual($fields, $detailFields);
		$detailFields = Model::getFields($post, 'detail');
		$this->assertEqual($fields, $detailFields);

		$setDetailFields = array('id', 'title', 'status', 'created');
		$post::$detailFields = $setDetailFields;
		$detailFields = Model::getDetailFields($post);
		$this->assertEqual($setDetailFields, array_keys($detailFields));
		$detailFields = Model::getFields($post, 'detail');
		$this->assertEqual($setDetailFields, array_keys($detailFields));

		$anyOtherFields = Model::getAnyOtherFields($post);
		$this->assertEqual($fields, $anyOtherFields);
		$anyOtherFields = Model::getFields($post, 'AnyOther');
		$this->assertEqual($fields, $anyOtherFields);

		$setAnyOtherFields = array('id', 'created', 'modified');
		$post::$anyOtherFields = $setAnyOtherFields;
		$anyOtherFields = Model::getAnyOtherFields($post);
		$this->assertEqual($setAnyOtherFields, array_keys($anyOtherFields));
		$anyOtherFields = Model::getFields($post, 'anyOther');
		$this->assertEqual($setAnyOtherFields, array_keys($anyOtherFields));
		$anyOtherFields = Model::getFields($post, 'AnyOther');
		$this->assertEqual($setAnyOtherFields, array_keys($anyOtherFields));
		$anyOtherFields = Model::getFields($post, 'any_other');
		$this->assertEqual($setAnyOtherFields, array_keys($anyOtherFields));
	}

	public function testUnsetScaffoldFormFields() {
		$post = $this->_model;
		$schema = $post::schema();
		$fields = Model::getFormFields($post);
		$expected = array(
			array(
				'legend' => null,
				'fields' => Model::invokeMethod('mapFormFields', array($post))
			)
		);
		$this->assertEqual($expected, $fields);

		$scaffoldFields = Model::getScaffoldFormFields($post);
		$this->assertEqual($fields, $scaffoldFields);
		$scaffoldFields = Model::getFormFields($post, 'scaffold');
		$this->assertEqual($fields, $scaffoldFields);
		$scaffoldFields = Model::getFormFields($post, 'scaffoldForm');
		$this->assertEqual($fields, $scaffoldFields);

		$createFields = Model::getCreateFormFields($post);
		$this->assertEqual($fields, $createFields);
		$createFields = Model::getFormFields($post, 'create');
		$this->assertEqual($fields, $createFields);

		$updateFields = Model::getUpdateFormFields($post);
		$this->assertEqual($fields, $updateFields);
		$updateFields = Model::getFormFields($post, 'update');
		$this->assertEqual($fields, $updateFields);
	}

}

?>
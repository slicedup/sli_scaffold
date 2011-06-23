<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_scaffold\tests\cases\extensions\helper;

use sli_scaffold\core\Scaffold;

class ScaffoldTest extends \sli_scaffold\tests\cases\extensions\helper\ScaffoldBaseTest {

	public function testUnconfiguredScaffold() {
		$model = $this->_model;
		$record = $model::create();
		$result = $this->helper->form(null, $record);
		$this->assertTags($result, array(
			'form' => array(
				'action' => $this->context->request()->url,
				'method' => 'post'
			),
			'fieldset' => array()
		));
		$fieldsets = Scaffold::getFormFields($model);
		foreach ($fieldsets[0]['fields'] as $field => $params) {
			$this->assertPattern('/name="'.$field.'"/', $result);
		}
	}
}

?>
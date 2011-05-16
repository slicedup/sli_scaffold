<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace slicedup_scaffold\tests\cases\extensions\helper\scaffold;

use lithium\test;

use slicedup_scaffold\extensions\helper\scaffold;

use slicedup_scaffold\extensions\helper\scaffold\Form;

class FormTest extends \slicedup_scaffold\tests\cases\extensions\helper\ScaffoldBaseTest {

	public function testBasicForm() {
		$form = new Form(array('context' => $this->context));
		$fieldset = $form->addFieldSet();
		$fields = array('id', 'name');
		$fieldset->addField('id', array('method' => 'hidden'));
		$fieldset->addField('name');
		$fieldset = $form->addFieldSet(array('legend' => 'Contact'));
		$fieldset->addField('email');
		$fieldset->addField('phone');

		$result = $form->render();
		$this->assertTags($result, array(
			'form' => array(
				'action' => '/',
				'method' => 'post',
			),
			'fieldset' => array(),
			'input' => array('type' => 'hidden', 'name' => 'id'),
			'div' => array(),
			'label' => array('for' => 'Name'),
			'Name',
			'/label',
		));

	}
}

?>
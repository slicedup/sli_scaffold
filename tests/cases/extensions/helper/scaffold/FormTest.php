<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_scaffold\tests\cases\extensions\helper\scaffold;

use sli_scaffold\extensions\helper\scaffold\Form;

class FormTest extends \sli_scaffold\tests\cases\extensions\helper\ScaffoldBaseTest {

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
			array('input' => array('type' => 'hidden', 'name' => 'id', 'id' => 'Id')),
			'div' => array(),
			'label' => array('for' => 'Name'),
			'Name',
			'/label'
		));
	}
}

?>
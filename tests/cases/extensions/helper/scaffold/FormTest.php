<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace slicedup_scaffold\tests\cases\extensions\helper\scaffold;

use slicedup_scaffold\extensions\helper\scaffold\Form;

class FormTest extends \slicedup_scaffold\tests\cases\extensions\helper\ScaffoldBaseTest {

	public function testBasicForm() {
		$form = new Form(array('context' => $this->context));
		$fieldset = $form->addFieldSet();
		$fieldset->addField('id');
		print($form);
	}
}

?>
<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2012, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

	echo $this->Form->create($record, compact('url'));
	foreach($fieldsets as $fieldset):
		echo '<fieldset>';
		if (isset($fieldset['legend'])):
			echo '<legend>' . $t($fieldset['legend']) . '</legend>';
		endif;
		foreach($fieldset['fields'] as $field => $options):
			echo $this->Form->field($field, $options);
		endforeach;
		echo '</fieldset>';
	endforeach;
	echo $this->Form->field($t('Create'), array('type' => 'submit', 'label' => false));
	echo $this->Form->end();
?>
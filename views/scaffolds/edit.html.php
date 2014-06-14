<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

$this->title($t($plural));
?>
<div class="scaffold scaffold-form <?php echo $plural;?> <?php echo $singular;?>-form">
	<h2><?php echo $t('{:action} {:entity}', array('action' => $t('Edit'), 'entity' => $t($singular)));?></h2>
	<div class="form update">
		<?php
			echo $this->ScaffoldForm->create($record, compact('url'));
			echo $this->ScaffoldForm->fieldsets($fieldsets);
			echo $this->ScaffoldForm->submit($t('Update'), array('type' => 'submit', 'label' => false));
			echo $this->ScaffoldForm->end();

			/*
			 * A sample 'vanilla' lithium scaffold form is provided in the following element
			 * this can be used instead of the packaged helper in your own custom forms if you
			 * prefer.
			 *
			 * echo $this->_render('element', 'scaffold_form', compact('record', 'url', 'fieldsets'), array(
			 * 	'library' => 'sli_scaffold'
			 * ));
			 */
		?>
	</div>
	<ul class="actions">
		<li><strong>Actions</strong></li>
		<?php if(in_array('view', $actions)):?>
		<li><?php echo $this->html->link($t('{:action} {:entity}', array('action' => $t('View'), 'entity' => $t($singular))), array('action' => 'view', 'args' => $record->key()));?></li>
		<?php endif;?>
		<?php if(in_array('delete', $actions)):?>
		<li><?php echo $this->html->link($t('{:action} {:entity}', array('action' => $t('Delete'), 'entity' => $t($singular))), array('action' => 'delete', 'args' => $record->key()));?></li>
		<?php endif;?>
		<?php if(in_array('index', $actions)):?>
		<li><?php echo $this->html->link($t('{:action} {:entity}', array('action' => $t('List'), 'entity' => $t($plural))), array('action' => 'index'));?></li>
		<?php endif;?>
	</ul>
</div>
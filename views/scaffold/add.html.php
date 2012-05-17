<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

$this->title($t($plural));
?>
<div class="scaffold <?php echo $plural;?> add<?php echo $singular;?>">
	<h2><?php echo $t('{:action} {:entity}', array('action' => $t('Add'), 'entity' => $t($singular)));?></h2>
	<div class="form create">
		<?php
			echo $this->ScaffoldForm->create($record, compact('url'));
			echo $this->ScaffoldForm->fieldsets($fieldsets);
			echo $this->ScaffoldForm->field('Create', array('type' => 'submit', 'label' => false));
			echo $this->ScaffoldForm->end();
		?>
	</div>
	<ul class="actions">
		<?php if(in_array('index', $actions)):?>
		<li><?php echo $this->html->link($t($plural), array('action' => 'index'));?></li>
		<?php endif;?>
	</ul>
</div>
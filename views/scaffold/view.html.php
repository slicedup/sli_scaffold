<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

$this->title($t($plural));
?>
<div class="<?php echo $plural;?>">
	<h2><?php echo $t('{:action} {:entity}', array('action' => $t('View'), 'entity' => $t($singular)));?></h2>
	<div class="view <?php echo $singular;?>">
		<dl>
		<?php foreach ($fields as $field => $name):?>
			<dt><?php echo $t($name)?></dt>
			<dd><?php echo $h($record->{$field});?></dd>
		<?php endforeach;?>
		</dl>
	</div>
	<ul class="actions">
		<?php if(in_array('edit', $actions)):?>
		<li><?php echo $this->html->link($t('Edit'), array('action' => 'edit', 'args' => $record->key()));?></li>
		<?php endif;?>
		<?php if(in_array('delete', $actions)):?>
		<li><?php echo $this->html->link($t('Delete'), array('action' => 'delete', 'args' => $record->key()));?></li>
		<?php endif;?>
		<?php if(in_array('index', $actions)):?>
		<li><?php echo $this->html->link($t($plural), array('action' => 'index'));?></li>
		<?php endif;?>
	</ul>
</div>
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
	<h2><?php echo $t($plural);?></h2>
	<div class="index">
		<table>
			<tr>
			<?php foreach ($fields as $field => $name):?>
				<th><?php echo $t($name);?></th>
			<?php endforeach;?>
				<th class="actions">Actions</th>
			</tr>
		<?php foreach ($recordSet as $record):?>
			<tr>
				<?php foreach ($fields as $field => $name):?>
				<td><?php echo $h($record->{$field});?></td>
				<?php endforeach;?>
				<td>
					<?php echo $this->html->link($t('View'), array('action' => 'view', 'args' => $record->key()));?> |
					<?php echo $this->html->link($t('Edit'), array('action' => 'edit', 'args' => $record->key()));?> |
					<?php echo $this->html->link($t('Delete'), array('action' => 'delete', 'args' => $record->key()));?>
				</td>
			</tr>
		<?php endforeach;?>
		</table>
	</div>
	<ul class="actions">
		<li><?php echo $this->html->link($t('{:action} {:entity}', array('action' => $t('Add'), 'entity' => $t($singular))), array('action' => 'add'));?></li>
	</ul>
</div>
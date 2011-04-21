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
	<h2><?php echo $t('{:action} {:entity}', array('action' => $t('Edit'), 'entity' => $t($singular)));?></h2>
	<div class="edit <?php echo $singular;?>">
		<?php
			echo $this->scaffold->form($fields, $record);
		?>
	</div>
	<ul class="actions">
		<li><?php echo $this->html->link($t('View'), array('action' => 'view', 'args' => $record->key()));?></li>
		<li><?php echo $this->html->link($t('Delete'), array('action' => 'delete', 'args' => $record->key()));?></li>
		<li><?php echo $this->html->link($t($plural), array('action' => 'index'));?></li>
	</ul>
</div>
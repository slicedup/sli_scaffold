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
	<h2><?php echo $t('{:action} {:entity}', array('action' => $t('Add'), 'entity' => $t($singular)));?></h2>
	<div class="edit <?php echo $singular;?>">
		<?php
			echo $this->elements->create('scaffold\Form', array(
				'fieldsets' => $fields,
				'binding' => $record
			), true);
		?>
	</div>
	<ul class="actions">
		<?php if(in_array('index', $actions)):?>
		<li><?php echo $this->html->link($t($plural), array('action' => 'index'));?></li>
		<?php endif;?>
	</ul>
</div>
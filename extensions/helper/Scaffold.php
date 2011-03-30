<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace slicedup_scaffold\extensions\helper;

use lithium\util\Set;

class Scaffold extends \lithium\template\Helper {

	/**
	 * Scaffolded record index table
	 */
	public function index() {}

	/**
	 * Scaffolded detail list
	 */
	public function view() {}

	/**
	 * Scaffolded form for adding/editing records
	 *
	 * @param array $params form params
	 */
	public function form($params = array()){
		$data =  $this->_context->data();
		$t = $data['t'];
		$key = $data['record']->key();
		$formParams = array(
			'url' => array(
				'action' => 'add'
			)
		);
		if ($data['record']->exists()) {
			$formParams = array(
				'url' => array(
					'action' => 'edit',
					'args' => $key
				)
			);
		}
		if (is_array($key)) {
			$key = key($key);
		}
		$formParams = Set::merge($formParams, $params);
		$output = $this->_context->form->create($data['record'], $formParams);

		$defaults = array(
			'helper' => 'form',
			'method' => 'field'
		);

		foreach ($data['fields'] as $fieldset => $fields) {
			$output .= '<fieldset>';
			if (!is_int($fieldset)) {
				$output.= '<legend>'.$t($fieldset).'</legend>';
			}
			foreach ($fields as $field => $options){
				if (!is_array($options)) {
					$options = array('label' => $options);
				}
				$options['label'] = $t($options['label']);
				$options += $defaults;
				if (!isset($options['type'])) {
					if ($field == $key) {
						$options['method'] = 'hidden';
					}
				}
				$method = $options['method'];
				$helper = $options['helper'];
				unset($options['method'], $options['helper']);
				$output.= $this->_context->{$helper}->{$method}($field, $options);
			}
			$output .= '</fieldset>';
		}

		$submit = $data['record']->exists() ? 'Update' : 'Create';
		$output .= $this->_context->form->submit($t($submit));
		$output .= $this->_context->form->end();
		return $output;
	}
}
?>
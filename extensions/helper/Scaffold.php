<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace slicedup_scaffold\extensions\helper;

use lithium\util\Set;
use lithium\util\Inflector;

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
	 * @todo there has to be a better way of getting the current url from
	 * the request object
	 * @todo properly format output string
	 *
	 * @param array $params form params
	 */
	public function form($record = null, $fields = array(), $params = array()){
		$args = array_filter(compact('record', 'fields', 'params'));
		extract($this->_context->data());
		extract($args);

		$key = null;
		if ($record) {
			$key = $record->key();
			if ($record->exists()) {
				$key = key($key);
			}
			if (empty($fields)) {
				$schema = $record->schema();
				$fields = array(array_keys($schema));
			}
		}

		$_params = array(
			'url' => $_REQUEST['url']
		);
		$params = Set::merge($_params, $params);
		$output = $this->_context->form->create($record, $params);

		$fieldDefaults = array(
			'helper' => 'form',
			'method' => 'field'
		);
		foreach ($fields as $fieldset => $_fields) {
			$output .= '<fieldset>';
			if (!is_int($fieldset)) {
				$output.= '<legend>' . $t($fieldset) . '</legend>';
			}
			foreach ($_fields as $field => $options){
				if (is_int($field)) {
					$field = $options;
				}
				if (!is_array($options)) {
					$options = array(
						'label' => Inflector::humanize($options)
					);
				} elseif (!isset($options['label'])) {
					$options['label'] = Inflector::humanize($field);
				}
				$options['label'] = $t($options['label']);
				if ($field == $key && empty($options['method'])) {
					$options['method'] = 'hidden';
				}
				$options += $fieldDefaults;
				$method = $options['method'];
				$helper = $options['helper'];
				unset($options['method'], $options['helper']);
				$output.= $this->_context->{$helper}->{$method}($field, $options);
			}
			$output .= '</fieldset>';
		}

		$output .= $this->_context->form->submit($t('Save'));
		$output .= $this->_context->form->end();
		return $output;
	}
}
?>
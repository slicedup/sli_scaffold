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
use slicedup_scaffold\core\Scaffold as Scaffolder;

class ScaffoldForm extends \lithium\template\Helper {

	protected $_classes = array(
		'scaffold' => 'slicedup_scaffold\core\Scaffold'
	);

	/**
	 * Scaffolded form for adding/editing records
	 *
	 * @todo there has to be a better way of getting the current url from
	 * the request object
	 * @todo fieldset options
	 *
	 * @param Record $record
	 * @param array $fields
	 * @param array $params form params
	 */
	public function create($record = null, $fields = array(), $params = array()) {
		$context = $this->_context;
		$args = array_filter(compact('record', 'fields', 'params'));
		extract($context->data());
		extract($args);

		$key = null;
		if ($record) {
			$key = $record->key();
			if ($record->exists()) {
				$key = key($key);
			}
			if (empty($fields)) {
				$scaffold = $this->_classes['scaffold'];
				$fields = $scaffold::mapFormFields($record->schema());
			}
		}

		$params = Set::merge(array(
			'url' => $_REQUEST['url']
		), $params);
		$fields = $this->_formatFormFields($fields, $params, $key);

		$params = compact('record', 'fields', 'params');
		$filter = function($self, $params) use ($context) {
			extract($context->data());
			extract($params);

			$output = $context->form->create($record, $params);
			foreach ($fields as $fieldset => $_fields) {
				$content = '';
				if (!is_int($fieldset)) {
					$content.= $self->invokeMethod('_render', array(
						'legend',
						'legend',
						array('content' => $t($fieldset))
					));
				}
				foreach ($_fields as $field => $options){
					$method = $options['method'];
					$helper = $options['helper'];
					unset($options['method'], $options['helper']);
					$options['label'] = $t($options['label']);
					$content.= $context->{$helper}->{$method}($field, $options);
				}
				$options = array();
				$output.= $self->invokeMethod('_render', array(
					'fieldset',
					'fieldset',
					compact('content', 'options'),
					array('escape' => false)
				));
			}

			$output.= $context->form->submit($t('Save'));
			$output.= $context->form->end();
			return $output;
		};
		return $this->_filter(__METHOD__, $params, $filter);
	}

	protected function _formatFormFields($fields, &$params = array(), $key = null) {
		$fieldsets = array();
		foreach ($fields as $fieldset => $_fields) {
			$fieldsets[$fieldset] = array();
			foreach ($_fields as $field => $options){
				if (!is_array($options)) {
					if (is_int($field)) {
						$field = $options;
					}
					$options = array(
						'label' => Inflector::humanize($options)
					);
				} elseif (!isset($options['label'])) {
					$options['label'] = Inflector::humanize($field);
				}
				if ($field == $key && empty($options['method'])) {
					$options['method'] = 'hidden';
				}
				$options += array(
					'helper' => 'form',
					'method' => 'field',
				);
				if (!empty($options['type']) && $options['type'] == 'file') {
					$params['type'] = 'file';
				}
				$fieldsets[$fieldset][$field] = $options;
			}
		}
		return $fieldsets;
	}
}
?>
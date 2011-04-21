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

	protected $_classes = array(
		'scaffold' => '\slicedup_scaffold\core\Scaffold',
		'form' => '\slicedup_scaffold\extensions\helper\scaffold\Form'
	);

	/**
	 * Scaffolded form for adding/editing records
	 *
	 * @param mixed $form
	 * @param mixed $binding
	 * @param array $options
	 */
	public function form($form = array(), $binding = null, $options = array()) {
		$formClass = $this->_classes['form'];
		if ($form instanceOf $formClass) {
			$formInstance = $form;
			$formInstance->config($options);
			$formInstance->binding($binding);
		} else {
			$formInstance = $this->formInstance($form, $binding, $options);
		}
		if (!$formInstance->context()) {
			$formInstance->context($this->_context);
		}
		return $formInstance->render();
	}

	/**
	 * Scaffold form class instance
	 *
	 * @param array $fields
	 * @param mixed $binding
	 * @param array $options
	 */
	public function formInstance($fields = array(), $binding = null, $options = array()) {
		$formClass = $this->_classes['form'];
		if (!isset($options['binding'])) {
			$options['binding'] = $binding;
		}
		if ($options['binding'] && empty($fields) && $fields !== false) {
			$scaffold = $this->_classes['scaffold'];
			//@todo support for collection (exists n/a)
			$action = $options['binding']->exists() ? 'Update' : 'Create';
			$getter = "get{$action}FormFields";
			$fields = $scaffold::$getter($options['binding']->model());
		}
		$options['data'] = $fields;
		return new $formClass($options);
	}

	/**
	 * @deprecated
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
				$action = $record->exists() ? 'Update' : 'Create';
				$getter = "get{$action}FormFields";
				$fields = $scaffold::$getter($record->model());
			}
		}

		$params = Set::merge(array(
			'url' => $context->request()->url
		), $params);
		$fields = $this->_formatFormFields($fields, $params, $key);
		$params = compact('record', 'fields', 'params');
		$filter = function($self, $params) use ($context) {
			extract($context->data());
			extract($params);
			if (!isset($t)) {
				$t = function ($value) {
					return $value;
				};
			}

			$output = $context->form->create($record, $params);
			foreach ($fields as $fieldset => $_fields) {
				$fieldset = $_fields['legend'];
				$_fields = $_fields['fields'];
				$content = '';
				if ($fieldset) {
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

	/**
	 * @deprecated
	 */
	protected function _formatFormFields($fields, &$params = array(), $key = null) {
		$fieldsets = array();
		foreach ($fields as $fieldset => $_fields) {
			$fieldsets[$fieldset] = array();
			if (!isset($_fields['legend'])) {
				$_fields['legend'] = !is_int($fieldset) ? $fieldset : null;
			}
			$fieldsets[$fieldset]['legend'] = $_fields['legend'];
			foreach ($_fields['fields'] as $field => $options){
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
				$fieldsets[$fieldset]['fields'][$field] = $options;
			}
		}
		return $fieldsets;
	}
}
?>
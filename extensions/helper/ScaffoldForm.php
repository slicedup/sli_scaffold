<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2012, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_scaffold\extensions\helper;

use lithium\util\Set;
use lithium\util\Inflector;

/**
 *  The `ScaffoldForm` helper class provides convenience methods for creating
 *  form fieldsets & field collections passed as arrays consistent with those
 *  configured and used by default in the sli_scaffold library
 */
class ScaffoldForm extends \lithium\template\helper\Form {
	
	/**
	 * Constructor
	 * 
	 * Overwrites attribute.id hanlding to use the model name instead of the
	 * model class as the prefix for form fields
	 */
	public function __construct(array $config = array()) {
		$self =& $this;
		$defaults = array(
			'attributes' => array(
				'id' => function($method, $name, $options) use (&$self) {
					if (in_array($method, array('create', 'end', 'label', 'error'))) {
						return;
					}
					if (!$name || ($method == 'hidden' && $name == '_method')) {
						return;
					}
					$id = Inflector::camelize(Inflector::slug($name));
					$model = ($binding = $self->binding()) ? $binding->model() : null;
					return $model ? $model::meta('name') . $id : $id;
				}
			)
		);
		parent::__construct(Set::merge($defaults, $config));
	}
	
	/**
	 * Render a collection of fieldsets
	 */
	public function fieldsets($fieldsets = array()) {
		$context = $this->_context;
		extract(array_filter(compact('fieldsets')) + $context->data());
		$output = '';
		foreach ($fieldsets as $fieldset) {
			$fieldset += array('fields' => '', 'options' => array());
			if (!isset($fieldset['legend'])) {
				$fieldset['legend'] = isset($singular) ? $t($singular) : '';
			}
			$output.= $this->fieldset($fieldset['fields'], $fieldset['legend'], $fieldset['options']);
		}
		return $output;
	}
	
	/**
	 * Render a collection of form fields
	 */
	public function fields($fields = array()) {
		$context = $this->_context;
		extract(array_filter(compact('fields')) + $context->data());
		$output = '';
		foreach ($fields as $field => $options){
			$options += array(
				'helper' => 'ScaffoldForm',
				'method' => 'field',
				'label' => Inflector::humanize($field)
			);
			$method = $options['method'];
			$helper = $options['helper'];
			unset($options['method'], $options['helper']);
			$options['label'] = $t($options['label']);
			$output.= $context->{$helper}->{$method}($field, $options);
		}
		return $output;
	}
	
	/**
	 * Render a single fieldset with a collection of fields and legend
	 */
	public function fieldset($fields, $legend = '', $options = array()) {
		if (is_array($fields)) {
			$fields = $this->fields($fields);
		}
		return $this->invokeMethod('_render', array(
			'fieldset',
			'fieldset',
			array(
				'content' => $legend, 
				'raw' => $fields,
				'options' => $this->_attributes($options)
			)
		));
	}
}

?>
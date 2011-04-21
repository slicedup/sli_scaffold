<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace slicedup_scaffold\extensions\helper\scaffold;

use RuntimeException;

class Fieldset extends \lithium\util\Collection {

	/**
	 * Fieldset config options (attributes)
	 *
	 * @var array
	 */
	protected $_config = array();

	/**
	 * Auto config params
	 *
	 * @var array
	 */
	protected $_autoConfig = array('form');

	/**
	 * Fieldset Fields
	 *
	 * @var array
	 */
	protected $_data = array();

	/**
	 * Form object fieldset is bound to
	 *
	 * @var slicedup_scaffold\extensions\helper\scaffold\Form
	 */
	protected $_form;

	/**
	 * Classes used by this class
	 *
	 * @var array
	 */
	protected $_classes = array(
		'field' => '\slicedup_scaffold\extensions\helper\scaffold\Field'
	);

	/**
	 * Not implementd
	 */
	protected $_renderWith = array(
		'fieldset' => array('form', 'fieldset'),
		'legend' => array('form', 'legend')
	);

	/**
	 * Init
	 */
	protected function _init() {
		if (isset($this->_config['fields'])) {
			$this->_config['data'] = $this->_config['fields'];
			unset($this->_config['fields']);
		}
		if (!empty($this->_config['data'])) {
			array_walk($this->_config['data'], array($this, 'addField'));
		}
		parent::_init();
		unset($this->_config['form']);
	}

	/**
	 * Get/Set fieldset config
	 *
	 * @param array $config
	 */
	public function config($config = array()) {
		if (!empty($config)) {
			$this->_config = $config + $this->_config;
		}
		return $this->_config;
	}

	/**
	 * Get/Set form context
	 *
	 * @param \slicedup_scaffold\extensions\helper\scaffold\Form $form
	 */
	public function form($form = null) {
		$formClass = '\slicedup_scaffold\extensions\helper\scaffold\Form';
		if ($form instanceOf $formClass) {
			$this->_form = $form;
		}
		if ($this->_form instanceOf $formClass) {
			return $this->_form;
		} else if ($form === true) {
			$message = "%s requires form context %s.";
			throw new RuntimeException(sprintf($message, get_class(), $formClass));
		}
	}

	public function addField($name, $config = array()) {
		$class = $this->_classes['field'];
		if (is_object($name)) {
			$key = null;
			$field = $name;
		} elseif(is_object($config)) {
			$key = $name;
			$field = $config;
		} elseif (is_array($name)) {
			$key = null;
			if (is_scalar($config)) {
				$key = $config;
				$name['name'] = $config;
			}
			$field = new $class($name);
		} else {
			if (!is_array($config)) {
				$config = array();
			}
			$key = $name;
			$config['name'] = $name;
			$field = new $class($config);
		}
		$field->fieldset($this);
		if (is_null($key)) {
			return $this->_data[] = $field;
		}
		return $this->_data[$key] = $field;
	}

	public function getField($key) {
		return $this->offsetGet($key);
	}

	public function removeField($key) {
		return $this->offsetUnset($key);
	}

	public function offsetSet($offset, $value) {
		return $this->addField($value, $offset);
	}

	public function __invoke($form = null) {
		return $this->render($form);
	}

	public function __toString() {
		try {
			return $this->render();
		} catch (RuntimeException $e) {
			return $e->getMessage();
		}
	}

	public function render($form = null) {
		if ($form) {
			$this->form($form);
		}
		$form = $this->form(true);
		$context = $form->context(true);
		$config = $this->_config;
		unset($config['init']);
		$content = '';
		if(isset($config['legend'])) {
			$template = $context->form->legend();
			$params = array(
				'legend',
				$template,
				array('content' => $config['legend'])
			);
			$content.= $context->scaffold->invokeMethod('_render', $params);
		}
		unset($config['legend']);
		$content.= implode($this->invoke('render', array($this)));

		$template = $context->form->fieldset(null, $config);
		$params = array(
			'fieldset',
			$template,
			array('content' => $content),
			array('escape' => false)
		);
		return $context->scaffold->invokeMethod('_render', $params);
	}
}

?>
<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace slicedup_scaffold\extensions\helper\scaffold;

use RuntimeException;

class Form extends \lithium\util\Collection {

	/**
	 * Form config options
	 *
	 * @var array
	 */
	protected $_config = array();

	/**
	 * Auto config params
	 *
	 * @var array
	 */
	protected $_autoConfig = array('binding', 'context');

	/**
	 * Form fieldsets
	 *
	 * @var array
	 */
	protected $_data = array();

	/**
	 * The data object or list of data objects to which the current form is bound.
	 *
	 * @see lithium\template\helper\Form::_binding
	 *
	 * @var mixed A single data object, a `Collection` of multiple data objects,
	 * 		or an array of data objects/`Collection`s.
	 */
	protected $_binding = null;

	/**
	 * The Renderer object this Helper element is bound to.
	 *
	 * @see lithium\template\view\Renderer
	 * @var lithium\template\view\Renderer
	 */
	protected $_context = null;

	/**
	 * Classes used by this class
	 *
	 * @var array
	 */
	protected $_classes = array(
		'fieldset' => '\slicedup_scaffold\extensions\helper\scaffold\Fieldset'
	);

	/**
	 * Not implementd
	 */
	protected $_renderWith = array(
		'start' => array('form', 'create'),
		'legend' => array('form', 'legend'),
		'submit' => array('form', 'submit'),
		'end' => array('form', 'end')
	);

	/**
	 * Init
	 */
	protected function _init() {
		if (isset($this->_config['fieldsets'])) {
			$this->_config['data'] = $this->_config['fieldsets'];
			unset($this->_config['fieldsets']);
		} elseif (isset($this->_config['fields'])) {
			$this->_config['data'] = array(
				array('fields' => $this->_config['fields'])
			);
			unset($this->_config['fields']);
		}
		if (!empty($this->_config['data'])) {
			array_walk($this->_config['data'], array($this, 'addFieldset'));
		}
		parent::_init();
		unset($this->_config['binding'], $this->_config['context']);
	}

	/**
	 * Get/Set form config options
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
	 * @param \lithium\template\view\Renderer $context
	 */
	public function context($context = null) {
		$contextClass = '\lithium\template\view\Renderer';
		if ($context instanceOf $contextClass) {
			$this->_context = $context;
		}
		if ($this->_context instanceOf $contextClass) {
			return $this->_context;
		} elseif ($context === true) {
			$message = "%s requires a renderrer context %s.";
			throw new RuntimeException(sprintf($message, get_class(), $contextClass));
		}
	}

	/**
	 * Get/Set form binding
	 *
	 * @param mixed $binding
	 */
	public function binding($binding = null) {
		if ($binding) {
			$this->_binding = $binding;
		}
		return $this->_binding;
	}

	public function addFieldset($config = array(), $key = null) {
		if (!is_object($config)) {
			$class = $this->_classes['fieldset'];
			if (!isset($config['legend'])) {
				$config['legend'] = !is_int($key) ? $key : null;
			}
			$fieldset = new $class($config);
		} else {
			$fieldset = $config;
		}
		$fieldset->form($this);
		if (is_null($key)) {
			return $this->_data[] = $fieldset;
		}
		return $this->_data[$key] = $fieldset;
	}

	public function getFieldset($key) {
		return $this->offsetGet($key);
	}

	public function removeFieldset($key) {
		return $this->offsetUnset($key);
	}

	public function offsetSet($offset, $value) {
		return $this->addFieldset($value, $offset);
	}

	public function __invoke($context = null) {
		return $this->render($context);
	}

	public function __toString() {
		try {
			return $this->render();
		} catch (RuntimeException $e) {
			return $e->getMessage();
		}
	}

	public function render($context = null) {
		if ($context) {
			$this->context($context);
		}
		$context = $this->context(true);
		$config = $this->_config;
		unset($config['init']);
		$legend = '';
		if(isset($config['legend'])) {
			$template = $context->form->legend();
			$params = array(
				'legend',
				$template,
				array('content' => $config['legend'])
			);
			$legend = $context->scaffold->invokeMethod('_render', $params);
		}
		unset($config['legend']);
		if (!isset($config['action']) && !isset($config['url'])) {
			$config['url'] = $context->request()->url;
		}
		$output = $context->form->create($this->binding(), $config);
		$output.= $legend;
		$output.= implode($this->invoke('render', array($this)));
		$output.= $context->form->submit('save');
		$output.= $context->form->end();
		return $output;
	}
}

?>
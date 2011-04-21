<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace slicedup_scaffold\extensions\helper\scaffold;

class Field extends \lithium\core\Object {

	/**
	 * Field config options (attributes)
	 *
	 * @var array
	 */
	protected $_config = array();

	/**
	 * Auto config params
	 *
	 * @var array
	 */
	protected $_autoConfig = array('fieldset');

	/**
	 * Fieldset object field is bound to
	 *
	 * @var slicedup_scaffold\extensions\helper\scaffold\Fieldset
	 */
	protected $_fieldset = null;

	/**
	 * Init
	 */
	protected function _init() {
		parent::_init();
		unset($this->_config['fieldset']);
	}

	/**
	 * Get/Set field config
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
	 * Get/Set fieldset context
	 *
	 * @param \slicedup_scaffold\extensions\helper\scaffold\Form $form
	 */
	public function fieldset($fieldset = null) {
		$fieldsetClass = '\slicedup_scaffold\extensions\helper\scaffold\Fieldset';
		if ($fieldset instanceOf $fieldsetClass) {
			$this->_fieldset = $fieldset;
		}
		if ($this->_fieldset instanceOf $fieldsetClass) {
			return $this->_fieldset;
		} else if ($fieldset === true) {
			$message = "%s requires fieldset context %s.";
			throw new RuntimeException(sprintf($message, get_class(), $fieldsetClass));
		}
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

	public function render($fieldset = null) {
		if ($fieldset) {
			$this->fieldset($fieldset);
		}
		$form = $this->fieldset(true)->form(true);
		$context = $form->context(true);
		$defaults = array(
			'helper' => 'form',
			'method' => 'field'
		);
		$config = $this->_config + $defaults;
		unset($config['init']);
		$helper = $config['helper'];
		$method = $config['method'];
		unset($config['helper'], $config['method']);
		$name = '';
		if (isset($config['name'])) {
			$name = $config['name'];
			unset($config['name']);
		}
		if (is_callable($method)) {
			return $method($name, $config);
		}
		return $context->$helper->$method($name, $config);
	}
}

?>
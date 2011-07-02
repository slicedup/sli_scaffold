<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_scaffold\extensions\element\scaffold;

class Form extends \sli_tom\template\element\form\Form {

	protected function _init() {
		parent::_init();
		$this->_config += array('submit' => 'Save');
	}

	public function render($context = null) {
		if (!empty($this->_config['submit'])) {
			$submit = self::create('submit', array(
				'title' => $this->_config['submit']
			));
			$this->insert($submit);
			unset($this->_config['submit']);
		}
		$options = $this->options();
		$attributes = $this->attributes();
		$this->context($context);
		$params = $attributes + $options;
		if (!isset($params['action']) && !isset($params['url'])) {
			$attributes['url'] = $this->context(true)->request()->url;
			$this->attributes($attributes);
		}
		return parent::render();
	}
}

?>
<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_scaffold\controllers;

use lithium\util\Inflector;
use lithium\util\String;
use slicedup_util\action\FlashMessage;
use sli_scaffold\core\Scaffold;

class ScaffoldController extends \lithium\action\Controller {

	public $scaffold;

	protected function _init() {
        $this->_render['negotiate'] = true;
        parent::_init();
    }

	public function index() {
		$vars = $this->_scaffoldVars();
		$fields = Scaffold::getSummaryFields($vars['model']);
		$params = $vars + compact('fields');

		$filter = function($self, $params){
			$model = $params['model'];
			if (empty($params['recordSet'])) {
				$params['recordSet'] = $model::all();
			}

			$self->set($params);
			return $params;
		};

		return $this->_filter(__METHOD__, $params, $filter);
	}

	public function view() {
		$vars = $this->_scaffoldVars();
		$fields = Scaffold::getDetailFields($vars['model']);
		$messages = array(
			'notfound' => '{:singular} not found.'
		);
		$params = $vars + compact('fields', 'messages');

		$filter = function($self, $params){
			$model = $params['model'];
			$messages = $params['messages'];
			if (empty($params['record'])) {
				$params['record'] = $model::first($self->request->id);
			}
			$record = $params['record'];
			if (!$record) {
				$self->flash('error', 'notfound', $messages, $params);
				return $self->redirect($params['redirect']);
			}

			$self->set($params);
			return $params;
		};

		return $this->_filter(__METHOD__, $params, $filter);

	}

	public function add() {
		$vars = $this->_scaffoldVars();
		$fields = Scaffold::getCreateFormFields($vars['model']);
		$messages = array(
			'error' => '{:singular} could not be created.',
			'success' => '{:singular} created.'
		);
		$params = $vars + compact('fields', 'messages');

		$filter = function($self, $params){
			$model = $params['model'];
			$messages = $params['messages'];
			if (empty($params['record'])) {
				$params['record'] = $model::create();
			}
			$record = $params['record'];

			if ($self->request->data) {
				if ($record->save($self->request->data)) {
					$self->flash('success', 'success', $messages, $params);
					return $self->redirect($params['redirect']);
				}
				$self->flash('error', 'error', $messages, $params);
			}

			$self->set($params);
			return $params;
		};

		return $this->_filter(__METHOD__, $params, $filter);
	}

	public function edit() {
		$vars = $this->_scaffoldVars();
		$fields = Scaffold::getUpdateFormFields($vars['model']);
		$messages = array(
			'notfound' => '{:singular} not found.',
			'error' => '{:singular} could not be updated.',
			'success' => '{:singular} updated.'
		);
		$params = $vars + compact('fields', 'messages');

		$filter = function($self, $params){
			$model = $params['model'];
			$messages = $params['messages'];
			if (empty($params['record'])) {
				$params['record'] = $model::find($self->request->id);
			}
			$record = $params['record'];

			if (!$record) {
				$self->flash('error', 'notfound', $messages, $params);
				return $self->redirect($params['redirect']);
			}

			if ($self->request->data) {
				if ($record->save($self->request->data)) {
					$self->flash('success', 'success', $messages, $params);
					return $self->redirect($params['redirect']);
				}
				$self->flash('error', 'error', $messages, $params);
			}

			$self->set($params);
			return $params;
		};

		return $this->_filter(__METHOD__, $params, $filter);
	}

	public function delete() {
		$vars = $this->_scaffoldVars();
		$messages = array(
			'notfound' => '{:singular} not found.',
			'error' => '{:singular} could not be deleted.',
			'success' => '{:singular} deleted.'
		);
		$params = $vars + compact('messages');

		$filter = function($self, $params){
			$model = $params['model'];
			$messages = $params['messages'];
			if (empty($params['record'])) {
				$params['record'] = $model::find($self->request->id);
			}
			$record = $params['record'];
			if (!$record) {
				$self->flash('error', 'notfound', $messages, $params);
			} elseif($record->delete()) {
				$self->flash('success', 'success', $messages, $params);
			} else {
				$self->flash('error', 'error', $messages, $params);
			}
			return $self->redirect($params['redirect']);
		};

		return $this->_filter(__METHOD__, $params, $filter);
	}

	public function display() {
		$vars = $this->_scaffoldVars();
		$path = func_get_args() ?: array('display');
		$params = $vars + compact('path');

		$filter = function($self, $params){
			if (!isset($params['template'])) {
				$params['template'] = join('/', (array) $params['path']);
			}
			return $self->render($params);
		};

		return $this->_filter(__METHOD__, $params, $filter);
	}

	public function flash($key, $messageKey, $messages, $data = array()) {
		if (!empty($messages[$messageKey])) {
			$flash = array($messages[$messageKey], $data);
			$message = $this->invokeMethod('_formatFlash', $flash);
			return FlashMessage::invokeMethod($key, $message);
		}
	}

	protected function _formatFlash($message, $vars) {
		if (!is_array($message)) {
			$message = array($message);
		}
		if ($string = array_shift($message)) {
			$string = String::insert($string, $vars);
			array_unshift($message, $string);
			return $message;
		}
	}

	protected function _scaffoldVars () {
		$name = Inflector::humanize($this->scaffold['name']);
		$vars = array(
			'model' => Scaffold::model($this->scaffold['name']),
			'plural' => Inflector::pluralize($name),
			'singular' => Inflector::singularize($name),
			'actions' => Scaffold::handledAction($name),
			'redirect' => array('action' => 'index')
		);
		$this->set($vars);
		return $vars;
	}
}

?>
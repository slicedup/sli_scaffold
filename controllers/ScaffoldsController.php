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
use sli_base\action\FlashMessage;
use sli_scaffold\core\Scaffold;

class ScaffoldsController extends \lithium\action\Controller {

	public $scaffold;

	protected function _init() {
        $this->_render['negotiate'] = true;
        parent::_init();
    }

	public function index() {
		return self::scaffoldAction($this, __FUNCTION__);
	}

	public function view() {
		return self::scaffoldAction($this, __FUNCTION__);
	}

	public function add() {
		return self::scaffoldAction($this, __FUNCTION__);
	}

	public function edit() {
		return self::scaffoldAction($this, __FUNCTION__);
	}

	public function delete() {
		return self::scaffoldAction($this, __FUNCTION__);
	}

	public function display() {
		return self::scaffoldAction($this, __FUNCTION__);
	}

	/**
	 * Invoke a scaffolded action
	 */
	public static function scaffoldAction(&$controller, $action, $invoke = true){
		$actions = static::_actions();
		if (isset($actions[$action])) {
			$vars = static::scaffoldVars($controller);
			$flash = array(get_called_class(), 'flashMessage');
			if (method_exists($controller, 'flashMessage')) {
				$flash = array($controller, 'flashMessage');
			}
			$call = call_user_func($actions[$action], $controller, $action, $vars, $flash);
			if ($invoke) {
				extract($call);
				$method = get_class($controller) . '::' . $action;
				return $controller->invokeMethod('_filter', array($method, $params, $filter));
			}
			return $call;
		}
	}

	/**
	 * Format a  default scaffold flash message by insetring defined data and
	 * passing the the flash message class
	 */
	public static function flashMessage($key, $messageKey, $messages, $data = array()) {
		if (!empty($messages[$messageKey])) {
			$message = $messages[$messageKey];
			if (!is_array($message)) {
				$message = array($message);
			}
			if ($string = array_shift($message)) {
				$string = String::insert($string, $data);
				array_unshift($message, $string);
				return FlashMessage::invokeMethod($key, $message);
			}
		}
	}

	/**
	 * Create & set the default view vars for a scaffolded controller
	 */
	public static function scaffoldVars(&$controller, $set = true) {
		$name = Inflector::humanize($controller->scaffold['_name']);
		$vars = array(
			'scaffold' => $controller->scaffold,
			'model' => Scaffold::model($controller->scaffold['name']),
			'plural' => Inflector::pluralize($name),
			'singular' => Inflector::singularize($name),
			'actions' => Scaffold::handledAction($controller->scaffold['name'], null, $controller->scaffold['prefix']),
			'action' => $controller->request->params['action'],
			'url' => $controller->request->url,
			'redirect' => array('action' => 'index')
		);
		if ($set) {
			$controller->set($vars);
		}
		return $vars;
	}

	/**
	 * Get scaffold action definitions
	 */
	protected static function _actions() {
		return array(
			'index' => function(&$controller, $action, $vars, $flash) {
				$query = array();
				$params = $vars + compact('query');
				$filter = function($self, $params){
					extract($params, EXTR_SKIP);
					if (!isset($recordSet)) {
						$recordSet = $model::all($query);
						$self->set(compact('recordSet'));
					}
					if (!isset($fields)) {
						$fields = Scaffold::getSummaryFields($model);
						$self->set(compact('fields'));
					}
					$self->set($params);
					return $params;
				};
				return compact('params', 'filter');
			},

			'view' => function(&$controller, $action, $vars, $flash) {
				$query = array();
				$messages = array(
					'notfound' => '{:singular} not found.'
				);
				$params = $vars + compact('messages', 'query');
				$filter = function($self, $params) use($flash){
					extract($params, EXTR_SKIP);
					if (!isset($record)) {
						$query = $query ?: $self->request->id;
						$record = $model::first($query);
						$self->set(compact('record'));
					}
					if (!$record) {
						call_user_func_array($flash, array('error', 'notfound', $messages, $params));
						return $self->redirect($redirect);
					}
					if (!isset($fields)) {
						$fields = Scaffold::getDetailFields($record);
						$self->set(compact('fields'));
					}
					$self->set($params);
					return $params;
				};
				return compact('params', 'filter');
			},

			'add' => function(&$controller, $action, $vars, $flash) {
				$data = array();
				$messages = array(
					'error' => '{:singular} could not be created.',
					'success' => '{:singular} created.'
				);
				$params = $vars + compact('messages', 'data');
				$filter = function($self, $params) use($flash){
					extract($params, EXTR_SKIP);
					if (!isset($record)) {
						$record = $model::create($data);
						$self->set(compact('record'));
					}
					if ($self->request->data) {
						if ($record->save($self->request->data)) {
							call_user_func_array($flash, array('success', 'success', $messages, $params));
							return $self->redirect($redirect);
						}
						call_user_func_array($flash, array('error', 'error', $messages, $params));
					}
					if (!isset($fieldsets)) {
						$fieldsets = Scaffold::getCreateFormFields($record);
						$self->set(compact('fieldsets'));
					}
					$self->set($params);
					return $params;
				};
				return compact('params', 'filter');
			},

			'edit' => function(&$controller, $action, $vars, $flash) {
				$query = array();
				$messages = array(
					'notfound' => '{:singular} not found.',
					'error' => '{:singular} could not be updated.',
					'success' => '{:singular} updated.'
				);
				$params = $vars + compact('messages', 'query');
				$filter = function($self, $params) use($flash){
					extract($params, EXTR_SKIP);
					if (!isset($record)) {
						$query = $query ?: $self->request->id;
						$record = $model::first($query);
						$self->set(compact('record'));
					}
					if (!$record) {
						call_user_func_array($flash, array('error', 'notfound', $messages, $params));
						return $self->redirect($redirect);
					}
					if ($self->request->data) {
						if ($record->save($self->request->data)) {
							call_user_func_array($flash, array('success', 'success', $messages, $params));
							return $self->redirect($redirect);
						}
						call_user_func_array($flash, array('error', 'error', $messages, $params));
					}
					if (!isset($fieldsets)) {
						$fieldsets = Scaffold::getUpdateFormFields($record);
						$self->set(compact('fieldsets'));
					}
					$self->set($params);
					return $params;
				};
				return compact('params', 'filter');
			},

			'delete' => function(&$controller, $action, $vars, $flash) {
				$query = array();
				$messages = array(
					'notfound' => '{:singular} not found.',
					'error' => '{:singular} could not be deleted.',
					'success' => '{:singular} deleted.'
				);
				$params = $vars + compact('messages', 'query');
				$filter = function($self, $params) use($flash){
					extract($params, EXTR_SKIP);
					if (!isset($record)) {
						$query = $query ?: $self->request->id;
						$record = $model::first($query);
						$self->set(compact('record'));
					}
					if (!$record) {
						call_user_func_array($flash, array('error', 'notfound', $messages, $params));
					} elseif($record->delete()) {
						call_user_func_array($flash, array('success', 'success', $messages, $params));
					} else {
						call_user_func_array($flash, array('error', 'error', $messages, $params));
					}
					return $self->redirect($redirect);
				};
				return compact('params', 'filter');
			},

			'display' => function(&$controller, $action, $vars, $flash) {
				$path = $controller->request->args ?: array('display');
				$params = $vars + compact('path');
				$filter = function($self, $params){
					if (!isset($params['template'])) {
						$params['template'] = join('/', (array) $params['path']);
					}
					return $self->render($params);
				};
				return compact('params', 'filter');
			}
		);
	}
}

?>
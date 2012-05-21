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

class ScaffoldController extends \lithium\action\Controller {

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
		$name = Inflector::humanize($controller->scaffold['name']);
		$vars = array(
			'model' => Scaffold::model($controller->scaffold['name']),
			'plural' => Inflector::pluralize($name),
			'singular' => Inflector::singularize($name),
			'actions' => Scaffold::handledAction($name),
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
				return compact('params', 'filter');
			},
			
			'view' => function(&$controller, $action, $vars, $flash) {
				$fields = Scaffold::getDetailFields($vars['model']);
				$messages = array(
					'notfound' => '{:singular} not found.'
				);
				$params = $vars + compact('fields', 'messages');
		
				$filter = function($self, $params) use($flash){
					$model = $params['model'];
					$messages = $params['messages'];
					if (empty($params['record'])) {
						$params['record'] = $model::first($self->request->id);
					}
					$record = $params['record'];
					if (!$record) {
						call_user_func_array($flash, array('error', 'notfound', $messages, $params));
						return $self->redirect($params['redirect']);
					}
		
					$self->set($params);
					return $params;
				};
				return compact('params', 'filter');
			},
			
			'add' => function(&$controller, $action, $vars, $flash) {
				$fieldsets = Scaffold::getCreateFormFields($vars['model']);
				$messages = array(
					'error' => '{:singular} could not be created.',
					'success' => '{:singular} created.'
				);
				$params = $vars + compact('fieldsets', 'messages');
		
				$filter = function($self, $params) use($flash){
					$model = $params['model'];
					$messages = $params['messages'];
					if (empty($params['record'])) {
						$params['record'] = $model::create();
					}
					$record = $params['record'];
		
					if ($self->request->data) {
						if ($record->save($self->request->data)) {
							call_user_func_array($flash, array('success', 'success', $messages, $params));
							return $self->redirect($params['redirect']);
						}
						call_user_func_array($flash, array('error', 'error', $messages, $params));
					}
		
					$self->set($params);
					return $params;
				};
				return compact('params', 'filter');
			},
			
			'edit' => function(&$controller, $action, $vars, $flash) {
				$fieldsets = Scaffold::getUpdateFormFields($vars['model']);
				$messages = array(
					'notfound' => '{:singular} not found.',
					'error' => '{:singular} could not be updated.',
					'success' => '{:singular} updated.'
				);
				$params = $vars + compact('fieldsets', 'messages');
		
				$filter = function($self, $params) use($flash){
					$model = $params['model'];
					$messages = $params['messages'];
					if (empty($params['record'])) {
						$params['record'] = $model::find($self->request->id);
					}
					$record = $params['record'];
		
					if (!$record) {
						call_user_func_array($flash, array('error', 'notfound', $messages, $params));
						return $self->redirect($params['redirect']);
					}
		
					if ($self->request->data) {
						if ($record->save($self->request->data)) {
							call_user_func_array($flash, array('success', 'success', $messages, $params));
							return $self->redirect($params['redirect']);
						}
						call_user_func_array($flash, array('error', 'error', $messages, $params));
					}
		
					$self->set($params);
					return $params;
				};
				return compact('params', 'filter');
			},
			
			'delete' => function(&$controller, $action, $vars, $flash) {
				$messages = array(
					'notfound' => '{:singular} not found.',
					'error' => '{:singular} could not be deleted.',
					'success' => '{:singular} deleted.'
				);
				$params = $vars + compact('messages');
				$filter = function($self, $params) use($flash){
					$model = $params['model'];
					$messages = $params['messages'];
					if (empty($params['record'])) {
						$params['record'] = $model::find($self->request->id);
					}
					$record = $params['record'];
					if (!$record) {
						call_user_func_array($flash, array('error', 'notfound', $messages, $params));
					} elseif($record->delete()) {
						call_user_func_array($flash, array('success', 'success', $messages, $params));
					} else {
						call_user_func_array($flash, array('error', 'error', $messages, $params));
					}
					return $self->redirect($params['redirect']);
				};
				return compact('params', 'filter');
			},
			
			'display' => function(&$controller, $action, $vars, $flash) {
				$path = func_get_args() ?: array('display');
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
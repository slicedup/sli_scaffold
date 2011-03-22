<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\action\Dispatcher;
use slicedup_scaffold\Scaffold;

Dispatcher::applyFilter('_callable', function($self, $params, $chain) {
	try {
		$controller = $chain->next($self, $params, $chain);
	} catch (lithium\action\DispatchException $e) {
		if(!$controller = Scaffold::callable($params)) {
			throw $e;
		}
	}
	if (property_exists($controller, 'scaffold')) {
		$name = $params['params']['controller'];
		$controller = Scaffold::prepareController($name, $controller);
		$controller->applyFilter('__invoke', function($self, $params, $chain){
			$name = $self->scaffold['name'];
			$request = $params['request'];
			$options = $params['options'];
			$dispatchParams = $params['dispatchParams'];
			$action = isset($dispatchParams['action']) ? $dispatchParams['action'] : 'index';
			if (!method_exists($self, $action) && Scaffold::action($name, $action)) {
				return Scaffold::invokeAction($name, $self, $params);
			}
			return $chain->next($self, $params, $chain);
		});
	}
	return $controller;
});
?>
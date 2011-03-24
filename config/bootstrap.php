<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\action\Dispatcher;
use slicedup_scaffold\util\Scaffold;

Dispatcher::applyFilter('_callable', function($self, $params, $chain) {
	$name = $params['params']['controller'];
	try {
		$controller = $chain->next($self, $params, $chain);
		if (property_exists($controller, 'scaffold')) {
			Scaffold::prepareController($name, $controller);
			Scaffold::reassignController($name, $controller, $params);
		}
	} catch (lithium\action\DispatchException $e) {
		if(!$controller = Scaffold::callable($params)) {
			throw $e;
		}
		Scaffold::prepareController($name, $controller);
	}
	return $controller;
});
?>
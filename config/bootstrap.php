<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\action\Dispatcher;
use slicedup_scaffold\core\Scaffold;

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
		Scaffold::prepareController($name, $controller, $params);
	}
	return $controller;
});
?>
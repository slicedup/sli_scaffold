<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\core\Libraries;
use lithium\action\Dispatcher;
use slicedup_scaffold\core\Scaffold;

/**
 * Dispatch filter to cathc scaffold requests
 */
Dispatcher::applyFilter('_callable', function($self, $params, $chain) {
	$scaffoldName = Scaffold::name($params['params']);
	$controller = $params['params']['controller'];
	if(!Libraries::locate('controllers', $controller)) {
		if ($controller = Scaffold::controller($scaffoldName)) {
			$params['params']['controller'] = $controller;
		}
	}

	$controller = $chain->next($self, $params, $chain);

	if (property_exists($controller, 'scaffold')) {
		if (isset($controller->scaffold['name'])) {
			$scaffoldName = $controller->scaffold['name'];
		}
		Scaffold::prepare($scaffoldName, $controller, $params);
	}

	return $controller;
});
?>
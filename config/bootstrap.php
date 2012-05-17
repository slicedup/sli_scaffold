<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\core\Libraries;
use lithium\action\Dispatcher;
use sli_scaffold\core\Scaffold;
use lithium\net\http\Media;
use lithium\util\String;

/**
 * Dispatch filter to patch the call to translate in scaffold templates that
 * are g11n enabled, when g11n hanlders are not set
 */
Dispatcher::applyFilter('_callable', function($self, $params, $chain) {
	Media::applyFilter('_handle', function($self, $params, $chain) {
		$t = function($message, $options = array()){
			if (!empty($options)) {
				$message = String::insert($message, $options);
			}
			return $message;
		};
		$params['handler'] += array('outputFilters' => array());
		$params['handler']['outputFilters'] += compact('t');
		return $chain->next($self, $params, $chain);
	});
	return $chain->next($self, $params, $chain);
});

/**
 * Dispatch filter to handle scaffold requests
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
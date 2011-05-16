<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\core\Libraries;
use lithium\net\http\Media;
use lithium\action\Dispatcher;
use lithium\util\Set;
use slicedup_scaffold\core\Scaffold;

/**
 * Dispatch filter to cathc scaffold requests
 */
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

/**
 * Set media paths to add scaffolds
 */
$scaffold = Libraries::get('slicedup_scaffold');
$htmlOptions = array(
	'view' => '\lithium\template\View',
	'paths' => array(
		'template' => array(
			'{:library}/views/{:controller}/{:template}.html.php',
			'{:library}/views/scaffold/{:template}.html.php',
			$scaffold['path'] . '/views/scaffold/{:template}.html.php'
		),
		'layout' => array(
			'{:library}/views/layouts/{:layout}.html.php',
			LITHIUM_APP_PATH . '/views/layouts/{:layout}.html.php'
		)
	)
);
$html = Media::type('html');
Media::type('html', $html['content'], Set::merge($html['options'], $htmlOptions));

$ajax = Media::type('ajax') ?: array_fill_keys(array('options','content'), array());
$ajax['options']  = Set::merge($ajax['options'], array(
	'view' => '\lithium\template\View',
	'paths' => array(
		'template' => array(
			'{:library}/views/{:controller}/{:template}.ajax.php',
			'{:library}/views/scaffold/{:template}.ajax.php',
			$scaffold['path'] . '/views/scaffold/{:template}.ajax.php',
			'{:library}/views/{:controller}/{:template}.html.php',
			'{:library}/views/scaffold/{:template}.html.php',
			$scaffold['path'] . '/views/scaffold/{:template}.html.php'
		),
		'layout' => array(
			'{:library}/views/layouts/{:layout}.ajax.php',
			LITHIUM_APP_PATH . '/views/layouts/{:layout}.ajax.php',
			'{:library}/views/layouts/{:layout}.html.php',
			LITHIUM_APP_PATH . '/views/layouts/{:layout}.html.php'
		)
	),
	'conditions' => array('ajax' => true)
));
$ajax['content'] = array('application/javascript', 'text/javascript', 'text/html', 'application/xhtml+xml');
Media::type('ajax', $ajax['content'], $ajax['options']);

?>
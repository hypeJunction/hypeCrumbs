<?php

/**
 * hypeCrumbs
 *
 * Rebuilds breadcrumbs with a more logical sequence
 * 
 * @author Ismayil Khayredinov <info@hypejunction.com>
 * @copyright Copyright (c) 2017, Ismayil Khayredinov
 */
require_once __DIR__ . '/autoloader.php';

elgg_register_event_handler('init', 'system', function() {

	elgg_register_plugin_hook_handler('prepare', 'breadcrumbs', [hypeJunction\Crumbs\Navigation::class, 'prepare'], 400);
});

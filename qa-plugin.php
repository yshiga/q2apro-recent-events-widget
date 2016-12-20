<?php

/*
	Plugin Name: Recent Events Widget
	Plugin URI: https://github.com/echteinfachtv/q2a-recent-events-widget
	Plugin Description: Displays the newest events of your q2a forum in a widget
	Plugin Version: 0.4
	Plugin Date: 2015-04-21
	Plugin Author: q2apro.com
	Plugin Author URI: http://www.q2apro.com/
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.5
	Plugin Update Check URI: https://raw.github.com/echteinfachtv/q2a-recent-events-widget/master/qa-plugin.php

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.gnu.org/licenses/gpl.html

*/

if ( !defined('QA_VERSION') )
{
	header('Location: ../../');
	exit;
}

// widget
qa_register_plugin_module('widget', 'qa-recent-events-widget.php', 'qa_recent_events_widget', 'Recent Events Widget');

// language file
qa_register_plugin_phrases('qa-recent-events-widget-lang-*.php', 'qa_recent_events_widget_lang');

// setting
qa_register_plugin_module('module', 'q2apro-recent-events-admin.php', 'q2apro_recent_events', 'q2apro Recent Event');

// page
qa_register_plugin_module('page', 'qa-recent-events-page.php', 'qa_recent_events_page', 'Recent Events Page');

<?php
/*
Plugin Name: JPics
Version: 0.1
Description: Adds webservice to handle actions specific to the JPics app
Plugin URI: http://piwigo.org/ext/extension_view.php?
Author: Josselin Manceau
Author URI: https://github.com/joss94
*/

/*
 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

defined('PHPWG_ROOT_PATH') or trigger_error('Hacking attempt!', E_USER_ERROR);

// +-----------------------------------------------------------------------+
// | Define plugin constants                                               |
// +-----------------------------------------------------------------------+
define('JPICS_ID', basename(dirname(__FILE__)));
define('JPICS_PATH',
       PHPWG_PLUGINS_PATH . JPICS_ID . '/');

include_once(JPICS_PATH.'/include/install.inc.php');


function plugin_install()
{
	jpics_install();
	define('jpics_installed', true);
	echo 'INSTALLING JPICS';

}

function plugin_uninstall()
{
	global $prefixeTable;
	
	$query = 'ALTER TABLE '.$prefixeTable.'images drop column is_archived;';
	pwg_query($query);
}

function plugin_activate()
{
	echo 'INSTALLING JPICS';

	if (!defined('jpics_installed')) // a plugin is activated just after its installation
	{
		jpics_install();
	}
}

if (defined('IN_ADMIN') && IN_ADMIN)
	{
		// Initialize global with default instance
		add_event_handler('init', 'JPics_Load',	
				EVENT_HANDLER_PRIORITY_NEUTRAL,
				JPICS_PATH . 'include/admin_events.inc.php');
	}

	// Add webservice apis
	add_event_handler(
		'ws_add_methods', 
		'JPics_Load_WS',
		EVENT_HANDLER_PRIORITY_NEUTRAL,
		JPICS_PATH . 'include/ws_functions.inc.php');


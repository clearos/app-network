<?php

/**
 * Network general settings view.
 *
 * @category   apps
 * @package    network
 * @subpackage views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.  
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('network');
$this->lang->load('base');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
	$read_only = FALSE;
	$buttons = array(
		form_submit_update('submit'),
		anchor_cancel('/app/network')
	);
} else {
	$read_only = TRUE;
	$buttons = array(anchor_edit('/app/network/settings/edit'));
}

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('network/settings/edit'); 
echo form_header(lang('base_settings'));

echo field_dropdown('network_mode', $network_modes, $network_mode, lang('network_network_mode'), $read_only);

if (!clearos_console()) {
    echo field_input('hostname', $hostname, lang('network_hostname'), $read_only);
    echo field_input('internet_hostname', $internet_hostname, lang('network_internet_hostname'), $read_only);
    echo field_input('domain', $domain, lang('network_default_domain'), $read_only);
}

echo field_button_set($buttons);

echo form_footer();
echo form_close();

<?php

/**
 * Network general settings view.
 *
 * @category   ClearOS
 * @package    Network
 * @subpackage Views
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
		anchor_cancel('/app/network/domain')
	);
} else if ($form_type === 'wizard') {
	$read_only = FALSE;
	$buttons = array(
		form_submit_update('submit'),
	);
} else {
	$read_only = TRUE;
	$buttons = array(anchor_edit('/app/network/domain/edit'));
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open('network/domain/edit'); 
echo form_header(lang('network_default_domain'));

echo field_input('domain', $domain, lang('network_domain'), $read_only);

echo field_button_set($buttons);

echo form_footer();
echo form_close();

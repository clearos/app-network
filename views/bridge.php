<?php

/**
 * Network bridge settings view.
 *
 * @category   apps
 * @package    network
 * @subpackage views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2018 ClearFoundation
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

$this->load->language('base');
$this->load->language('network');

///////////////////////////////////////////////////////////////////////////////
// Infobox
///////////////////////////////////////////////////////////////////////////////

if ((count($unconfigured) < 2) && ($form_type === 'add')) {
    $options['buttons'] = array(anchor_cancel('/app/network/iface'));
    echo infobox_warning(lang('base_warning'), lang('network_bridge_required_multiple_interfaces'), $options);
    return;
}

///////////////////////////////////////////////////////////////////////////////
// Form modes
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
    $read_only = FALSE;
    $form_path = '/network/iface/edit_bridge/' . $bridge;
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/network/iface'),
        anchor_delete('/app/network/iface/delete_bridge/' . $bridge)
    );
} else if ($form_type === 'add') {
    $read_only = FALSE;
    $form_path = '/network/iface/add_bridge';
    $buttons = array(
        form_submit_add('submit'),
        anchor_cancel('/app/network/iface'),
    );
} else  {
    $read_only = TRUE;
    $form_path = '';
    $buttons = array(
        anchor_cancel('/app/network/iface'),
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open($form_path);
echo form_header(lang('network_bridge_interface'));

echo fieldset_header(lang('base_settings'));
echo field_input('bridge', $bridge, lang('network_bridge_interface'), TRUE);
echo fieldset_footer();

echo fieldset_header(lang('network_interfaces_in_bridge'));
foreach ($unconfigured as $iface)
    echo field_checkbox('slave_' . $iface, $iface, $iface, $read_only);
echo fieldset_footer();

echo field_button_set($buttons);

echo form_footer();
echo form_close();

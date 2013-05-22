<?php

/**
 * VLAN network interface settings view.
 *
 * @category   apps
 * @package    network
 * @subpackage views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
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
// Form modes
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
    $read_only = FALSE;
    $iface_read_only = TRUE;
    $form_path = '/network/iface/edit_vlan/' . $iface;
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/network/iface/'),
        anchor_delete('/app/network/iface/delete/' . $iface)
    );
} else if ($form_type === 'add') {
    $read_only = FALSE;
    $iface_read_only = FALSE;
    $form_path = '/network/iface/add_vlan';
    $buttons = array(
        form_submit_add('submit'),
        anchor_cancel('/app/network/iface/'),
    );
} else  {
    $read_only = TRUE;
    $iface_read_only = TRUE;
    $form_path = '';
    $buttons = array(
        anchor_cancel('/app/network/iface/'),
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open($form_path);
echo form_header(lang('network_interface'));

// Common settings
//----------------

if ($iface_read_only) {
    echo field_input('iface', $iface, lang('network_interface'), TRUE);
    echo field_input('vlan_id', $iface_info['vlan_id'], lang('network_vlan_id'), TRUE);
} else {
    echo field_simple_dropdown('iface', $ifaces, $iface, lang('network_interface'), $read_only);
    echo field_input('vlan_id', $iface_info['vlan_id'], lang('network_vlan_id'), $read_only);
}

echo field_dropdown('role', $roles, $iface_info['role'], lang('network_role'), $read_only, array('id' => 'role'));
echo field_dropdown('bootproto', $bootprotos, $iface_info['ifcfg']['bootproto'], lang('network_connection_type'), $bootproto_read_only);

// Static
//-------

echo field_input('ipaddr', $iface_info['ifcfg']['ipaddr'], lang('network_ip'), $read_only);
echo field_input('netmask', $iface_info['ifcfg']['netmask'], lang('network_netmask'), $read_only);
echo field_input('gateway', $iface_info['ifcfg']['gateway'], lang('network_gateway'), $read_only);

if ($show_dhcp)
    echo field_checkbox('enable_dhcp', $enable_dhcp, lang('network_enable_dhcp_server'), $read_only);

// DHCP
//-----

echo field_input('hostname', $iface_info['ifcfg']['dhcp_hostname'], lang('network_hostname'), $read_only);
echo field_checkbox('dhcp_dns', $dns, lang('network_automatic_dns_servers'), $read_only);

echo field_button_set($buttons);

echo form_footer();
echo form_close();

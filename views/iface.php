<?php

/**
 * Network interface settings view.
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

use \clearos\apps\network\Iface as Iface;
use \clearos\apps\network\Role as Role;

$this->load->language('base');
$this->load->language('network');

///////////////////////////////////////////////////////////////////////////////
// Form modes
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
    $read_only = FALSE;
    $form_path = '/network/iface/edit/' . $interface;
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/network/iface/'),
        anchor_delete('/app/network/iface/delete/' . $interface)
    );
} else if ($form_type === 'add') {
    $read_only = FALSE;
    $form_path = '/network/iface/add/' . $interface;
    $buttons = array(
        form_submit_add('submit'),
        anchor_cancel('/app/network/iface/'),
    );
} else  {
    $read_only = TRUE;
    $form_path = '';
    $buttons = array(
        anchor_cancel('/app/network/iface/'),
    );
}

$bus = empty($iface_info['bus']) ? '' : $iface_info['bus'];
$vendor = empty($iface_info['vendor']) ? '' : $iface_info['vendor'];
$device = empty($iface_info['device']) ? '' : $iface_info['device'];
$link = (isset($iface_info['link']) && $iface_info['link']) ? lang('base_yes') : lang('base_no');
$speed = (isset($iface_info['speed']) && ($iface_info['speed'] > 0)) ? $iface_info['speed'] . ' ' . lang('base_megabits_per_second') : lang('base_unknown');
$dns = (isset($iface_info['ifcfg']['peerdns'])) ? $iface_info['ifcfg']['peerdns'] : TRUE;

$bootproto_read_only = (isset($iface_info['type']) && $iface_info['type'] === Iface::TYPE_PPPOE) ? TRUE : $read_only;

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open($form_path);
echo form_header(lang('network_interface'));

///////////////////////////////////////////////////////////////////////////////
// General information
///////////////////////////////////////////////////////////////////////////////

echo fieldset_header(lang('base_information'));

if ($vendor)
    echo field_input('vendor', $vendor, lang('network_vendor'), TRUE);

if ($device)
    echo field_input('device', $device, lang('network_device'), TRUE);

if ($bus)
    echo field_input('bus', $bus, lang('network_bus'), TRUE);

echo field_input('link', $link, lang('network_link'), TRUE);
echo field_input('speed', $speed, lang('network_speed'), TRUE);
echo fieldset_footer();

///////////////////////////////////////////////////////////////////////////////
// Settings
///////////////////////////////////////////////////////////////////////////////

// Common settings
//----------------

echo fieldset_header(lang('base_settings'));
echo field_input('interface', $interface, lang('network_interface'), TRUE);
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

// PPPoE
//------

echo field_input('username', $iface_info['ifcfg']['user'], lang('base_username'), $read_only);
echo field_input('password', $password, lang('base_password'), $read_only);
echo field_input('mtu', $iface_info['ifcfg']['mtu'], lang('network_mtu'), $read_only);
echo field_checkbox('pppoe_dns', $dns, lang('network_automatic_dns_servers'), $read_only);

///////////////////////////////////////////////////////////////////////////////
// Maximum Upload/Download
///////////////////////////////////////////////////////////////////////////////

echo fieldset_header(lang('network_maximum_bandwith_available'), array('id' => 'fieldset_header_bandwidth'));
echo field_input('max_upstream', $max_upstream, lang('network_upstream') . ' (' . lang('base_kilobits_per_second') . ')', $read_only);
echo field_input('max_downstream', $max_downstream, lang('network_downstream') . ' (' . lang('base_kilobits_per_second') . ')', $read_only);
echo fieldset_footer();

///////////////////////////////////////////////////////////////////////////////
// Upstream Proxy
///////////////////////////////////////////////////////////////////////////////

echo fieldset_header(lang('network_upstream_proxy'), array('id' => 'fieldset_header_upstream_proxy'));
echo field_input('proxy_server', $proxy_server, lang('network_proxy_server'), $read_only);
echo field_input('proxy_port', $proxy_port, lang('network_port'), $read_only);
echo field_input('proxy_username', $proxy_username, lang('base_username'), $read_only);
echo field_password('proxy_password', $proxy_password, lang('base_password'), $read_only);
echo fieldset_footer();

///////////////////////////////////////////////////////////////////////////////
// Wireless
///////////////////////////////////////////////////////////////////////////////

if ($iface_info['type'] === Iface::TYPE_WIRELESS) {
    echo fieldset_header(lang('network_wireless'));
    echo field_dropdown('mode', $modes, $iface_info['wireless_mode'], lang('wireless_mode'), $read_only);
    echo field_input('ssid', $iface_info['wireless_ssid'], lang('wireless_ssid'), $read_only);
    echo field_password('passphrase', $iface_info['wireless_passphrase'], lang('wireless_passphrase'), $read_only);
    echo field_dropdown('channel', $channels, $iface_info['wireless_channel'], lang('wireless_channel'), $read_only);
    echo fieldset_footer();
}

///////////////////////////////////////////////////////////////////////////////
// Common footer
///////////////////////////////////////////////////////////////////////////////

echo field_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();

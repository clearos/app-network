<?php

/**
 * Network mode wizard view.
 *
 * @category   apps
 * @package    network
 * @subpackage views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
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

$this->lang->load('base');
$this->lang->load('network');

$read_only = FALSE;

///////////////////////////////////////////////////////////////////////////////
// Content
///////////////////////////////////////////////////////////////////////////////

// FIXME: translate
// TODO: move HTML/CSS elements to theme

$gateway_label = "<span style='font-size: 13px; font-weight: bold;'>Gateway Mode</span>";
$gateway_options['image'] = clearos_app_htdocs('network') . '/gateway.png';
$gateway_options['label_help'] = "<p style='font-size: 13px;  padding-left: 25px'>Gateway mode is used to connect a network of systems to the Internet or internal network.  You need at least two network cards for this mode.</p>";

$private_label = "<span style='font-size: 13px; font-weight: bold;'>Private Server Mode</span>";
$private_options['image'] = clearos_app_htdocs('network') . '/private_server.png';
$private_options['label_help'] = "<p style='font-size: 13px;  padding-left: 25px'>This mode is appropriate for standalone servers installed on a protected network, for example, an office network.  The firewall is disabled in this mode.</p>";

$public_label = "<span style='font-size: 13px; font-weight: bold;'>Public Server Mode</span>";
$public_options['image'] = clearos_app_htdocs('network') . '/public_server.png';
$public_options['label_help'] = "<p style='font-size: 13px; padding-left: 25px;'>This mode is appropriate for standalone servers installed in hostile environment, for example a data center or public hotspot.</p>";

$checked[$network_mode] = TRUE;

// Put what makes sense at the top of the list
if ($iface_count > 1) {
    $radio_buttons = array(
        field_radio_set_item('gateway', 'network_mode', $gateway_label, $checked['gateway'], $read_only, $gateway_options),
        field_radio_set_item('trustedstandalone', 'network_mode', $private_label, $checked['trustedstandalone'], $read_only, $private_options),
        field_radio_set_item('standalone', 'network_mode', $public_label, $checked['standalone'], $read_only, $public_options),
    );
} else {
    $gateway_options['disabled'] = TRUE;
    $gateway_label = "<span style='font-size: 13px; font-weight: bold;'>Gateway Mode (Unavailable)</span>";
    $gateway_options['label_help'] = "<p style='font-size: 13px;  padding-left: 25px'>Gateway mode is used to connect a network of systems to the Internet or internal network.  <span style='color: red'>You need at least two network cards for this mode.</span></p>";

    $radio_buttons = array(
        field_radio_set_item('trustedstandalone', 'network_mode', $private_label, $checked['trustedstandalone'], $read_only, $private_options),
        field_radio_set_item('standalone', 'network_mode', $public_label, $checked['standalone'], $read_only, $public_options),
        field_radio_set_item('gateway', 'network_mode', $gateway_label, $checked['gateway'], $read_only, $gateway_options),
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open('network/mode', array('id' => 'mode_form'));
echo form_header(lang('base_settings'));

echo field_radio_set(
    '',
    $radio_buttons
);

echo form_footer();
echo form_close();

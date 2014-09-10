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

$gateway_label = "Gateway Mode";
$gateway_options['image'] = clearos_app_htdocs('network') . '/gateway.png';
$gateway_options['label_help'] = "Gateway mode is used to connect a network of systems to the Internet or internal network.  You need at least two network cards for this mode.";

$private_label = "Private Server Mode";
$private_options['image'] = clearos_app_htdocs('network') . '/private_server.png';
$private_options['label_help'] = "This mode is appropriate for standalone servers installed on a protected network, for example, an office network.  The firewall is disabled in this mode.";

$public_label = "Public Server Mode";
$public_options['image'] = clearos_app_htdocs('network') . '/public_server.png';
$public_options['label_help'] = "This mode is appropriate for standalone servers installed in hostile environment, for example a data center or public hotspot.";

$checked[$network_mode] = TRUE;

// Put what makes sense at the top of the list
if ($iface_count > 1) {
    $radio_buttons = array(
        radio_set_item('gateway', 'network_mode', $gateway_label, $checked['gateway'], $gateway_options),
        radio_set_item('trustedstandalone', 'network_mode', $private_label, $checked['trustedstandalone'], $private_options),
        radio_set_item('standalone', 'network_mode', $public_label, $checked['standalone'], $public_options),
    );
} else {
    $gateway_options['disabled'] = TRUE;
    $gateway_label = "Gateway Mode (Unavailable)";
    $gateway_options['label_help'] = "Gateway mode is used to connect a network of systems to the Internet or internal network.  <span class='theme-text-alert'>You need at least two network cards for this mode.</span>";

    $radio_buttons = array(
        radio_set_item('trustedstandalone', 'network_mode', $private_label, $checked['trustedstandalone'], $private_options),
        radio_set_item('standalone', 'network_mode', $public_label, $checked['standalone'], $public_options),
        radio_set_item('gateway', 'network_mode', $gateway_label, $checked['gateway'], $gateway_options),
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open('network/mode', array('id' => 'mode_form'));
echo form_header(lang('base_settings'));

echo radio_set(
    'network_mode',
    $radio_buttons,
    'network_mode_options'
);

echo form_footer();
echo form_close();

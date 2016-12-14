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

$gateway_label = lang('network_gateway_mode');
$gateway_options['image'] = clearos_app_htdocs('network') . '/gateway.png';
$gateway_options['label_help'] = lang('network_gateway_mode_help');

$private_label = lang('network_private_server_mode');
$private_options['image'] = clearos_app_htdocs('network') . '/private_server.png';
$private_options['label_help'] = lang('network_private_server_mode_help');

$public_label = lang('network_public_server_mode');
$public_options['image'] = clearos_app_htdocs('network') . '/public_server.png';
$public_options['label_help'] = lang('network_public_server_mode_help');

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
    $gateway_label = lang('network_gateway_mode_unavailable');
    $gateway_options['label_help'] = lang('network_gateway_mode_unavailable_help') . ' ' .
        "<span class='theme-text-alert'>" . lang('network_gateway_mode_unavailable_tip') . '</span>';

    $radio_buttons = array(
        radio_set_item('trustedstandalone', 'network_mode', $private_label, $checked['trustedstandalone'], $private_options),
        radio_set_item('standalone', 'network_mode', $public_label, $checked['standalone'], $public_options),
        radio_set_item('gateway', 'network_mode', $gateway_label, $checked['gateway'], $gateway_options),
    );
}

if ($network_mode == 'trustedgateway') {
    $trustedgateway_label = lang('network_trusted_gateway_mode');
    $trustedgateway_options['label_help'] = lang('network_trusted_gateway_mode_help');
    $radio_buttons[] = radio_set_item('trustedgateway', 'network_mode', $trustedgateway_label, $checked['trustedgateway'], $trustedgateway_options);
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open('network/mode', array('id' => 'mode_form'));
echo form_header(lang('base_settings'));

echo radio_set(
    $radio_buttons,
    'network_mode_options'
);

echo form_footer();
echo form_close();

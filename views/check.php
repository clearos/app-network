<?php

/**
 * Network check view.
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

$this->load->language('network');

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

foreach ($rules as $rule) {
    if ($rule['firewalled'])
        $firewalled = "<span class='theme-text-alert'>" . lang('network_blocked') . "</span>";
    else
        $firewalled = "<span class='theme-text-ok'>" . lang('network_allowed') . "</span>";

    $item['title'] = $rule['name'];
    $item['anchors'] = '';
    $item['details'] = array(
        $rule['name'],
        $rule['protocol'],
        $rule['port'],
        $firewalled
    );

    $items[] = $item;
}

$options['no_action'] = TRUE;

$headers = array(
    lang('base_description'),
    lang('network_protocol'),
    lang('network_port'),
    lang('base_status')
);

ob_start();
echo summary_table(
    lang('network_firewall_summary'),
    array(),
    $headers,
    $items,
    $options
);
$summary_table = ob_get_clean();


///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

if ($type == 'info') {
    $options['buttons'] = array(
        anchor_custom('/app/' . $app_name . '/network/add', lang('network_allow_connections')),
        anchor_custom('/app/incoming_firewall', lang('network_manual_configuration'), 'low'),
    );

    echo infobox_info(
        lang('base_information'),
        lang('network_firewall_configuration_warning'),
        $options
    );
} else {
    $options['buttons'] = array(
        anchor_custom('/app/' . $app_name . '/network/add', lang('network_allow_connections')),
        anchor_custom('/app/incoming_firewall', lang('network_manual_configuration'), 'low'),
        anchor_custom('/app/' . $app_name . '/network/dismiss', lang('network_hide_this_warning'), 'low'),
    );

    echo infobox_warning(
        lang('base_warning'),
        lang('network_firewall_configuration_warning') . '<br><br>' . $summary_table,
        $options
    );
}

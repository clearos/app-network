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

$this->lang->load('network');
$this->lang->load('base');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('network_interface'),
    lang('network_role'),
    lang('network_type'),
    lang('network_ip'),
    lang('network_link'),
    lang('network_max_downstream'),
    lang('network_max_upstream'),
);

///////////////////////////////////////////////////////////////////////////////
// Title
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'wizard')
    $title = lang('base_settings');
else
    $title = lang('network_network_interfaces');

///////////////////////////////////////////////////////////////////////////////
// Anchors 
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'wizard') {
    $anchors = array();
} else {
    $anchors = array();
    $anchors[] = anchor_custom('/app/network/iface/add_vlan', lang('network_add_vlan_interface'));
    $anchors[] = anchor_custom('/app/network/iface/add_virtual', lang('network_add_virtual_interface'));
}

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

$types = array();
$items = array();
$items_grouped = array();

foreach ($network_interfaces as $interface => $detail) {

    // Create summary
    $ip = empty($detail['address']) ? '' : $detail['address'];
    $speed = (isset($detail['speed']) && $detail['speed'] > 0) ? $detail['speed'] . " " . lang('base_megabits') : '';
    $role = isset($detail['roletext']) ? $detail['roletext'] : '';
    $bootproto = isset($detail['ifcfg']['bootprototext']) ? $detail['ifcfg']['bootprototext'] : '';

    if (isset($detail['link'])) {
        if ($detail['link'] == -1)
            $link = '';
        else if ($detail['link'] == 0)
            $link = lang('base_no');
        else
            $link = lang('base_yes');
    } else {
        $link = '';
    }

    // Behavior when interface is configured
    //--------------------------------------

    if ($detail['configured']) {

        // Show edit/delete for supported Ethernet and PPPoE types
        //--------------------------------------------------------

        if (($detail['type'] === Iface::TYPE_ETHERNET) 
            || ($detail['type'] === Iface::TYPE_PPPOE)
            || ($detail['type'] === Iface::TYPE_WIRELESS)) {
            $buttons = array(
                anchor_edit('/app/network/iface/edit/' . $interface),
                anchor_delete('/app/network/iface/delete/' . $interface)
            );
            if ($detail['role'] === Role::ROLE_EXTERNAL && $form_type != 'wizard')
                $buttons[] = anchor_custom(
                    '#',
                    icon('speedometer'),
                    'low',
                    array(
                        'no_escape_html' => TRUE,
                        'class' => 'network-speed-test',
                        'data' =>
                            array(
                                'original-title' => lang('network_speed_test'),
                                'toggle' => 'tooltip',
                                'container' => 'body',
                                'interface' => $interface
                            )
                    )
                );

        // Virtual interfaces
        //-------------------

        } else if ($detail['type'] === Iface::TYPE_VIRTUAL) {
            $buttons = array(
                anchor_edit('/app/network/iface/edit_virtual/' . $interface),
                anchor_delete('/app/network/iface/delete/' . $interface)
            );

        // VLAN interfaces
        //----------------

        } else if ($detail['type'] === Iface::TYPE_VLAN) {
            $buttons = array(
                anchor_edit('/app/network/iface/edit_vlan/' . $interface),
                anchor_delete('/app/network/iface/delete/' . $interface)
            );

        // Show view for bridged, bonded, and wireless types
        //--------------------------------------------------

        } else if (($detail['type'] === Iface::TYPE_BONDED) || ($detail['type'] === Iface::TYPE_BRIDGED)) {
            $buttons = array(
                anchor_view('/app/network/iface/view/' . $interface),
            );

        // Skip all other unsupported types
        //---------------------------------

        } else {
            continue;
        }

        if ($detail['role'] === Role::ROLE_EXTERNAL) {
            $max_downstream = "<span id='max-dn-$interface'>" . $detail['max_downstream'] . ' ' . lang('base_kilobits_per_second') . "</span>";
            $max_upstream = "<span id='max-up-$interface'>" . $detail['max_upstream'] . ' ' . lang('base_kilobits_per_second') . "</span>";
        } else {
            $max_downstream = lang('base_not_applicable');
            $max_upstream = lang('base_not_applicable');
        }

    // Behavior when interface is not configured
    //------------------------------------------

    } else {
        // Show Ethernet interfaces
        //-------------------------

        if (($detail['type'] === Iface::TYPE_ETHERNET) && !preg_match('/^ppp/', $interface)) {
            $buttons = array(
                anchor_add('/app/network/iface/add/' . $interface),
            );

        // Show wireless interfaces, but only actions if app-wireless installed
        //---------------------------------------------------------------------

        } else if ($detail['type'] === Iface::TYPE_WIRELESS) {
            if ($wireless_installed) {
                $buttons = array(
                    anchor_add('/app/network/iface/add/' . $interface),
                );
            } else {
                $buttons = array();
            }

        // Skip all other unsupported types
        //---------------------------------

        } else {
            continue;
        }

        $max_downstream = lang('base_not_applicable');
        $max_upstream = lang('base_not_applicable');
    }

    ///////////////////////////////////////////////////////////////////////////
    // Item details
    ///////////////////////////////////////////////////////////////////////////

    $item['title'] = $interface;
    $item['action'] = '';
    $item['anchors'] = button_set($buttons);
    $item['details'] = array(
        $interface,
        "<span id='role_" . $interface . "'>$role</span>",
        "<span id='bootproto_" . $interface . "'>$bootproto</span>",
        "<span id='ip_" . $interface . "'>$ip</span>",
        "<span id='link_" . $interface . "'>$link</span>",
        $max_downstream,
        $max_upstream,
    );

    $items[] = $item;

    $types[$detail['type']] = TRUE;
    array_unshift($item['details'], $detail['type']);

    $items_grouped[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

$options['id'] = 'network_summary';
$options['responsive'] = array(5 => 'none', 6 => 'none');

if (count($types) > 1) {
    $options['grouping'] = TRUE;
    $items = $items_grouped;
    array_unshift($headers, lang('network_type'));
}

echo summary_table(
    $title,
    $anchors,
    $headers,
    $items,
    $options
);

echo modal_confirm(
    lang('network_speed_test'),
    lang('network_speed_test_help') .
    "<div id='speed-test-container' class='theme-hidden'>" .

    row_open() .
    column_open(6, NULL, NULL, array('class' => 'theme-align-right')) .
    lang('network_ping') .
    column_close() .
    column_open(6) .
    "<div id='speed-test-result-ping'></div>" .
    column_close() .
    row_close() .

    row_open() .
    column_open(6, NULL, NULL, array('class' => 'theme-align-right')) .
    lang('network_downstream') .
    column_close() .
    column_open(6) .
    "<div id='speed-test-result-downstream'></div>" .
    column_close() .
    row_close() .

    row_open() .
    column_open(6, NULL, NULL, array('class' => 'theme-align-right')) .
    lang('network_upstream') .
    column_close() .
    column_open(6) .
    "<div id='speed-test-result-upstream'></div>" .
    column_close() .
    row_close() .

    "<div class='text-center theme-hidden' id='clearos-speed-test-save-container' style='padding-top:20px;'>" .
    anchor_custom('#', lang('network_save_speed_test_results'), 'high', array('id' => 'clearos-speed-test-save')) .
    "</div>" .

    "</div>",
    array(),
    array('class' => 'network-speed-test'),
    NULL,
    'start-speed-test',
    array('stay_open_on_confirm' => TRUE)
);

///////////////////////////////////////////////////////////////////////////////
// External interface warning
///////////////////////////////////////////////////////////////////////////////

if (count($external_interfaces) == 0)
    echo infobox_warning(lang('base_warning'), lang('network_lang_one_external_interface_required'));

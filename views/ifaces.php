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

///////////////////////////////////////////////////////////////////////////////
// External interface warning
///////////////////////////////////////////////////////////////////////////////

if (count($external_interfaces) == 0)
    echo infobox_warning(lang('base_warning'), lang('network_lang_one_external_interface_required'));

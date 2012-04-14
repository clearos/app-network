<?php

/**
 * Network DNS server view.
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

if ($is_automatic) {
    $is_automatic_warning = TRUE;
    $edit_button_text = lang('network_override');
} else {
    $is_automatic_warning = FALSE;
    $edit_button_text = lang('base_edit');
}

if ($form_type === 'edit') {
    $form = '/network/dns/edit';
    $read_only = FALSE;
    if ($is_wizard) {
        $buttons = array();
    } else {
        $buttons = array(
            form_submit_update('submit'),
            anchor_cancel('/app/network/dns')
        );
    }
} else if ($form_type === 'wizarwfsdfasdfasfd') {
    if ($is_automatic) {
        $read_only = TRUE;
        $buttons = array(anchor_custom('/app/network/dns/wizard_edit', lang('network_override')));
    } else {
        $read_only = FALSE;
        $buttons = array();
    }
} else {
    $form = '/network/dns/view';
	$read_only = TRUE;
    $is_automatic_warning = FALSE; // Don't show auto warning in view only mode
    $buttons = array(anchor_custom('/app/network/dns/edit', $edit_button_text));
}

$dns_count = count($dns);
$dns_fields = $dns_count;

// Append a field for adding a DNS server
if (! $read_only) {
    $dns_fields++;

    // Always show at least 1 DNS server
    if ($dns_fields < 3)
        $dns_fields = 3;

// Show 2 DNS servers when in automatic mode
} else if ($is_automatic) {
    $dns_fields = 2;
}

///////////////////////////////////////////////////////////////////////////////
// Warnings
///////////////////////////////////////////////////////////////////////////////

if (!($form_type === 'wizard') && !$read_only) {
    if ($dns_count === 0)
        echo infobox_warning(lang('network_network_degraded'), lang('network_no_dns_servers_warning'));
    else if ($dns_count === 3)
        echo infobox_highlight(lang('network_best_practices'), lang('network_too_many_dns_servers_warning'));
    else if ($dns_count > 3)
        echo infobox_warning(lang('network_network_degraded'), lang('network_too_many_dns_servers_warning'));
}

if ($is_automatic_warning)
    echo infobox_highlight(lang('network_dns_automatically_configured'), lang('network_dns_automatically_configured_message'));

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open($form, array('id' => 'dns_form')); 
echo form_header(lang('network_dns'));

///////////////////////////////////////////////////////////////////////////////
// Form fields and buttons
///////////////////////////////////////////////////////////////////////////////

if (($read_only) && $is_automatic)
    echo field_view(lang('network_dns_servers'), '', 'dns_auto');

for ($inx = 1; $inx < $dns_fields + 1; $inx++) {
    $dns_server = isset($dns[$inx-1]) ? $dns[$inx-1] : '';

    // TODO: the variable name used in read-only mode is not javascript friendly
    // For now, use field_view and a simplified variable name.
    // echo field_input('dns[' . $inx . ']', $dns_server, lang('network_dns_server') . " #" . $inx, $read_only);

    if (($read_only) && $is_automatic)
        echo field_view(lang('network_dns_server') . " #" . $inx, $dns_server, 'dns' . ($inx-1));
    else
        echo field_input('dns[' . $inx . ']', $dns_server, lang('network_dns_server') . " #" . $inx, $read_only);
}

if (! empty($buttons))
    echo field_button_set($buttons);

///////////////////////////////////////////////////////////////////////////////
// Form close
///////////////////////////////////////////////////////////////////////////////

echo form_footer();
echo form_close();
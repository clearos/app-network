<?php

/**
 * Network ajax helper.
 *
 * @category   apps
 * @package    network
 * @subpackage javascript
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011-2013 ClearFoundation
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
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');
clearos_load_language('network');

///////////////////////////////////////////////////////////////////////////////
// J A V A S C R I P T
///////////////////////////////////////////////////////////////////////////////

header('Content-Type:application/x-javascript');
?>

$(document).ready(function() {

    // Translations
    //-------------

    lang_yes = '<?php echo lang("base_yes"); ?>';
    lang_no = '<?php echo lang("base_no"); ?>';
    lang_unknown = '<?php echo lang("base_unknown"); ?>';
    lang_megabits_per_second = '<?php echo lang("base_megabits_per_second"); ?>';
    lang_offline = '<?php echo lang("network_offline"); ?>';
    lang_waiting = '<?php echo lang("base_waiting"); ?>';
    lang_connected = '<?php echo lang("network_connected"); ?>';
    lang_dns_failed = '<?php echo lang("network_dns_lookup_failed"); ?>';
    lang_dns_passed = '<?php echo lang("network_dns_lookup_passed"); ?>';

    // Defaults
    //---------

    $('#dns0_field').hide();
    $('#dns1_field').hide();

    // Wizard next button handling
    //----------------------------

    if (($(location).attr('href').match('.*\/iface\/edit') != null) 
        || ($(location).attr('href').match('.*\/iface\/add') != null)
        || ($(location).attr('href').match('.*\/dns\/edit\/verify') != null)) {
        $('#theme_wizard_nav_next').hide();
        $('#theme_wizard_nav_previous').hide();
    }

    $("#wizard_nav_next").click(function(){
        if ($(location).attr('href').match('.*\/hostname$') != null)
            $('form#hostname_form').submit();
        else if ($(location).attr('href').match('.*\/mode$') != null)
            $('form#mode_form').submit();
        else if ($(location).attr('href').match('.*\/domain$') != null)
            $('form#domain_form').submit();
        else if ($(location).attr('href').match('.*\/dns\/edit\/verify') != null)
            window.location = '/app/base/wizard/next_step';
        else if ($(location).attr('href').match('.*\/dns\/edit') != null)
            $('form#dns_form').submit();
        else if ($(location).attr('href').match('.*\/dns') != null)
            window.location = '/app/network/dns/edit/verify';
        else if ($(location).attr('href').match('.*\/iface') != null)
            window.location = '/app/base/wizard/next_step';
    });

    // Network interface configuration
    //--------------------------------

    if ($('#role').length != 0)  {
        setInterfaceFields();
        setGateway();
        if ($('#interface').length != 0)
            getInterfaceInfo();

        $('#role').change(function() {
            setGateway();
        });

        $('#bootproto').change(function() {
            setInterfaceFields();
            setGateway();
        });

        if ($('#passphrase').length != 0) {
            setWirelessFields();
            $('#mode').change(function() {
                setWirelessFields();
            });
        }
    }

    // Sidebar report
    //---------------

    if ($('#network_status_label').length != 0) {
        getNetworkStatusInfo();
        getDnsStatusInfo();
    }

    // DNS details and interfaces
    //---------------------------

    if (($('#dns_auto_text').length != 0) || ($('#network_summary').length != 0))
        getAllNetworkInfo();
});

/**
 * Ajax call to get network information for all interfaces
 */

function getAllNetworkInfo() {

    $.ajax({
        url: '/app/network/get_all_info',
        method: 'GET',
        dataType: 'json',
        success : function(payload) {
            showAllNetworkInfo(payload);
            window.setTimeout(getAllNetworkInfo, 3000);
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            window.setTimeout(getAllNetworkInfo, 3000);
        }
    });
}

/**
 * Ajax call for network status information.
 */

function getDnsStatusInfo() {

    $.ajax({
        url: '/app/network/get_dns_status_info',
        method: 'GET',
        dataType: 'json',
        success : function(payload) {
            showDnsStatusInfo(payload);
            window.setTimeout(getDnsStatusInfo, 3000);
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            window.setTimeout(getDnsStatusInfo, 3000);
        }
    });
}

/**
 * Ajax call for network status information.
 */

function getNetworkStatusInfo() {
    $.ajax({
        url: '/app/network/get_network_status_info',
        method: 'GET',
        dataType: 'json',
        success : function(payload) {
            showNetworkStatusInfo(payload);
            window.setTimeout(getNetworkStatusInfo, 3000);
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            window.setTimeout(getNetworkStatusInfo, 3000);
        }
    });
}

/**
 * Ajax call to get network information.
 */

function getInterfaceInfo() {
    var iface = $('#interface').val();

    $.ajax({
        url: '/app/network/get_info/' + iface,
        method: 'GET',
        dataType: 'json',
        success : function(payload) {
            showInterfaceInfo(payload);
            window.setTimeout(getInterfaceInfo, 1000);
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            window.setTimeout(getInterfaceInfo, 1000);
        }
    });
}

/**
 * Updates network information (IP, link) for all interfaces
 */

function showAllNetworkInfo(payload) {

    // Network interface details
    //--------------------------

    for (var iface in payload['network']) {
        var link_text = (payload['network'][iface].link) ? lang_yes : lang_no;
        var ip_text = '';

        if (payload['network'][iface].configured)
             ip_text = (payload['network'][iface].address) ? payload['network'][iface].address : '<div class="theme-loading-small"></div>';

        // Funky - jQuery selectors need . and : escaped.  Should probably just create a global function
        iface_select = iface.replace(/(:|\.)/g,'\\$1');

        $('#role_' + iface_select).html(payload['network'][iface].roletext);
        $('#bootproto_' + iface_select).html(payload['network'][iface].bootprototext);
        $('#ip_' + iface_select).html(ip_text);
        $('#link_' + iface_select).html(link_text);
    }

    // DNS server details
    //-------------------

    if (payload['dns_servers'].length == 0) {
        $('#dns_auto_text').html('<span class="theme-loading-small">' + lang_waiting + '</span>');
        $('#dns_auto_field').show();
        $('#dns0_field').hide();
        $('#dns1_field').hide();
    } else if (payload['dns_servers'].length == 1) {
        $('#dns_auto_text').html('');
        $('#dns_auto_field').hide();
        $('#dns0_field').show();
        $('#dns1_field').hide();
    } else {
        $('#dns_auto_text').html('');
        $('#dns_auto_field').hide();
        $('#dns0_field').show();
        $('#dns1_field').show();
    }

    for (dns_index in payload['dns_servers']) {
        var dns_html_index = dns_index + 1;
        $('#dns' + dns_index + '_text').html(payload['dns_servers'][dns_index]);
    }
}

/**
 * Shows DNS status information.
 */

function showDnsStatusInfo(payload) {

    var dns_status_message = '';

    if (payload['dns_status'] == 'online') {
        dns_status_message = '<span class=\'theme-text-good-status\'>' + lang_connected + '</span>';

        if ($('#theme_wizard_nav_next').length != 0) {
            $('#dns_test_message').html(lang_dns_passed);
            $('#dns_test_message').removeClass('theme-text-bad-status');

            $('#theme_wizard_nav_next').show();
            $('#theme_wizard_nav_previous').show();
            $('#dns_edit_anchor').hide();
        }
    } else {
        dns_status_message = '<span class=\'theme-text-bad-status\'>' + lang_offline + '</span>';

        if ($('#theme_wizard_nav_next').length != 0) {
            $('#dns_test_message').html(lang_dns_failed);
            $('#dns_test_message').addClass('theme-text-bad-status');

            $('#dns_edit_anchor').show();
        }
    }

    $('#dns_status_text').html(dns_status_message);
}

/**
 * Shows network status information.
 */

function showNetworkStatusInfo(payload) {

    var connection_status_message = '';
    var gateway_status_message = '';

    if (payload['connection_status'] == 'online')
        connection_status_message = '<span class=\'theme-text-good-status\'>' + lang_connected + '</span>';
    else if (payload['connection_status'] == 'online_no_dns')
        connection_status_message = '<span class=\'theme-text-good-status\'>' + lang_connected + '</span>';
    else
        connection_status_message = '<span class=\'theme-text-bad-status\'>' + lang_offline + '</span>';

    if (payload['gateway_status'] == 'online')
        gateway_status_message = '<span class=\'theme-text-good-status\'>' + lang_connected + '</span>';
    else
        gateway_status_message = '<span class=\'theme-text-bad-status\'>' + lang_offline + '</span>';

    $('#network_status_text').html(connection_status_message);
    $('#gateway_status_text').html(gateway_status_message);
}

/**
 * Updates network information (IP, link)
 */

function showInterfaceInfo(payload) {
    var link_text = (payload.link) ? lang_yes : lang_no;
    var speed_text = (payload.speed > 0) ? payload.speed + ' ' + lang_megabits_per_second : lang_unknown;

    $('#link_text').html(link_text);
    $('#speed_text').html(speed_text);
}

/**
 * Sets visibility of gateway field.
 *
 * The gateway field should be shown on external interfaces with static IPs.
 */

function setGateway() {
    role = $('#role').val();
    type = $('#bootproto').val();

    if (type == 'static') {
        if (role == 'EXTIF') {
            $('#gateway_field').show();
            $('#enable_dhcp_field').hide();
        } else {
            $('#gateway_field').hide();
            $('#enable_dhcp_field').show();
        }
    }
}

/**
 * Sets visibility of network interface fields.
 */

function setInterfaceFields() {
    // Static
    $('#ipaddr_field').hide();
    $('#netmask_field').hide();
    $('#gateway_field').hide();
    $('#enable_dhcp_field').hide();

    // DHCP
    $('#hostname_field').hide();
    $('#dhcp_dns_field').hide();

    // PPPoE
    $('#username_field').hide();
    $('#password_field').hide();
    $('#mtu_field').hide();
    $('#pppoe_dns_field').hide();

    type = $('#bootproto').val();

    if (type == 'static') {
        $('#ipaddr_field').show();
        $('#netmask_field').show();
        $('#gateway_field').show();
        $('#enable_dhcp_field').show();
    } else if (type == 'dhcp') {
        $('#hostname_field').show();
        $('#dhcp_dns_field').show();
    } else if (type == 'pppoe') {
        $('#username_field').show();
        $('#password_field').show();
        $('#mtu_field').show();
        $('#pppoe_dns_field').show();
    }
}

function setWirelessFields() {
    if ($('#mode').val() === 'WPA-EAP')
        $('#passphrase_field').hide();
    else
        $('#passphrase_field').show();
}

// vim: ts=4 syntax=javascript

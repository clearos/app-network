<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'network';
$app['version'] = '1.0.8';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('network_app_description');
$app['inline_help'] = array(
    lang('network_settings') => lang('network_settings_help'),
    lang('network_dns') => lang('network_dns_help'),
);

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('network_app_name');
$app['category'] = lang('base_category_network');
$app['subcategory'] = lang('base_subcategory_settings');

/////////////////////////////////////////////////////////////////////////////
// Controller info
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['network']['title'] = lang('network_app_name');
$app['controllers']['dns']['title'] = lang('network_dns');
$app['controllers']['iface']['title'] = lang('network_network_interfaces');
$app['controllers']['settings']['title'] = lang('base_settings');

// Wizard extras
$app['controllers']['iface']['wizard_name'] = lang('network_network_interfaces');
$app['controllers']['iface']['wizard_description'] = 'Network Interfaces Description'; // FIXME
$app['controllers']['iface']['inline_help'] = array(
    lang('network_interfaces') => lang('network_interfaces_help'),
);

$app['controllers']['mode']['wizard_name'] = 'Network Mode'; // FIXME
$app['controllers']['mode']['wizard_description'] = 'Network Mode Description'; // FIXME
$app['controllers']['mode']['inline_help'] = array(
    lang('network_standalone_or_gateway') => lang('network_mode_help'),
);

$app['controllers']['hostname']['wizard_name'] = 'Network Hostname'; // FIXME
$app['controllers']['hostname']['wizard_description'] = 'Network Hostname Description'; // FIXME
$app['controllers']['hostname']['inline_help'] = array(
    lang('network_standalone_or_gateway') => lang('network_mode_help'),
);

$app['controllers']['domain']['wizard_name'] = 'Base Domain'; // FIXME
$app['controllers']['domain']['wizard_description'] = 'Domain Description'; // FIXME
$app['controllers']['domain']['inline_help'] = array(
    'extra help' => 'blah blah blah'
);

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'avahi',
    'bind-utils',
    'bridge-utils',
    'csplugin-filewatch',
    'dhclient',
    'ethtool',
    'net-tools',
    'ppp',
    'rp-pppoe',
    'syswatch',
    'traceroute',
    'wireless-tools',
);

$app['core_directory_manifest'] = array(
    '/var/clearos/network' => array(),
    '/var/clearos/network/backup' => array(),
);

$app['core_file_manifest'] = array(
    'dhclient-exit-hooks' => array('target' => '/etc/dhclient-exit-hooks'),
    'filewatch-network.conf' => array('target' => '/etc/clearsync.d/filewatch-network.conf'),
    'filewatch-network-hostname.conf' => array('target' => '/etc/clearsync.d/filewatch-network-hostname.conf'),
    'network.conf' => array(
        'target' => '/etc/clearos/network.conf',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
);

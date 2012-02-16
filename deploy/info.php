<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'network';
$app['version'] = '1.0.4';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('network_app_description');
$app['inline_help'] = array(
    lang('network_settings') => lang('network_settings_help'),
    lang('network_dns') => lang('network_dns_help'),
    lang('network_interfaces') => lang('network_interfaces_help'),
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

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'avahi',
    'bind-utils',
    'bridge-utils',
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
   'dhclient-exit-hooks' => array(
        'target' => '/etc/dhclient-exit-hooks',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
    ),
   'network.conf' => array(
        'target' => '/etc/clearos/network.conf',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
);

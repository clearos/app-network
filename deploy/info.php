<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'network';
$app['version'] = '1.2.9';
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
$app['controllers']['iface']['wizard_description'] = lang('network_interfaces_help');
$app['controllers']['iface']['inline_help'] = array(
    lang('network_external') => lang('network_external_help'),
    lang('network_lan') => lang('network_lan_help'),
    lang('network_dmz_versus_hotlan') => lang('network_dmz_versus_hotlan_help'),
);

$app['controllers']['mode']['wizard_name'] = lang('network_network_mode');
$app['controllers']['mode']['wizard_description'] = lang('network_network_mode_wizard_description');
$app['controllers']['mode']['inline_help'] = array(
    lang('network_you_can_change_your_mind_later') => lang('network_network_mode_help'),
    lang('network_best_practices') => lang('network_network_mode_best_practices_help'),
);

$app['controllers']['dns']['wizard_name'] = lang('network_dns_servers');
$app['controllers']['dns']['wizard_description'] = lang('network_dns_help');
$app['controllers']['dns']['inline_help'] = array(
    lang('network_best_practices') => lang('network_dns_best_practices_help'),
);

$app['controllers']['hostname']['wizard_name'] = lang('network_hostname');
$app['controllers']['hostname']['wizard_description'] = lang('network_hostname_wizard_description');
$app['controllers']['hostname']['inline_help'] = array(
    lang('network_hostname') => lang('network_hostname_help'),
    lang('network_internet_hostname') => lang('network_internet_hostname_help'),
);

$app['controllers']['domain']['wizard_name'] = lang('network_internet_domain');
$app['controllers']['domain']['wizard_description'] = lang('network_internet_domain_wizard_description');
$app['controllers']['domain']['inline_help'] = array(
    lang('network_just_a_default') => lang('network_internet_domain_wizard_help'),
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
    '/etc/clearos/network.d' => array(),
    '/var/clearos/network' => array(),
    '/var/clearos/network/backup' => array(),
);

$app['core_file_manifest'] = array(
    'dhclient-exit-hooks' => array(
        'target' => '/etc/dhcp/dhclient-exit-hooks',
        'mode' => '0755',
    ),
    'filewatch-network.conf' => array('target' => '/etc/clearsync.d/filewatch-network.conf'),
    'filewatch-network-hostname.conf' => array('target' => '/etc/clearsync.d/filewatch-network-hostname.conf'),
    'filewatch-network-connected.conf' => array('target' => '/etc/clearsync.d/filewatch-network-connected.conf'),
    'network.conf' => array(
        'target' => '/etc/clearos/network.conf',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
);

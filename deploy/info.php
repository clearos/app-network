<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'network';
$app['version'] = '2.4.6';
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
$app['controllers']['proxy']['title'] = lang('network_upstream_proxy');

$app['controllers']['settings']['inline_help'] = array(
    lang('network_settings') => lang('network_settings_help'),
    lang('base_warning') => lang('network_switching_modes_help'),
);

// Wizard extras
$app['controllers']['iface']['wizard_name'] = lang('network_network_interfaces');
$app['controllers']['iface']['wizard_description'] = lang('network_interfaces_help');
$app['controllers']['iface']['inline_help'] = array(
    lang('network_external') => lang('network_external_help'),
    lang('network_lan') => lang('network_lan_help'),
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
    lang('network_dns') => lang('network_dns_help'),
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
    'app-base-core >= 1:2.4.2',
    'app-events-core',
    'avahi',
    'bind-utils',
    'bridge-utils',
    'csplugin-filewatch',
    'csplugin-procwatch',
    'dhclient >= 12:4.1.1-31.P1.v6.1',
    'ethtool',
    'initscripts >= 9.03.31-3',
    'iw',
    'net-tools',
    'ppp',
    'rp-pppoe >= 3.10-8.1',
    'telnet',
    'traceroute',
    'tcpdump',
);

$app['core_obsoletes'] = array(
    'app-upstream-proxy-core',
);

$app['obsoletes'] = array(
    'app-upstream-proxy',
);

$app['core_directory_manifest'] = array(
    '/etc/clearos/network.d' => array(),
    '/var/clearos/network' => array(),
    '/var/clearos/network/backup' => array(),
    '/var/clearos/network/check' => array(),
    '/var/clearos/events/network_configuration' => array(),
    '/var/clearos/events/network_connected' => array(),
    '/var/clearos/events/network_peerdns' => array(),
);

$app['core_file_manifest'] = array(
    'dhclient-exit-hooks' => array(
        'target' => '/etc/dhcp/dhclient-exit-hooks',
        'mode' => '0755',
    ),
    'network' => array(
        'target' => '/usr/sbin/network',
        'mode' => '0755',
    ),
    'filewatch-network-connected-event.conf' => array('target' => '/etc/clearsync.d/filewatch-network-connected-event.conf'),
    'filewatch-network-configuration-event.conf' => array('target' => '/etc/clearsync.d/filewatch-network-configuration-event.conf'),
    'filewatch-network-peerdns-event.conf' => array('target' => '/etc/clearsync.d/filewatch-network-peerdns-event.conf'),
    'procwatch-network-proxy-event.conf' => array('target' => '/etc/clearsync.d/procwatch-network-proxy-event.conf'),
    'network.conf' => array(
        'target' => '/etc/clearos/network.conf',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
    'network-configuration-event'=> array(
        'target' => '/var/clearos/events/network_configuration/network',
        'mode' => '0755'
    ),
    'network_resolver2'=> array(
        'target' => '/var/clearos/events/network_peerdns/network_resolver',
        'mode' => '0755'
    ),
    'network_resolver'=> array(
        'target' => '/var/clearos/events/network_configuration/network_resolver',
        'mode' => '0755'
    ),
    'clearos_speed_test'=> array(
        'target' => '/usr/sbin/clearos_speed_test',
        'mode' => '0755'
    ),
);

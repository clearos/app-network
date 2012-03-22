<?php
$lang['network_hostname_help'] = 'The hostname is the name of your system when accessed from an internal network or VPN.  For example, a file server might have the hostname fileserver.lan.  It is what you normally expect for a hostname.';
$lang['network_internet_hostname_help'] = 'The Internet hostname is the name of your system when accessed from the Internet.  Continuing our file server example, there may be a public web folder available for downloading files.  The Internet hostname could be set to filleserver.example.com.';
$lang['network_hostname_and_internet_hostname'] = 'Hostname and Internet Hostname';
$lang['network_hostname_wizard_description'] = 'It is time to define the hostname for your system.  Hey, wait a minute... why do I need two hostnames?  Often, these two hostnames are the same, but please read the help section for more information.';
$lang['network_internet_hostname_invalid'] = 'Internet hostname is invalid.';
$lang['network_network_mode'] = 'Network Mode';
$lang['network_network_mode_wizard_description'] = 'You can configure your system as a standalone server or gateway.';
$lang['network_network_mode_help'] = 'You can always change the network mode of your system after the install wizard.  For example, if you are preparing the system for a deployment, you can run the install as a standalone system now and change it to a gateway later.';
$lang['network_you_can_change_your_mind_later'] = 'You Can Change It Later';
$lang['network_best_practices'] = 'Best Practices';
$lang['network_network_mode_best_practices_help'] = 'It is always good practice to have a dedicated system for firewall and gateway duties, while having separate systems for server duties.';
$lang['network_internet_hostname'] = 'Internet Hostname';
$lang['network_internet_domain_wizard_description'] = 'Set a default Internet domain for your system, for example: example.com.  By the way, we use "Internet domain" instead of just "domain" in our terminology to avoid confusion with "Windows domains".';
$lang['network_internet_domain_wizard_help'] = 'Many apps need to know the Internet domain that you use for your network.  DHCP, VPN, LDAP and many others require an Internet domain in the app configuration.  In the end, it is just a starting point and you can always override the default in each app.';
$lang['network_just_a_default'] = 'Just a Default';

$lang['network_connecting_to_the_internet'] = 'Connecting to the Internet';
$lang['network_standalone_or_gateway'] = 'Standalone or Gateway';
$lang['network_default_domain'] = 'Default Domain';
$lang['network_mode_help'] = 'Network mode help';

$lang['network_internet_domain'] = 'Internet Domain';
$lang['network_internet_domain_invalid'] = 'Internet domain is invalid.';
$lang['network_wins_server'] = 'WINS Server';
$lang['network_wins_server_invalid'] = 'WINS server is invalid.';
$lang['network_dns_server_invalid'] = 'DNS server is invalid.';

$lang['network_mode_transparent_bridge'] = 'Transparent Bridge';

$lang['network_automatic_dns_servers'] = 'Automatic DNS Servers';
$lang['network_automatic_dns_server_flag_invalid'] = 'Automatic DNS server flag is invalid.';
$lang['network_add_virtual_interface'] = 'Add Virtual Interface';
$lang['network_dns'] = 'DNS';
$lang['network_too_many_dns_servers_warning'] = 'We do not recommend configuring more than 3 DNS servers.';
$lang['network_no_dns_servers_warning'] = 'No DNS servers are configured.';
$lang['network_network_degraded'] = 'Network Degraded';
$lang['network_best_practices'] = 'Best Practices';
$lang['network_dns_automatically_configured'] = 'DNS Automatically Configured';
$lang['network_dns_automatically_configured_message'] = 'The DNS servers for this system are automatically configured.';

$lang['network_app_description'] = 'Provides administrators with the ability to configure the most common network tasks like mode, system hostname, DNS servers and Network Interface Card (NIC) settings.';
$lang['network_app_name'] = 'IP Settings';
$lang['network_bandwidth'] = 'Bandwidth';
$lang['network_boot_protocol_invalid'] = 'Boot protocol is invalid.';
$lang['network_bootproto_bootp'] = 'BootP';
$lang['network_bootproto_dhcp'] = 'DHCP';
$lang['network_bootproto_pppoe'] = 'PPPoE';
$lang['network_bootproto_static'] = 'Static';
$lang['network_broadcast'] = 'Broadcast Address';
$lang['network_bus'] = 'Bus';
$lang['network_connected'] = 'Connected';
$lang['network_connection_status'] = 'Connection Status';
$lang['network_connection_type'] = 'Connection Type';
$lang['network_destination'] = 'Destination';
$lang['network_detected'] = 'Detected';
$lang['network_device'] = 'Device';
$lang['network_dhcp_server_not_found'] = 'DHCP server not found.';
$lang['network_dhcp_server_did_not_respond'] = 'DHCP server did not respond.';
$lang['network_dmz'] = 'DMZ';
$lang['network_dns_server'] = 'DNS Server';
$lang['network_domain'] = 'Domain';
$lang['network_domain_invalid'] = 'Domain is invalid.';
$lang['network_domain_must_have_a_period'] = 'Internet domain must have at least one period.';
$lang['network_entire_network'] = 'All Networks';
$lang['network_entry_already_exists'] = 'Entry already exists.';
$lang['network_entry_not_found'] = 'Entry not found.';
$lang['network_exception_ethers_already_exists'] = 'Ethers entry already exists: %s';
$lang['network_exception_ethers_not_found'] = 'Ethers entry not found: %s';
$lang['network_external'] = 'External';
$lang['network_gateway'] = 'Gateway';
$lang['network_gateway_invalid'] = 'Gateway is invalid.';
$lang['network_gateway_flag_invalid'] = 'Gateway flag is invalid.';
$lang['network_host_entry_already_exists'] = 'Host entry already exists.';
$lang['network_host_entry_not_found'] = 'Host entry not found.';
$lang['network_hostname_alias_invalid'] = 'Hostname alias is invalid.';
$lang['network_hostname'] = 'Hostname';
$lang['network_hostname_invalid'] = 'Hostname is invalid.';
$lang['network_hostname_must_have_a_period'] = 'Full hostname must have at least one period.';
$lang['network_hot_lan'] = 'Hot LAN';
$lang['network_interface'] = 'Interface';
$lang['network_interfaces'] = 'Interfaces';
$lang['network_ip_invalid'] = 'IP address is invalid.';
$lang['network_ip'] = 'IP Address';
$lang['network_ip_not_part_of_network'] = 'IP address is not part of the network.';
$lang['network_ip_range_invalid'] = 'IP range is invalid.';
$lang['network_ip_range'] = 'IP Address Range';
$lang['network_ip_range_is_reversed'] = 'IP range is reversed.';
$lang['network_lan'] = 'LAN';
$lang['network_link'] = 'Link';
$lang['network_mac_address_invalid'] = 'MAC address is invalid.';
$lang['network_mac_address'] = 'MAC Address';
$lang['network_mode_auto'] = 'Automatic Mode';
$lang['network_mode_bridge'] = 'Bridge Mode';
$lang['network_mode_dmz'] = 'DMZ Mode';
$lang['network_mode_gateway'] = 'Gateway Mode';
$lang['network_mode_invalid'] = 'Mode is invalid.';
$lang['network_network_mode'] = 'Network Mode';
$lang['network_mode_standalone'] = 'Standalone';
$lang['network_mode_trusted_gateway'] = 'Trusted Gateway';
$lang['network_mode_standalone_no_firewall'] = 'Standalone - No Firewall';
$lang['network_mtu'] = 'MTU';
$lang['network_mtu_invalid'] = 'MTU is invalid.';
$lang['network_port'] = 'Port';
$lang['network_netmask_invalid'] = 'Netmask is invalid.';
$lang['network_netmask_invalid'] = 'Netmask is invalid.';
$lang['network_netmask'] = 'Netmask';
$lang['network_network'] = 'Network';
$lang['network_network_interface_invalid'] = 'Network interface is invalid.';
$lang['network_network_interfaces'] = 'Network Interfaces';
$lang['network_pppoe_server_not_found'] = 'PPPoE server not found.';
$lang['network_pppoe_authentication_failed'] = 'PPPoE authentication failed.';
$lang['network_prefix_invalid'] = 'Prefix is invalid';
$lang['network_role_invalid'] = 'Role is invalid.';
$lang['network_role'] = 'Role';
$lang['network_speed'] = 'Speed';
$lang['network_type'] = 'Type';
$lang['network_type_bonded'] = 'Bonded';
$lang['network_type_bonded_slave'] = 'Bonded Slave';
$lang['network_type_bridged'] = 'Bridged';
$lang['network_type_bridged_slave'] = 'Bridged Slave';
$lang['network_type_ethernet'] = 'Ethernet';
$lang['network_type_pppoe'] = 'PPPoE';
$lang['network_type_virtual'] = 'Virtual';
$lang['network_type_vlan'] = 'VLAN';
$lang['network_type_wireless'] = 'Wireless';
$lang['network_type_unknown'] = 'Unknown';
$lang['network_username_invalid'] = 'Username is invalid';
$lang['network_validate_unusual_gateway_setting'] = 'Your network settings include the following unusual gateway configuration.  Click on help for more information.';
$lang['network_vendor'] = 'Vendor';
$lang['network_network_connection_status_offline'] = 'Network connection status is offline.';
$lang['network_network_connection_status_unknown'] = 'Network connection status is unknown.';
$lang['network_network_error_occurred'] = 'Network error occurred';
$lang['network_firewall'] = 'Firewall';
$lang['network_allow_connections'] = 'Allow Connections';
$lang['network_settings'] = 'Settings';
$lang['network_settings_help'] = 'Your system can be configured as a gateway or a standalone server.';
$lang['network_dns_help'] = 'DNS configuration is essential for your system.  Without DNS, well, bad things happen.  If you need a public DNS server to get your system up and running, you can use Google\'s DNS Servers: 8.8.8.8 and 8.8.4.4.';
$lang['network_interfaces_help'] = 'You need at least one network interface configured in external mode.  An "external" role means the network interface can be used to gain access the Internet; it does not necessarily mean it is connected directly to the Internet.';
$lang['network_enable_dhcp_server'] = 'Enable DHCP Server';

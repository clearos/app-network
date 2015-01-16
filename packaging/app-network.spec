
Name: app-network
Epoch: 1
Version: 2.0.11
Release: 1%{dist}
Summary: IP Settings
License: GPLv3
Group: ClearOS/Apps
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = 1:%{version}-%{release}
Requires: app-base

%description
The IP Settings app provides the tools to configure the most common network tasks like network mode, system hostname, DNS servers and network interface settings.

%package core
Summary: IP Settings - Core
License: LGPLv3
Group: ClearOS/Libraries
Requires: app-base-core
Requires: app-base-core >= 1:1.6.0
Requires: app-events-core
Requires: avahi
Requires: bind-utils
Requires: bridge-utils
Requires: csplugin-filewatch
Requires: dhclient >= 12:4.1.1-31.P1.v6.1
Requires: ethtool
Requires: initscripts >= 9.03.31-3
Requires: iw
Requires: net-tools
Requires: ppp
Requires: rp-pppoe >= 3.10-8.1
Requires: syswatch
Requires: traceroute
Requires: tcpdump

%description core
The IP Settings app provides the tools to configure the most common network tasks like network mode, system hostname, DNS servers and network interface settings.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/network
cp -r * %{buildroot}/usr/clearos/apps/network/

install -d -m 0755 %{buildroot}/etc/clearos/network.d
install -d -m 0755 %{buildroot}/var/clearos/events/network_configuration
install -d -m 0755 %{buildroot}/var/clearos/events/network_connected
install -d -m 0755 %{buildroot}/var/clearos/events/network_peerdns
install -d -m 0755 %{buildroot}/var/clearos/network
install -d -m 0755 %{buildroot}/var/clearos/network/backup
install -D -m 0755 packaging/dhclient-exit-hooks %{buildroot}/etc/dhcp/dhclient-exit-hooks
install -D -m 0644 packaging/filewatch-network-configuration-event.conf %{buildroot}/etc/clearsync.d/filewatch-network-configuration-event.conf
install -D -m 0644 packaging/filewatch-network-connected-event.conf %{buildroot}/etc/clearsync.d/filewatch-network-connected-event.conf
install -D -m 0644 packaging/filewatch-network-peerdns-event.conf %{buildroot}/etc/clearsync.d/filewatch-network-peerdns-event.conf
install -D -m 0755 packaging/network %{buildroot}/usr/sbin/network
install -D -m 0755 packaging/network-configuration-event %{buildroot}/var/clearos/events/network_configuration/network
install -D -m 0755 packaging/network-connected-event %{buildroot}/var/clearos/events/network_connected/network
install -D -m 0644 packaging/network.conf %{buildroot}/etc/clearos/network.conf
install -D -m 0755 packaging/network_resolver %{buildroot}/var/clearos/events/network_configuration/network_resolver
install -D -m 0755 packaging/network_resolver2 %{buildroot}/var/clearos/events/network_peerdns/network_resolver

%post
logger -p local6.notice -t installer 'app-network - installing'

%post core
logger -p local6.notice -t installer 'app-network-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/network/deploy/install ] && /usr/clearos/apps/network/deploy/install
fi

[ -x /usr/clearos/apps/network/deploy/upgrade ] && /usr/clearos/apps/network/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-network - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-network-core - uninstalling'
    [ -x /usr/clearos/apps/network/deploy/uninstall ] && /usr/clearos/apps/network/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/network/controllers
/usr/clearos/apps/network/htdocs
/usr/clearos/apps/network/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/network/packaging
%dir /usr/clearos/apps/network
%dir /etc/clearos/network.d
%dir /var/clearos/events/network_configuration
%dir /var/clearos/events/network_connected
%dir /var/clearos/events/network_peerdns
%dir /var/clearos/network
%dir /var/clearos/network/backup
/usr/clearos/apps/network/deploy
/usr/clearos/apps/network/language
/usr/clearos/apps/network/libraries
/etc/dhcp/dhclient-exit-hooks
/etc/clearsync.d/filewatch-network-configuration-event.conf
/etc/clearsync.d/filewatch-network-connected-event.conf
/etc/clearsync.d/filewatch-network-peerdns-event.conf
/usr/sbin/network
/var/clearos/events/network_configuration/network
/var/clearos/events/network_connected/network
%config(noreplace) /etc/clearos/network.conf
/var/clearos/events/network_configuration/network_resolver
/var/clearos/events/network_peerdns/network_resolver

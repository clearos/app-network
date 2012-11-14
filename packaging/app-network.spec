
Name: app-network
Epoch: 1
Version: 1.4.3
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
Requires: avahi
Requires: bind-utils
Requires: bridge-utils
Requires: csplugin-filewatch
Requires: dhclient
Requires: ethtool
Requires: net-tools
Requires: ppp
Requires: rp-pppoe
Requires: syswatch
Requires: traceroute
Requires: wireless-tools

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
install -d -m 0755 %{buildroot}/var/clearos/network
install -d -m 0755 %{buildroot}/var/clearos/network/backup
install -D -m 0755 packaging/dhclient-exit-hooks %{buildroot}/etc/dhcp/dhclient-exit-hooks
install -D -m 0644 packaging/filewatch-network-connected.conf %{buildroot}/etc/clearsync.d/filewatch-network-connected.conf
install -D -m 0644 packaging/filewatch-network-hostname.conf %{buildroot}/etc/clearsync.d/filewatch-network-hostname.conf
install -D -m 0644 packaging/filewatch-network.conf %{buildroot}/etc/clearsync.d/filewatch-network.conf
install -D -m 0644 packaging/network.conf %{buildroot}/etc/clearos/network.conf

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
%exclude /usr/clearos/apps/network/tests
%dir /usr/clearos/apps/network
%dir /etc/clearos/network.d
%dir /var/clearos/network
%dir /var/clearos/network/backup
/usr/clearos/apps/network/deploy
/usr/clearos/apps/network/language
/usr/clearos/apps/network/libraries
/etc/dhcp/dhclient-exit-hooks
/etc/clearsync.d/filewatch-network-connected.conf
/etc/clearsync.d/filewatch-network-hostname.conf
/etc/clearsync.d/filewatch-network.conf
%config(noreplace) /etc/clearos/network.conf

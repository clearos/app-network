<?php

/**
 * Network interface class.
 *
 * @category   apps
 * @package    network
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2002-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////
//
// Maintenance notes
// -----------------
//
// - The Red Hat network scripts have two tags that define the connection type
//   - BOOTPROTO: dhcp, bootp, dialup, static
//   - TYPE:      xDSL, <other>   (i.e. anything else will NOT be xDSL)
//              Though the "TYPE" tag is only used to signify PPPoE, it is
//              also used to store other network types (notably, "dialup"
//              and "wireless").
//
// - Before writing a new config, you must disable the interface.  Otherwise,
//   you won't be able to bring the interface down *after* a config change.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\network;

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
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\network\Chap as Chap;
use \clearos\apps\network\Iface as Iface;
use \clearos\apps\network\Iface_Manager as Iface_Manager;
use \clearos\apps\network\Network_Utils as Network_Utils;
use \clearos\apps\network\Role as Role;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Shell');
clearos_load_library('network/Chap');
clearos_load_library('network/Iface');
clearos_load_library('network/Iface_Manager');
clearos_load_library('network/Network_Utils');
clearos_load_library('network/Role');

// Exceptions
//-----------

use \Exception as Exception;
use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_No_Match_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network interface class.
 *
 * @category   apps
 * @package    network
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2002-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Iface extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    // Misc
    const CONSTANT_ONE_TO_ONE_NAT_START = 200;
    const CONSTANT_WEP_CLIENT = 'managed';

    // Commands
    const COMMAND_ETHTOOL = '/sbin/ethtool';
    const COMMAND_IFCONFIG = '/sbin/ifconfig';
    const COMMAND_IFDOWN = '/sbin/ifdown';
    const COMMAND_IFUP = '/sbin/ifup';
    const COMMAND_IW = '/sbin/iw';

    // Files and paths
    const FILE_LOG = '/var/log/messages';
    const FILE_PCI_ID = '/usr/share/hwdata/pci.ids';
    const FILE_USB_ID = '/usr/share/hwdata/usb.ids';
    const PATH_SYS_CLASS_NET = '/sys/class/net';
    const PATH_SYSCONF = '/etc/sysconfig';

    // Boot protocols
    const BOOTPROTO_BOOTP = 'bootp';
    const BOOTPROTO_DHCP = 'dhcp';
    const BOOTPROTO_DIALUP = 'dialup';
    const BOOTPROTO_PPPOE = 'pppoe';
    const BOOTPROTO_STATIC = 'static';

    // Network types
    const TYPE_BONDED = 'Bonded';
    const TYPE_BONDED_SLAVE = 'BondedChild';
    const TYPE_BRIDGED = 'Bridge';
    const TYPE_BRIDGED_SLAVE = 'BridgeChild';
    const TYPE_ETHERNET = 'Ethernet';
    const TYPE_PPPOE = 'xDSL';
    const TYPE_PPPOE_SLAVE = 'PPPoEChild';
    const TYPE_UNKNOWN = 'Unknown';
    const TYPE_VIRTUAL = 'Virtual';
    const TYPE_VLAN = 'VLAN';
    const TYPE_WIRELESS = 'Wireless';

    // Wireless types
    const WIRELESS_WEP_CLIENT = 'WEP';
    const WIRELESS_WPA_PSK = 'WPA-PSK';
    const WIRELESS_WPA_EAP = 'WPA-EAP';

    // Flags
    const IFF_UP = 0x1;
    const IFF_BROADCAST = 0x2;
    const IFF_DEBUG = 0x4;
    const IFF_LOOPBACK = 0x8;
    const IFF_POINTOPOINT = 0x10;
    const IFF_NOTRAILERS = 0x20;
    const IFF_RUNNING = 0x40;
    const IFF_NOARP = 0x80;
    const IFF_PROMISC = 0x100;
    const IFF_ALLMULTI = 0x200;
    const IFF_MASTER = 0x400;
    const IFF_SLAVE = 0x800;
    const IFF_MULTICAST = 0x1000;
    const IFF_PORTSEL = 0x2000;
    const IFF_AUTOMEDIA = 0x4000;
    const IFF_DYNAMIC = 0x8000;
    const IFF_LOWER_UP = 0x10000;
    const IFF_DORMANT = 0x20000;

    protected $iface = NULL;
    protected $is_configured = NULL;
    protected $config = NULL;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Iface constructor.
     *
     * @param string $iface interface
     */

    public function __construct($iface = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->iface = $iface;
    }

    /**
     * Deletes interface configuration.
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function delete_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        // KLUDGE: more PPPoE crap

        $info = $this->get_info();

        if (isset($info['ifcfg']['user'])) {
            $chap = new Chap();
            $chap->delete_secret($info['ifcfg']['user']);
        }

        if (isset($info['ifcfg']['eth'])) {
            $pppoedev = new Iface($info['ifcfg']['eth']);
            $pppoedev->delete_config();
        }

        try {
            $this->disable();
        } catch (Engine_Exception $e) {
            // Not fatal
        }

        sleep(2); // Give it a chance to disappear

        $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

        if ($file->exists())
            $file->delete();
    }

    /**
     * Deletes virtual interface.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_virtual()
    {
        clearos_profile(__METHOD__, __LINE__);

        list($device, $metric) = preg_split('/:/', $this->iface, 5);

        if (!strlen($metric))
            return;

        $shell = new Shell();
        $retval = $shell->execute(self::COMMAND_IFDOWN, $this->iface, TRUE);

        if ($retval != 0) {
            // Really force it down if ifdown fails.  Don't bother logging errors...
            $retval = $shell->execute(self::COMMAND_IFCONFIG, $this->iface . ' down', TRUE);
        }

        $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

        if ($file->exists())
            $file->delete();
    }

    /**
     * Takes interface down.
     *
     * @param string $iface Interface name (optional)
     *
     * @return  void
     * @throws Engine_Exception
     */

    public function disable($iface = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if($iface != NULL) $this->iface = $iface;

        try {
            $options['validate_exit_code'] = FALSE;

            $shell = new Shell();
            $retval = $shell->execute(self::COMMAND_IFDOWN, $this->iface, TRUE, $options);

            if ($retval != 0) {
                // Really force it down if ifdown fails.  Don't bother logging errors...
                $retval = $shell->execute(self::COMMAND_IFCONFIG, $this->iface . ' down', TRUE, $options);
            }
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Brings interface up.
     *
     * @param boolean $background perform enable in the background
     *
     * @return void
     * @throws Engine_Exception
     */

    public function enable($background = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $options = array();

        if ($background)
            $options['background'] = TRUE;

        $shell = new Shell();
        $retval = $shell->execute(self::COMMAND_IFUP, $this->iface, TRUE, $options);
    }

    /**
     * Returns the boot protocol of interface in user-friendly text.
     *
     * @return string boot protocol of interface
     * @throws Engine_Exception
     */

    public function get_boot_protocol()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        $bootproto = '';

        if ($this->is_configured()) {
            $info = $this->read_config();
            $bootproto = $info['bootproto'];

            // PPPOEKLUDGE - set the boot protocol on PPPoE interfaces
            if ($this->get_type() == self::TYPE_PPPOE)
                $bootproto = self::BOOTPROTO_PPPOE;
        }

        return $bootproto;
    }

    /**
     * Returns the boot protocol of interface as a readable string for end users.
     *
     * @return string boot protocol of interface
     * @throws Engine_Exception
     */

    public function get_boot_protocol_text()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        $bootproto = $this->get_boot_protocol();
        $text = '';

        if ($bootproto == self::BOOTPROTO_DHCP)
            $text = lang('network_bootproto_dhcp');
        else if ($bootproto == self::BOOTPROTO_STATIC)
            $text = lang('network_bootproto_static');
        else if ($bootproto == self::BOOTPROTO_PPPOE)
            $text = lang('network_bootproto_pppoe');

        return $text;
    }

    /**
     * Returns interface information as an associative array.
     *
     * @return  array  interface information
     * @throws  Engine_Exception, Engine_Exception
     */

    public function get_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        // Using ioctl(2) calls (from custom extension ifconfig.so).

        if (! extension_loaded('ifconfig'))
            throw new Engine_Exception(lang('network_network_error_occurred'));

        $handle = @ifconfig_init();
        ifconfig_debug($handle, FALSE);

        $info = array();

        $info['address'] = @ifconfig_address($handle, $this->iface);
        $info['netmask'] = @ifconfig_netmask($handle, $this->iface);
        $info['broadcast'] = @ifconfig_broadcast($handle, $this->iface);
        $info['hwaddress'] = @ifconfig_hwaddress($handle, $this->iface);
        $info['mtu'] = @ifconfig_mtu($handle, $this->iface);
        $info['metric'] = @ifconfig_metric($handle, $this->iface) + 1;
        $info['flags'] = @ifconfig_flags($handle, $this->iface);
        $info['debug'] = @ifconfig_debug($handle, $this->iface);

        // TODO: the existence of an IP address has always been used
        // to determine the "state" of the network interface.  This
        // policy should be changed and the $info['state'] should be
        // explicitly defined.

        // TODO II: on a DHCP connection, the interface can have an IP
        // (an old one) and be "up" during the DHCP lease renewal process
        // (even if it fails).  This should be added to the state flag?

        try {
            $info['link'] = $this->get_link_status();
        } catch (Exception $e) {
            // Keep going?
        }

        try {
            $info['speed'] = $this->get_speed();
        } catch (Exception $e) {
            // Keep going?
        }

        try {
            $info['type'] = $this->get_type();
            $info['type_text'] = $this->get_type_text();
        } catch (Exception $e) {
            // Keep going?
        }

        // Vendor info
        //------------

        try {
            $vendor_stuff = $this->get_vendor_info();
            if (is_array($vendor_stuff))
                $info = array_merge($info, $vendor_stuff);
        } catch (Exception $e) {
            // Keep going?
        }

        // VLAN ID
        //--------

        if ($info['type'] === self::TYPE_VLAN)
            $info['vlan_id'] = preg_replace('/.*\./', '', $this->iface);

        // Role info
        //----------

        $role = new Role();
        $info['role'] = $role->get_interface_role($this->iface);
        $info['role_text'] = $role->get_interface_role_text($this->iface);

        // Other info
        //-----------

        if (preg_match('/^[a-z]+\d+:/', $this->iface)) {
            $info['virtual'] = TRUE;

            $virtualnum = preg_replace('/[a-z]+\d+:/', '', $this->iface);

            if ($virtualnum >= self::CONSTANT_ONE_TO_ONE_NAT_START)
                $info['one-to-one-nat'] = TRUE;
            else
                $info['one-to-one-nat'] = FALSE;
        } else {
            $info['virtual'] = FALSE;
            $info['one-to-one-nat'] = FALSE;
        }

        if ($this->is_configurable())
            $info['configurable'] = TRUE;
        else
            $info['configurable'] = FALSE;

        if ($this->is_configured()) {
            try {
                $info['configured'] = TRUE;
                $info['ifcfg'] = $this->read_config();
            } catch (Exception $e) {
                // Keep going?
            }
        } else {
            $info['configured'] = FALSE;
        }

        // Wireless extras
        //----------------

        if ($this->is_configured()) {
            if (isset($info['ifcfg']['mode']) && ($info['ifcfg']['mode'] === self::CONSTANT_WEP_CLIENT)) {
                $info['wireless_ssid'] = isset($info['ifcfg']['essid']) ? $info['ifcfg']['essid'] : '';
                $info['wireless_mode'] = self::WIRELESS_WEP_CLIENT;
                $info['wireless_channel'] = isset($info['ifcfg']['channel']) ? $info['ifcfg']['channel'] : '';
                $info['wireless_passphrase'] = isset($info['ifcfg']['key']) ? $info['ifcfg']['key'] : '';
            } else {
                if (clearos_library_installed('wireless/Hostapd')) {
                    clearos_load_library('wireless/Hostapd');
                    $hostapd = new \clearos\apps\wireless\Hostapd();
                    $info['wireless_ssid'] = $hostapd->get_ssid();
                    $info['wireless_mode'] = $hostapd->get_mode();
                    $info['wireless_channel'] = $hostapd->get_channel();
                    $info['wireless_passphrase'] = $hostapd->get_wpa_passphrase();
                }
            }
        }

        return $info;
    }

    /**
     * Returns the last connection status in the logs.
     *
     * @return string
     * @throws Engine_Exception
     */

    public function get_ip_connection_log()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        $config = $this->read_config();
        $bootproto = $this->get_boot_protocol();
    
        if ($bootproto == self::BOOTPROTO_PPPOE) {

            $file = new File(self::FILE_LOG, TRUE);
            $results = $file->get_search_results(' (pppd|pppoe)\[\d+\]: ');
            $last_lines = (count($results) < 15) ? count($results) : 15;

            for ($inx = count($results); $inx > (count($results) - $last_lines); $inx--) {
                if (preg_match('/Timeout waiting for/', $results[$inx]))
                    return lang('network_pppoe_server_not_found');
                else if (preg_match('/LCP: timeout/', $results[$inx]))
                    return lang('network_pppoe_server_not_found');
                else if (preg_match('/PAP authentication failed/', $results[$inx]))
                    return lang('network_pppoe_authentication_failed');
            }

        } else if ($bootproto == self::BOOTPROTO_DHCP) {

            $file = new File(self::FILE_LOG, TRUE);
            $results = $file->get_search_results('dhclient\[\d+\]: ');
            $last_lines = (count($results) < 10) ? count($results) : 10;

            for ($inx = count($results); $inx > (count($results) - $last_lines); $inx--) {
                if (preg_match('/No DHCPOFFERS received/', $results[$inx]))
                    return lang('network_dhcp_server_not_found');
                else if (preg_match('/DHCPDISCOVER/', $results[$inx]))
                    return lang('network_dhcp_server_did_not_respond');
            }
        }

        return '';
    }

    /**
     * Returns the link status.
     *
     * @return  int FALSE (0) if link is down, TRUE (1) if link present, -1 if not supported by driver.
     * @throws  Engine_Exception, Engine_Exception
     */

    public function get_link_status()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        $type = $this->get_type();

        // Wireless interfaces always have link.
        // PPPOEKLUDGE -- get link status from underlying PPPoE interface.  Sigh.

        if ($type == self::TYPE_WIRELESS) {
            return 1;
        } else if ($type == self::TYPE_PPPOE) {
            $ifaceconfig = $this->read_config();
            $realiface = $ifaceconfig['eth'];
        } else {
            $realiface = $this->iface;
        }

        $shell = new Shell();
        $retval = $shell->execute(self::COMMAND_ETHTOOL, $realiface, TRUE);

        if ($retval != 0)
            return -1;

        $output = $shell->get_output();

        $match = array();
        
        for ($i = 0; $i < sizeof($output); $i++) {
            if (preg_match('/Link detected: ([A-z]*)/', $output[$i], $match)) {
                $link = ($match[1] == 'yes') ? 1 : 0;
                break;
            }
        }

        return $link;
    }

    /**
     * Returns the live IP address of the interface.
     *
     * @return string IP of interface
     * @throws Engine_Exception, Engine_Exception
     */

    public function get_live_ip()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        // Using ioctl(2) calls (from custom extension ifconfig.so).

        if (! extension_loaded('ifconfig'))
            throw new Engine_Exception(lang('network_network_error_occurred'));

        $handle = @ifconfig_init();
        $ip = @ifconfig_address($handle, $this->iface);

        return $ip;
    }

    /**
     * Returns the MAC address.
     *
     * @return string MAC address
     * @throws Engine_Exception, Engine_Exception
     */

    public function get_live_mac()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        // Using ioctl(2) calls (from custom extension ifconfig.so).

        if (! extension_loaded('ifconfig'))
            throw new Engine_Exception(lang('network_network_error_occurred'));

        $handle = @ifconfig_init();
        $mac = @ifconfig_hwaddress($handle, $this->iface);

        return $mac;
    }

    /**
     * Returns the netmask.
     *
     * @return  string  netmask of interface
     * @throws  Engine_Exception, Engine_Exception
     */

    public function get_live_netmask()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        // Using ioctl(2) calls (from custom extension ifconfig.so).
        if (! extension_loaded('ifconfig'))
            throw new Engine_Exception(lang('network_network_error_occurred'));

        // This method is from: /var/webconfig/lib/ifconfig.so
        $handle = @ifconfig_init();
        $netmask = @ifconfig_netmask($handle, $this->iface);

        return $netmask;
    }

    /**
     * Gets an interface's MTU.
     *
     * @return int mtu Interface MTU
     * @throws Engine_Exception
     */

    public function get_mtu()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        if (! extension_loaded('ifconfig'))
            throw new Engine_Exception(lang('network_network_error_occurred'));

        $handle = @ifconfig_init();

        try {
            $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

            if (! $file->exists())
                return @ifconfig_mtu($handle, $this->iface);

            return preg_replace('/"/', '', $file->lookup_value('/^MTU\s*=\s*/'));
        } catch (File_No_Match_Exception $e) {
            return @ifconfig_mtu($handle, $this->iface);
        } catch (Exception $e) {
            throw new Engine_Exception($e->GetMessage(), COMMON_WARNING);
        }
    }

    /**
     * Returns the network address.
     *
     * @return string network address
     * @throws Engine_Exception, Engine_Exception
     */

    public function get_network()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        $ip = $this->get_live_ip();

        if (empty($ip))
            return '';

        $netmask = $this->get_live_netmask();

        if (empty($netmask))
            return '';

        $network = Network_Utils::get_network_address($ip, $netmask);
        $prefix = Network_Utils::get_prefix($netmask);

        return $network . '/' . $prefix;
    }

    /**
     * Returns the interface speed.
     *
     * This method may not be supported in all network card drivers.
     *
     * @return  int  speed in megabits per second
     * @throws  Engine_Exception, Engine_Exception
     */

    public function get_speed()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        $speed = -1;

        $type = $this->get_type();

        // Wireless interfaces
        //--------------------

        if ($type == self::TYPE_WIRELESS) {
            $options['validate_exit_code'] = FALSE;

            $shell = new Shell();
            $shell->execute(self::COMMAND_IW, $this->iface . ' link', FALSE, $options);
            $output = $shell->get_output();
            $matches = array();
            
            foreach ($output as $line) {
                if (preg_match('/bitrate:\s*([0-9]*)/', $line, $matches)) {
                    $speed = $matches[1];
                    break;
                }
            }

            // Non-wireless interfaces
            //------------------------

        } else {
            // PPPOEKLUDGE -- get speed from underlying PPPoE interface.  Sigh.
            if ($type == self::TYPE_PPPOE) {
                $ifaceconfig = $this->read_config();
                $realiface = $ifaceconfig['eth'];
            } else {
                $realiface = $this->iface;
            }

            $shell = new Shell();
            $retval = $shell->execute(self::COMMAND_ETHTOOL, $realiface, TRUE);
            $output = $shell->get_output();
            $matches = array();

            foreach ($output as $line) {
                if (preg_match('/^\s*Speed: ([0-9]*)/', $line, $matches)) {
                    $speed = $matches[1];
                    break;
                }
            }
        }

        return $speed;
    }

    /**
     * Returns supported bootprotos for the interface.
     *
     * The options['filter_pppoe'] will filter out the PPPoE protocol.
     * PPPoE does not make much sense in many situations.  Shakes fist.
     *
     * @param array $options options
     *
     * @return array supported bootprotos
     * @throws Engine_Exception
     */

    public function get_supported_bootprotos($options = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        $bootprotos = array(
            self::BOOTPROTO_DHCP => lang('network_bootproto_dhcp'),
            self::BOOTPROTO_STATIC => lang('network_bootproto_static'),
        );

        if (empty($options['filter_pppoe']) || !$options['filter_pppoe'])
            $bootprotos[self::BOOTPROTO_PPPOE] = lang('network_bootproto_pppoe');

        return $bootprotos;
    }

    /**
     * Returns supported roles for the interface.
     *
     * @return array supported roles
     * @throws Engine_Exception
     */

    public function get_supported_roles()
    {
        clearos_profile(__METHOD__, __LINE__);

        $role = new Role();

        return $role->get_interface_roles($this->iface);
    }

    /**
     * Returns supported wireless channels.
     *
     * @return array supported wireless channels
     * @throws Engine_Exception
     */

    public function get_supported_wireless_channels()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Could we scan the network for other APs and flag some as recommend?
        // If so, add the recommendations on the RHS of hash array

        return array(
            0 => lang('base_automatic'),
            1 => '1',
            2 => '2',
            3 => '3',
            4 => '4',
            5 => '5',
            6 => '6',
            7 => '7',
            8 => '8',
            9 => '9',
            10 => '10',
            11 => '11',
            12 => '12',
            13 => '13',
            14 => '14',
            15 => '15',
        );
    }

    /**
     * Returns supported wireless modes for the interface.
     *
     * @return array supported wireless modes
     * @throws Engine_Exception
     */

    public function get_supported_wireless_modes()
    {
        clearos_profile(__METHOD__, __LINE__);

        $modes = array(self::WIRELESS_WEP_CLIENT => lang('wireless_wep'));

        if (clearos_library_installed('wireless/Hostapd')) {
            $modes[self::WIRELESS_WPA_PSK] = lang('wireless_wpa_preshared_key');
            $modes[self::WIRELESS_WPA_EAP] = lang('wireless_wpa_infrastructure');
        }

        return $modes;
    }

    /**
     * Returns the type of interface.
     *
     * Return types:
     *  - TYPE_BONDED
     *  - TYPE_BONDED_SLAVE
     *  - TYPE_BRIDGE
     *  - TYPE_BRIDGE_SLAVE
     *  - TYPE_ETHERNET
     *  - TYPE_PPPOE
     *  - TYPE_PPPOE_SLAVE
     *  - TYPE_VIRTUAL
     *  - TYPE_VLAN
     *  - TYPE_WIRELESS
     *  - TYPE_UNKOWN
     *
     * @return string  type of interface
     * @throws Engine_Exception
     */

    public function get_type()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        $isconfigured = $this->is_configured();

        // Not configured?  We can still detect a wireless type
        //-----------------------------------------------------

        if (! $isconfigured) {
            // TODO: must be a /proc or /sys way to get this information
            try {
                $options['validate_exit_code'] = FALSE;

                $shell = new Shell();
                $shell->execute(self::COMMAND_IW, $this->iface . ' info', FALSE, $options);
                $output = $shell->get_output();

                foreach ($output as $line) {
                    if (preg_match('/channel/', $line))
                        return self::TYPE_WIRELESS;
                }
            } catch (Engine_Exception $e) {
                // not fatal
            }

            return self::TYPE_ETHERNET;
        }

        $netinfo = $this->read_config();

        // Trust the "type" in the configuration file (if available)
        //----------------------------------------------------------

        if (isset($netinfo['type']))
            return $netinfo['type'];

        // Next, use the interface name as the clue
        //-----------------------------------------

        if (isset($netinfo['device'])) {
            if (preg_match('/^br/', $netinfo['device']))
                return self::TYPE_BRIDGED;

            if (preg_match('/^bond/', $netinfo['device']))
                return self::TYPE_BONDED;
        }

        // Last clue -- unique parameters in the file
        //-------------------------------------------

        if (isset($netinfo['vlan']))
            return self::TYPE_VLAN;

        if (isset($netinfo['bridge']))
            return self::TYPE_BRIDGED_SLAVE;

        if (isset($netinfo['master']))
            return self::TYPE_BONDED_SLAVE;

        if (isset($netinfo['essid']))
            return self::TYPE_WIRELESS;

        // PPPoE - you are a dirty protocol
        //---------------------------------

        if (isset($netinfo['bootproto']) && ($netinfo['bootproto'] === 'none'))
            return self::TYPE_PPPOE_SLAVE;

        return self::TYPE_ETHERNET;
    }

    /**
     * Returns the type of interface as a readable string for end users.
     *
     * @return  string  type of interface
     * @throws  Engine_Exception
     */

    public function get_type_text()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        $type = $this->get_type();

        if ($type == self::TYPE_BONDED)
            return lang('network_type_bonded');
        else if ($type == self::TYPE_BONDED_SLAVE)
            return lang('network_type_bonded_slave');
        else if ($type == self::TYPE_BRIDGED)
            return lang('network_type_bridged');
        else if ($type == self::TYPE_BRIDGED_SLAVE)
            return lang('network_type_bridged_slave');
        else if ($type == self::TYPE_ETHERNET)
            return lang('network_type_ethernet');
        else if ($type == self::TYPE_PPPOE)
            return lang('network_type_pppoe');
        else if ($type == self::TYPE_VIRTUAL)
            return lang('network_type_virtual');
        else if ($type == self::TYPE_VLAN)
            return lang('network_type_vlan');
        else if ($type == self::TYPE_WIRELESS)
            return lang('network_type_wireless');
        else
            return lang('network_type_unknown');
    }

    /**
     * Returns vendor information.
     *
     * TODO: This method uses fopen/fread/fgets directly rather than the file class
     * for performance reasons.  We don't need super-user access to gather interface
     * details.
     *
     * @return array vendor information
     * @throws Engine_Exception
     */

    public function get_vendor_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        $details = array();
        $details['vendor'] = NULL;
        $details['device'] = NULL;
        $details['sub_device'] = NULL;
        $details['bus'] = NULL;

        $id_vendor = 0;
        $id_device = 0;
        $id_sub_vendor = 0;
        $id_sub_device = 0;

        $device_link = self::PATH_SYS_CLASS_NET . '/' . $this->iface . '/device';

        if (!file_exists($device_link))
            return array();

        // Determine if this is a USB device
        $is_usb = FALSE;

        if (!($path = readlink($device_link)))
            return '';

        if (strstr($path, 'usb'))
            $is_usb = TRUE;

        // Obtain vendor ID number
        $path = $device_link . (($is_usb) ? '/../idVendor' : '/vendor');

        if (!file_exists($path))
            return '';

        if (!($fh = fopen($path, 'r')))
            return '';

        fscanf($fh, '%x', $id_vendor);
        fclose($fh);

        if ($id_vendor == 0)
            return '';

        // Obtain device ID number
        $path = $device_link . (($is_usb) ? '/../idProduct' : '/device');

        if (!($fh = fopen($path, "r")))
            return '';

        fscanf($fh, '%x', $id_device);
        fclose($fh);

        if ($id_device == 0)
            return '';

        if (!$is_usb) {
            // Obtain (optional) sub-vendor ID number (PCI devices only)
            if (file_exists("$device_link/subsystem_vendor") && (($fh = @fopen("$device_link/subsystem_vendor", 'r')))) {
                fscanf($fh, '%x', $id_sub_vendor);
                fclose($fh);

                if ($id_sub_vendor == 0)
                    return '';
            }

            // Obtain (optional) sub-device ID number (PCI devices only)
            if (file_exists("$device_link/subsystem_device") && (($fh = @fopen("$device_link/subsystem_device", 'r')))) {
                fscanf($fh, '%x', $id_sub_device);
                fclose($fh);

                if ($id_sub_device == 0)
                    return '';
            }
        }

        // Scan PCI/USB Id database for vendor/device[/sub-vendor/sub-device]
        if (!($fh = fopen((!$is_usb ? self::FILE_PCI_ID : self::FILE_USB_ID), 'r')))
            return '';

        $details['bus'] = ($is_usb) ? 'USB' : 'PCI';

        // Find vendor id first
        $search = sprintf('%04x', $id_vendor);

        while (!feof($fh)) {
            $buffer = chop(fgets($fh, 4096));
            if (substr($buffer, 0, 4) != $search)
                continue;
            $details['vendor'] = substr($buffer, 6);
            break;
        }

        if ($details['vendor'] == NULL) {
            fclose($fh);
            return '';
        }

        // Find device id next
        $search = sprintf('%04x', $id_device);

        while (!feof($fh)) {
            $byte = fread($fh, 1);
            if ($byte == '#') {
                fgets($fh, 4096);
                continue;
            } else if ($byte != "\t") {
                break;
            }

            $buffer = chop(fgets($fh, 4096));
            if (substr($buffer, 0, 4) != $search)
                continue;
            $details['device'] = substr($buffer, 6);
            break;
        }

        if ($details['device'] == NULL) {
            if (!$is_usb) 
                fclose($fh);

            return $details;
        }

        if ($id_sub_vendor == 0) {
            fclose($fh);
            return $details;
        }

        // Find (optional) sub-vendor id next
        $search = sprintf('%04x %04x', $id_sub_vendor, $id_sub_device);

        while (!feof($fh)) {
            $byte = fread($fh, 1);
            if ($byte == '#') {
                fgets($fh, 4096);
                continue;
            } else if ($byte != "\t") {
                break;
            }

            if(fread($fh, 1) != "\t")
                break;

            $buffer = chop(fgets($fh, 4096));
            if (substr($buffer, 0, 9) != $search)
                continue;
            $details['sub_device'] = substr($buffer, 11);
            break;
        }

        fclose($fh);

        return $details;
    }
    
    /**
     * Returns state of interface.
     *
     * @return boolean TRUE if active
     * @throws Engine_Exception
     */

    public function is_active()
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        $shell = new Shell();
        $shell->execute(self::COMMAND_IFCONFIG, $this->iface, TRUE);

        $output = $shell->get_output();

        foreach ($output as $line) {
            if (preg_match('/^' .$this->iface . '/', $line))
                return TRUE;
        }

        return FALSE;
    }

    /**
     * Returns the configurability of interface.
     *
     * Dynamic interfaces (e.g. an incoming pppX interface from PPTP VPN)
     * are not configurable.
     *
     * @return  boolean TRUE if configurable
     */

    public function is_configurable()
    {
        clearos_profile(__METHOD__, __LINE__);

        // PPPoE interfaces are configurable, but only if they already configured.

        if (preg_match('/^eth/', $this->iface)
            || preg_match('/^wlan/', $this->iface)
            || preg_match('/^ath/', $this->iface)
            || preg_match('/^em/', $this->iface)
            || preg_match('/^en/', $this->iface)
            || preg_match('/^p\d+p/', $this->iface)
            || preg_match('/^br/', $this->iface) 
            || preg_match('/^bond/', $this->iface)
            || (preg_match('/^ppp/', $this->iface) && $this->is_configured())
        ) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Checks to see if interface has an associated configuration file.
     *
     * @return  boolean TRUE if configuration file exists
     * @throws  Engine_Exception
     */

    public function is_configured()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!is_null($this->is_configured))
            return $this->is_configured;

        $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

        $this->is_configured = ($file->exists()) ? TRUE : FALSE;

        return $this->is_configured;
    }

    /**
     * Checks to see if interface name is available on the system.
     *
     * @return boolean TRUE if interface is valid
     * @throws Engine_Exception
     */

    public function is_valid()
    {
        clearos_profile(__METHOD__, __LINE__);

        $options['filter_loopback'] = FALSE;

        $iface_manager = new Iface_Manager();
        $interfaces = $iface_manager->get_interfaces($options);

        foreach ($interfaces as $iface) {
            if ($this->iface === $iface)
                return TRUE;
        }

        return FALSE;
    }

    /**
     * Sets network MTU.
     *
     * @param integer $mtu interface network MTU
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_mtu($mtu)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

        if (! $file->exists())
            return;

        try {
            $file->lookup_value('/^MTU\s*=\s*/');
            $file->replace_lines('/^MTU\s*=.*$/', "MTU=\"$mtu\"\n", 1);
        } catch (File_No_Match_Exception $e) {
            $file->add_lines("MTU=\"$mtu\"\n");
        }

        $this->config = NULL;
    }

    /**
     * Reads interface configuration file.
     *
     * @return  array  network configuration settings
     * @throws  Engine_Exception
     */

    public function read_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!is_null($this->config))
            return $this->config;

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        $options['skip_size_check'] = TRUE;

        $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface, FALSE, FALSE, $options);

        if (! $file->exists())
            return NULL;

        $lines = $file->get_contents_as_array();

        foreach ($lines as $line) {
            $line = preg_replace('/"/', '', $line);

            if (preg_match('/^\s*#/', $line) || !strlen($line))
                continue;

            $line = preg_split('/=/', $line);

            if (preg_match('/^no$/i', $line[1]))
                $netinfo[strtolower($line[0])] = FALSE;
            else if (preg_match('/^yes$/i', $line[1]))
                $netinfo[strtolower($line[0])] = TRUE;
            else
                $netinfo[strtolower($line[0])] = $line[1];
        }

        // Translate constants into English
        if (isset($netinfo['bootproto'])) {
            // PPPOEKLUDGE - "dialup" is used by PPPoE
            if ($netinfo['bootproto'] == self::BOOTPROTO_DIALUP)
                $netinfo['bootproto'] = self::BOOTPROTO_PPPOE;

            if ($netinfo['bootproto'] == self::BOOTPROTO_STATIC)
                $netinfo['bootprototext'] = lang('network_bootproto_static');
            else if ($netinfo['bootproto'] == self::BOOTPROTO_DHCP)
                $netinfo['bootprototext'] = lang('network_bootproto_dhcp');
            else if ($netinfo['bootproto'] == self::BOOTPROTO_PPPOE)
                $netinfo['bootprototext'] = lang('network_bootproto_pppoe');
            else if ($netinfo['bootproto'] == self::BOOTPROTO_BOOTP)
                $netinfo['bootprototext'] = lang('network_bootproto_bootp');
            else 
                $netinfo['bootprototext'] = lang('network_bootproto_static');
        }

        // Set some default based on behavior of network scripts
        if ((isset($netinfo['bootproto']) 
            && (($netinfo['bootproto'] == self::BOOTPROTO_PPPOE) || ($netinfo['bootproto'] == self::BOOTPROTO_DHCP)))
            && (!isset($netinfo['peerdns']))
        )

        $netinfo['peerdns'] = TRUE;

        $this->config = $netinfo;

        return $netinfo;
    }

    /**
     * Writes interface configuration file.
     *
     * @param array $netinfo network information
     *
     * @return boolean TRUE if write succeeds
     * @throws Engine_Exception
     */

    public function write_config($netinfo)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));

        $file = new File(self::PATH_SYSCONF . '/network-scripts/ifcfg-' . $this->iface);

        if ($file->exists())
            $file->delete();

        $mode = ($netinfo['TYPE'] === self::TYPE_WIRELESS) ? '0600' : '0644';

        $file->create('root', 'root', $mode);

        foreach ($netinfo as $key => $value) {
            // The underlying network scripts do not like quotes on DEVICE
            if ($key == 'DEVICE')
                $file->add_lines(strtoupper($key) . '=' . $value . "\n");
            else
                $file->add_lines(strtoupper($key) . '="' . $value . "\"\n");
        }

        return TRUE;
    }

    /**
     * Creates a PPPoE configuration.
     *
     * @param string  $eth      ethernet interface to use
     * @param string  $username username
     * @param string  $password password
     * @param integer $mtu      MTU
     * @param boolean $peerdns  set DNS servers
     *
     * @return string New/current PPPoE interface name
     * @throws Engine_Exception
     */

    public function save_pppoe_config($eth, $username, $password, $mtu = NULL, $peerdns = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));
        Validation_Exception::is_valid($this->validate_interface($eth));
        Validation_Exception::is_valid($this->validate_username($username));
        Validation_Exception::is_valid($this->validate_password($password));
        Validation_Exception::is_valid($this->validate_peerdns($peerdns));
        if (!empty($mtu))
            Validation_Exception::is_valid($this->validate_mtu($mtu));

        // PPPoE hacking... again.
        // Before saving over an existing configuration, grab
        // the current configuration and delete the associated
        // password from chap/pap secrets.

        $chap = new Chap();
        $oldiface = new Iface($eth);
        $oldinfo = $oldiface->get_info();

        if (isset($oldinfo['ifcfg']['user']))
            $chap->delete_secret($oldinfo['ifcfg']['user']);

        if (isset($oldinfo['role'])) {
            try {
                $role = new Role();
                $role->remove_interface_role($eth);
            } catch (Engine_Exception $e) {
                // Not fatal
            }
        }

        $physdev = $eth;

        if (substr($eth, 0, 3) == 'ppp') {
            $pppoe = new Iface($eth);
            $ifcfg = $pppoe->get_info();
            $physdev = $ifcfg['ifcfg']['eth'];
        } else {
            for ($i = 0; $i < 64; $i++) {
                $pppoe = new Iface('ppp' . $i);
                if (! $pppoe->is_configured()) {
                    $eth = 'ppp' . $i;
                    break;
                }
            }
        }

        // Blank out the ethernet interface used for PPPoE
        //------------------------------------------------

        $ethernet = new Iface($physdev);
        $liveinfo = $ethernet->get_info();

        $ethinfo = array();
        $ethinfo['DEVICE'] = $physdev;
        $ethinfo['BOOTPROTO'] = 'none';
        $ethinfo['ONBOOT'] = 'no';

        try {
            $ethernet->disable(); // See maintenance note
        } catch (Engine_Exception $e) {
            // Not fatal
        }

        $ethernet->write_config($ethinfo);
        $this->config = NULL;

        // Write PPPoE config
        //-------------------

        $info = array();
        $info['DEVICE'] = $eth;
        $info['TYPE'] = self::TYPE_PPPOE;
        $info['USERCTL'] = 'no';
        $info['BOOTPROTO'] = 'dialup';
        $info['NAME'] = 'DSL' . $eth;
        $info['ONBOOT'] = 'yes';
        $info['PIDFILE'] = '/var/run/pppoe-' . $eth . '.pid';
        $info['FIREWALL'] = 'NONE';
        $info['PING'] = '.';
        $info['PPPOE_TIMEOUT'] = '80';
        $info['LCP_FAILURE'] = '5';
        $info['LCP_INTERVAL'] = '20';
        $info['CLAMPMSS'] = '1412';
        $info['CONNECT_POLL'] = '6';
        $info['CONNECT_TIMEOUT'] = '80';
        $info['DEFROUTE'] = 'yes';
        $info['SYNCHRONOUS'] = 'no';
        $info['ETH'] = $physdev;
        $info['PROVIDER'] = 'DSL' . $eth;
        $info['PEERDNS'] = ($peerdns) ? 'yes' : 'no';
        $info['USER'] = $username;

        if (!empty($mtu))
            $info['MTU'] = $mtu;

        $pppoe = new Iface($eth);

        try {
            $pppoe->disable();
        } catch (Engine_Exception $e) {
            // Not fatal
        }

        $pppoe->write_config($info);
        $this->config = NULL;

        // Add password to chap-secrets
        //-----------------------------

        $chap->add_secret($username, $password);

        return $eth;
    }

    /**
     * Creates a standard ethernet configuration.
     *
     * @param string  $hostname optional DHCP hostname (for DHCP only)
     * @param boolean $peerdns  set to TRUE if you want to use the DHCP peer DNS settings
     * @param array   $wireless wireless information if wireless
     *
     * @return void
     * @throws  Engine_Exception
     */

    public function save_dhcp_config($hostname, $peerdns, $wireless = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));
        Validation_Exception::is_valid($this->validate_peerdns($peerdns));

        if (! empty($hostname))
            Validation_Exception::is_valid($this->validate_hostname($hostname));

        $liveinfo = $this->get_info();
        $hwaddress = $liveinfo['hwaddress'];

        // Disable interface - see maintenance note
        try {
            $this->disable();
        } catch (Engine_Exception $e) {
            // Not fatal
        }

        $info = array();
        $info['DEVICE'] = $this->iface;
        $info['TYPE'] = self::TYPE_ETHERNET;
        $info['ONBOOT'] = 'yes';
        $info['USERCTL'] = 'no';
        $info['BOOTPROTO'] = 'dhcp';
        $info['PEERDNS'] = ($peerdns) ? 'yes' : 'no';

        if (strlen($hostname))
            $info['DHCP_HOSTNAME'] = $hostname;

        if (! empty($wireless['mode']))
            $this->_save_wireless_settings($info, $wireless);

        $this->write_config($info);
        $this->config = NULL;
    }

    /**
     * Creates a standard ethernet configuration.
     *
     * @param string $ip       IP address (for static only)
     * @param string $netmask  netmask (for static only)
     * @param string $gateway  gateway (for static only)
     * @param array  $wireless wireless information if wireless
     *
     * @return void
     * @throws  Engine_Exception
     */

    public function save_static_config($ip, $netmask, $gateway = NULL, $wireless = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_ip($ip));
        Validation_Exception::is_valid($this->validate_netmask($netmask));

        if (! empty($gateway))
            Validation_Exception::is_valid($this->validate_gateway($gateway));

        $liveinfo = $this->get_info();
        $hwaddress = $liveinfo['hwaddress'];

        // Disable interface - see maintenance note

        try {
            $this->disable();
        } catch (Engine_Exception $e) {
            // Not fatal
        }

        $info = array();
        $info['DEVICE'] = $this->iface;
        $info['TYPE'] = self::TYPE_ETHERNET;
        $info['ONBOOT'] = 'yes';
        $info['USERCTL'] = 'no';
        $info['BOOTPROTO'] = 'static';
        $info['IPADDR'] = $ip;
        $info['NETMASK'] = $netmask;

        if (! empty($gateway))
            $info['GATEWAY'] = $gateway;

        if (! empty($wireless['mode']))
            $this->_save_wireless_settings($info, $wireless);

        $this->write_config($info);
        $this->config = NULL;
    }

    /**
     * Creates a virtual ethernet configuration.
     *
     * @param string $ip      IP address
     * @param string $netmask netmask
     *
     * @return string  name of virtual interface
     * @throws Engine_Exception, Engine_Exception
     */

    public function save_virtual_config($ip, $netmask)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));
        Validation_Exception::is_valid($this->validate_ip($ip));
        Validation_Exception::is_valid($this->validate_netmask($netmask));

        list($device, $metric) = preg_split('/:/', $this->iface, 5);

        if (! strlen($metric)) {
            // Find next free virtual metric

            for ($metric = 0; $metric < 1024; $metric++) {
                if (! file_exists(self::PATH_SYSCONF .  '/network-scripts/ifcfg-' . $this->iface . ':' . $metric))
                    break;
            }

            // Rename interface
            $this->iface = $this->iface . ':' . $metric;
        }

        // Disable interface - see maintenance note

        try {
            $this->disable();
        } catch (Engine_Exception $e) {
            // Not fatal
        }

        $info = array();
        $info['DEVICE'] = $this->iface;
        $info['TYPE'] = self::TYPE_VIRTUAL;
        $info['ONBOOT'] = 'yes';
        $info['USERCTL'] = 'no';
        $info['BOOTPROTO'] = 'static';
        $info['NO_ALIASROUTING'] = 'yes';
        $info['IPADDR'] = $ip;
        $info['NETMASK'] = $netmask;
        $this->write_config($info);
        $this->config = NULL;

        return $this->iface;
    }

    /**
     * Creates a standard VLAN DHCP configuration.
     *
     * @param string  $hostname optional DHCP hostname (for DHCP only)
     * @param boolean $peerdns  set to TRUE if you want to use the DHCP peer DNS settings
     *
     * @return void
     * @throws  Engine_Exception
     */

    public function save_vlan_dhcp_config($vlan_id, $hostname, $peerdns)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));
        Validation_Exception::is_valid($this->validate_vlan_id($vlan_id));
        Validation_Exception::is_valid($this->validate_peerdns($peerdns));

        if (! empty($hostname))
            Validation_Exception::is_valid($this->validate_hostname($hostname));

        if (!preg_match('/\.\d+$/', $this->iface))
            $this->iface = $this->iface . '.' . $vlan_id;

        // Disable interface - see maintenance note
        try {
            $this->disable();
        } catch (Engine_Exception $e) {
            // Not fatal
        }

        $info = array();
        $info['DEVICE'] = $this->iface;
        $info['TYPE'] = self::TYPE_VLAN;
        $info['ONBOOT'] = 'yes';
        $info['USERCTL'] = 'no';
        $info['BOOTPROTO'] = 'dhcp';
        $info['PEERDNS'] = ($peerdns) ? 'yes' : 'no';
        $info['VLAN'] = 'yes';

        if (strlen($hostname))
            $info['DHCP_HOSTNAME'] = $hostname;

        $this->write_config($info);
        $this->config = NULL;

        return $this->iface;
    }

    /**
     * Creates a VLAN ethernet configuration.
     *
     * @param intenger $vlan_id VLAN ID
     * @param string   $ip      IP address
     * @param string   $netmask netmask
     * @param string   $gateway  gateway (for static only)
     *
     * @return string name of VLAN interface
     * @throws Engine_Exception, Engine_Exception
     */

    public function save_vlan_static_config($vlan_id, $ip, $netmask, $gateway = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($this->iface));
        Validation_Exception::is_valid($this->validate_vlan_id($vlan_id));
        Validation_Exception::is_valid($this->validate_ip($ip));
        Validation_Exception::is_valid($this->validate_netmask($netmask));

        if (! empty($gateway))
            Validation_Exception::is_valid($this->validate_gateway($gateway));

        if (!preg_match('/\.\d+$/', $this->iface))
            $this->iface = $this->iface . '.' . $vlan_id;

        // Disable interface - see maintenance note
        try {
            $this->disable();
        } catch (Engine_Exception $e) {
            // Not fatal
        }

        $info = array();
        $info['DEVICE'] = $this->iface;
        $info['TYPE'] = self::TYPE_VLAN;
        $info['ONBOOT'] = 'yes';
        $info['USERCTL'] = 'no';
        $info['BOOTPROTO'] = 'static';
        $info['IPADDR'] = $ip;
        $info['NETMASK'] = $netmask;
        $info['VLAN'] = 'yes';

        if (! empty($gateway))
            $info['GATEWAY'] = $gateway;

        $this->write_config($info);
        $this->config = NULL;

        return $this->iface;
    }

    ///////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for boot protocol.
     *
     * @param string $boot_protocol boot protocol
     *
     * @return string error message if boot protocol is invalid
     */

    public function validate_boot_protocol($boot_protocol)
    {
        clearos_profile(__METHOD__, __LINE__);

        $supported = $this->get_supported_bootprotos();

        if (! array_key_exists($boot_protocol, $supported))
            return lang('network_boot_protocol_invalid');
    }

    /**
     * Validation routine for gateway.
     *
     * @param string $gateway gateway
     *
     * @return string error message if gateway is invalid
     */

    public function validate_gateway($gateway)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_ip($gateway))
            return lang('network_gateway_invalid');
    }

    /**
     * Validation routine for gateway flag.
     *
     * @param string $gateway_flag gateway flag
     *
     * @return string error message if gateway flag is invalid
     */

    public function validate_gateway_flag($gateway_flag)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_is_valid_boolean($gateway_flag))
            return lang('network_gateway_flag_invalid');
    }

    /**
     * Validation routine for hostname.
     *
     * @param string $hostname hostname
     *
     * @return string error message if hostname is invalid
     */

    public function validate_hostname($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!(Network_Utils::is_valid_hostname_alias($hostname) || Network_Utils::is_valid_hostname($hostname)))
            return lang('network_hostname_invalid');
    }

    /**
     * Validation routine for network interface.
     *
     * @param string $interface network interface
     *
     * @return string error message if network interface is invalid
     */

    public function validate_interface($interface)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/^[a-zA-Z0-9:\._]+$/', $interface))
            return lang('network_network_interface_invalid');
    }

    /**
     * Validation routine for IP address.
     *
     * @param string $ip IP address
     *
     * @return string error message if IP address is invalid
     */

    public function validate_ip($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_ip($ip))
            return lang('network_ip_invalid');
    }

    /**
     * Validation routine for netmask.
     *
     * @param string $netmask netmask
     *
     * @return string error message if netmask is invalid
     */

    public function validate_netmask($netmask)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_netmask($netmask))
            return lang('network_netmask_invalid');
    }

    /**
     * Validation routine for password.
     *
     * @param string $password password
     *
     * @return string error message if password is invalid
     */

    public function validate_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO
    }

    /**
     * Validation routine for network peerdns.
     *
     * @param string $peerdns network peerdns
     *
     * @return string error message if network peerdns is invalid
     */

    public function validate_peerdns($peerdns)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_is_valid_boolean($peerdns))
            return lang('network_automatic_dns_server_flag_invalid');
    }

    /**
     * Validation routine for network MTU.
     *
     * @param string $mtu network MTU
     *
     * @return string error message if network MTU is invalid
     */

    public function validate_mtu($mtu)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/^[0-9]+$/', $mtu))
            return lang('network_mtu_invalid');
    }

    /**
     * Validation routine for username.
     *
     * @param string $username username
     *
     * @return string error message if username is invalid
     */

    public function validate_username($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO
        // if (! preg_match('/^[a-zA-Z0-9:]+$/', $username))
        //    return lang('network_username_invalid');
    }

    /**
     * Validation routine for VLAN ID.
     *
     * @param string $vlan_id VLAN ID
     *
     * @return string error message if VLAN ID is invalid
     */

    public function validate_vlan_id($vlan_id)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match('/^\d+$/', $vlan_id) || ($vlan_id < 0) || ($vlan_id > 4095))
            return lang('network_vlan_id_invalid');
    }

    /**
     * Validation routine for wireless channels.
     *
     * @param integer $channel wireless channel
     *
     * @return string error message if wireless channel is invalid
     */

    public function validate_wireless_channel($channel)
    {
        clearos_profile(__METHOD__, __LINE__);

        $supported = $this->get_supported_wireless_channels();

        if (! array_key_exists($channel, $supported))
            return lang('network_wireless_channel_invalid');
    }

    /**
     * Validation routine for wireless mode.
     *
     * @param integer $mode wireless mode
     *
     * @return string error message if wireless mode is invalid
     */

    public function validate_wireless_mode($mode)
    {
        clearos_profile(__METHOD__, __LINE__);

        $supported = $this->get_supported_wireless_modes();

        if (! array_key_exists($mode, $supported))
            return lang('network_wireless_mode_invalid');
    }

    /**
     * Validation routine for wireless passphrase.
     *
     * @param integer $passphrase wireless passphrase
     *
     * @return string error message if wireless passphrase is invalid
     */

    public function validate_wireless_passphrase($passphrase)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (strlen($passphrase) > 32)
            return lang('network_wireless_passphrase_invalid');
    }

    /**
     * Validation routine for wireless SSID.
     *
     * @param integer $ssid wireless SSID
     *
     * @return string error message if wireless SSID is invalid
     */

    public function validate_wireless_ssid($ssid)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match('/^[\w\-_\.]+$/', $ssid))
            return lang('network_ssid_invalid');

        if (strlen($ssid) > 32)
            return lang('network_ssid_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Creates wireless network configuration.
     *
     * Options include:
     * - ssid
     * - channel
     * - mode
     * - key
     *
     * @param array $options wireless configuration options
     *
     * @return void
     * @throws  Engine_Exception, Engine_Exception
     */

    protected function _get_wireless_configlet($options)
    {
        clearos_profile(__METHOD__, __LINE__);


        return $info;
    }

    /**
     * Configures extra wireless settings in Hostapd and RADIUS.
     *
     * Options include:
     * - ssid
     * - channel
     * - mode
     * - key
     *
     * @param array $options wireless configuration options
     *
     * @return void
     * @throws  Engine_Exception, Engine_Exception
     */

    protected function _save_hostapd_hooks($options)
    {
        clearos_profile(__METHOD__, __LINE__);

    }

    /**
     * Saves wireless configuration and hooks.
     *
     * Wireless ptions include:
     * - ssid
     * - channel
     * - mode
     * - key
     *
     * @param array &$info    configuration file options
     * @param array $wireless specified wireless options
     *
     * @return void
     * @throws  Engine_Exception, Engine_Exception
     */

    protected function _save_wireless_settings(&$info, $wireless)
    {
        clearos_profile(__METHOD__, __LINE__);

        unset($info['MODE']);
        unset($info['ESSID']);
        unset($info['CHANNEL']);
        unset($info['KEY']);

        // Set ifcfg-ethX configuration
        //-----------------------------

        $info['TYPE'] = self::TYPE_WIRELESS;

        if ($wireless['mode'] === self::WIRELESS_WEP_CLIENT) {
            $info['MODE'] = self::CONSTANT_WEP_CLIENT;
            $info['ESSID'] = $wireless['ssid'];
            $info['KEY'] = $wireless['passphrase'];

            if ($wireless['channel'] != 0)
                $info['CHANNEL'] = $wireless['channel'];

            return;
        }

        // Save hostapd stuff
        //-------------------

        if (! clearos_library_installed('wireless/Hostapd'))
            return;

        clearos_load_library('wireless/Hostapd');

        $hostapd = new \clearos\apps\wireless\Hostapd();
        $hostapd->set_ssid($wireless['ssid']);
        $hostapd->set_mode($wireless['mode']);
        $hostapd->set_interface($this->iface);

        if ($wireless['channel'] != 0)
            $hostapd->set_channel($wireless['channel']);

        if ($wireless['mode'] === self::WIRELESS_WPA_PSK)
            $hostapd->set_wpa_passphrase($wireless['passphrase']);

        try {
            if ($hostapd->get_running_state())
                $hostapd->restart();
            else
                $hostapd->set_running_state(TRUE);

            if (!$hostapd->get_boot_state())
                $hostapd->set_boot_state(TRUE);
        } catch (Exception $e) {
            // Keep going.
        }
    }
}

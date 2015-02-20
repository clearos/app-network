<?php

/**
 * Network interface manager class.
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
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\network\Iface as Iface;
use \clearos\apps\network\Network as Network;
use \clearos\apps\network\Role as Role;
use \clearos\apps\network\Routes as Routes;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('network/Iface');
clearos_load_library('network/Network');
clearos_load_library('network/Role');
clearos_load_library('network/Routes');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;

clearos_load_library('base/Engine_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network interface manager class.
 *
 * @category   apps
 * @package    network
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2002-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Iface_Manager extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const EXTERNAL_ROLE = 'EXTIF'; // TODO: should match firewall/Role constant
    const PATH_NET_CONFIG = '/etc/sysconfig/network-scripts';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $is_loaded = FALSE;
    protected $ethinfo = array();
    protected $ifconfig_ctx = NULL;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Iface_Manager constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! extension_loaded('ifconfig'))
            throw new Engine_Exception(lang('network_network_error_occurred'));

        $this->ifconfig_ctx = @ifconfig_init();
    }

    /**
     * Cleans MAC addresses.
     *
     * The ifcfg-ethX files embed the MAC address.  When the configured MAC 
     * does not match the hardware MAC, the network interface will not work.
     * I'm sure there's a good reason for this, but it's problematic on 
     * backup/restore.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function clean_macs()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ifaces = $this->get_interface_details();

        foreach ($ifaces as $iface_name => $details) {
            if (!empty($details['configured']) && isset($details['ifcfg']['hwaddr'])) {
                $file = new File('/etc/sysconfig/network-scripts/ifcfg-' . $iface_name);
                if ($file->exists()) {
                    clearos_log('network', 'removing old MAC address in network configuration: ' . $iface_name);
                    $file->delete_lines('/^HWADDR=/');
                }
            } 
        }
    }

    /**
     * Returns array of interfaces (real and dynamic).
     *
     * Filter options:
     * - filter_ibvpn: filters out ibVPN tunnels (default: TRUE)
     * - filter_imq: filters out IMQ interfaces (default: TRUE)
     * - filter_ppp: filters out PPP interfaces (default: FALSE)
     * - filter_loopback: filter out loopback interface (default: TRUE)
     * - filter_pptp: filters out PPTP VPN interfaces (default: TRUE)
     * - filter_sit: filters out sit interfaces (default: TRUE)
     * - filter_tun: filters out tunnel (OpenVPN) interfaces (default: TRUE)
     * - filter_virtual: filters out virtual interfaces (default: TRUE)
     *
     * @param array $options filter options
     *
     * @return array list of network devices (using ifconfig.so)
     * @throws Engine_Exception
     */

    public function get_interfaces($options = NULL)
    {
        $details = $this->_get_interface_details($options);

        return array_keys($details);
    }

    /**
     * Returns interface count (real interfaces only).
     *
     * @return int number of real network devices (using ifconfig.so)
     * @throws Engine_Exception
     */

    public function get_interface_count()
    {
        clearos_profile(__METHOD__, __LINE__);

        $count = 0;
        $list = @ifconfig_list($this->ifconfig_ctx);

        foreach ($list as $device) {
            $flags = @ifconfig_flags($this->ifconfig_ctx, $device);

            if (($flags & IFF_NOARP)) continue;
            if (($flags & IFF_LOOPBACK)) continue;
            if (($flags & IFF_POINTOPOINT)) continue;

            // No virtual interfaces either...
            if (preg_match("/:\d+$/", $device)) continue;

            $count++;
        }

        return $count;
    }

    /**
     * Returns detailed information on all network interfaces.
     *
     * See get_interfaces for details on the options parameter. This method
     * also adds the following options:
     *
     * - filter_ppp: filters out PPP interfaces (default: FALSE)
     *
     * @param array $options filter options
     *
     * @return array information on all network interfaces.
     * @throws Engine_Exception
     */

    public function get_interface_details($options = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_interface_details($options);
    }

    /**
     * Returns list of most trusted network interfaces.
     *
     * In gateway mode, this will return a list of LAN IP addresses.
     * In standalone mode, it will return all IPs (typically just one).
     *
     * @return array list of most trusted network interfaces
     * @throws Engine_Exception
     */

    public function get_most_trusted_interfaces()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_most_trusted('interfaces');
    }

    /**
     * Returns list of most trusted IPs.
     *
     * In gateway mode, this will return a list of LAN IP addresses.
     * In standalone mode, it will return all IPs (typically just one).
     *
     * @return array list of most trusted IPs.
     * @throws Engine_Exception
     */

    public function get_most_trusted_ips()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_most_trusted('ips');
    }

    /**
     * Returns list of most trusted networks.
     *
     * In gateway mode, this will return a list of LAN networks.
     * In standalone mode, it will return all networks (typically just one).
     *
     * @param boolean $use_prefix set TRUE if prefix should be returned instead of netmask
     * @param boolean $extra_lans set TRUE if extra LANs should be included
     *
     * @return array list of most trusted networks.
     * @throws Engine_Exception
     */

    public function get_most_trusted_networks($use_prefix = FALSE, $extra_lans = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $networks = $this->_get_most_trusted('networks', $use_prefix);

        if ($extra_lans) {
            $routes = new Routes();
            $lans = $routes->get_extra_lans();

            if (! empty($lans))
                $networks = array_merge($networks, $lans);
        }

        return $networks;
    }

    /**
     * Returns list of Wifi interfaces.
     *
     * @return array list of Wifi interfaces
     * @throws Engine_Exception
     */

    public function get_wifi_interfaces()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ethlist = $this->get_interface_details();
        $wifilist = array();

        foreach ($ethlist as $eth => $details) {
            if ($details['type'] == Iface::TYPE_WIRELESS)
                $wifilist[] = $eth;
        }

        return $wifilist;
    }
    
    /**
     * Returns the external IP address
     *
     * @return external IP address
     * @throws Engine_Exception
     */

    public function get_external_ip_address()
    {
        $interface = $this->get_external_interface();

        if ($interface != NULL)
            return $interface['address'];
    }

    /**
     * Returns the external IP addresses.
     *
     * @return list of external IP addresses
     * @throws Engine_Exception
     */

    public function get_external_ip_addresses()
    {
        $ifaces = $this->get_external_interfaces();

        $addresses = array();

        foreach ($ifaces as $interface) {
            $iface = new Iface($interface);
            try {
                $addresses[] = $iface->get_live_ip();
            } catch (Exception $e) {
                // keep going
            }
        }

        return $addresses;
    }

    /**
     * Returns the external interface.
     *
     * @return external interface
     * @throws Engine_Exception
     */

    public function get_external_interface()
    {
        $ethlist = $this->get_interface_details();

        foreach ($ethlist as $eth => $details) {
            if ($details['role'] == 'EXTIF')
                return $details;
        }
    }

    /**
     * Returns a list of interfaces configured with the given role.
     *
     * @return array list of external interfaces
     * @throws Engine_Exception
     */

    public function get_external_interfaces()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ifaces = array();
        $ethlist = $this->get_interface_details();

        foreach ($ethlist as $eth => $info) {
            if ($info['role'] != Role::ROLE_EXTERNAL)
                continue;

            // Skip interfaces used 'indirectly' (e.g. PPPoE, bonded interfaces)
            if (isset($info['master']))
                continue;

            // Skip 1-to-1 NAT interfaces
            if (isset($info['one-to-one-nat']) && $info['one-to-one-nat'])
                continue;

            // Skip non-configurable interfaces
            if (! $info['configurable'])
                continue;

            // Skip virtual interfaces
            if (isset($info['virtual']) && $info['virtual'])
                continue;

            $ifaces[] = $eth;   
        }

        return $ifaces;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Returns interface details.
     *
     * See get_interface_details.
     *
     * @param array $options filter options
     *
     * @return array interface details
     */

    protected function _get_interface_details($options = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->is_loaded)
            return $this->ethinfo;

        $ethinfo = array();

        $options['filter_ibvpn'] = isset($options['filter_ibvpn']) ? $options['filter_ibvpn'] : TRUE;
        $options['filter_imq'] = isset($options['filter_imq']) ? $options['filter_imq'] : TRUE;
        $options['filter_ppp'] = isset($options['filter_ppp']) ? $options['filter_ppp'] : FALSE;
        $options['filter_loopback'] = isset($options['filter_loopback']) ? $options['filter_loopback'] : TRUE;
        $options['filter_pptp'] = isset($options['filter_pptp']) ? $options['filter_pptp'] : TRUE;
        $options['filter_sit'] = isset($options['filter_sit']) ? $options['filter_sit'] : TRUE;
        $options['filter_tun'] = isset($options['filter_tun']) ? $options['filter_tun'] : TRUE;
        $options['filter_virtual'] = isset($options['filter_virtual']) ? $options['filter_virtual'] : TRUE;
        $options['filter_vlan'] = isset($options['filter_vlan']) ? $options['filter_vlan'] : FALSE;
        $options['filter_1to1_nat'] = isset($options['filter_1to1_nat']) ? $options['filter_1to1_nat'] : TRUE;
        $options['filter_non_configurable'] = isset($options['filter_non_configurable']) ? $options['filter_non_configurable'] : TRUE;
        $options['filter_slave'] = isset($options['filter_slave']) ? $options['filter_slave'] : TRUE;
        $options['filter_wireless_mon'] = isset($options['filter_wireless_mon']) ? $options['filter_wireless_mon'] : TRUE;

        $list = @ifconfig_list($this->ifconfig_ctx);
        $list = array_unique($list);
        sort($list);

        $rawlist = array();

        // Running interfaces
        //-------------------

        foreach ($list as $device) {
            $flags = @ifconfig_flags($this->ifconfig_ctx, $device);
            $rawlist[] = $device;
        }

        // Configured interfaces
        //----------------------

        $matches = array();
        $folder = new Folder(self::PATH_NET_CONFIG);
        $listing = $folder->get_listing();

        foreach ($listing as $netconfig) {
            if (preg_match('/^ifcfg-(.*)/', $netconfig, $matches))
                $rawlist[] = $matches[1];
        }

        // Purge unwanted interfaces
        //--------------------------

        $rawlist = array_unique($rawlist);
        $interfaces = array();

        foreach ($rawlist as $iface) {
            if ($options['filter_imq'] && preg_match('/^imq/', $iface))
                continue;

            if ($options['filter_loopback'] && $iface == 'lo')
                continue;

            if ($options['filter_ppp'] && preg_match('/^ppp/', $iface))
                continue;

            if ($options['filter_pptp'] && preg_match('/^pptp/', $iface))
                continue;

            if ($options['filter_sit'] && preg_match('/^sit/', $iface))
                continue;

            if ($options['filter_tun'] && preg_match('/^tun/', $iface))
                continue;

            if ($options['filter_ibvpn'] && preg_match('/^ibvpn/', $iface))
                continue;

            if ($options['filter_wireless_mon'] && preg_match('/^mon/', $iface))
                continue;

            if ($options['filter_virtual'] && preg_match('/:/', $iface))
                continue;

            if ($options['filter_vlan'] && preg_match('/\.\d+$/', $iface))
                continue;

            $interfaces[] = $iface;
        }

        $slaveif = array();

        // Now go through the configuration
        //---------------------------------

        foreach ($interfaces as $eth) {

            $interface = new Iface($eth);

            // Check is_configurable filter before expensive get_info() call below
            //--------------------------------------------------------------------
            
            if ($options['filter_non_configurable'] && !$interface->is_configurable())
                continue;

            // Grab interface information
            //---------------------------

            $ifdetails = $interface->get_info();

            // Core configuration
            //-------------------

            foreach ($ifdetails as $key => $value)
                $ethinfo[$eth][$key] = $value;

            // Flag network interfaces used by PPPoE
            //--------------------------------------

            if (isset($ethinfo[$eth]['ifcfg']['eth'])) {
                $pppoeif = $ethinfo[$eth]['ifcfg']['eth'];
                $ethinfo[$pppoeif]['master'] = $eth;
                $slaveif[$eth] = $pppoeif;
            }

            // Filter options
            //---------------

            if ($options['filter_1to1_nat'] && isset($ethinfo[$eth]['one-to-one-nat']) && $ethinfo[$eth]['one-to-one-nat']) {
                unset($ethinfo[$eth]);
                continue;
            }

            // Interface role
            //---------------

            try {
                $role = new Role();
                $role_code = $role->get_interface_role($eth);
                $role_name = $role->get_interface_role_text($eth);

                $ethinfo[$eth]['role'] = $role_code;
                $ethinfo[$eth]['roletext'] = $role_name;
            } catch (Exception $e) {
                // keep going
            }
        }

        // Go through interfaces to handle PPPoE slaves and roles
        //-------------------------------------------------------

        foreach ($slaveif as $master => $slave) {
            if ($options['filter_slave']) {
                unset($ethinfo[$slave]);
            } else {
                $ethinfo[$slave]['role'] = $ethinfo[$master]['role'];
                $ethinfo[$slave]['roletext'] = $ethinfo[$master]['roletext'];
            }
        }

        // Done
        //-----

        $this->ethinfo = $ethinfo;
        $this->is_loaded = TRUE;

        return $ethinfo;
    }

    /**
     * Returns most trusted network information.
     *
     * @param string  $type       type of information to return
     * @param boolean $use_prefix set TRUE if prefix should be returned instead of netmask
     *
     * @return array list of most trusted networks.
     * @throws Engine_Exception
     */

    public function _get_most_trusted($type, $use_prefix = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $network = new Network();
        $mode = $network->get_mode();

        $ethlist = $this->get_interface_details();

        $lans = array();
        $lan_ips = array();
        $lan_ifaces = array();

        foreach ($ethlist as $eth => $details) {
            // Only interested in configured interfaces
            if (! $details['configured'])
                continue;

            if (empty($details['address']))
                continue;

            // Gateway mode
            if (($details['role'] == Role::ROLE_LAN) && (! empty($details['address'])) && (! empty($details['netmask']))) {
                $basenetwork = Network_Utils::get_network_address($details['address'], $details['netmask']);
                $suffix = ($use_prefix) ? Network_Utils::get_prefix($details['netmask']) : $details['netmask'];
    
                $lans[] = $basenetwork . '/' . $suffix;
                $lan_ips[] = $details['address'];
                $lan_ifaces[] = $eth;
            }

            // Standalone mode
            else if (($details['role'] == Role::ROLE_EXTERNAL) && (! empty($details['address'])) && (! empty($details['netmask']))
                && ($mode == Network::MODE_TRUSTED_STANDALONE) || ($mode == Network::MODE_STANDALONE)
            ) {
                $basenetwork = Network_Utils::get_network_address($details['address'], $details['netmask']);
                $suffix = ($use_prefix) ? Network_Utils::get_prefix($details['netmask']) : $details['netmask'];

                $lans[] = $basenetwork . '/' . $suffix;
                $lan_ips[] = $details['address'];
                $lan_ifaces[] = $eth;
            }
        }

        if ($type === 'networks')
            return $lans;
        else if ($type === 'ips')
            return $lan_ips;
        else if ($type === 'interfaces')
            return $lan_ifaces;
    }
}

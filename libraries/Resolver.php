<?php

/**
 * The Resolver class manages the /etc/resolv.conf and /etc/resolv-peerdns.conf files.
 *
 * @category   apps
 * @package    network
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2002-2012 ClearFoundation
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
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\network\Domain as Domain;
use \clearos\apps\network\Iface_Manager as Iface_Manager;
use \clearos\apps\network\Network_Utils as Network_Utils;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Shell');
clearos_load_library('network/Domain');
clearos_load_library('network/Iface_Manager');
clearos_load_library('network/Network_Utils');

// Exceptions
//-----------

use \Exception as Exception;
use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;
use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_Not_Found_Exception');
clearos_load_library('base/File_No_Match_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * The Resolver class manages the /etc/resolv.conf and /etc/resolv-peerdns.conf files.
 *
 * @category   apps
 * @package    network
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2002-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Resolver extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_PEERDNS = '/etc/resolv-peerdns.conf';
    const FILE_RESOLV = '/etc/resolv.conf';
    const CONST_TEST_HOST = 'www.google.com';
    const PUBLIC_DNS1 = '8.8.8.8';
    const PUBLIC_DNS2 = '8.8.4.4';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Resolver constructor.
     *
     * @return void
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Returns domain.
     *
     * @return string domain
     * @throws Engine_Exception
     */

    public function get_local_domain()
    {
        clearos_profile(__METHOD__, __LINE__);

        $domain = $this->_get_parameter('domain');

        return $domain;
    }

    /**
     * Returns DNS servers.
     *
     * @return array DNS servers in an array
     * @throws Engine_Exception
     */

    public function get_nameservers()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_PEERDNS);

        if (! $file->exists())
            return array();

        // Fill the array
        //---------------

        $nameservers = array();

        $lines = $file->get_contents_as_array();

        foreach ($lines as $line) {
            if (preg_match('/^nameserver\s+/', $line))
                array_push($nameservers, preg_replace('/^nameserver\s+/', '', $line));
        }

        return $nameservers;
    }

    /**
     * Returns search parameter.
     *
     * @return string search
     * @throws Engine_Exception
     */

    public function get_search()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_parameter('search');
    }

    /**
     * Checks to see if DNS server settings are automatically set.
     *
     * @return boolean TRUE if DNS servers settings are automatically set
     */

    public function is_automatically_configured()
    {
        clearos_profile(__METHOD__, __LINE__);

        $iface_manager = new Iface_Manager();

        $ifaces = $iface_manager->get_interface_details();

        $is_automatic = FALSE;

        foreach ($ifaces as $iface => $details) {
            if (isset($details['ifcfg']['peerdns']) && $details['ifcfg']['peerdns'])
                $is_automatic = TRUE;
        }

        return $is_automatic;
    }

    /**
     * Sets DNS servers.
     *
     * Setting the DNS servers to blank will remove the line from /etc/resolv.conf.
     *
     * @param array $nameservers DNS servers
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_nameservers($nameservers)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! is_array($nameservers))
            $nameservers = array($nameservers);

        // Validate
        //---------

        $thelist = Array();

        foreach ($nameservers as $server) {
            $server = trim($server);

            if (! $server) {
                continue;
            } else {
                Validation_Exception::is_valid($this->validate_ip($server));
                $thelist[] = 'nameserver ' . $server;
            }
        }

        if (count($thelist) > 0)
            $this->_set_parameter('nameserver', $thelist);
        else
            $this->_set_parameter('nameserver', '');
    }

    /**
     * Perform DNS lookup.
     *
     * Performs a test DNS lookup using an external DNS resolver.  The PHP
     * system will cache the contents of /etc/resolv.conf.  That's leads to
     * FALSE DNS lookup errors when DNS servers happen to change.
     *
     * @param string  $domain  domain name to look-up
     * @param integer $timeout number of seconds until we time-out
     *
     * @return boolean result
     * @throws Engine_Exception, Validation_Exception
     */

    public function test_lookup($domain = self::CONST_TEST_HOST, $timeout = 5)
    {
        clearos_profile(__METHOD__, __LINE__);

        $shell = new Shell();
        $options['validate_exit_code'] = FALSE;

        $servers = $this->get_nameservers();

        foreach ($servers as $server) {
            if ($shell->execute('/usr/bin/dig', "@$server $domain +time=$timeout", FALSE, $options) == 0)
                return TRUE;
        }

        return FALSE;
    }

    /**
     * Perform DNS test.
     *
     * Performs a DNS look-up on each name server.
     *
     * @param string  $domain  domain name to look-up
     * @param integer $timeout number of seconds until we time-out
     *
     * @return array DNS test results
     * @throws Engine_Exception, Validation_Exception
     */

    public function test_nameservers($domain = self::CONST_TEST_HOST, $timeout = 10)
    {
        clearos_profile(__METHOD__, __LINE__);

        $result = array();
        $shell = new Shell();

        try {
            $servers = $this->get_nameserverss();

            foreach ($servers as $server) {
                if ($shell->execute('/usr/bin/dig', "@$server $domain +time=$timeout") == 0) {
                    $result[$server]['success'] = TRUE;
                } else {
                    $result[$server]['success'] = FALSE;
                }
            }
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e), CLEAROS_INFO);
        }

        return $result;
    }

    /**
     * Write /etc/resolv.conf.
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function handle_resolver_configuration()
    {
        clearos_profile(__METHOD__, __LINE__);

        // If Samba Directory installed, set domain to the realm
        //------------------------------------------------------

        $samba_dns_running = FALSE;
        $dnsmasq_running = FALSE;

        if (clearos_library_installed('samba_directory/Samba_Directory')) {
            clearos_load_library('samba_directory/Samba_Directory');
            $samba = new \clearos\apps\samba_directory\Samba_Directory();
            $samba_dns_running = $samba->get_dns_state();
            $domain = strtolower($samba->get_realm());

            $ifaces = new Iface_Manager();
            $trusted_ip = $ifaces->get_most_trusted_ips();

            if (empty($trusted_ip[0]))
                $dns_server = self::PUBLIC_DNS1;
            else
                $dns_server = $trusted_ip[0];

            // TODO: Clean this up at some point - forwarder should be dnsmasq
            // Adjust forwarder in smb.conf for now

            clearos_load_library('samba_common/Samba');

            $forwarders = $this->get_nameservers();

            $samba_common = new \clearos\apps\samba_common\Samba();
            $samba_common->set_dns_forwarder($forwarders[0]);
        }

        if (clearos_library_installed('dns/Dnsmasq')) {
            clearos_load_library('dns/Dnsmasq');
            $dnsmasq = new \clearos\apps\dns\Dnsmasq();
            $dnsmasq_running = $dnsmasq->get_running_state();

            $domain_object = new Domain();
            $domain = $domain_object->get_default();
        }

        $resolv_lines = "# Please do not edit this file.\n";
        $resolv_lines .= "# See http://www.clearcenter.com/support/documentation/clearos_guides/dns_and_resolver\n";

        if ($samba_dns_running) {
            if (! empty($domain))
                $resolv_lines .= "domain $domain\n";

            $resolv_lines .= "nameserver $dns_server\n";
        } else if ($dnsmasq_running) {
            if (! empty($domain))
                $resolv_lines .= "domain $domain\n";

            $resolv_lines .= "nameserver 127.0.0.1\n";
        } else {
            try {
                $peerdns_file = new File(self::FILE_PEERDNS);
                $raw_lines = $peerdns_file->get_contents_as_array();
                foreach ($raw_lines as $line) {
                    if (!preg_match('/^;/', $line))
                        $resolv_lines .= $line . "\n";
                }
            } catch (File_Not_Found_Exception $e) {
                $resolv_lines .= "nameserver " . self::PUBLIC_DNS1 . "\n";
                $resolv_lines .= "nameserver " . self::PUBLIC_DNS2 . "\n";
            }
        }

        $resolv_file = new File(self::FILE_RESOLV, TRUE);

        if ($resolv_file->exists())
            $current = $resolv_file->get_contents();
        else
            $current = '';

        if (trim($resolv_lines) == trim($current))
            return;

        if ($resolv_file->exists())
            $resolv_file->delete();

        $resolv_file->create('root', 'root', '0644');

        $resolv_lines .= "\n";
        $resolv_file->add_lines("$resolv_lines");
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates a DNS IP address.
     *
     * @param string $ip IP address
     *
     * @return string error message if ip address is invalid
     */

    public function validate_ip($ip)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_ip($ip))
            return lang('network_ip_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * A generic method to grab information from /etc/resolv.conf.
     *
     * @param string $key parameter - eg domain
     *
     * @access private
     * @return string value for given key
     * @throws Engine_Exception
     */

    protected function _get_parameter($key)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_PEERDNS);

        try {
            $value = $file->lookup_value("/^$key\s+/");
        } catch (File_Not_Found_Exception $e) {
             return '';
        } catch (File_No_Match_Exception $e) {
             return '';
        }

        return $value;
    }

    /**
     * Generic set parameter for /etc/resolv.conf.
     *
     * @param string $key         parameter that is being replaced
     * @param string $replacement full replacement (could be multiple lines)
     *
     * @access private
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    protected function _set_parameter($key, $replacement)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_PEERDNS);

        // Create file if it does not exist
        //---------------------------------

        if (! $file->exists())
            $file->create('root', 'root', '0644');

        $file->replace_lines("/^$key/", '');

        // Add domain (if it exists)
        //--------------------------

        if ($replacement) {
            if (is_array($replacement)) {
                foreach ($replacement as $line)
                $file->add_lines("$line\n");
            } else {
                $file->add_lines("$replacement\n");
            }
        }
    }
}

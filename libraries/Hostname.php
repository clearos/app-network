<?php

/**
 * Hostname class.
 *
 * @category   apps
 * @package    network
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2002-2014 ClearFoundation
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

clearos_load_language('network');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\network\Hosts as Hosts;
use \clearos\apps\network\Iface as Iface;
use \clearos\apps\network\Network as Network;
use \clearos\apps\network\Network_Utils as Network_Utils;
use \clearos\apps\network\Role as Role;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Shell');
clearos_load_library('network/Hosts');
clearos_load_library('network/Iface');
clearos_load_library('network/Network');
clearos_load_library('network/Network_Utils');
clearos_load_library('network/Role');

// Exceptions
//-----------

use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;
use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/File_No_Match_Exception');
clearos_load_library('base/File_Not_Found_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Hostname class.
 *
 * @category   apps
 * @package    network
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2002-2014 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Hostname extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // M E M B E R S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/sysconfig/network';
    const FILE_CONFIG_V7 = '/etc/hostname';
    const FILE_APP_CONFIG = '/etc/clearos/network.conf';
    const COMMAND_HOSTNAME = '/bin/hostname';
    const DEFAULT_HOSTNAME = 'system.lan';
    const DEFAULT_INTERNET_HOSTNAME = 'external.system.lan';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Hostname constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Fixes the common "cannot lookup my hostname" issue.
     *
     * Many software packages (Apache, Squid, ProFTP, ...) require a valid
     * hostname on startup.  This method will add an entry into the /etc/hosts
     * file to get around this "feature".
     *
     * @param boolean $force force an update even if hostname is valid in DNS
     *
     * @return void
     */

    public function auto_fix($force = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Hostname is ok - return right away
        //-----------------------------------

        if (!$force && $this->is_resolvable())
            return;

        // Get hostname from the /etc/hosts entry
        //---------------------------------------

        $real_hostname = $this->get();

        // Get the IP for the /etc/hosts entry
        //------------------------------------

        // - Find out what network mode is running
        // - Get the IP of the LAN interface on a gateway, eth0/ppp0 on other modes

        $role = new Role();
        $network = new Network();
        $mode = $network->get_mode();

        if (($mode === Network::MODE_TRUSTED_STANDALONE) || ($mode === Network::MODE_STANDALONE))
            $eth = $role->get_interface_definition(Role::ROLE_EXTERNAL);
        else
            $eth = $role->get_interface_definition(Role::ROLE_LAN);

        if (!$eth)
            return;

        $iface = new Iface($eth);
        $ip = $iface->get_live_ip();

        // Check for entry in /etc/hosts
        //------------------------------

        $hosts = new Hosts();

        $host_ip = $hosts->get_ip_by_hostname($real_hostname);
        if ($host_ip)
            return;

        // Grab hostnames for IP (if any)
        //-------------------------------

        if ($hosts->entry_exists($ip))
            $entry = $hosts->get_entry($ip);
        else
            $entry = array();

        // Add/Update /etc/hosts entry
        //----------------------------

        if (empty($entry)) {
            $hosts->add_entry($ip, $real_hostname, array());
        } else {
            // Allow default hostname to be deleted
            if ($entry['hostname'] !== self::DEFAULT_HOSTNAME) {
                $entry['aliases'][] = $real_hostname;
                $real_hostname = $entry['hostname'];
            }

            $hosts->edit_entry($ip, $real_hostname, $entry['aliases']);
        }
    }

    /**
     * Returns hostname from the gethostname system call.
     *
     * @return string hostname
     * @throws Exception
     */

    public function get_actual()
    {
        clearos_profile(__METHOD__, __LINE__);

        $shell = new Shell();

        $options['validate_output'] = TRUE;
        $shell->execute(self::COMMAND_HOSTNAME, '', FALSE, $options);

        return $shell->get_first_output_line();
    }

    /**
     * Returns hostname from configuration first, then system if not available.
     *
     * @return string hostname
     * @throws Exception
     */

    public function get()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (clearos_version() >= 7) {
            try {
                $file = new File(self::FILE_CONFIG_V7);

                $hostname = $file->get_contents();
            } catch (File_Not_Found_Exception $e) {
                // Not fatal
            }
        } else {
            try {
                $file = new File(self::FILE_CONFIG);

                $hostname = $file->lookup_value('/^HOSTNAME=/');
                $hostname = preg_replace('/"/', '', $hostname);
            } catch (File_No_Match_Exception $e) {
                // Not fatal
            }
        }

        if (empty($hostname))
            $hostname = $this->get_actual();

        return $hostname;
    }

    /**
     * Returns configured domain name.
     *
     * If hostname is two parts or less (eg example.com
     * or example), we just return the hostname.  If hostname has more than
     * two parts (eg www.example.com or www.eastcoast.example.com) it
     * strips the first part.
     *
     * @return string domain name
     * @throws Exception
     */

    public function get_domain()
    {
        clearos_profile(__METHOD__, __LINE__);

        $hostname = $this->get();

        if (substr_count($hostname, '.') < 2)
            return $hostname;

        $domain = preg_replace('/^([\w\-]*)\./', '', $hostname);

        return $domain;
    }

    /**
     * Returns Internet hostname.
     *
     * In order to support certain apps (e.g. Certificate Manager), an Internet 
     * hostname needs to be specified.
     *
     * @return string Internet hostname
     */

    public function get_internet_hostname()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_APP_CONFIG);

        try {
            $hostname = $file->lookup_value('/^INTERNET_HOSTNAME=/');
            $hostname = preg_replace('/"/', '', $hostname);
        } catch (File_No_Match_Exception $e) {
            // Not fatal
        }

        if (empty($hostname))
            $hostname = $this->get_actual();

        return $hostname;
    }

    /**
     * Returns true if configured hostname can be resolved.
     *
     * @return boolean true if configured hostname is resolvable
     */

    public function is_resolvable()
    {
        clearos_profile(__METHOD__, __LINE__);

        $hostname = $this->get_actual() . '.';

        $retval = gethostbyname($hostname);

        if ($retval == $hostname)
            return FALSE;

        return TRUE;
    }

    /**
     * Sets hostname.
     *
     * Hostname must have at least one period.
     *
     * @param string $hostname hostname
     *
     * @return void
     * @throws Exception, Validation_Exception
     */

    public function set($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_hostname($hostname));

        // Update tag if it exists
        //------------------------

        if (clearos_version() >= 7) {
            $file = new File(self::FILE_CONFIG_V7);

            if ($file->exists())
                $file->delete();

            $file->create('root', 'root', '0644');
            $file->add_lines("$hostname\n");
        } else {
            $file = new File(self::FILE_CONFIG);
            $match = $file->replace_lines('/^HOSTNAME=/', "HOSTNAME=\"$hostname\"\n");

            if (! $match)
                $file->add_lines("HOSTNAME=\"$hostname\"\n");
        }

        // Run hostname command...
        //------------------------

        $shell = new Shell();
        $shell->execute(self::COMMAND_HOSTNAME, $hostname, TRUE);
    }

    /**
     * Sets Internet hostname.
     *
     * Internet hostname must have at least one period.
     *
     * @param string $hostname hostname
     *
     * @return void
     * @throws Exception, Validation_Exception
     */

    public function set_internet_hostname($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_hostname($hostname));

        // Update tag if it exists
        //------------------------

        $file = new File(self::FILE_APP_CONFIG);

        $match = $file->replace_lines('/^INTERNET_HOSTNAME=/', "INTERNET_HOSTNAME=\"$hostname\"\n");

        // If tag does not exist, add it
        //------------------------------

        if (! $match)
            $file->add_lines("INTERNET_HOSTNAME=\"$hostname\"\n");
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates a hostname.
     *
     * @param string $hostname hostname
     *
     * @return string error message if hostname is invalid
     */

    public function validate_hostname($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_hostname($hostname))
            return lang('network_hostname_invalid');
    }

    /**
     * Validates an Internet hostname.
     *
     * @param string $hostname hostname
     *
     * @return string error message if Internet hostname is invalid
     */

    public function validate_internet_hostname($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_hostname($hostname))
            return lang('network_internet_hostname_invalid');
    }
}

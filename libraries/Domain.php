<?php

/**
 * Domain class.
 *
 * @category   apps
 * @package    network
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
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
use \clearos\apps\network\Hostname as Hostname;
use \clearos\apps\network\Network_Utils as Network_Utils;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('network/Hostname');
clearos_load_library('network/Network_Utils');

// Exceptions
//-----------

use \Exception as Exception;
use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/File_No_Match_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Domain class.
 *
 * @category   apps
 * @package    network
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Domain extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // M E M B E R S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/clearos/network.conf';
    const DEFAULT_DOMAIN = 'system.lan';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Domain constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Returns default domain.
     *
     * @return string default domain
     * @throws Exception
     */

    public function get_default()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);

        try {
            $domain = $file->lookup_value('/^DEFAULT_DOMAIN=/');
            $domain = preg_replace('/"/', '', $domain);
        } catch (File_No_Match_Exception $e) {
            $domain = self::DEFAULT_DOMAIN;
        }

        return $domain;
    }

    /**
     * Returns inferred domain name.
     *
     * If hostname is two parts or less (eg example.com
     * or example), we just return the hostname.  If hostname has more than
     * two parts (eg www.example.com or www.eastcoast.example.com) it
     * strips the first part.
     *
     * @return string domain name
     * @throws Exception
     */

    public function get_inferred()
    {
        clearos_profile(__METHOD__, __LINE__);

        $hostname_obj = new Hostname();
        $hostname = $hostname_obj->get();

        if (substr_count($hostname, '.') < 2)
            return $hostname;

        $domain = preg_replace('/^([\w\-]*)\./', '', $hostname);

        return $domain;
    }

    /**
     * Sets default domain.
     *
     * @param string $domain default domain
     *
     * @return  void
     * @throws  Exception, Validation_Exception
     */

    public function set_default($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_domain($domain));

        $file = new File(self::FILE_CONFIG);

        $match = $file->replace_lines('/^DEFAULT_DOMAIN=/', "DEFAULT_DOMAIN=\"$domain\"\n");

        if (! $match)
            $file->add_lines("DEFAULT_DOMAIN=\"$domain\"\n");

        // Set default for core apps that might already be installed 
        //----------------------------------------------------------

        if (clearos_library_installed('dhcp/Dnsmasq')) {
            clearos_load_library('dhcp/Dnsmasq');

            try {
                $dhcp = new \clearos\apps\dhcp\Dnsmasq();
                $dhcp->set_domain_name($domain);
            } catch (Exception $e) {
                // Keep going, not fatal
            }
        }

        if (clearos_library_installed('openvpn/OpenVPN')) {
            clearos_load_library('openvpn/OpenVPN');

            try {
                $openvpn = new \clearos\apps\openvpn\OpenVPN();
                $openvpn->set_domain($domain);
            } catch (Exception $e) {
                // Keep going, not fatal
            }
        }

        if (clearos_library_installed('google_apps/Provision')) {
            clearos_load_library('google_apps/Provision');

            try {
                $google_apps = new \clearos\apps\google_apps\Provision();
                $google_apps->set_domain($domain);
            } catch (Exception $e) {
                // Keep going, not fatal
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates a domain.
     *
     * @param string $domain domain
     *
     * @return string error message if domain is invalid
     */

    public function validate_domain($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_domain($domain))
            return lang('network_domain_invalid');
    }
}

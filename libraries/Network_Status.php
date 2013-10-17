<?php

/**
 * Network status class.
 *
 * @category   apps
 * @package    network
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
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
use \clearos\apps\network\Resolver as Resolver;
use \clearos\apps\network\Routes as Routes;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Shell');
clearos_load_library('network/Resolver');
clearos_load_library('network/Routes');

// Exceptions
//-----------

use \clearos\apps\network\Network_Status_Unknown_Exception as Network_Status_Unknown_Exception;

clearos_load_library('network/Network_Status_Unknown_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network status class.
 *
 * @category   apps
 * @package    network
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2006-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Network_Status extends Engine
{
    ///////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////

    const STATUS_ONLINE = 'online';
    const STATUS_ONLINE_NO_DNS = 'online_no_dns';
    const STATUS_OFFLINE = 'offline';
    const STATUS_UNKNOWN = 'unknown';

    // TODO: move to syswatch
    const FILE_STATE = '/var/lib/syswatch/state';
    const COMMAND_PING = '/bin/ping';
    const COMMAND_ARPING = '/sbin/arping';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $ifs_in_use = array();
    protected $ifs_working = array();
    protected $is_state_loaded = FALSE;
    protected $ping_hosts = array();
    protected $ping_ips = array();

    ///////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Network status constructor.
     */

    public function __construct() 
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->ping_hosts = array(
            'www.google.com.',
            'google-public-dns-a.google.com.',
        );

        $this->ping_ips = array(
            '8.8.8.8',
            '69.90.141.1',
        );
    }

    /**
     * Returns list of working external (WAN) interfaces.
     *
     * Syswatch monitors the connections to the Internet.  A connection
     * is considered online when it can ping the Internet.
     *
     * @return array list of working WAN interfaces
     * @throws Engine_Exception, Network_Status_Unknown_Exception
     */

    public function get_working_external_interfaces()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_state_loaded)
            $this->_load_status();

        return $this->ifs_working;
    }

    /**
     * Returns list of in use external (WAN) interfaces.
     *
     * Syswatch monitors the connections to the Internet.  A connection
     * is considered in use when it can ping the Internet and is actively
     * used to connect to the Internet.  A WAN interface used for only backup
     * purposes is only included in this list when non-backup WANs are all down.
     *
     * @return array list of in use WAN interfaces
     * @throws Engine_Exception, Network_Status_Unknown_Exception
     */

    public function get_in_use_external_interfaces()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_state_loaded)
            $this->_load_status();

        return $this->ifs_in_use;
    }

    /**
     * Returns status of connection to Internet.
     *
     * @return integer status of Internet connection
     * @throws Engine_Exception
     */

    public function get_connection_status()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $ifaces = $this->get_working_external_interfaces();

            if (empty($ifaces))
                return self::STATUS_OFFLINE;
            else
                return self::STATUS_ONLINE;
        } catch (Network_Status_Unknown_Exception $e) {
            return self::STATUS_UNKNOWN;
        }
    }

    /**
     * Returns live status of connection to Internet.
     *
     * @return integer status of Internet connection
     * @throws Engine_Exception
     */

    public function get_live_connection_status()
    {
        clearos_profile(__METHOD__, __LINE__);

        $shell = new Shell();
        $options['validate_exit_code'] = FALSE;

        foreach ($this->ping_hosts as $host) {
            $retval = $shell->execute(self::COMMAND_PING, '-c 1 -w 5 ' . $host, FALSE, $options);
            clearos_profile(__METHOD__, __LINE__, 'network status ping host retval ' . $retval);
            if ($retval === 0)
                return self::STATUS_ONLINE;
        }

        foreach ($this->ping_ips as $ip) {
            $retval = $shell->execute(self::COMMAND_PING, '-c 1 -w 5 ' . $ip, FALSE, $options);
            clearos_profile(__METHOD__, __LINE__, 'network status ping ip retval ' . $retval);
            if ($retval === 0)
                return self::STATUS_ONLINE_NO_DNS;
        }

        return self::STATUS_OFFLINE;
    }

    /**
     * Returns live status of DNS requests.
     *
     * @return integer status of DNS requests
     * @throws Engine_Exception
     */

    public function get_live_dns_status()
    {
        clearos_profile(__METHOD__, __LINE__);

        $resolver = new Resolver();

        $dns_okay = $resolver->test_lookup(Resolver::CONST_TEST_HOST, 5);
        clearos_profile(__METHOD__, __LINE__, 'network status dns test ' . $dns_okay);

        if ($dns_okay)
            return self::STATUS_ONLINE;
        else
            return self::STATUS_OFFLINE;
    }

    /**
     * Returns live status of connection to the gateway.
     *
     * @return integer status of gateway connection
     * @throws Engine_Exception
     */

    public function get_live_gateway_status()
    {
        clearos_profile(__METHOD__, __LINE__);

        $shell = new Shell();
        $options['validate_exit_code'] = FALSE;

        $routes = new Routes();
        $gateway = $routes->get_default();

        // Try ping first, if that fails try arping
        if (! empty($gateway)) {
            $retval = $shell->execute(self::COMMAND_PING, '-c 1 -w 5 ' . $gateway, FALSE, $options);
            clearos_profile(__METHOD__, __LINE__, 'network status ping gateway retval ' . $retval);
            if ($retval === 0)
                return self::STATUS_ONLINE;

            $retval = $shell->execute(self::COMMAND_ARPING, '-c 1 -w 5 ' . $gateway, TRUE, $options);
            clearos_profile(__METHOD__, __LINE__, 'network status arping gateway retval ' . $retval);
            if ($retval === 0)
                return self::STATUS_ONLINE;
        }

        return self::STATUS_OFFLINE;
    }

    ///////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Loads state file.
     *
     * @access private
     *
     * @return void
     * @throws Engine_Exception, Network_Status_Unknown_Exception
     */

    protected function _load_status()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_STATE);

        if (! $file->exists())
            throw new Network_Status_Unknown_Exception();

        $lines = $file->get_contents_as_array();

        foreach ($lines as $line) {
            $match = array();

            if (preg_match('/^SYSWATCH_WANIF=(.*)/', $line, $match)) {
                $ethraw = $match[1];
                $ethraw = preg_replace('/"/', '', $ethraw);

                if (! empty($ethraw)) {
                    $ethlist = explode(' ', $ethraw);
                    $this->ifs_in_use = explode(' ', $ethraw);
                    $this->is_state_loaded = TRUE;
                }
            }

            if (preg_match('/^SYSWATCH_WANOK=(.*)/', $line, $match)) {
                $ethraw = $match[1];
                $ethraw = preg_replace('/"/', '', $ethraw);

                if (! empty($ethraw)) {
                    $ethlist = explode(' ', $ethraw);
                    $this->ifs_working = explode(' ', $ethraw);
                    $this->is_state_loaded = TRUE;
                }
            }
        }
    }
}

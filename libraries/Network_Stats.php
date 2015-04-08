<?php

/**
 * Network stats class.
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

clearos_load_language('base');
clearos_load_language('network');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;

clearos_load_library('base/Engine');
clearos_load_library('base/File');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network stats class.
 *
 * @category   apps
 * @package    network
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Network_Stats extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_STATS = '/proc/net/dev';
    const FILE_STATE = '/var/clearos/network/state';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Network stats constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Returns interface stats and rates.
     *
     * @return array interface stats and rates
     * @throws Engine_Exception
     */

    public function get_interface_stats_and_rates()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Grab current stats
        //-------------------

        $stats = $this->get_interface_stats();

        // Grab previous stats (if it exists)
        //-----------------------------------

        $previous_stats = array();

        $file = new File(self::FILE_STATE);

        if ($file->exists()) {
            $previous_stats = unserialize($file->get_contents());
            $file->delete();
        }

        // Dump current stats
        //-------------------

        $now = time();

        $state['stats'] = $stats;
        $state['timestamp'] = $now;

        $file->create('root', 'root', '0644');
        $file->add_lines(serialize($state));
        
        // Calculate rates
        //----------------

        $rates = array();

        if (isset($previous_stats['timestamp']))
            $time_diff = time() - $previous_stats['timestamp'];
        else
            $time_diff = 0;

        foreach ($stats as $iface => $details) {
            if (isset($previous_stats['stats'][$iface])) {
                $rx_diff = $details['rx_bytes'] - $previous_stats['stats'][$iface]['rx_bytes'];
                $tx_diff = $details['tx_bytes'] - $previous_stats['stats'][$iface]['tx_bytes'];

                // Only calculate the difference if the last check was within the last hour-ish
                // On reboots or resets, rx/tx can be negative -- check these results.

                if (($time_diff > 0) && ($time_diff < 4000) && ($rx_diff > 0) && ($tx_diff > 0)) {
                    $stats[$iface]['rx_rate'] = round($rx_diff / $time_diff);
                    $stats[$iface]['tx_rate'] = round($tx_diff / $time_diff);
                } else {
                    $stats[$iface]['rx_rate'] = 0;
                    $stats[$iface]['tx_rate'] = 0;
                }
            } else {
                $stats[$iface]['rx_rate'] = 0;
                $stats[$iface]['tx_rate'] = 0;
            }
        }

        return $stats;
    }

    /**
     * Returns interface stats.
     *
     * @return array interface stats
     * @throws Engine_Exception
     */

    public function get_interface_stats()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_STATS);

        $lines = $file->get_contents_as_array();

        $stats = array();

        foreach ($lines as $line) {
            $matches = array();
            if (preg_match('/^\s*([a-zA-Z0-9\.]+):\s*(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $line, $matches)) {
                // Skip imq and loopback interfaces
                if (!preg_match('/^imq/', $matches[1]) && !preg_match('/^lo$/', $matches[1])) {
                    $stats[$matches[1]]['rx_bytes'] = $matches[2];
                    $stats[$matches[1]]['rx_packets'] = $matches[3];
                    $stats[$matches[1]]['rx_errors'] = $matches[4];
                    $stats[$matches[1]]['rx_drop'] = $matches[5];
                    $stats[$matches[1]]['tx_bytes'] = $matches[10];
                    $stats[$matches[1]]['tx_packets'] = $matches[11];
                    $stats[$matches[1]]['tx_errors'] = $matches[12];
                    $stats[$matches[1]]['tx_drop'] = $matches[13];
                }
            }
        }

        return $stats;
    }

    /**
     * Returns interface list.
     *
     * @return array interface list
     * @throws Engine_Exception
     */

    public function get_interfaces()
    {
        clearos_profile(__METHOD__, __LINE__);

        $details = $this->get_interface_stats();

        $ifaces = array_keys($details);

        sort($ifaces);

        return $ifaces;
    }
}

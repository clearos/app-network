<?php

/**
 * Speed_Test class.
 *
 * @category   apps
 * @package    network
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2015 ClearFoundation
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

use \clearos\apps\base\File as File;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\network\Iface as Iface;

clearos_load_library('base/File');
clearos_load_library('base/Shell');
clearos_load_library('network/Iface');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;

clearos_load_library('base/Engine_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Speed_Test class.
 *
 * @category   apps
 * @package    network
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2015 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Speed_Test extends Iface
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_STATUS = 'clearos_speed_test.result';
    const COMMAND_SCRIPT = '/usr/sbin/clearos_speed_test';
    const COMMAND_PYTHON = '/usr/bin/python';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Speed_Test constructor.
     */

    public function __construct($iface)
    {
        clearos_profile(__METHOD__, __LINE__);
        parent::__construct($iface);
    }

    /**
     * Start speed test.
     *
     * @return void
     * @throws Exception
     */

    public function run()
    {
        clearos_profile(__METHOD__, __LINE__);

        $options = array('background' => TRUE);
        $args = "-i " . $this->iface;
        $shell = new Shell();
        $shell->execute(self::COMMAND_SCRIPT, $args, FALSE, $options);
    }

    /**
     * Returns status of network speed test.
     *
     * @return array status info
     * @throws Exception
     */

    public function get_result()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(CLEAROS_TEMP_DIR . "/" . self::FILE_STATUS);
        if (!$file->exists()) {
            $result = array('code' => -1);
            return $result;
        }

        $lines = $file->get_contents_as_array();
        $result = array('code' => 0);
        foreach ($lines as $line) {
            if (preg_match("/^Ping:\s*(.*)\s+ms$/", $line, $match)) {
                $result['ping'] = $match[1];
            } else if (preg_match("/Download\:\s*(.*)\s+Mbit\/s$/", $line, $match)) {
                $result['downstream'] = $match[1];
            } else if (preg_match("/Upload\:\s*(.*)\s+Mbit\/s$/", $line, $match)) {
                $result['upstream'] = $match[1];
                $result['complete'] = TRUE;
            }
        }
        return $result;
    }

    /**
     * Saves network speed test result.
     *
     * @param string $nic network interface
     *
     * @return void
     * @throws Exception
     */

    public function save_result($nic)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(CLEAROS_TEMP_DIR . "/" . self::FILE_STATUS);
        if (!$file->exists())
            throw new Engine_Exception(clearos_exception_message($e), lang('base_no_results'));

        $result = $this->get_result();
        $this->set_max_downstream($result['downstream'] * 1000);
        $this->set_max_upstream($result['upstream'] * 1000);
    }

};

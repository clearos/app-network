<?php

/**
 * Speed Test controller.
 *
 * @category   apps
 * @package    network
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Speed Test controller.
 *
 * @category   apps
 * @package    network
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */
 
class Speed_Test extends ClearOS_Controller
{
    /**
     * Speed test overview.
     *
     * @return view
     */

    function index()
    {
        echo "Nothing to see...";
    }

    /**
     * Start network speed test.
     *
     * @return JSON network information
     */

    function start($nic)
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');

        // Load libraries
        //---------------

        $this->load->library('network/Speed_Test', $nic);

        try {
            $this->speed_test->run();
            echo json_encode(array('code' => 0));
        } catch (Exception $e) {
            echo json_encode(Array('code' => clearos_exception_code($e), 'errmsg' => clearos_exception_message($e)));
        }
    }

    /**
     * Save network speed test.
     *
     * @return JSON network information
     */

    function save($nic)
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');

        // Load libraries
        //---------------

        $this->load->library('network/Speed_Test', $nic);

        try {
            $this->speed_test->save_result();
            echo json_encode(array('code' => 0));
        } catch (Exception $e) {
            echo json_encode(Array('code' => clearos_exception_code($e), 'errmsg' => clearos_exception_message($e)));
        }
    }

    /**
     * Get network speed test results.
     *
     * @return JSON network information
     */

    function result()
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');

        // Load libraries
        //---------------

        $this->load->library('network/Speed_Test');

        try {
            $results = $this->speed_test->get_result();
            echo json_encode($results);
        } catch (Exception $e) {
            echo json_encode(Array('code' => clearos_exception_code($e), 'errmsg' => clearos_exception_message($e)));
        }
    }
}

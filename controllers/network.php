<?php

/**
 * Network controller.
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
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\network\Network_Status as Network_Status;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network controller.
 *
 * @category   apps
 * @package    network
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */
 
class Network extends ClearOS_Controller
{
    /**
     * Network overview.
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->lang->load('network');

        // Load views
        //-----------

        if (clearos_console()) {
            $options['type'] = MY_Page::TYPE_CONSOLE;
            $views = array('network/settings', 'network/dns', 'network/iface');
        } else {
            $options['type'] = NULL;
            $views = array('network/panic', 'network/settings', 'network/dns', 'network/iface');
        }

        $this->page->view_controllers($views, lang('network_network'), $options);
    }

    /**
     * Network information.
     *
     * @return JSON network information
     */

    function get_all_info()
    {
        // Load libraries
        //---------------

        $this->load->library('network/Iface_Manager');
        $this->load->library('network/Resolver');

        // Dump JSON information
        //----------------------

        $data['network'] = $this->iface_manager->get_interface_details();
        $data['dns_servers'] = $this->resolver->get_nameservers();

        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');
        echo json_encode($data);
    }

    /**
     * Returns DNS status information.
     *
     * @return JSON DNS status information
     */

    function get_dns_status_info()
    {
        // Load libraries
        //---------------

        $this->load->library('base/Install_Wizard');
        $this->load->library('network/Network_Status');

        // Dump JSON information
        //----------------------

        sleep(3);
        $data['dns_status'] = $this->network_status->get_live_dns_status();

        // This is a bit of a hack...It updates the a limited number of app packages during the
        // Wizard only and once DNS is determined to be OK that may need to be updated prior to software
        // updates
        if ($this->session->userdata('wizard') && $data['dns_status'] === Network_Status::STATUS_ONLINE)
            $this->install_wizard->run_update_script();

        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');
        echo json_encode($data);
    }

    /**
     * Network information.
     *
     * @return JSON network information
     */

    function get_info($iface)
    {
        // Load libraries
        //---------------

        $this->load->library('network/Iface', $iface);

        // Dump JSON information
        //----------------------

        if ($this->iface->validate_interface($iface))
            $network = array();
        else
            $network = $this->iface->get_info();

        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');
        echo json_encode($network);
    }

    /**
     * Internet connection status.
     *
     * @return JSON network information
     */

    function get_internet_connection_status()
    {
        // Load libraries
        //---------------

        $this->load->library('network/Network_Status');

        // Dump JSON information
        //----------------------

        $status = $this->network_status->get_connection_status();

        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');
        echo json_encode($status);
    }

    /**
     * Network information.
     *
     * @return JSON network information
     */

    function get_network_status_info()
    {
        // Load libraries
        //---------------

        $this->load->library('network/Network_Status');

        // Dump JSON information
        //----------------------

        $data['gateway_status'] = $this->network_status->get_live_gateway_status();
        $data['connection_status'] = $this->network_status->get_live_connection_status();

        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');
        echo json_encode($data);
    }

}

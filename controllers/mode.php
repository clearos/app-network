<?php

/**
 * Network settings controller.
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

use \clearos\apps\firewall\Firewall as Firewall;
use \clearos\apps\network\Network as Network;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network settings controller.
 *
 * @category   apps
 * @package    network
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Mode extends ClearOS_Controller
{
    /**
     * Mode settings overview.
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->lang->load('network');
        $this->load->library('network/Network');
        $this->load->library('network/Iface_Manager');
        $this->load->library('network/Network_Utils');

        // Handle form submit
        //-------------------

        if ($this->input->post('network_mode')) {
            try {
                $this->network->set_mode($this->input->post('network_mode'));

                // Open port 81 if going into standalone mode, or users
                // will get locked out!

                if (($this->input->post('network_mode') !== Network::MODE_TRUSTED_STANDALONE)
                    && clearos_library_installed('incoming_firewall/Incoming')) {

                    $this->load->library('incoming_firewall/Incoming');

                    // TODO: workaround - hard code 'TCP' for now (update firewall/Incoming class)
                    $firewall_status = $this->incoming->check_port('TCP', '81');

                    if ($firewall_status === Firewall::CONSTANT_NOT_CONFIGURED)
                        $this->incoming->add_allow_port('webconfig', 'TCP', '81');
                    else if ($firewall_status === Firewall::CONSTANT_DISABLED)
                        $this->incoming->set_allow_port_state(TRUE, 'TCP', '81');
                }

                $this->page->set_status_updated();
                redirect($this->session->userdata('wizard_redirect'));
            } catch (Engine_Exception $e) {
                $this->page->view_exception($e->get_message());
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['network_mode'] = $this->network->get_mode();
            $data['network_modes'] = $this->network->get_supported_modes();
            $data['iface_count'] = $this->iface_manager->get_interface_count();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // If network interface is a public IP in standalone, default to firewall enabled
        /* TODO: need to set a default (or blank) in the firewall to get this done
           otherwise, we'll keep stomping on the user's choice

        $data['is_public'] = FALSE;

        try {
            if ($data['iface_count'] === 1)  {
                $ifaces = $this->iface_manager->get_interfaces();

                $this->load->library('network/Iface', $ifaces[0]);

                $ip = $this->iface->get_live_ip();

                if (! $this->network_utils->is_private_ip($ip))
                    $data['is_public'] = TRUE;
            }
        } catch (Exception $e) {
            // Not fatal
        }
        $data['is_public'] = TRUE;
        */

        // Load views
        //-----------

        $this->page->view_form('network/wizard/mode', $data, lang('network_network_mode'));
    }
}

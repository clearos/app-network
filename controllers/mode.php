<?php

/**
 * Network settings controller.
 *
 * @category   Apps
 * @package    Network
 * @subpackage Controllers
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
 * @category   Apps
 * @package    Network
 * @subpackage Controllers
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

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('network_mode', 'network/Network', 'validate_mode', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
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
                redirect('/network/mode');
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
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('network/wizard/mode', $data, lang('network_network_mode'), $options);
    }
}

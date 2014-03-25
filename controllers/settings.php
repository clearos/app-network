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

class Settings extends ClearOS_Controller
{
    /**
     * General settings overview.
     *
     * @return view
     */

    function index()
    {
        $this->_view_edit('view');
    }

    /**
     * General settings edit view.
     *
     * @return view
     */

    function edit()
    {
        $this->_view_edit('edit');
    }

    /**
     * Common view/edit form
     *
     * @param string $form_type form type
     *
     * @return view
     */

    function _view_edit($form_type)
    {
        // Load libraries
        //---------------

        $this->load->library('network/Network');
        $this->load->library('network/Hostname');
        $this->load->library('network/Domain');

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('network_mode', 'network/Network', 'validate_mode', TRUE);

        if (!clearos_console()) {
            $this->form_validation->set_policy('hostname', 'network/Hostname', 'validate_hostname', TRUE);
            $this->form_validation->set_policy('internet_hostname', 'network/Hostname', 'validate_internet_hostname', TRUE);
            $this->form_validation->set_policy('domain', 'network/Domain', 'validate_domain', TRUE);
        }

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->network->set_mode($this->input->post('network_mode'));

                if (!clearos_console()) {
                    $this->hostname->set_internet_hostname($this->input->post('internet_hostname'));
                    $this->hostname->set($this->input->post('hostname'));
                    $this->domain->set_default($this->input->post('domain'));
                }

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
                redirect('/network/settings');
            } catch (Engine_Exception $e) {
                $this->page->view_exception($e->get_message());
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['form_type'] = $form_type;
            $data['network_mode'] = $this->network->get_mode();
            $data['network_modes'] = $this->network->get_supported_modes();
            $data['hostname'] = $this->hostname->get();
            $data['internet_hostname'] = $this->hostname->get_internet_hostname();
            $data['domain'] = $this->domain->get_default();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $options['type'] = (clearos_console()) ? MY_Page::TYPE_CONSOLE : NULL;

        $this->page->view_form('network/settings', $data, lang('base_settings'), $options);
    }
}

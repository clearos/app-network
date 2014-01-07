<?php

/**
 * Hostname controller.
 *
 * This controller is only used during the install wizard.
 *
 * @category   apps
 * @package    network
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
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

use \clearos\apps\network\Network as Network;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Hostname controller.
 *
 * @category   apps
 * @package    network
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Hostname extends ClearOS_Controller
{
    /**
     * General settings overview.
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->load->library('network/Domain');
        $this->load->library('network/Network');
        $this->load->library('network/Hostname');

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('hostname', 'network/Hostname', 'validate_hostname', TRUE);
        $this->form_validation->set_policy('internet_hostname', 'network/Hostname', 'validate_internet_hostname', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('hostname') && $form_ok)) {
            try {
                $this->hostname->set($this->input->post('hostname'));
                $this->hostname->set_internet_hostname($this->input->post('internet_hostname'));

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
            $data['form_type'] = 'edit';
            $data['hostname'] = $this->hostname->get();
            $data['internet_hostname'] = $this->hostname->get_internet_hostname();

            // If the default is detected, provide an intelligent default
            if (($data['hostname'] == 'system.domain.lan') || ($data['hostname'] == 'localhost.localdomain')) {
                $mode = $this->network->get_mode();
                $domain = $this->domain->get_default();

                if ($mode === Network::MODE_GATEWAY)
                    $prefix = 'gateway';
                else
                    $prefix = 'server';

                $data['hostname'] = $prefix . '.' . $domain;
                $data['internet_hostname'] = $data['hostname'];
            }
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('network/wizard/hostname', $data, lang('base_settings'), $options);
    }

    /**
     * Ajax helper for wizard.
     *
     * @return JSON
     */

    function wizard_update()
    {
        // Load libraries
        //---------------

        $this->load->library('network/Hostname');

        // Handle form submit
        //-------------------

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Fri, 01 Jan 2010 05:00:00 GMT');
        header('Content-type: application/json');

        try {
            $this->hostname->set($this->input->post('hostname'));

            echo json_encode(array('code' => 0));
        } catch (Exception $e) {
            echo json_encode(array('code' => clearos_exception_code($e), 'error_message' => clearos_exception_message($e)));
        }
    }
}

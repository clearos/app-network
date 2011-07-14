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
        $this->load->library('network/Resolver');
        $this->load->library('network/Network_Utils');

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('network_mode', 'network/Network', 'validate_mode', TRUE);
        $this->form_validation->set_policy('hostname', 'network/Hostname', 'validate_hostname', TRUE);

        for ($dns_id = 1; $dns_id < 3; $dns_id++) {
            $field = sprintf('dns%d', $dns_id);
            if (strlen($this->input->post($field)) == 0) continue;
            $this->form_validation->set_policy($field, 'network/Resolver', 'validate_ip', TRUE);
        }

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->network->set_mode($this->input->post('network_mode'));
                $this->hostname->set($this->input->post('hostname'));
                $dns_id = 1;
                $dns_list = array();
                for ($dns_id = 1; $dns_id < 3; $dns_id++) {
                    $field = sprintf('dns%d', $dns_id);
                    if (strlen($this->input->post($field)) == 0) continue;
                    $dns_list[] = $this->input->post($field);
                }
                if (count($dns_list))
                    $this->resolver->set_nameservers($dns_list);
                $this->page->set_status_updated();
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
            $data['network_modes'] = $this->network->get_modes();
            $data['hostname'] = $this->hostname->get();

            $dns_id = 1;
            $nameservers = $this->resolver->get_nameservers();
            foreach ($nameservers as $host)
                $data[sprintf('dns%d', $dns_id++)] = $host;
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $options['type'] = MY_Page::TYPE_SPLASH;

        $this->page->view_form('settings/view_edit', $data, lang('base_settings'), $options);
    }
}

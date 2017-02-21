<?php

/**
 * Upstrem proxy controller.
 *
 * @category   apps
 * @package    network
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2017 ClearFoundation
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
 * Upstrem proxy controller.
 *
 * @category   apps
 * @package    network
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2017 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Proxy extends ClearOS_Controller
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

        $this->load->library('network/Proxy');

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('proxy_server', 'network/Proxy', 'validate_server');
        $this->form_validation->set_policy('proxy_port', 'network/Proxy', 'validate_port');
        $this->form_validation->set_policy('proxy_username', 'network/Proxy', 'validate_username');
        $this->form_validation->set_policy('proxy_password', 'network/Proxy', 'validate_password');

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->proxy->set_server($this->input->post('proxy_server'));
                $this->proxy->set_port($this->input->post('proxy_port'));
                $this->proxy->set_username($this->input->post('proxy_username'));
                $this->proxy->set_password($this->input->post('proxy_password'));
                $this->proxy->write_profile();

                $this->page->set_status_updated();
                redirect('/network/proxy');
            } catch (Engine_Exception $e) {
                $this->page->view_exception($e->get_message());
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['form_type'] = $form_type;
            $data['proxy_server'] = $this->proxy->get_server();
            $data['proxy_port'] = $this->proxy->get_port();
            $data['proxy_username'] = $this->proxy->get_username();
            $data['proxy_password'] = $this->proxy->get_password();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $options['type'] = (clearos_console()) ? MY_Page::TYPE_CONSOLE : NULL;

        $this->page->view_form('network/proxy', $data, lang('network_upstream_proxy'), $options);
    }
}

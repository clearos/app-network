<?php

/**
 * Network DNS server controller.
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
 * Network DNS server controller.
 *
 * @category   apps
 * @package    network
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class DNS extends ClearOS_Controller
{
    /**
     * General DNS overview.
     *
     * @return view
     */

    function index()
    {
        $this->_view_edit('view');
    }

    /**
     * General DNS edit view.
     *
     * @return view
     */

    function edit($verify = NULL)
    {
        if ($verify)
            $this->verify();
        else
            $this->_view_edit('edit');
    }

    /**
     * General DNS verify view.
     *
     * @return view
     */

    function verify()
    {
        // Load libraries
        //---------------

        $this->load->library('network/Resolver');

        // Load views
        //-----------

        $this->page->view_form('network/dns_verify', $data, lang('network_dns'));
    }

    /**
     * General DNS read-only view.
     *
     * @return view
     */

    function view()
    {
        $this->_view_edit('view');
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

        $this->load->library('network/Resolver');

        // Handle wizard mode
        //-------------------

        $is_automatic = $this->resolver->is_automatically_configured();

        // if wizard mode and automatic DNS is disabled, go straight to edit mode
        if ($form_type === 'view') {
            $is_wizard = ($this->session->userdata('wizard')) ? TRUE : FALSE;

            if ($is_wizard && !$is_automatic)
                redirect('/network/dns/edit');
        }

        // Set validation rules
        //---------------------
         
        $dns = $this->input->post('dns');

        for ($dns_id = 1; $dns_id <= count($dns); $dns_id++) {
            $this->form_validation->set_policy('dns[' . $dns_id . ']', 'network/Resolver', 'validate_ip');
        }

        $form_ok = $this->form_validation->run();

        // Extra validation
        //-----------------

        if ($form_ok && !$is_automatic) {
            $dns_empty = TRUE;

            foreach ($dns as $server) {
                if (! empty($server))
                    $dns_empty = FALSE;
            }

            if ($dns_empty) {
                $this->form_validation->set_error('dns[1]', lang('required'));
                $form_ok = FALSE;
            }
        }

        // Handle form submit
        //-------------------

        $data['dns_okay'] = TRUE;

        if (($this->input->post('dns') && $form_ok)) {
            try {
                $this->resolver->set_nameservers($this->input->post('dns'));

                $this->page->set_status_updated();

                if ($this->session->userdata('wizard_redirect'))
                    redirect('/network/dns/edit/verify');
                else
                    redirect('/network/dns');
            } catch (Engine_Exception $e) {
                $this->page->view_exception($e->get_message());
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['form_type'] = $form_type;
            $data['is_wizard'] = ($this->session->userdata('wizard')) ? TRUE : FALSE;
            $data['is_automatic'] = $is_automatic;
            $data['dns'] = $this->resolver->get_nameservers();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $options['type'] = (clearos_console()) ? MY_Page::TYPE_CONSOLE : NULL;

        $this->page->view_form('network/dns', $data, lang('network_dns'), $options);
    }
}

<?php

/**
 * Network interface controller.
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

use \clearos\apps\network\Iface as IfaceAPI;
use \clearos\apps\network\Role as Role;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network interface controller.
 *
 * @category   apps
 * @package    network
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Iface extends ClearOS_Controller
{
    /**
     * Network interface summary.
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->load->library('network/Iface_Manager');
        $this->load->library('network/Network_Status');

        // Load view data
        //---------------

        try {
            $iface_options['filter_virtual'] = FALSE;
            $iface_options['filter_vlan'] = FALSE;

            $data['form_type'] = ($this->session->userdata('wizard')) ? 'wizard' : 'view';
            $data['network_status'] = $this->network_status->get_connection_status();
            $data['network_interfaces'] = $this->iface_manager->get_interface_details($iface_options);
            $data['external_interfaces'] = $this->iface_manager->get_external_interfaces();
            $data['wireless_installed'] = clearos_library_installed('wireless/Hostapd');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $page_options['type'] = (clearos_console()) ? MY_Page::TYPE_CONSOLE : NULL;

        $this->page->view_form('network/ifaces', $data, lang('network_interfaces'), $page_options);
    }

    /**
     * Add interface view.
     *
     * @param string $interface interface
     *
     * @return view
     */

    function add($interface = NULL)
    {
        $this->_item('add', $interface);
    }

    /**
     * Add virutal interface view.
     *
     * @return view
     */

    function add_virtual()
    {
        $this->_virtual_item('add');
    }

    /**
     * Add VLAN interface view.
     *
     * @return view
     */

    function add_vlan()
    {
        $interface = $this->input->post('iface');

        $this->_vlan_item('add', $interface);
    }

    /**
     * Delete interface view.
     *
     * @param string $interface interface
     *
     * @return view
     */

    function delete($interface = NULL)
    {
        $confirm_uri = '/app/network/iface/destroy/' . $interface;
        $cancel_uri = '/app/network/iface';
        $items = array($interface);

        $page_options['type'] = (clearos_console()) ? MY_Page::TYPE_CONSOLE : NULL;

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items, $page_options);
    }

    /**
     * Edit interface view.
     *
     * @param string $interface interface
     *
     * @return view
     */

    function edit($interface = NULL)
    {
        $this->_item('edit', $interface);
    }

    /**
     * Edit virtual interface view.
     *
     * @param string $interface interface
     *
     * @return view
     */

    function edit_virtual($interface = NULL)
    {
        $this->_virtual_item('edit', $interface);
    }

    /**
     * Edit VLAN interface view.
     *
     * @param string $interface interface
     *
     * @return view
     */

    function edit_vlan($interface = NULL)
    {
        $this->_vlan_item('edit', $interface);
    }

    /**
     * Destroys interface.
     *
     * @param string $interface interface
     *
     * @return view
     */

    function destroy($interface = NULL)
    {
        // Load libraries
        //---------------

        $this->load->library('network/Iface', $interface);
        $this->load->library('network/Role');
        $this->load->library('network/Routes');

        // Handle delete
        //--------------

        try {
            $this->iface->delete_config();
            $this->role->remove_interface_role($interface);

            $current_route = $this->routes->get_gateway_device();

            if ($role === Role::ROLE_EXTERNAL) {
                $this->routes->set_gateway_device($interface);
            } else if ($interface == $current_route) {
                $this->routes->delete_gateway_device();
            }

            $this->page->set_status_deleted();
            redirect('/network/iface');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * View interface view.
     *
     * @param string $interface interface
     *
     * @return view
     */

    function view($interface = NULL)
    {
        $this->_item('view', $interface);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Common add/edit/view form handler.
     *
     * @param string $form_type form type
     * @param string $interface interface
     *
     * @return view
     */

    function _item($form_type, $interface)
    {
        // Load libraries
        //---------------

        $this->lang->load('network');
        $this->load->library('network/Iface', $interface);
        $this->load->library('network/Iface_Manager');
        $this->load->library('network/Role');

        if (clearos_app_installed('dhcp'))
            $this->load->library('dhcp/Dnsmasq');

        if (clearos_library_installed('wireless/Hostapd'))
            $this->load->library('wireless/Hostapd');

        // Set validation rules
        //---------------------

        $bootproto = $this->input->post('bootproto');
        $role = $this->input->post('role');

        $this->form_validation->set_policy('role', 'network/Role', 'validate_role', TRUE);
        $this->form_validation->set_policy('bootproto', 'network/Iface', 'validate_boot_protocol', TRUE);

        if ($bootproto == IfaceAPI::BOOTPROTO_STATIC) {
            $this->form_validation->set_policy('ipaddr', 'network/Iface', 'validate_ip', TRUE);
            $this->form_validation->set_policy('netmask', 'network/Iface', 'validate_netmask', TRUE);
            if ($role == Role::ROLE_EXTERNAL)
                $this->form_validation->set_policy('gateway', 'network/Iface', 'validate_gateway', TRUE);
            else if (clearos_app_installed('dhcp'))
                $this->form_validation->set_policy('enable_dhcp', 'dhcp/Dnsmasq', 'validate_dhcp_state');
        } else if ($bootproto == IfaceAPI::BOOTPROTO_DHCP)  {
            $this->form_validation->set_policy('hostname', 'network/Iface', 'validate_hostname');
            $this->form_validation->set_policy('dhcp_dns', 'network/Iface', 'validate_peerdns');
        } else if ($bootproto == IfaceAPI::BOOTPROTO_PPPOE)  {
            $this->form_validation->set_policy('username', 'network/Iface', 'validate_username', TRUE);
            $this->form_validation->set_policy('password', 'network/Iface', 'validate_password', TRUE);
            $this->form_validation->set_policy('mtu', 'network/Iface', 'validate_mtu');
            $this->form_validation->set_policy('pppoe_dns', 'network/Iface', 'validate_peerdns');
        }

        if ($this->input->post('mode')) {
            $this->form_validation->set_policy('mode', 'network/Iface', 'validate_wireless_mode', TRUE);
            $this->form_validation->set_policy('ssid', 'network/Iface', 'validate_wireless_ssid', TRUE);
            $this->form_validation->set_policy('channel', 'network/Iface', 'validate_wireless_channel', TRUE);

            if ($this->input->post('mode') !== IfaceAPI::WIRELESS_WPA_EAP)
                $this->form_validation->set_policy('passphrase', 'network/Iface', 'validate_wireless_passphrase', TRUE);
        }

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok === TRUE)) {

            try {
                // Wireless options
                //-----------------

                $wireless = array();
                $wireless['mode'] = ($this->input->post('mode')) ? $this->input->post('mode') : '';
                $wireless['ssid'] = ($this->input->post('ssid')) ? $this->input->post('ssid') : '';
                $wireless['channel'] = ($this->input->post('channel')) ? $this->input->post('channel') : '';
                $wireless['passphrase'] = ($this->input->post('passphrase')) ? $this->input->post('passphrase') : '';

                // Set interface configuration
                //----------------------------

                if ($bootproto == IfaceAPI::BOOTPROTO_STATIC) {
                    $this->iface->save_static_config(
                        $this->input->post('ipaddr'),
                        $this->input->post('netmask'),
                        $this->input->post('gateway'),
                        $wireless
                    );

                    $this->_update_routing($interface, $role);
                    $this->iface->enable(FALSE);

                    if (clearos_app_installed('dhcp') && ($this->input->post('enable_dhcp')))
                        $this->dnsmasq->add_subnet_default($interface);
                } else if ($bootproto == IfaceAPI::BOOTPROTO_DHCP) {
                    $this->iface->save_dhcp_config(
                        $this->input->post('hostname'),
                        (bool) $this->input->post('dhcp_dns'),
                        $wireless
                    );

                    $this->_update_routing($interface, $role);
                    $this->iface->enable(TRUE);
                } else if ($bootproto == IfaceAPI::BOOTPROTO_PPPOE) {
                    $interface = $this->iface->save_pppoe_config(
                        $interface,
                        $this->input->post('username'),
                        $this->input->post('password'),
                        $this->input->post('mtu'),
                        (bool) $this->input->post('pppoe_dns'),
                        $wireless
                    );

                    $this->_update_routing($interface, $role);
                    $this->iface->enable(TRUE);
                }

                // Return to summary page with status message
                //-------------------------------------------

                $this->page->set_status_updated();
                redirect('/network/iface');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load the view data 
        //------------------- 

        try {
            $data['roles'] = $this->iface->get_supported_roles();
            $data['bootprotos'] = $this->iface->get_supported_bootprotos();
            $data['iface_info'] = $this->iface->get_info();

            $iface_count = $this->iface_manager->get_interface_count();

            // Default to enable on unconfigured interfaces
            if (clearos_app_installed('dhcp') && ($data['iface_info']['configured'] === FALSE)) {
                $data['show_dhcp'] = TRUE;
                $data['enable_dhcp'] = TRUE;
            } else {
                $data['show_dhcp'] = FALSE;
            }

            if (clearos_library_installed('wireless/Hostapd')) {
                $data['modes'] = $this->iface->get_supported_wireless_modes();
                $data['channels'] = $this->iface->get_supported_wireless_channels();
            }
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Set defaults
        if (!$data['iface_info']['configured'])
            $data['iface_info']['role'] = ($iface_count === 1) ? Role::ROLE_EXTERNAL : Role::ROLE_LAN;

        if (empty($data['iface_info']['ifcfg']['bootproto']))
            $data['iface_info']['ifcfg']['bootproto'] = \clearos\apps\network\Iface::BOOTPROTO_STATIC;

        if (empty($data['iface_info']['ifcfg']['netmask']))
            $data['iface_info']['ifcfg']['netmask'] = '255.255.255.0';

        $data['form_type'] = $form_type;
        $data['interface'] = $interface;

        // Load the views
        //---------------

        $page_options['type'] = (clearos_console()) ? MY_Page::TYPE_CONSOLE : NULL;

        $this->page->view_form('network/iface', $data, lang('network_interface'), $page_options);
    }

    /**
     * Updates routing information
     *
     */

    function _update_routing($interface, $role)
    {
        // Load libraries
        //---------------

        $this->load->library('network/Routes');

        try {
            // Set routing
            //------------

            $current_route = $this->routes->get_gateway_device();

            if ($role === Role::ROLE_EXTERNAL) {
                $this->routes->set_gateway_device($interface);
            } else if ($interface == $current_route) {
                $this->routes->delete_gateway_device();
                // FIXME: should try to restore route if possible
            }

            // Set interface role
            //-------------------

            $this->role->set_interface_role($interface, $role);
        } catch (\Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Common add/edit/view form handler for virtual interfaces.
     *
     * @param string $form_type form type
     * @param string $interface interface
     *
     * @return view
     */

    function _virtual_item($form_type, $interface)
    {
        if ($form_type === 'add')
            $interface = $this->input->post('iface');

        // Load libraries
        //---------------

        $this->lang->load('network');
        $this->load->library('network/Iface', $interface);
        $this->load->library('network/Iface_Manager');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('ipaddr', 'network/Iface', 'validate_ip', TRUE);
        $this->form_validation->set_policy('netmask', 'network/Iface', 'validate_netmask', TRUE);

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok === TRUE)) {

            try {
                $this->iface->save_virtual_config($this->input->post('ipaddr'), $this->input->post('netmask'));
                $this->iface->enable();

                $this->page->set_status_updated();
                redirect('/network/iface');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load the view data 
        //------------------- 

        try {
            $iface_options['filter_vlan'] = TRUE;

            $data['form_type'] = $form_type;
            $data['iface'] = $interface;
            $data['ifaces'] = $this->iface_manager->get_interfaces($iface_options);

            if ($form_type !== 'add')
                $data['iface_info'] = $this->iface->get_info();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load the views
        //---------------

        $page_options['type'] = (clearos_console()) ? MY_Page::TYPE_CONSOLE : NULL;

        $this->page->view_form('network/virtual', $data, lang('network_interface'), $page_options);
    }

    /**
     * Common add/edit/view form handler for VLAN interfaces.
     *
     * @param string $form_type form type
     * @param string $interface interface
     *
     * @return view
     */

    function _vlan_item($form_type, $interface)
    {
        // Load libraries
        //---------------

        $this->lang->load('network');
        $this->load->library('network/Iface', $interface);
        $this->load->library('network/Iface_Manager');
        $this->load->library('network/Role');

        if (clearos_app_installed('dhcp'))
            $this->load->library('dhcp/Dnsmasq');

        // Set validation rules
        //---------------------

        $bootproto = $this->input->post('bootproto');
        $role = $this->input->post('role');

        $this->form_validation->set_policy('role', 'network/Role', 'validate_role', TRUE);
        $this->form_validation->set_policy('bootproto', 'network/Iface', 'validate_boot_protocol', TRUE);
        $this->form_validation->set_policy('vlan_id', 'network/Iface', 'validate_vlan_id', TRUE);

        if ($bootproto == IfaceAPI::BOOTPROTO_STATIC) {
            $this->form_validation->set_policy('ipaddr', 'network/Iface', 'validate_ip', TRUE);
            $this->form_validation->set_policy('netmask', 'network/Iface', 'validate_netmask', TRUE);
            if ($role == Role::ROLE_EXTERNAL)
                $this->form_validation->set_policy('gateway', 'network/Iface', 'validate_gateway', TRUE);
            else if (clearos_app_installed('dhcp'))
                $this->form_validation->set_policy('enable_dhcp', 'dhcp/Dnsmasq', 'validate_dhcp_state');
        } else if ($bootproto == IfaceAPI::BOOTPROTO_DHCP)  {
            $this->form_validation->set_policy('hostname', 'network/Iface', 'validate_hostname');
            $this->form_validation->set_policy('dhcp_dns', 'network/Iface', 'validate_peerdns');
        }

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok === TRUE)) {
            if ($form_type === 'add')
                $interface = $this->input->post('iface') . '.' . $this->input->post('vlan_id');

            try {
                if ($bootproto == IfaceAPI::BOOTPROTO_STATIC) {
                    $this->iface->save_vlan_static_config(
                        $this->input->post('vlan_id'),
                        $this->input->post('ipaddr'),
                        $this->input->post('netmask'),
                        $this->input->post('gateway')
                    );

                    $this->_update_routing($interface, $role);
                    $this->iface->enable(FALSE);

                    if (clearos_app_installed('dhcp') && ($this->input->post('enable_dhcp')))
                        $this->dnsmasq->add_subnet_default($interface);
                } else if ($bootproto == IfaceAPI::BOOTPROTO_DHCP) {
                    $this->iface->save_vlan_dhcp_config(
                        $this->input->post('vlan_id'),
                        $this->input->post('hostname'),
                        (bool) $this->input->post('dhcp_dns')
                    );
                
                    $this->_update_routing($interface, $role);
                    $this->iface->enable(TRUE);
                }

                // Return to summary page with status message
                //-------------------------------------------

                $this->page->set_status_updated();
                redirect('/network/iface');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load the view data 
        //------------------- 

        try {
            $options['filter_pppoe'] = TRUE;
            $iface_options['filter_vlan'] = TRUE;

            $data['form_type'] = $form_type;
            $data['iface'] = $interface;
            $data['ifaces'] = $this->iface_manager->get_interfaces($iface_options);
            $data['roles'] = $this->iface->get_supported_roles();
            $data['bootprotos'] = $this->iface->get_supported_bootprotos($options);

            if ($form_type !== 'add')
                $data['iface_info'] = $this->iface->get_info();

            // Default to enable on unconfigured interfaces
            if (clearos_app_installed('dhcp') && ($form_type === 'add')) {
                $data['show_dhcp'] = TRUE;
                $data['enable_dhcp'] = TRUE;
            } else {
                $data['show_dhcp'] = FALSE;
            }
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Set defaults
        if (!$data['iface_info']['configured'])
            $data['iface_info']['role'] = Role::ROLE_LAN;

        if (empty($data['iface_info']['ifcfg']['bootproto']))
            $data['iface_info']['ifcfg']['bootproto'] = \clearos\apps\network\Iface::BOOTPROTO_STATIC;

        if (empty($data['iface_info']['ifcfg']['netmask']))
            $data['iface_info']['ifcfg']['netmask'] = '255.255.255.0';

        // Load the views
        //---------------

        $page_options['type'] = (clearos_console()) ? MY_Page::TYPE_CONSOLE : NULL;

        $this->page->view_form('network/vlan', $data, lang('network_interface'), $page_options);
    }
}

<?php

/**
 * Network check controller.
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

use \clearos\apps\firewall\Firewall as Firewall;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network check controller.
 *
 * @category   apps
 * @package    network
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Network_Check extends ClearOS_Controller
{
    protected $app_name = NULL;
    protected $rules = NULL;
    protected $type = '';

    /**
     * Network check constructor.
     *
     * @param string $app_name app basename
     * @param array  $rules    firewall rules
     * @param string $type     warning or info
     *
     * @return view
     */

    function __construct($app_name, $rules, $type = 'warning')
    {
        $this->app_name = $app_name;
        $this->rules = $rules;
        $this->type = $type;
    }

    /**
     * Network check view.
     *
     * @return view
     */

    function index()
    {
        // Bail if firewall is not installed
        //----------------------------------

        if (!clearos_app_installed('incoming_firewall'))
            return;

        // Load dependencies
        //------------------

        $this->lang->load('network');
        $this->load->library('incoming_firewall/Port');
        $this->load->library('network/Network_Check');

        // Bail if admin has dismissed widget
        //-----------------------------------

        $is_dismissed = $this->network_check->get_dismiss_state($this->app_name);

        if ($is_dismissed)
            return;

        // Load view data
        //---------------

        try {
            $result = [];
            $show_widget = FALSE;

            foreach ($this->rules as $rule) {
                $is_firewalled = $this->port->is_firewalled($rule['protocol'], $rule['port']);
                $rule['firewalled'] = $is_firewalled;
                $result[] = $rule;

                if ($is_firewalled)
                    $show_widget = TRUE;
            }
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        if (! $show_widget)
            return;

        $data['app_name'] = $this->app_name;
        $data['type'] = $this->type;
        $data['rules'] = $result;

        // Load views
        //-----------

        $this->page->view_form('network/check', $data, lang('network_network_check'));
    }

    /**
     * Adds rules to incoming firewall.
     *
     * @return view
     */

    function add()
    {
        // Load libraries
        //---------------

        if (!(clearos_app_installed('incoming_firewall')))
            return;

        $this->load->library('incoming_firewall/Incoming');

        // Add rules
        //----------

        foreach ($this->rules as $rule) {
            // TODO: remove PPTP/IPsec hacks
            if ($rule['protocol'] == 'PPTP') {
                $this->incoming->add_allow_standard_service('PPTP');
            } else if ($rule['protocol'] == 'IPsec') {
                $this->incoming->add_allow_standard_service('IPsec');
            } else {
                $check_port = $this->incoming->check_port($rule['protocol'], $rule['port']);
                if ($check_port == Firewall::CONSTANT_DISABLED) {
                    $this->incoming->set_allow_port_state(TRUE, $rule['protocol'], $rule['port']);
                } else if ($check_port == Firewall::CONSTANT_NOT_CONFIGURED) {
                    $name = preg_replace('/ - /', '-', $rule['name']);
                    $name = preg_replace('/ /', '_', $name);
                    $this->incoming->add_allow_port($name, $rule['protocol'], $rule['port']);
                }
            }
        }

        // Load view data
        //---------------

        $this->page->set_message(lang('network_firewall_updated'), 'info');

        $this->load->library('user_agent');

        redirect($this->agent->referrer());
    }

    /**
     * Hides firewall warning.
     *
     * @param string $protocol protocol
     * @param string $port     port
     * @return view
     */

    function dismiss()
    {
        // Load libraries
        //---------------

        if (!(clearos_app_installed('incoming_firewall')))
            return;

        $this->load->library('network/Network_Check');

        // Set dismiss state
        //------------------

        $this->network_check->set_dismiss_state($this->app_name, TRUE);

        // Load view data
        //---------------

        $this->load->library('user_agent');

        redirect($this->agent->referrer());
    }
}

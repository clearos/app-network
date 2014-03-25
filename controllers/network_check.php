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
    protected $protocol = NULL;
    protected $port_number = NULL;

    /**
     * Network check constructor.
     *
     * @param string $app_name app basename
     *
     * @return view
     */

    function __construct($app_name, $protocol = NULL, $port = NULL)
    {
        $this->app_name = $app_name;
        $this->protocol = $protocol;
        $this->port_number = $port;
    }

    /**
     * Network check view.
     *
     * @return view
     */

    function index($protocol = NULL, $port = NULL)
    {
        // SSH and maybe some others have floating protocol an port numbers
        // Added a way to override these on the fly instead of using the
        // constructor.

        // Bail if firewall is not installed
        //----------------------------------

        if (!clearos_app_installed('incoming_firewall'))
            return;

        // Load dependencies
        //------------------

        $this->lang->load('network');
        $this->load->library('incoming_firewall/Port');

        if (! empty($port))
            $this->port_number = $port;

        if (! empty($protocol))
            $this->protocol = $protocol;

        // Load view data
        //---------------

        try {
            $is_firewalled = $this->port->is_firewalled($this->protocol, $this->port_number);
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        $data['app_name'] = $this->app_name;
        $data['protocol'] = $this->protocol;
        $data['port'] = $this->port_number;

        // Load views
        //-----------

        if (! $is_firewalled)
            return;

        $this->page->view_form('network/check', $data, lang('network_network_check'));
    }

    /**
     * Network add to incoming firewall.
     *
     * @param string $protocol protocol
     * @param string $port     port
     * @return view
     */

    function add($protocol, $port = NULL)
    {
        // Load libraries
        //---------------

        if (!(clearos_app_installed('incoming_firewall')))
            return;

        $this->load->library('incoming_firewall/Port');
        $this->load->library('incoming_firewall/Incoming');
        $this->lang->load('incoming_firewall');

        // Handle form submit
        //-------------------

        // TODO: remove PPTP/IPsec hacks
        if ($protocol == 'PPTP') {
            $this->incoming->add_allow_standard_service('PPTP');
        } else if ($protocol == 'IPsec') {
            $this->incoming->add_allow_standard_service('IPsec');
        } else {
            if ($this->incoming->check_port($protocol, $port) == Firewall::CONSTANT_DISABLED)
                $this->incoming->set_allow_port_state(TRUE, $protocol, $port);
            else
                $this->incoming->add_allow_port($this->app_name, $protocol, $port);
        }

        // Load view data
        //---------------

        $this->page->set_status_updated();

        $this->load->library('user_agent');

        if ($this->agent->is_referral())
            redirect($this->agent->referrer());
        else
            redirect('/incoming_firewall/allow');
    }
}

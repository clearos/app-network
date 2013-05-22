<?php

/**
 * Network status report.
 *
 * @category   apps
 * @package    network
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
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
 * Network status report.
 *
 * @category   apps
 * @package    network
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Report extends ClearOS_Controller
{
    /**
     * Network status sidebar.
     *
     * @return view
     */

    function sidebar()
    {
        // Only show sidebar on some pages
        //--------------------------------

        $uri = $this->uri->uri_string();

        if (! (($uri === 'network/iface') || ($uri === 'network') || preg_match('/^network\/dns/', $uri)))
            return;

        // Load dependencies
        //------------------

        $this->lang->load('network');

        // Load views
        //-----------

        $this->page->view_form('report', array(), lang('network_network_status'));
    }
}

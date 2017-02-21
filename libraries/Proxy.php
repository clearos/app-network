<?php

/**
 * Upstream proxy class.
 *
 * @category   apps
 * @package    network 
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012-2017 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\network;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('network');
clearos_load_language('network');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\network\Network_Utils as Network_Utils;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('network/Network_Utils');

// Exceptions
//-----------

use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;
use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/File_No_Match_Exception');
clearos_load_library('base/File_Not_Found_Exception');
clearos_load_library('base/Validation_Exception');


///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Upstream proxy class.
 *
 * @category   apps
 * @package    network 
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012-2017 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network/
 */

class Proxy extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/clearos/upstream_proxy.conf';
    const FILE_PROFILE = '/etc/profile.d/proxy.sh';

    ///////////////////////////////////////////////////////////////////////////////
    // M E M B E R S
    ///////////////////////////////////////////////////////////////////////////////

    protected $config = NULL;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Upstream proxy constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Returns proxy password.
     *
     * @return string proxy password
     * @throws Exception
     */

    public function get_password()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_load_config();

        return $this->config['password'];
    }

    /**
     * Returns proxy port.
     *
     * @return string proxy port
     * @throws Exception
     */

    public function get_port()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_load_config();

        return $this->config['port'];
    }

    /**
     * Returns proxy server.
     *
     * @return string proxy server
     * @throws Exception
     */

    public function get_server()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_load_config();

        return $this->config['server'];
    }

    /**
     * Returns proxy username.
     *
     * @return string proxy username
     * @throws Exception
     */

    public function get_username()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_load_config();

        return $this->config['username'];
    }

    /**
     * Sets proxy password.
     *
     * @param string $password password
     *
     * @return void
     * @throws Exception, Validation_Exception
     */

    public function set_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_password($password));

        $this->_set_parameter('password', $password);
    }

    /**
     * Sets proxy port.
     *
     * @param string $port port number
     *
     * @return void
     * @throws Exception, Validation_Exception
     */

    public function set_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_port($port));

        $this->_set_parameter('port', $port);
    }

    /**
     * Sets proxy server.
     *
     * @param string $server hostname or IP address
     *
     * @return void
     * @throws Exception, Validation_Exception
     */

    public function set_server($server)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_server($server));

        $this->_set_parameter('server', $server);
    }

    /**
     * Sets proxy username.
     *
     * @param string $username username
     *
     * @return void
     * @throws Exception, Validation_Exception
     */

    public function set_username($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_username($username));

        $this->_set_parameter('username', $username);
    }

    /**
     * Writes profile proxy configuration.
     *
     * @return void
     * @throws Exception, Validation_Exception
     */

    public function write_profile()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_load_config();

        $file = new File(self::FILE_PROFILE);

        // Delete proxy settings if server not defined
        //--------------------------------------------

        if (empty($this->config['server'])) {
            if ($file->exists())
                $file->delete();

            return;
        }

        // Write out proxy settings
        //-------------------------

        $server = $this->config['server'] . ':' . $this->config['port'] ;

        if (!empty($this->config['username']))
            $server = $this->config['username'] . ':' . $this->config['password'] . '@' . $server;

        $contents = "export ftp_proxy=\"http://$server\"\n";
        $contents .= "export http_proxy=\"http://$server\"\n";
        $contents .= "export https_proxy=\"http://$server\"\n";

        try {
            if ($file->exists())
                $file->delete();
        } catch (Exception $e) {
            // Keep going
        }

        $file->create('root', 'root', '0640');
        $file->add_lines($contents);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates proxy password.
     *
     * @param string $password password
     *
     * @return string error message if password is invalid
     */

    public function validate_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (strlen($password > 100))
            return lang('base_password_is_invalid');
    }

    /**
     * Validates proxy port..
     *
     * @param string $port port number
     *
     * @return string error message if port is invalid
     */

    public function validate_port($port)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (empty($port))
            return;

        if (! Network_Utils::is_valid_port($port))
            return lang('network_port_invalid');
    }

    /**
     * Validates proxy username.
     *
     * @param string $username username
     *
     * @return string error message if username is invalid
     */

    public function validate_username($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (empty($username))
            return;

        if (!preg_match("/^([a-z0-9_\-\.\$]+)$/", $username))
            return lang('base_username_invalid');
    }

    /**
     * Validates proxy server.
     *
     * @param string $server IP address or hostname
     *
     * @return string error message if server is invalid
     */

    public function validate_server($server)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (empty($server))
            return;

        if (!(Network_Utils::is_valid_domain($server) || Network_Utils::is_valid_ip($server)))
            return lang('network_proxy_server_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Loads configuration file
     *
     * @return void
     * @throws Engine_Exception
     */
    
    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! is_null($this->config))
            return;

        $file = new File(self::FILE_CONFIG);

        try {
            $lines = array();
            if ($file->exists())
                $lines = $file->get_contents_as_array();
        } catch (File_Not_Found_Exception $e) {
        } catch (File_No_Match_Exception $e) {
        }

        $this->config['server'] = '';
        $this->config['port'] = '';
        $this->config['username'] = '';
        $this->config['password'] = '';

        $params = array('server', 'port', 'username', 'password');

        foreach ($lines as $line) {
            foreach ($params as $param) {
                $matches = array();
                if (preg_match("/^\s*$param\s*=\s(.*)/", $line, $matches))
                    $this->config[$param] = $matches[1];
            }
        }
    }

    /**
     * Sets a configuration parameter.
     *
     * @param string $key   key
     * @param string $value value
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_parameter($key, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG);

        if (! $file->exists())
            $file->create('root', 'root', '0600');

        $match = $file->replace_lines("/^\s*$key\s*=/", "$key = $value\n");

        if (! $match)
            $file->add_lines("$key = $value\n");
    }
}

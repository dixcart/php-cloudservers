<?php

/**
 * PHP Cloud Server implementation for RackSpace (tm)
 *
 * THIS SOFTWARE IS PROVIDED "AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 *
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS;
 * OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
 * EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Aleksey Korzun <al.ko@webfoundation.net>
 * @link http://github.com/AlekseyKorzun/php-cloudservers/
 * @link http://www.schematic.com
 * @author Richard Benson <richard.benson@dixcart.com> - For Load Balancers
 * @link http://www.dixcart.com/it
 * @version 0.2
 * @license bsd
 */

class Server {
	
	public $par;

    protected $_apiBackup = array(
        'weekly' => array(
                'DISABLED',
                'SUNDAY',
                'MONDAY',
                'TUESDAY',
                'WEDNESDAY',
                'THURSDAY',
                'FRIDAY',
                'SATURDAY'),
        'daily' => array(
                'DISABLED',
                'H_0000_0200',
                'H_0200_0400',
                'H_0400_0600',
                'H_0600_0800',
                'H_0800_1000',
                'H_1000_1200',
                'H_1400_1600',
                'H_1600_1800',
                'H_1800_2000',
                'H_2000_2200',
                'H_2200_0000'));

    protected $_apiServers = array();
    protected $_apiFiles = array();

    /**
     * Retrieves details regarding specific server flavor
     *
     * @param int $flavorId id of a flavor you wish to retrieve details for
     * @return mixed returns json string containing details for requested flavor or
     * false on failure
     */
    public function getFlavor ($flavorId)
    {
        $this->par->_apiResource = '/flavors/'. (int) $flavorId;
        $this->par->_doRequest();

        if ($this->par->_apiResponseCode && ($this->par->_apiResponseCode == '200'
           	    || $this->par->_apiResponseCode == '203')) {
        	return $this->par->_apiResponse;
        }

        return false;
    }

    /**
     * Retrieves all of the available server flavors
     *
     * @return mixed returns json string containing available server configurations or
     * false on failure
     */
    public function getFlavors ($isDetailed = false)
    {
        $this->par->_apiResource = '/flavors' . ($isDetailed ? '/detail' : '');
        $this->par->_doRequest();

        if ($this->par->_apiResponseCode && ($this->par->_apiResponseCode == '200'
           	    || $this->par->_apiResponseCode == '203')) {
        	return $this->par->_apiResponse;
        }

        return false;
    }

    /**
     * Creates a new image of server
     *
     * @param string $name name of new image
     * @param int $serverId server id for which you wish to base this image on
     * @return mixed returns json string containing details of created image or false on failure
     */
    public function createImage ($name, $serverId)
    {
        $this->par->_apiResource = '/images';
        $this->par->_apiJson = array ('image' => array(
                                    'serverId' => (int) $serverId,
                                    'name' => (string) $name));
        $this->par->_doRequest(Cloud::METHOD_POST);

        if ($this->par->_apiResponseCode && $this->par->_apiResponseCode == '200') {
        	return $this->par->_apiResponse;
        }

        return false;
    }

    /**
     * Retrieves details of specific image
     *
     * @param int $imageId id of image you wish to retrieve details for
     * @return json string containing details of requested image
     */
    public function getImage ($imageId)
    {
        $this->par->_apiResource = '/images/'. (int) $imageId;
        $this->par->_doRequest();

        if ($this->par->_apiResponseCode && ($this->par->_apiResponseCode == '200'
                || $this->par->_apiResponseCode == '203')) {
            return $this->par->_apiResponse;
        }
    }

    /**
     * Retrieves all of the available images
     *
     * @return mixed returns json string of available images or false on failure
     */
    public function getImages ($isDetailed = false)
    {
        $this->par->_apiResource = '/images' . ($isDetailed ? '/detail' : '');
        $this->par->_doRequest();

        if ($this->par->_apiResponseCode && ($this->par->_apiResponseCode == '200'
                || $this->par->_apiResponseCode == '203')) {
            return $this->par->_apiResponse;
        }

        return false;
    }

    /**
     * Retrieves configuration details for specific server
     *
     * @return mixed json string containing server details or false on failure
     */
    public function getServer ($serverId)
    {
        $this->par->_apiResource = '/servers/'. (int) $serverId;
        $this->par->_doRequest();
        
        if ($this->par->_apiResponseCode && ($this->par->_apiResponseCode == '200' || $this->par->_apiResponseCode == '203')) {
            // Save server names to avoid creating dublicate servers
            if (property_exists($this->par->_apiResponse, 'server')) {
                $this->_apiServers[(int) $this->par->_apiResponse->server->id] =
                    array('id' => (int) $this->par->_apiResponse->server->id,
                            'name' => (string) $this->par->_apiResponse->server->name);
            }

            return $this->par->_apiResponse;
        }

        return false;
    }

    /**
     * Retrieves currently available servers
     *
     * @return mixed json string containing current servers or false on failure
     */
    public function getServers ($isDetailed = false)
    {
        $this->par->_apiResource = '/servers'. ($isDetailed ? '/detail' : '');
        $this->par->_doRequest();

        if ($this->par->_apiResponseCode && ($this->par->_apiResponseCode == '200' || $this->par->_apiResponseCode == '203')) {
            if (property_exists($this->par->_apiResponse, 'servers')) {
                // Reset internal server array
                $this->_apiServers = array();
                foreach ($this->par->_apiResponse->servers as $server) {
                    $this->_apiServers[(int) $server->id]['name'] = (string) $server->name;
                }
            }

            return $this->par->_apiResponse;
        }

        return false;
    }

    public function shareServerIp ($serverId, $serverIp, $groupId, $doConfigure = false)
    {
        $this->par->_apiResource = '/servers/'. (int) $serverId .'/ips/public/'. $serverIp;
        $this->par->_apiJson = array ('shareIp' => array(
                                    'sharedIpGroupId' => (int) $groupId,
                                    'configureServer' => (bool) $doConfigure));
		$this->par->_doRequest(Cloud::METHOD_PUT);

        if ($this->par->_apiResponseCode && $this->par->_apiResponseCode == '201') {
            return true;
        }

        return false;
    }

    /**
     * Removes a shared server IP from server
     * @param int $serverId id of server this action is peformed for
     * @param string $serverIp IP you wish to unshare
     * @return bool returns true on success or false on failure
     */
    public function unshareServerIp ($serverId, $serverIp)
    {
        $this->par->_apiResource = '/servers/'. (int) $serverId .'/ips/public/'. (string) $serverIp;
        $this->par->_doRequest(Cloud::METHOD_DELETE);

        if ($this->par->_apiResponseCode && $this->par->_apiResponseCode == '202') {
            return true;
        }

        return false;
    }

    /**
     * Get IP's assigned to server
     *
     * @param int $serverId id of server you wish to retrieve ips for
     * @param string $type type of addresses to retrieve could be private/public or
     * false for both types.
     * @return mixed returns json string of server addresses or false of failure
     */
    public function getServerIp ($serverId, $type = false)
    {
       $this->par->_apiResource = '/servers/'. (int) $serverId .'/ips'. ($type ? '/'. $type : '');
       $this->par->_doRequest();

        if ($this->par->_apiResponseCode && ($this->par->_apiResponseCode == '200'
                || $this->par->_apiResponseCode == '203')) {
            return $this->par->_apiResponse;
        }

        return false;
    }

    /**
     * Add a server to shared ip group
     *
     * @param string $name name of shared ip group you are creating
     * @param int $serverId id of server you wish to add to this group
     * @return mixed returns json string containing id of created shared ip group or false on failure
     */
    public function addSharedIpGroup ($name, $serverId)
    {
        $this->par->_apiResource = '/shared_ip_groups';
        $this->par->_apiJson = array ('sharedIpGroup' => array(
                                    'name' => (string) $name,
                                    'server' => (int) $serverId));
        $this->par->_doRequest(Cloud::METHOD_POST);

        if ($this->par->_apiResponseCode && $this->par->_apiResponseCode == '201') {
            return $this->par->_apiResponse;
        }

        return false;
    }

    /**
     * Delete shared IP group
     *
     * @param int $groupId id of group you wish to delete
     * @return bool returns true on success and false on failure
     */
    public function deleteSharedIpGroup ($groupId)
    {
        $this->par->_apiResource = '/shared_ip_groups/'. (int) $groupId;
        $this->par->_doRequest(Cloud::METHOD_DELETE);

        if ($this->par->_apiResponseCode && $this->par->_apiResponseCode == '204') {
            return true;
        }

        return false;
    }

    /**
     * Retrieve details for specific IP group
     *
     * @param int $groupId id of specific shared group you wish to retrieve details
     * for
     * @return mixed returns json string containing details about requested group
     * or false on failure
     */
    public function getSharedIpGroup ($groupId)
    {
        $this->par->_apiResource = '/shared_ip_groups/'. (int) $groupId;
        $this->par->_doRequest();

        if ($this->par->_apiResponseCode && ($this->par->_apiResponseCode == '200'
                || $this->par->_apiResponseCode == '203')) {
            return $this->par->_apiResponse;
        }

        return false;
    }

    /**
     * Retrieve all the available shared IP groups
     *
     * @param bool $isDetailed should response contain an array of servers group has
     * @return mixed returns json string of groups or false on failure
     */
    public function getSharedIpGroups ($isDetailed = false)
    {
        $this->par->_apiResource = '/shared_ip_groups'. ($isDetailed ? '/detail' : '');
        $this->par->_doRequest();

        if ($this->par->_apiResponseCode && ($this->par->_apiResponseCode == '200'
                || $this->par->_apiResponseCode == '203')) {
        	return $this->par->_apiResponse;
        }

        return false;
    }

    /**
     * Retrieve back-up schedule for a specific server
     *
     * @param int $serverId id of server you wish to retrieve back-up schedule for
     * @return mixed returns json string of current back-up schedule or false on failure
     */
    public function getBackupSchedule ($serverId)
    {
        $this->par->_apiResource = '/servers/'. (int) $serverId .'/backup_schedule';
        $this->par->_doRequest();

	    if ($this->par->_apiResponseCode && ($this->par->_apiResponseCode == '200'
                || $this->par->_apiResponseCode == '203')) {
            return $this->par->_apiResponse;
	    }

        return false;
    }

    /**
     * Create a new back-up schedule for a server
     *
     * @param int $serverId id of a server this back-up schedule is intended for
     * @param string $weekly day of the week this back-up should run, please
     * $_apiBackup array and/or documentation for valid parameters.
     * @param string $daily time of the day this back-up should run, please
     * $_apiBackup array and/or documentation for valid parameters.
     * @param bool $isEnabled should this scheduled back-up be enabled or disabled,
     * default is set to enabled.
     * @throws Cloud_Exception
     * @return bool true on success and false on failure
     */
    public function addBackupSchedule ($serverId, $weekly, $daily, $isEnabled = true)
    {
        if (!in_array((string) strtoupper($weekly), $this->_apiBackup['weekly'])) {
            throw new Cloud_Exception ('Passed weekly back-up parameter is not supported');
        }

        if (!in_array((string) strtoupper($daily), $this->_apiBackup['daily'])) {
            throw new Cloud_Exception ('Passed daily back-up parameter is not supported');
        }

        $this->par->_apiResource = '/servers/'. (int) $serverId .'/backup_schedule';
        $this->par->_apiJson = array ('backupSchedule' => array(
                                    'enabled' => (bool) $isEnabled,
                                    'weekly' => (string) strtoupper($weekly),
                                    'daily' => (string) strtoupper($daily)));
        $this->par->_doRequest(Cloud::METHOD_POST);

	    if ($this->par->_apiResponseCode && $this->par->_apiResponseCode == '204') {
            return true;
	    }

        return false;
    }

    /**
     * Deletes scheduled back-up for specific server
     *
     * @param int $serverId id of server you wish to delete all scheduled back-ups
     * for
     * @return bool returns true on success or false on failure
     */
    public function deleteBackupSchedule ($serverId)
    {
        $this->par->_apiResource = '/servers/'. (int) $serverId .'/backup_schedule';
        $this->par->_doRequest(Cloud::METHOD_DELETE);

	    if ($this->par->_apiResponseCode && $this->_apiResponseCode == '204') {
            return true;
	    }

        return false;
    }

    /**
     * Creates a new server on the cloud
     *
     * @param string $name server name, must be unique
     * @param int $imageId server image you wish to use
     * @param int $flavorId server flavor you wish to use
     * @param int $groupId optional group id of server cluster
     * @return mixed returns json string of server's configuration or false on failure
     */
    public function createServer ($name, $imageId, $flavorId, $groupId = false)
    {
        // Since Rackspace automaticly removes all spaces/non alpha-numeric characters
        // let's do this on our end before submitting data
        $name = preg_replace("/[^a-zA-Z0-9-]/", '', (string) $name);

        // We need to check if we are creating a dublicate server name,
        // since creating two servers with same name can cause problems.
        $this->getServers();

        foreach ($this->_apiServers as $server) {
            if (strtolower($server['name']) == strtolower($name)) {
                throw new Cloud_Exception ('Server with name: '. $name .' already exists!');
            }
        }

        $this->par->_apiResource = '/servers';
        $this->par->_apiJson = array ('server' => array(
                                'name' => $name,
                                'imageId' => (int) $imageId,
                                'flavorId' => (int) $flavorId,
                                'metadata' => array(
                                    'Original Name' => $name,
                                    'Creation' => date("F j, Y, g:i a")),
                                'personality' => array()));

        if (is_array($this->_apiFiles) && !empty($this->_apiFiles)) {
            foreach ($this->_apiFiles as $file => $content) {
                array_push($this->par->_apiJson['server']['personality'],
                   array('path' => $file, 'contents' => base64_encode($content)));
            }
        }

        if (is_numeric($groupId)) {
			$this->par->_apiJson['server']['sharedIpGroupId'] = (int) $groupId;
        }
		
		echo json_encode($this->par->_apiJson);

        $this->par->_doRequest(Cloud::METHOD_POST);

        if ($this->par->_apiResponseCode && $this->par->_apiResponseCode == '202') {
            return $this->par->_apiResponse;
        }

        return false;
    }

    /**
     * Adds file to inject while creating new server
     *
     * @param string $file full file path where file will be put (/etc/motd,etc)
     * @param string $content content of the file (Welcome to my server, etc)
     * @return array returns array of all files pending injection
     */
    public function addServerFile ($file, $content) {
        $this->_apiFiles[(string) $file] = (string) $content;
        return $this->_apiFiles;
    }

    /**
     * Update server's name and password
     *
     * @param int $serverId id of server you wish to update
     * @param string $name new server name
     * @param string $password new server password
     * @return mixed returns false on failure or server configuration on success
     */
    public function updateServer ($serverId, $name, $password)
    {
        $this->par->_apiResource = '/servers/'. (int) $serverId;
        $this->par->_apiJson = array ('server' => array(
                                    'name' => (string) $name,
                                    'adminPass' => (string) $password));
        $this->par->_doRequest(Cloud::METHOD_PUT);

        if ($this->par->_apiResponseCode && $this->par->_apiResponseCode == '202') {
            return true;
        }

        return false;
    }

    /**
     * Delete server
     *
     * @param int $serverId id of server you wish to delete
     * @return bool returns true on success or false on fail
     */
    public function deleteServer ($serverId)
    {
        $this->par->_apiResource = '/servers/'. (int) $serverId;
        $this->par->_doRequest(Cloud::METHOD_DELETE);

        // If server was deleted
        if ($this->par->_apiResponseCode && $this->par->_apiResponseCode == '202') {
            return true;
        }

        return false;
    }

    /**
     * Rebuild server using another server image
     *
     * @param int $serverId id of server you wish to rebuild
     * @param int $imageId id of server image you wish to use for this rebuild
     * @return bool returns true on success or false on fail
     */
    public function rebuildServer ($serverId, $imageId)
    {
        $this->par->_apiResource = '/servers/' . (int) $serverId .'/action';
        $this->par->_apiJson = array ('rebuild' => array(
                                    'imageId' => (int) $imageId));
        $this->par->_doRequest(Cloud::METHOD_PUT);

        // If rebuild request is successful
        if ($this->par->_apiResponseCode && $this->par->_apiResponseCode == '202') {
            return true;
        }

        return false;
    }

    /**
     * Resize server to another flavor (server configuration)
     *
     * @param int $serverId id of server you wish to resize
     * @return bool returns true on success or false on fail
     */
    public function resizeServer ($serverId, $flavorId)
    {
        $this->par->_apiResource = '/servers/'. (int) $serverId .'/action';
        $this->par->_apiJson = array ('resize' => array(
                                    'flavorId' => (int) $flavorId));
        $this->par->_doRequest(Cloud::METHOD_PUT);

        // If confirmation is successful
        if ($this->par->_apiResponseCode && $this->par->_apiResponseCode == '202') {
            return true;
        }

        return false;
    }

    /**
     * Confirm resize of server
     *
     * @param int $serverId id of server this confirmation is for
     * @return bool returns true on success or false on fail
     */
    public function confirmResize ($serverId)
    {
        $this->par->_apiResource = '/servers/'. (int) $serverId .'/action';
        $this->par->_apiJson = array ('confirmResize' => '1');
        $this->par->_doRequest(Cloud::METHOD_PUT);

        // If confirmation is successful
        if ($this->par->_apiResponseCode && $this->par->_apiResponseCode == '202') {
            return true;
        }

        return false;
    }

    /**
     * Revert resize changes
     *
     * @param int $serverId id of server you wish to revert resize for
     * @return bool returns true on success or false on fail
     */
    public function revertResize ($serverId)
    {
        $this->par->_apiResource = '/servers/'. (int) $serverId .'/action';
        $this->par->_apiJson = array ('revertResize' => '1');
        $this->par->_doRequest(Cloud::METHOD_PUT);

        // If revert is successful
        if ($this->par->_apiResponseCode && $this->par->_apiResponseCode == '202') {
            return true;
        }

        return false;
    }

    /**
     * Reboots server
     *
     * @param int $serverId id of server you wish to reboot
     * @param string $type specify what kind of reboot you wish to perform
     * @return bool returns true on success or false on fail
     */
    public function rebootServer ($serverId, $type = 'soft')
    {
        $this->par->_apiResource = '/servers/'. (int) $serverId .'/action';
        $this->par->_apiJson = array ('reboot' => array(
                                    'type' => (string) strtoupper($type)));
        $this->par->_doRequest(Cloud::METHOD_POST);

        // If reboot request was successfully recieved
        if ($this->par->_apiResponseCode && $this->par->_apiResponseCode == '202') {
            return true;
        }

        return false;
    }
}

/* Legacy Class for upgraders */
if (!class_exists('Cloud')) require_once('Cloud.php');
class Cloud_Server extends Cloud {
	
	public function getFlavor ($flavorId) { return $this->servers->getFlavor($flavorId); }
	public function getFlavors ($isDetailed = false) { return $this->servers->getFlavors($isDetailed); }
	public function createImage ($name, $serverId) { return $this->servers->createImage($name, $serverId); }
	public function getImage ($imageId) { return $this->servers->getImage($imageId); }
	public function getImages ($isDetailed = false) { return $this->servers->getImages($isDetailed); }
	public function getServer ($serverId) { return $this->servers->getServer($serverId); }
	public function getServers ($isDetailed = false) { return $this->servers->getServers($isDetailed); }
	public function shareServerIp ($serverId, $serverIp, $groupId, $doConfigure = false) { return $this->servers->shareServerIp($serverId, $serverIp, $groupId, $doConfigure); }
	public function unshareServerIp ($serverId, $serverIp) { return $this->servers->unshareServerIp($serverId, $serverIp); }
	public function getServerIp ($serverId, $type = false) { return $this->servers->getServerIp($serverId, $type); }
	public function addSharedIpGroup ($name, $serverId) { return $this->servers->addSharedIpGroup($name, $serverId); }
	public function deleteSharedIpGroup ($groupId) { return $this->servers->deleteSharedIpGroup($groupId); }
	public function getSharedIpGroup ($groupId) { return $this->servers->getSharedIpGroup($groupId); }
	public function getSharedIpGroups ($isDetailed = false) { return $this->servers->getSharedIpGroups($isDetailed); }
	public function getBackupSchedule ($serverId) { return $this->servers->getBackupSchedule($serverId); }
	public function addBackupSchedule ($serverId, $weekly, $daily, $isEnabled = true) { return $this->servers->addBackupSchedule($serverId, $weekly, $daily, $isEnabled); }
	public function deleteBackupSchedule ($serverId) { return $this->servers->deleteBackupSchedule($serverId); }
	public function createServer ($name, $imageId, $flavorId, $groupId = false) { return $this->servers->createServer($name, $imageId, $flavorId, $groupId); }
	public function addServerFile ($file, $content) { return $this->servers->addServerFile($file, $content); }
	public function updateServer ($serverId, $name, $password) { return $this->servers->updateServer($serverId, $name, $password); }
	public function deleteServer ($serverId) { return $this->servers->deleteServer($serverId); }
	public function rebuildServer ($serverId, $imageId) { return $this->servers->rebuildServer($serverId, $imageId); }
	public function resizeServer ($serverId, $flavorId) { return $this->servers->resizeServer($serverId, $flavorId); }
	public function confirmResize ($serverId) { return $this->servers->confirmResize($serverId); }
	public function revertResize ($serverId) { return $this->servers->revertResize($serverId); }
	public function rebootServer ($serverId, $type = 'soft') { return $this->servers->rebootServer($serverId, $type); }
	
	public function getLimits () { return $this->getLimits(); }
	
}
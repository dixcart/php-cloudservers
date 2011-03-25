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

class LoadBalancer {
	
	public $par;
	
	protected $_apiBalancers;
	protected $_apiNodes;
	
	function __construct()
	{
		$this->_apiNodes = array();
	}
	
	/**
     * Lists current Loadbalancers
     *
     * @return mixed json string containing current servers or false on failure
     */
    public function getBalancers ()
    {
        $this->par->_apiResource = '/loadbalancers.json'; // As of 25/03 API does not default to JSON on this command
        $this->par->_doRequest(Cloud::METHOD_GET, Cloud::RESOURCE_BALANCER);

        if ($this->par->_apiResponseCode && ($this->par->_apiResponseCode == '200' || $this->par->_apiResponseCode == '203')) {
            if (property_exists($this->par->_apiResponse, 'loadBalancers')) {
                // Reset internal balancer array
                $this->_apiBalancers = array();
                foreach ($this->par->_apiResponse->loadBalancers as $balancer) {
                    $this->_apiBalancers[(int) $balancer->id]['name'] = (string) $balancer->name;
                }
            }

            return $this->par->_apiResponse;
        }

        return false;
    }

    /**
     * Retrieves configuration details for specific server
     *
     * @return mixed json string containing server details or false on failure
     */
    public function getBalancer ($balancerId)
    {
        $this->par->_apiResource = '/loadbalancers/'. (int) $balancerId . '.json'; // As of 25/03 API does not default to JSON on this command
        $this->par->_doRequest(Cloud::METHOD_GET, Cloud::RESOURCE_BALANCER);
        
        if ($this->par->_apiResponseCode && ($this->par->_apiResponseCode == '200' || $this->par->_apiResponseCode == '203')) {
            // Save balancer names to avoid creating duplicate balancers
            if (property_exists($this->par->_apiResponse, 'loadBalancers')) {
                $this->_apiBalancers[(int) $this->par->_apiResponse->server->id] =
                    array('id' => (int) $this->par->_apiResponse->server->id,
                            'name' => (string) $this->par->_apiResponse->server->name);
            }

            return $this->par->_apiResponse;
        }

        return false;
    }
	
	/**
	 * Adds a node before creating a balancer
	 *
	 * @param string $address IP Address of the node
	 * @param int $port local port on the node
	 * @param strong $condition Whether the node is enabled
	 */
	 public function addNode ($address, $port, $condition = true)
	 {
		if($condition) {
			$condition = "ENABLED";
		} else {
			$condition = "DISABLED";
		}
		array_push ($this->_apiNodes, array('address' => $address,
											'port' => (string)$port,
											'condition' => $condition));
		return $this->_apiNodes;
	 }
	

    /**
     * Creates a new load balancer
     *
     * @param string $name balancer name, must be unique
     * @param int $port external facing port balancer will use
     * @param string $protocol protocol for the balancer
	 * @param string $virtualIP PUBLIC, SERVICENET or ID of existing IP
     * @return mixed returns json string of balancer's configuration or false on failure
     */
    public function createBalancer ($name, $port, $protocol, $virtualIp = "PUBLIC")
    {
		// We have to have nodes set up to create a balancer
		if (count($this->_apiNodes) == 0) return false;
		
        // Since Rackspace automaticly removes all spaces/non alpha-numeric characters
        // let's do this on our end before submitting data
        $name = preg_replace("/[^a-zA-Z0-9-]/", '', (string) $name);

        // We need to check if we are creating a duplicate balancer name,
        // since creating two balancers with same name can cause problems.
        $this->getBalancers();

        foreach ($this->_apiBalancers as $balancer) {
            if (strtolower($balancer['name']) == strtolower($name)) {
                throw new Cloud_Exception ('Balancer with name: '. $name .' already exists!');
            }
        }
		
		if ($virtualIp === "PUBLIC" || $virtualIp === "SERVICENET") {
			$Ips = array(array('type' => $virtualIp));
		} else {
			$Ips = array(array('id' => $virtualIp));
		}

        $this->par->_apiResource = '/loadbalancers';
        $this->par->_apiJson = array ('loadBalancer' => array(
                                'name' => $name,
                                'port' => (string) $port,
                                'protocol' => $protocol,
								'virtualIps' => $Ips,
                                'nodes' => $this->_apiNodes));

        $this->par->_doRequest(Cloud::METHOD_POST, Cloud::RESOURCE_BALANCER);

        if ($this->par->_apiResponseCode && $this->par->_apiResponseCode == '202') {
			//Clear nodes
			$this->_apiNodes = array();
            return $this->par->_apiResponse;
        }

        return false;
    }

	/**
     * Delete Balancer
     *
     * @param int $balancerId id of balancer you wish to delete
     * @return bool returns true on success or false on fail
     */
    public function deleteBalancer ($balancerId)
    {
        $this->par->_apiResource = '/loadbalancers/'. (int) $balancerId;
        $this->par->_doRequest(Cloud::METHOD_DELETE, Cloud::RESOURCE_BALANCER);

        // If server was deleted
        if ($this->par->_apiResponseCode && $this->par->_apiResponseCode == '202') {
            return true;
        }

        return false;
    }

    /**
     * Retrieves list of virtual IPs on a specific balancer
     *
     * @return mixed returns json string containing details on the Virtual IPs
     * false on failure
     */
    public function getVirtualIPs ($balancerId)
    {
        $this->par->_apiResource = '/loadbalancers/'. $balancerId . '/virtualips.json'; // As of 25/03 API does not default to JSON on this command
        $this->par->_doRequest(Cloud::METHOD_GET, Cloud::RESOURCE_BALANCER);

        if ($this->par->_apiResponseCode && ($this->par->_apiResponseCode == '200'
           	    || $this->par->_apiResponseCode == '202')) {
        	return $this->par->_apiResponse;
        }

        return false;
    }

	/**
     * Retrieves all of the available balancing protocols
     *
     * @return mixed returns json string containing available protocol and ports or
     * false on failure
     */
    public function getProtocols ()
    {
        $this->par->_apiResource = '/loadbalancers/protocols';
        $this->par->_doRequest(Cloud::METHOD_GET, Cloud::RESOURCE_BALANCER);

        if ($this->par->_apiResponseCode && ($this->par->_apiResponseCode == '200'
           	    || $this->par->_apiResponseCode == '203')) {
        	return $this->par->_apiResponse;
        }

        return false;
    }

}
<?php
/**
 * PHP Cloud DNS implementation for RackSpace (tm)
 * 
 * @package phpCloudServers
 */
/**
 * DNS API implementation
 * 
 * @package phpCloudServers
 */
class Cloud_DNS extends Cloud {

    /**
     * Returns list of all domain names on the account
     * 
     * @param bool $isDetailed Return detailed results
     * @param string $filter text to filter the list of domains by. NB: The wildcard is assumed from the left but not the right.
     * @return mixed returns json string containing list of all domains on the
     * account or false on failure 
     */
    public function getDomains ($isDetailed = false, $filter = '')
    {
        $this->_apiResource = '/domains'. ($isDetailed ? '/detail' : '');
        if ($filter != '') $this->_apiResource .= '?name=' . $filter;
        $this->_doRequest(self::METHOD_GET, self::RESOURCE_DNS);

        if ($this->_apiResponseCode && ($this->_apiResponseCode == '200'
           	    || $this->_apiResponseCode == '202')) {
        	return $this->_apiResponse;
        }

        return false;
    }
    
    /**
     * Returns details of a specific domain
     * 
     * @param int $domainId ID of the domain to get details for
     * @return mixed JSON details of the specified domain or false on error
     */
    public function getDomain ($domainId)
    {
        $this->_apiResource = '/domains/'. (int)$domainId;
        $this->_doRequest(self::METHOD_GET, self::RESOURCE_DNS);

        if ($this->_apiResponseCode && ($this->_apiResponseCode == '200')) {
        	return $this->_apiResponse;
        }

        return false;
    }
    
}
?>
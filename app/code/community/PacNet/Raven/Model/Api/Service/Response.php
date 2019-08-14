<?php
/**
 * PacNet's Raven Payment Gateway
 *
 * MIT License
 *
 * Copyright (c) 2016, PacNet Services Ltd
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @category    PacNet
 * @package     PacNet_Raven
 * @author      Joerg Beekmann <joerg@deepcovelabs.com>
 * @link        https://pacnetservices.com/
 * @copyright   Copyright (c) 2016, PacNet Services Ltd
 * @license     https://opensource.org/licenses/MIT MIT License
 */

/**
 * Raven API payment service response
 */
class PacNet_Raven_Model_Api_Service_Response
{
	/**
	 * @var string
	 */
	protected $_userName;

	/**
	 * @var string
	 */
	protected $_secret;

	/**
	 * @var string
	 */
	protected $_httpResponseHeader;

	/**
	 * @var string
	 */
	protected $_ravenResponseString;

	/**
	 * @var int
	 */
	protected $_httpStatus;

	/**
	 * @var string
	 */
	protected $_serviceName;

	/**
	 * @var array
	 */
	protected $_data = array();


	/**
	 * Constructor.
	 *
	 * @param $config
	 * @param $httpResponseHeader
	 * @param $ravenResponseString
	 * @param $serviceName
	 *
	 * @throws PacNet_Raven_Model_Api_Response_Exception
	 */
	public function __construct($config, $httpResponseHeader, $ravenResponseString, $serviceName)
    {
        $this->setConfig($config);
        $this->setRavenResponseString($ravenResponseString);
        $this->setServiceName($serviceName);

        $httpResponse = null;
        if ($httpResponseHeader == null) {
            throw new PacNet_Raven_Model_Api_Response_Exception('No response from server, please try again!');
        }
        $this->setHttpHeader($httpResponseHeader);

        switch($this->getHttpStatus()) {
            case 200:
                $this->parseResponse($ravenResponseString);
                break;
            case 500:
                throw new PacNet_Raven_Model_Api_Response_Exception('No response from server, please try again!');
                break;
            default:
                break;
        }
    }

	/**
	 * Sets config
	 *
	 * @param $config
	 *
	 * @return $this
	 */
	public function setConfig($config)
    {
        if ($config['UserName']) $this->setUserName($config['UserName']);
        if ($config['Secret']) $this->setSecret($config['Secret']);
        return $this;
    }

	/**
	 * Gets the Raven request result
	 *
	 * @return string
	 */
	public function getRequestResult()
    {
        $status = $this->getHttpStatus();
        if($status == 500) {
            return 'serverError';
        }
        return $this->getRequestResult();
    }

	/**
	 * Gets the Raven response string
	 *
	 * @return mixed
	 */
	public function getRavenResponseString()
    {
        return $this->_ravenResponseString;
    }

	/**
	 * Gets the Raven response data
	 *
	 * @return array
	 */
	public function getRavenResponseData() {
        return $this->_data;
    }

	/**
	 * Sets the Raven response string
	 *
	 * @param $ravenResponseString
	 *
	 * @return mixed
	 */
	protected function setRavenResponseString($ravenResponseString)
    {
        return $this->_ravenResponseString = $ravenResponseString;
    }

	/**
	 * Parse the Raven response
	 *
	 * @param $ravenResponseString
	 *
	 * @throws PacNet_Raven_Model_Api_Response_Exception
	 */
	protected function parseResponse($ravenResponseString)
    {
        $paramAndReportPairs = explode("\r", $ravenResponseString, 2);
        $this->setResponseParameters($paramAndReportPairs[0]);
        if (count($paramAndReportPairs) == 2) {
            $this->setReportParameters($paramAndReportPairs[1]);
        }

        $this->authenticate();
    }

	/**
	 * Sets response parameters in object
	 *
	 * @param $responseParamData
	 */
	protected function setResponseParameters($responseParamData)
    {
        $paramPairs = explode('&', $responseParamData);
        foreach ($paramPairs as $paramPair) {
            list($key, $value) = explode('=', $paramPair);
            if ($key != '') {
                $key = htmlspecialchars(urldecode($key));
                $value = htmlspecialchars(urldecode($value));
                $this->set($key, $value);
            }
        }
    }

	/**
	 * Sets the response report parameters
	 *
	 * @param $reportData
	 */
	protected function setReportParameters($reportData)
    {
        $this->setReport($reportData);
    }

	/**
	 * Authenticates the Raven response
	 *
	 * @throws PacNet_Raven_Model_Api_Response_Exception
	 */
	protected function authenticate()
    {
        if ($this->verificationSignature() != $this->getSignature()) {
            throw new PacNet_Raven_Model_Api_Response_Exception("Invalid Raven signature!");
        }
    }

	/**
	 * Verify the Raven signature
	 *
	 * @return string
	 */
	protected function verificationSignature()
    {
        $data = $this->getUserName() . $this->getTimestamp() . $this->getRequestID();
        return hash_hmac("sha1", $data, $this->getSecret());
    }

	/**
	 * Sets the Http header
	 *
	 * @param $httpResponseHeader
	 */
	protected function setHttpHeader($httpResponseHeader)
    {
        $this->setHttpResponseHeader($httpResponseHeader);
        $pattern = '/^HTTP\/1\.[01] ([0-9]{3})/';
        preg_match($pattern, $httpResponseHeader[0], $matches);
        $this->setHttpStatus($matches[1]);
    }

	/**
	 * Gets the Http response header
	 *
	 * @return mixed
	 */
	public function getHttpResponseHeader()
    {
        return $this->_httpResponseHeader;
    }

	/**
	 * Sets the Http response header
	 *
	 * @param $httpResponseHeader
	 *
	 * @return $this
	 */
	public function setHttpResponseHeader($httpResponseHeader)
    {
        $this->_httpResponseHeader = $httpResponseHeader;
        return $this;
    }

	/**
	 * Gets the Http status
	 *
	 * @return mixed
	 */
	public function getHttpStatus()
    {
        return $this->_httpStatus;
    }

	/**
	 * Sets the Http status
	 *
	 * @param $httpStatus
	 *
	 * @return $this
	 */
	public function setHttpStatus($httpStatus)
    {
        $this->_httpStatus = $httpStatus;
        return $this;
    }

	/**
	 * Gets the service name
	 *
	 * @return mixed
	 */
	public function getServiceName()
    {
        return $this->_serviceName;
    }

	/**
	 * Sets the service name
	 *
	 * @param $serviceName
	 *
	 * @return $this
	 */
	public function setServiceName($serviceName)
    {
        $this->_serviceName = $serviceName;
        return $this;
    }

	/**
	 * Gets the API username
	 *
	 * @return mixed
	 */
	public function getUserName()
    {
        return $this->_userName;
    }

	/**
	 * Sets the API username
	 *
	 * @param $userName
	 *
	 * @return $this
	 */
	public function setUserName($userName)
    {
        $this->_userName = $userName;
        return $this;
    }

	/**
	 * Gets the API secret
	 *
	 * @return mixed
	 */
	public function getSecret()
    {
        return $this->_secret;
    }

	/**
	 * Sets the API secret
	 *
	 * @param $secret
	 *
	 * @return $this
	 */
	public function setSecret($secret)
    {
        $this->_secret = $secret;
        return $this;
    }

	/**
	 * Gets the transactional info
	 *
	 * Used for Magento transaction additional info
	 *
	 * @return array
	 */
	public function getTransactionAdditionalInfo()
    {
        return array(
            'RequestID' => $this->getRequestID(),
            'ApprovalCode' => $this->getApprovalCode(),
            'Status' => $this->getStatus(),
            'TrackingNumber' => $this->getTrackingNumber()
        );
    }

	/**
	 * Get magic method
	 *
	 * @param $key
	 *
	 * @return mixed|null
	 */
	public function __get($key)
    {
        return $this->get($key);
    }

	/**
	 * Set magic method
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return PacNet_Raven_Model_Api_Service_Response
	 */
	public function __set($key, $value)
    {
        return $this->set($key, $value);
    }

	/**
	 * Global get
	 *
	 * @param $key
	 *
	 * @return mixed|null
	 */
	public function get($key) {
        if(array_key_exists($key, $this->_data)) {
            return $this->_data[$key];
        }

        return null;
    }

	/**
	 * Global set
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return $this
	 */
	public function set($key, $value)
    {
        $this->_data[$key] = $value;
        return $this;
    }

	/**
	 * Call magic method
	 *
	 * @param $name
	 * @param $args
	 *
	 * @return PacNet_Raven_Model_Api_Service_Response|mixed|null
	 * @throws PacNet_Raven_Model_Api_Service_Request_Exception
	 */
	public function __call($name, $args)
    {
        $key = substr($name, 3);
        $type = substr($name, 0, 3);
        switch ($type) {
            case 'get':
                return $this->get($key);
                break;
            case 'set':
                echo $key; exit;
                return $this->set($key, $args[0]);
                break;
            default:
                throw new PacNet_Raven_Model_Api_Service_Request_Exception("Method '$name' does not exist");
                break;
        }
    }

} 
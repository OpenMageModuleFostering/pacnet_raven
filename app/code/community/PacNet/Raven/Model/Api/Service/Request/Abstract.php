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
 * Raven API service request abstract
 */
abstract class PacNet_Raven_Model_Api_Service_Request_Abstract
{
	/**
	 * Raven API version
	 */
	const RAPI_VERSION = 2;

	/**
	 * Rave API interface
	 */
	const RAPI_INTERFACE = 'PHPVer2.3';

	/**
	 * Raven API gateway
	 */
	const RAPI_GATEWAY = 'https://raven.pacnetservices.com/realtime';

	/**
	 * Raven API content type
	 */
	const CONTENT_TYPE = 'application/x-www-form-urlencoded';

	/**
	 * Raven mandatory request data
	 */
	protected $_userName;
	protected $_secret;
	protected $_PRN;
	protected $_requestID;
	protected $_requestResult;
	protected $_signature;
	protected $_timestamp;
	protected $_signatureParams;
	protected $_serviceName;

	/**
	 * Allowed request properties
	 *
	 * @var
	 */
	protected $_dataKeys;

	/**
	 * Request properties
	 *
	 * @var
	 */
	protected $_data;

	/**
	 * Constructor.
	 *
	 * @param $config
	 */
	public function __construct($config)
    {
        $this->setConfig($config);
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
        if ($config['RequestID']) $this->setRequestID($config['RequestID']);
        if ($config['PRN']) $this->setPRN($config['PRN']);

        return $this;
    }

	/**
	 * Gets gateway url
	 *
	 * @return string
	 */
	public function getUrl()
    {
        return self::RAPI_GATEWAY . '/' . $this->getServiceName();
    }

	/**
	 * Send a Raven request
	 *
	 * @return PacNet_Raven_Model_Api_Service_Response
	 */
	public function send()
    {
        $responseConfig = array(
          'UserName' => $this->getUserName(),
            'Secret' => $this->getSecret()
        );
        list($httpResponseHeader, $responseData) = $this->_postRequest();
        return new PacNet_Raven_Model_Api_Service_Response(
            $responseConfig,
            $httpResponseHeader,
            $responseData,
            $this->getServiceName());
    }

	/**
	 * Creates and posts the request
	 *
	 * @param null $optional_headers
	 *
	 * @return array
	 */
	protected function _postRequest($optional_headers = null)
    {
        $url = $this->getUrl();
        $data = $this->getData(true);

        $params = array('http' => array(
            'method' => 'POST',
            'header' => 'Content-type: ' . self::CONTENT_TYPE,
            'content' => $data
        ));

        if ($optional_headers !== null) {
            $params['http']['header'] = $optional_headers;
        }

        $context = stream_context_create($params);
        $responseData = file_get_contents($url, false, $context);

        $httpResponseHeader = null;

        if (isset($http_response_header)) {
            $httpResponseHeader = $http_response_header;
        }

        return array($httpResponseHeader, $responseData);
    }

	/**
	 * Creates a request signature
	 *
	 * @return string
	 * @throws PacNet_Raven_Model_Api_Service_Request_Exception
	 */
	protected function createSignature()
    {
        $data = '';
        foreach($this->_signatureParams as $param) {
            $data .= $this->get($param);
        }
        return hash_hmac("sha1", $data, $this->getSecret());
    }

	/**
	 * Creates a request reference no.
	 *
	 * @return string
	 */
	protected function createRequestID()
    {
        return uniqid('RQ');
    }

	/**
	 * Gets object data
	 *
	 * @param bool $toString
	 * @param bool $secure
	 *
	 * @return array|string
	 */
	public function getData($toString=false, $secure=false)
    {
        $configData                  = array();
        $configData['RAPIVersion']   = self::RAPI_VERSION;
        $configData['RAPIInterface'] = self::RAPI_INTERFACE;
        $configData['UserName']      = $this->getUserName();
        $configData['Timestamp']     = $this->getTimestamp();
        $configData['RequestID']     = $this->getRequestID();
        $configData['Signature']     = $this->getSignature();
        $configData['PRN']           = $this->getPRN();
        $data                        = array_merge($configData, $this->_data);

	    if ($secure) {
		    if (isset($data['CardNumber'])) $data['CardNumber'] = $this->maskCreditCard($data['CardNumber']);
		    if (isset($data['CVV2'])) $data['CVV2'] = str_repeat('X',strlen($data['CVV2']));
	    }

        if ($toString) {
            $data = '&' . http_build_query($data);
        }

        return $data;
    }

	/**
	 * Masks credit card no.
	 *
	 * Replaces all but the last for digits with x's in the given credit card number
	 *
	 * @param int|string $cc The credit card number to mask
	 * @return string The masked credit card number
	 */
	public function maskCreditCard($cc){
		$ccLength = strlen($cc);
		for($i=0; $i<$ccLength-4; $i++){
			if($cc[$i] == '-'){continue;}
			$cc[$i] = 'X';
		}
		return $cc;
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
	 */
	public function setUserName($userName)
    {
        $this->_userName = $userName;
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
	 * Gets the API secret
	 *
	 * @param $secret
	 */
	public function setSecret($secret)
    {
        $this->_secret = $secret;
    }

	/**
	 * Gets the API payment routing number
	 *
	 * @return mixed
	 */
	public function getPRN()
    {
        return $this->_PRN;
    }

	/**
	 * Gets the API payment routing number
	 *
	 * @param $PRN
	 *
	 * @return $this
	 */
	public function setPRN($PRN)
    {
        $this->_PRN = $PRN;
        return $this;
    }

	/**
	 * Gets the request id/reference no.
	 *
	 * @return string
	 */
	public function getRequestID()
    {
        if (null==$this->_requestID) {
            $this->_requestID = $this->createRequestID();
        }
        return $this->_requestID;
    }

	/**
	 * Sets the request id/reference no.
	 *
	 * @param $requestID
	 */
	public function setRequestID($requestID)
    {
        $this->_requestID = $requestID;
    }

	/**
	 * Gets the timestamp
	 *
	 * Returns current timestamp if none present
	 *
	 * @return string
	 */
	public function getTimestamp()
    {
        if (null == $this->_timestamp) {
            $this->_timestamp = gmdate('Y-m-d\TH:i:s.000\Z');
        }

        return $this->_timestamp;
    }

	/**
	 * Gets the request signature
	 *
	 * @return string
	 */
	public function getSignature()
    {
        if (null === $this->_signature) {
            $this->_signature = $this->createSignature();
        }

        return $this->_signature;
    }

	/**
	 * Get magic method
	 *
	 * @param $key
	 *
	 * @return mixed
	 * @throws PacNet_Raven_Model_Api_Service_Request_Exception
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
	 * @return PacNet_Raven_Model_Api_Service_Request_Abstract
	 * @throws PacNet_Raven_Model_Api_Service_Request_Exception
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
	 * @return mixed
	 * @throws PacNet_Raven_Model_Api_Service_Request_Exception
	 */
	public function get($key)
    {
        $method = 'get' . $key;

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        if (!array_key_exists($key, $this->_data)) {
            throw new PacNet_Raven_Model_Api_Service_Request_Exception("Trying to get value ($key) that doesn't exist");
        }

        return $this->_data[$key];
    }

	/**
	 * Global set
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return $this
	 * @throws PacNet_Raven_Model_Api_Service_Request_Exception
	 */
	public function set($key, $value)
    {
        if (!$value) return $this;

        $method = 'set' . $key;

        if (method_exists($this, $method)) {
            return $this->$method($value);
        }
        if (!in_array($key, $this->_dataKeys)) {
            throw new PacNet_Raven_Model_Api_Service_Request_Exception("Trying to set value ($key) that doesn't exist");
        }

        $this->_data[$key] = $value;

        return $this;
    }

	/**
	 * Call magic method
	 *
	 * @param $name
	 * @param $args
	 *
	 * @return PacNet_Raven_Model_Api_Service_Request_Abstract|mixed
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
                return $this->set($key, $args[0]);
                break;
            default:
                throw new PacNet_Raven_Model_Api_Service_Request_Exception("Method '$name' does not exist");
                break;
        }
    }
} 
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
 * Raven API payment service
 */
class PacNet_Raven_Model_Api_Service
{
	/**
	 * Raven service config
	 *
	 * @var array|null
	 */
	protected $_config;

	/**
	 * Enable/disable config
	 *
	 * @var bool
	 */
	protected $_debug;

	/**
	 * Raven service config
	 *
	 * @var string
	 */
	protected $_debugFile = 'raven_api.log';

	/**
	 * Constructor.
	 *
	 * @param $config
	 * @param bool $debug
	 */
	public function __construct($config, $debug=false)
    {
        $this->_setConfig($config);
        $this->_debug = $debug;
    }

	/**
	 * Sets config
	 *
	 * @param $config
	 */
	protected function _setConfig($config)
    {
        $this->_config = $config;
    }

	/**
	 * Gets config
	 *
	 * @return mixed
	 * @throws PacNet_Raven_Model_Api_Service_Request_Exception
	 */
	protected function _getConfig()
    {
        if (null === $this->_config) {
            throw new PacNet_Raven_Model_Api_Service_Request_Exception('Config could not be found!');
        }

        return $this->_config;
    }

	/**
	 * Makes a debit payment
	 *
	 * @param Varien_Object $payment
	 * @param $amount
	 * @param null $description
	 *
	 * @return PacNet_Raven_Model_Api_Service_Response
	 * @throws PacNet_Raven_Model_Api_Service_Request_Exception
	 */
	public function debit(Varien_Object $payment, $amount, $description=null)
    {
        if($this->_debug) {
            Mage::log('Initiating debit service', null, $this->_debugFile);
        }
		
        
        $amount = $amount * 100;
        $ravenSubmitRequest = new PacNet_Raven_Model_Api_Service_Request_Submit($this->_getConfig());

        $ravenSubmitRequest->setPaymentType(PacNet_Raven_Model_Api_Service_Request_Submit::CC_DEBIT)
            ->setAmount($amount)
            ->setDescription($description);
            //->setComment();

        if($payment->getCcNumber()) {
            $CcExpYear = substr($payment->getCcExpYear(), 2, 2);

            $ravenSubmitRequest->setCardNumber($payment->getCcNumber())
                ->setCVV2($payment->getCcCid())
                ->setExpiryDate(sprintf('%02d%02d', $payment->getCcExpMonth(), $CcExpYear))
                ->setIssueNumber($payment->getCcSsIssue());
        }

        if ($order = $payment->getOrder()) {
            $ravenSubmitRequest->setCurrency($order->getBaseCurrencyCode())
                ->setReference($order->getIncrementId())
                ->setContactEmail($order->getCustomerEmail());

            if ($billing = $order->getBillingAddress()) {
                $ravenSubmitRequest->setAccountName($billing->getFirstname() . ' ' . $billing->getLastname())
                    ->setBillingAddressLine1($billing->getStreet(1))
                    ->setBillingAddressLine2($billing->getStreet(2))
                    ->setBillingCity($billing->getCity())
                    ->setBillingRegion($billing->getRegion())
                    ->setBillingPostal($billing->getPostcode())
                    ->setBillingCountry($billing->getCountry())
                    ->setCustomerPhone($billing->getTelephone())
                    ->set('CustomerIP',Mage::helper('core/http')->getRemoteAddr($ipToLong = false));
//		            ->set('CustomerIP',$order->getRemoteAddr());
//                    Mage::log($ravenSubmitRequest);
            }

            if ($shipping = $order->getShippingAddress()) {
                $ravenSubmitRequest->setShippingAddressLine1($shipping->getStreet(1))
                    ->setShippingAddressLine2($shipping->getStreet(2))
                    ->setShippingCity($shipping->getCity())
                    ->setShippingRegion($shipping->getRegion())
                    ->setShippingPostal($shipping->getPostcode())
                    ->setShippingCountry($shipping->getCountry());
            }
        }

        if($this->_debug) {
            Mage::log('Request Data: ' . print_r($ravenSubmitRequest->getData(false, true),true) , null, $this->_debugFile);
        }

        $ravenSubmitResponse = $ravenSubmitRequest->send();

        if($this->_debug) {
            Mage::log('Response String: ' . print_r($ravenSubmitResponse->getRavenResponseString(), true), null, $this->_debugFile);
            Mage::log('Response Data: ' .  print_r($ravenSubmitResponse->getRavenResponseData(), true), null, $this->_debugFile);
        }

        return $ravenSubmitResponse;
    }

	/**
	 * Makes a recurring payment
	 *
	 * @param $templateNo
	 * @param Varien_Object $payment
	 * @param $amount
	 * @param null $description
	 *
	 * @return PacNet_Raven_Model_Api_Service_Response
	 * @throws PacNet_Raven_Model_Api_Service_Request_Exception
	 */
	public function reccurring($templateNo, Varien_Object $payment, $amount, $description=null)
    {
        if($this->_debug) {
            Mage::log('Initiating reccuring service', null, $this->_debugFile);
        }

        $amount = $amount * 100;
        $ravenSubmitRequest = new PacNet_Raven_Model_Api_Service_Request_Submit($this->_getConfig());

        $ravenSubmitRequest->setPaymentType(PacNet_Raven_Model_Api_Service_Request_Submit::CC_DEBIT)
            ->setTemplateNumber($templateNo)
            ->setAmount($amount)
            ->setDescription($description);
            //->setComment();

        if ($order = $payment->getOrder()) {
            $ravenSubmitRequest->setCurrency($order->getBaseCurrencyCode())
                ->setReference($order->getIncrementId())
                ->setContactEmail($order->getCustomerEmail());

            if ($billing = $order->getBillingAddress()) {
                $ravenSubmitRequest->setAccountName($billing->getFirstname() . ' ' . $billing->getLastname())
                    ->setBillingAddressLine1($billing->getStreet(1))
                    ->setBillingAddressLine2($billing->getStreet(2))
                    ->setBillingCity($billing->getCity())
                    ->setBillingRegion($billing->getRegion())
                    ->setBillingPostal($billing->getPostcode())
                    ->setBillingCountry($billing->getCountry())
                    ->setCustomerPhone($billing->getTelephone());
            }

            if ($shipping = $order->getShippingAddress()) {
                $ravenSubmitRequest->setShippingAddressLine1($shipping->getStreet(1))
                    ->setShippingAddressLine2($shipping->getStreet(2))
                    ->setShippingCity($shipping->getCity())
                    ->setShippingRegion($shipping->getRegion())
                    ->setShippingPostal($shipping->getPostcode())
                    ->setShippingCountry($shipping->getCountry());
            }
        }

        if($this->_debug) {
            Mage::log('Request Data: ' . print_r($ravenSubmitRequest->getData(),true) , null, $this->_debugFile);
        }

        $ravenSubmitResponse = $ravenSubmitRequest->send();

        if($this->_debug) {
            Mage::log('Response String: ' . print_r($ravenSubmitResponse->getRavenResponseString(), true), null, $this->_debugFile);
            Mage::log('Response Data: ' .  print_r($ravenSubmitResponse->getRavenResponseData(), true), null, $this->_debugFile);
        }

        return $ravenSubmitResponse;
    }

	/**
	 * Makes a pre-authorised payment
	 * @todo To be implemented
	 */
	public function preauth()
    {

    }

	/**
	 * Settles a pre-authorised payment
	 * @todo To be implemented
	 */
	public function settle()
    {

    }

	/**
	 * Voids a payment
	 * @todo To be implemented
	 */
	public function void()
    {

    }

	/**
	 * Refund a payment
	 * @todo To be implemented
	 */
	public function refund()
    {

    }

	/**
	 * Makes a hello request
	 * @todo To be implemented
	 */
	public function hello()
    {

    }

	/**
	 * Gets a Raven response
	 *
	 * @param $requestID
	 *
	 * @return mixed
	 * @throws PacNet_Raven_Model_Api_Service_Request_Exception
	 */
	public function getResponse($requestID)
    {
        $ravenResponseRequest = new PacNet_Raven_Model_Api_Service_Response($this->_getConfig());
        $ravenResponseRequest->setRequestID($requestID);

        try {
            $response = $ravenResponseRequest->send();
        } catch (PacNet_Raven_Model_Api_Service_Request_Exception $e) {
            //throw caught exception
        }

        return $response;

    }

	/**
	 * Format amount for Raven api
	 *
	 * @param $amount
	 * @param bool $asFloat
	 *
	 * @return float|string
	 */
	protected function _formatAmount($amount, $asFloat = false)
    {
        $amount = Mage::app()->getStore()->roundPrice($amount);
        return !$asFloat ? (string)$amount : $amount;
    }
} 

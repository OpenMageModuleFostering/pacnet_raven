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
 * Raven API payment method
 */
class PacNet_Raven_Model_Api extends Mage_Payment_Model_Method_Cc
{
    protected $_code                    = 'pacnet_raven_api';
    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canRefundInvoicePartial = false;
    protected $_canVoid                 = true;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = true;
    protected $_canSaveCc               = false;

    protected $_ravenConfig;
    protected $_ravenService;
    protected $_trackingNumber;

	/**
	 * Assigns data to payment method
	 *
	 * Tracking number added for repeat payments
	 *
	 * @param mixed $data
	 *
	 * @return $this
	 */
	public function assignData($data)
    {
        $this->setTrackingNumber($data->getPacnetTrackingNumber());
        parent::assignData($data);
        return $this;
    }

	/**
	 * Validates payment method
	 *
	 * @return $this
	 */
	public function validate()
    {
        if (!$this->hasTrackingNumber()) {
            parent::validate();
        }
        return $this;
    }

	/**
	 * Captures payment
	 *
	 * @param Varien_Object $payment
	 * @param float $amount
	 *
	 * @return $this
	 * @throws Mage_Core_Exception
	 */
	public function capture(Varien_Object $payment, $amount)
    {
        $debug = $this->getConfigData('debug') ? $this->getConfigData('debug') : false;
        $ravenService = new PacNet_Raven_Model_Api_Service($this->getRavenConfig(), $debug);

        $ravenResponse = $this->hasTrackingNumber() ?
            $ravenService->reccurring($this->getTrackingNumber(), $payment, $amount) :
            $ravenService->debit($payment, $amount);

		$errorMsg = null;
			
        if (!$ravenResponse) {
            $errorMsg = $this->_getHelper()->__('There was an error processing payment');
        } else {

            if ('500' == $ravenResponse->getHttpStatus()) {
                //$errorMsg = $this->_getHelper()->__('There was an error processing payment at the payment gateway');
            }

            if ('Approved' == $ravenResponse->getStatus()) {
                $payment->setTransactionId($ravenResponse->getRequestID())
                    ->setIsTransactionClosed(1)
                    ->setTransactionAdditionalInfo(
                        Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                        $ravenResponse->getTransactionAdditionalInfo()
                    );
            } else {
                $errorMsg = $this->_getHelper()->__('Payment was not approved');
            }
        }

        if ($errorMsg) {
            Mage::throwException($errorMsg);
        }

        return $this;
    }

	/**
	 * Voids payment
	 *
	 * @param Varien_Object $payment
	 *
	 * @return Mage_Payment_Model_Abstract
	 */
	public function void(Varien_Object $payment)
    {
        return parent::void($payment);
    }

	/**
	 * Gets the Raven config
	 *
	 * @return array
	 */
	public function getRavenConfig()
    {
        if (null === $this->_ravenConfig) {
            $this->_ravenConfig = array(
                'UserName' => $this->getConfigData('username'),
                'Secret' => $this->getConfigData('shared_secret'),
                'PRN' => $this->getConfigData('prn'),
                'RequestID' => $this->_createRavenRequestID()
            );
        }

        return $this->_ravenConfig;
    }

	/**
	 * Gets Raven service
	 *
	 * @return PacNet_Raven_Model_Api_Service
	 */
	public function getRavenService()
    {
        if (null === $this->_ravenService) {
            $this->_ravenService = new PacNet_Raven_Model_Api_Service($this->getRavenConfig());
        }

        return $this->_ravenService;
    }


	/**
	 * Creates a unique Raven reference no.
	 *
	 * @return string
	 */
	protected function _createRavenRequestID()
    {
        return uniqid('RQ');
    }

	/**
	 * Gets tracking no.
	 *
	 * @return mixed
	 */
	public function getTrackingNumber()
    {
        if (!$this->_trackingNumber) {
            $this->_trackingNumber = $this->getInfoInstance()->getPacnetTrackingNumber();
        }
        return $this->_trackingNumber;
    }

	/**
	 * Sets tracking no.
	 *
	 * @param $trackingNumber
	 */
	public function setTrackingNumber($trackingNumber)
    {
        $this->_trackingNumber = $trackingNumber;
    }

	/**
	 * Check if payment uses a tracking no.
	 *
	 * @return bool
	 */
	public function hasTrackingNumber()
    {
        return $this->getTrackingNumber() ? true : false;
    }
}
?>
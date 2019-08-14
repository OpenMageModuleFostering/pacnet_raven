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
 * Raven MarketDirect payment response model
 */
class PacNet_Raven_Model_MarketDirect_Response extends PacNet_Raven_Model_MarketDirect_Abstract
{
	/**
	 * Constructor
	 *
	 * @param $response
	 * @param $order
	 */
	public function __construct($response)
	{
		$this->setData($response);

		$orderIncrementId = $this->getOrderId();
		$order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);

		$this->setOrder($order);

		if ($this->_getPaymentConfigData('debug')) {
			Mage::log('Raven MarketDirect response data: ' . print_r($response, true) , null, $this->_logFile);
		}
	}

	/**
	 * Checks if response is valid
	 *
	 * @return bool
	 * @throws Mage_Core_Exception
	 */
	public function isValid()
	{
		if (! $this->_isValidSignature()) {
			throw Mage::exception('PacNet_Raven',
				Mage::helper('pacnet_raven')->__('Invalid signature from Raven MarketDirect response'));
		}

		if (! $this->_checkAmountPaid()) {
			throw Mage::exception('PacNet_Raven',
				Mage::helper('pacnet_raven')->__('Invalid payment amount received from Raven MarketDirect response'));
		}

		if (! $this->_isValidPaymentType()) {
			throw Mage::exception('PacNet_Raven',
				Mage::helper('pacnet_raven')->__('Invalid payment type in Raven MarketDirect response'));
		}

		return true;
	}

	/**
	 * Is transaction approved
	 *
	 * @return bool
	 */
	public function isApproved()
	{
		return 'Approved' == $this->getData('md_status') ? true : false;
	}

	/**
	 * Gets the order Id
	 *
	 * @return mixed
	 */
	public function getOrderId()
	{
		return $this->getData('md_reference2');
	}

	/**
	 * Gets amount paid
	 *
	 * Formats to Magento invoice amount
	 *
	 * @return float
	 */
	public function getAmountPaid()
	{
		return $this->_formatInvoiceAmount($this->getData('md_amount'));

	}

	/**
	 * Gets credit card type
	 *
	 * @return mixed
	 */
	public function getCcType()
	{
		return self::$ccTypes[$this->getData('md_card_scheme')];
	}

	/**
	 * Gets the payment type
	 *
	 * @return mixed
	 */
	public function getPaymentType()
	{
		return $this->getData('md_reference3');
	}

	/**
	 * Generates a Raven MarketDirect response signature
	 *
	 * @return bool
	 */
	protected function _generateRavenSignature() {
		$first = $this->getData('md_submitter') . ',' . $this->getData('md_timestamp') . ','
		         . $this->getData('md_amount') . ',' . $this->getData('md_currency') . ','
		         . $this->getData('md_reference');
		$second = strtoupper(sha1($first));
		$third = $second . ',' . $this->_getPaymentConfigData('shared_secret');
		$final = strtoupper(sha1($third));
		return $final == $this->getData('md_signature') ? true : false;
	}

	/**
	 * Check for a valid signature
	 *
	 * @return bool
	 */
	protected function _isValidSignature()
	{
		return $this->_generateRavenSignature() == $this->getData('md_signature') ? true : false;
	}

	/**
	 * Checks for a valid payment type
	 *
	 * @return bool
	 */
	protected function _isValidPaymentType()
	{
		return in_array($this->getPaymentType(), $this->_acceptedPaymentTypes);
	}

	/**
	 * Checks response amount is valid
	 *
	 * @return bool
	 */
	protected function _checkAmountPaid()
	{
		return $this->getData('md_amount') == $this->_formatAmount($this->getOrder()->getGrandTotal()) ? true : false;
	}
}
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
 * Raven MarketDirect payment abstract model
 */
class PacNet_Raven_Model_MarketDirect_Abstract extends Varien_Object
{
	/**
	 * Raven payment types
	 */
	const RAVEN_PAYMENT_TYPE_DEBIT = 'debit';
	const RAVEN_PAYMENT_TYPE_PREAUTH = 'preauth';

	/**
	 * Sales order
	 *
	 * @var Mage_Sales_Model_Order
	 */
	protected $_order;

	/**
	 * Flag to use order currency instead of base currency
	 *
	 * @var bool
	 */
	protected $_useOrderCurrency = false;

	/**
	 * Name of log file
	 *
	 * @var string
	 */
	protected $_logFile = 'raven_marketdirect.log';


	/**
	 * Credit card types available
	 *
	 * @var array
	 */
	static public $ccTypes = array (
		'DC' => 'Diners Club/Carte Blanche',
		'DV' => 'Discover',
		'ER' => 'EnRoute',
		'GE' => 'GE Capital',
		'JB' => 'JCB',
		'JC' => 'Laser',
		'MA' => 'Maestro',
		'MC' => 'MasterCard/Eurocard',
		'MD' => 'MasterCard/Eurocard Debit',
		'SO' => 'Solo',
		'SW' => 'Switch',
		'VD' => 'Visa Debit',
		'VE' => 'Visa Electron',
		'VI' => 'Visa',
		'VP' => 'Visa Purchase'
	);

	/**
	 * Accepted payment types
	 *
	 * @var array
	 */
	protected $_acceptedPaymentTypes = array (
		self::RAVEN_PAYMENT_TYPE_DEBIT, self::RAVEN_PAYMENT_TYPE_PREAUTH
	);

	/**
	 * Gets sales order
	 *
	 * @return Mage_Sales_Model_Order
	 */
	public function getOrder()
	{
		return $this->_order;
	}

	/**
	 * Sets sales order
	 *
	 * @param mixed $order
	 *
	 * @return $this
	 */
	public function setOrder($order)
	{
		$this->_order = $order;
		return $this;
	}

	/**
	 * Checks if order currency can be used to make payment
	 *
	 * @return bool
	 */
	public function canUseOrderCurrency()
	{
		return $this->_useOrderCurrency;
	}

	/**
	 * Gets payment config data
	 *
	 * @param $field
	 * @param null $storeId
	 *
	 * @return mixed
	 */
	protected function _getPaymentConfigData($field, $storeId = null)
	{
		return $this->getOrder()->getPayment()->getMethodInstance()->getConfigData($field, $storeId);
	}

	/**
	 * Formats Raven MarketDirect amount ready for invoicing
	 *
	 * @param $amount
	 *
	 * @return float
	 */
	protected function _formatInvoiceAmount($amount)
	{
		return $amount / 100;
	}

	/**
	 * Formats amount for Raven MarketDirect
	 *
	 * @param $amount
	 *
	 * @return float
	 */
	protected function _formatAmount($amount, $asFloat=false)
	{
		$amount = Mage::app()->getStore()->roundPrice($amount) * 100;
		return !$asFloat ? (string)$amount : $amount;
	}
}
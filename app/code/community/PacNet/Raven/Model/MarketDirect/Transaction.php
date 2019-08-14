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
 * Raven MarketDirect payment transaction model
 */
class PacNet_Raven_Model_MarketDirect_Transaction extends PacNet_Raven_Model_MarketDirect_Abstract
{
	/**
	 * Raven post URI for payments
	 */
	const RAVEN_PAYMENT_URI = 'https://raven.pacnetservices.com/payment';

	/**
	 * Constructor
	 *
	 * @param Mage_Sales_Model_Order $order
	 */
	public function __construct(Mage_Sales_Model_Order $order)
	{
		$this->setOrder($order);
	}

	/**
	 * Sets an order
	 *
	 * @param Mage_Sales_Model_Order $order
	 *
	 * @return $this
	 */
	public function setOrder(Mage_Sales_Model_Order $order)
	{
		parent::setOrder($order);
		$this->_exchangeOrderData($this->_order);
		return $this;
	}

	/**
	 * Exchange data from order to Raven MarketDirect transaction
	 */
	protected function _exchangeOrderData()
	{
		$order = $this->_order;

		$this->_getPaymentConfigData('active');
		$this->_getPaymentConfigData('debug');

		$this->setPaymentTitle($this->_getPaymentConfigData('title'))
			->setSubmitter($this->_getPaymentConfigData('submitter'))
			->setSharedSecret($this->_getPaymentConfigData('shared_secret'))
			->setRouting($this->_getPaymentConfigData('prn'))
			->setPaymentType($this->_getPaymentConfigData('payment_type'))
			->setEmailReceipt($this->_getPaymentConfigData('email_receipt') ? 'yes' : 'no')
			->setTitle($this->_getPaymentConfigData('custom_title'))
			->setColor($this->_getPaymentConfigData('custom_colour'))
			->setLogoPath(Mage::getBaseDir('media') . '/pacnet/' . $this->_getPaymentConfigData('custom_logo'))
			->setGoogleAnalytics($this->_getPaymentConfigData('custom_google_analytics'));

		if ($this->_getPaymentConfigData('enable_fulfillment_notification')
		    && $this->_getPaymentConfigData('fulfillment_url')) {
            $this->setFulFillmentUrl($this->_getPaymentConfigData('fulfillment_url'))
				->setRetryFulfillmentNotification($this->_getPaymentConfigData('retry_fulfillment_notification'));
		}

		if ($order) {
			if ($this->canUseOrderCurrency()) {
				$this->setAmount($this->_formatAmount($order->getGrandTotal()))
				     ->setCurrency($order->getOrderCurrencyCode());
			} else {
				$this->setAmount($this->_formatAmount($order->getBaseGrandTotal()))
				     ->setCurrency($order->getBaseCurrencyCode());
			}

			$this->setReference2($order->getIncrementId())
				 ->setReference3($this->_getPaymentConfigData('payment_type'))
                 ->setContactEmail($order->getCustomerEmail())
			     ->setIpaddress($order->getRemoteIp());

			if ($billing = $order->getBillingAddress()) {
				$this->setBillingName($billing->getFirstname() . ' ' . $billing->getLastname())
				     ->setBillingAddress1($billing->getStreet(1))
				     ->setBillingAddress2($billing->getStreet(2))
				     ->setBillingCity($billing->getCity())
				     ->setBillingState($billing->getRegion() ? $billing->getRegion() : $billing->getCity())
				     ->setBillingPostal($billing->getPostcode())
				     ->setBillingCountry($billing->getCountry())
				     ->setBillingPhone($billing->getTelephone())
				     ->setBillingFax($billing->getFax());
			}

			if ($shipping = $order->getShippingAddress()) {
				$this->setShippingName($shipping->getFirstname() . ' ' . $shipping->getLastname())
				     ->setShippingCompany($shipping->getCompany())
				     ->setShippingAddress1($shipping->getStreet(1))
				     ->setShippingAddress2($shipping->getStreet(2))
				     ->setShippingCity($shipping->getCity())
				     ->setShippingState($shipping->getRegion() ? $shipping->getRegion() : $shipping->getCity())
				     ->setShippingPostal($shipping->getPostcode())
				     ->setShippingCountry($shipping->getCountry());
			}

			$count = 0;
			/** @var Mage_Sales_Model_Order_Item $item */
			foreach ($order->getAllVisibleItems() as $item) {
				$count++;
				$this->setData('detail_item_' . $count, $item->getName())
					->setData('detail_cost_' . $count, $this->_formatAmount($item->getPriceInclTax()))
					->setData('detail_qty_'. $count, $item->getQtyOrdered());
			}
		}

		// other settings
		$this->addData(array(
			'collect_shipping' => 'Display',
			'collect_billing' => 'Display',
			'collect_email' => 'Display',
			'language' => $this->_getMageLanguage(),
			'result_url' => Mage::getUrl('pacnet-raven/marketdirect/response', array('_secure' => true))
		));
	}

	/**
	 * Gets langauge from Mage locale
	 *
	 * @return mixed
	 */
	protected function _getMageLanguage() {
		$locale = Mage::app()->getLocale()->getLocaleCode();
		$localeParts = explode('_', $locale);
		return $localeParts[0];
	}

	/**
	 * Convert object data to array
	 *
	 * @param array $arrAttributes
	 *
	 * @return array
	 */
	public function toArray( array $arrAttributes = array() )
	{
		$data = parent::toArray( $arrAttributes );

		$newData = array();

		foreach ($data as $key => $value) {
			if (!$value) continue;
			if ('payment_title' == $value) continue;
			$newKey = substr( $key, 0, 3 ) === 'md_' ? $key : 'md_' . $key;
			$newData[$newKey] = $value;
		}

		return $newData;
	}

	/**
	 * Generate inputs for submission
	 *
	 * @param array|null $data
	 */
	public function generateInputs(array $data=null)
	{
		if (!$data) {
			$data = $this->toArray();
		}

		$container = array();
		foreach ($data as $key => $value) {
			$container[ltrim($key,"*")] = $value;
		}
		if (empty($container['md_timestamp'])) {
			$container['md_timestamp'] = $this->_generateTimestamp();
		}
		if (empty($container['md_reference'])) {
			$container['md_reference'] = $this->_generateReference();
		}
		if (empty($container['md_signature'])) {
			$container['md_signature'] = $this->_generateSignature($container);
		}

		if (!empty($container['md_logo_path'])) {
			$imgData = $this->_readImage( $container['md_logo_path']);
			if ($imgData != null) {
				$container['md_logo_image'] = $imgData;
			}
			unset($container['md_logo_path']);
		}
		unset($container['md_shared_secret']);

		if ($this->_getPaymentConfigData('debug')) {
			Mage::log('Raven MarketDirect request data: ' . print_r($container, true) , null, $this->_logFile);
		}

		$formFields = '';
		while (list($key, $val) = each($container)) {
			$formFields .= "<input type=\"hidden\" name=\"$key\" value=\"$val\"/>\r\n";
		}
		return $formFields;
	}

	/**
	 * Generates a payment timestamp
	 *
	 * @return string
	 */
	private function _generateTimestamp ()
	{
		return gmdate('Y-m-d\TH:i:s\Z');
	}

	/**
	 * Generates a payment reference
	 *
	 * @return string
	 */
	private function _generateReference()
	{
		return md5(uniqid(rand(),true));
	}

	/**
	 * Generates a payment signature
	 *
	 * @param $data
	 *
	 * @return string
	 */
	private function _generateSignature($data)
	{
		$first = $data['md_submitter'] . ',' . $data['md_timestamp'] . ','
		         . $data['md_amount'] . ',' . $data['md_currency'] . ',' . $data['md_reference'];
		$second = strtoupper(md5($first));
		$third = $second . ',' . $data['md_shared_secret'];
		$final = strtoupper(md5($third));
		return $final;
	}

	/**
	 * Converts image file to Raven compatible image data
	 *
	 * Data is used to populate the md_logo_image field
	 *
	 * @param $file
	 *
	 * @return null|string
	 */
	private function _readImage($file)
	{
		if(!file_exists($file)) return null;
		$mime = $this->_getMIME( $file);
		if($mime == null) return null;
		$data = base64_encode(file_get_contents($file));
		return $mime . "," . $data;
	}

	/**
	 * Gets the MIME type of an image
	 *
	 * @param $filename
	 *
	 * @return null|string
	 */
	private function _getMIME($filename)
	{
		preg_match("|\.([a-z0-9]{2,4})$|i",$filename,$fileSuffix);
		switch(strtolower($fileSuffix[1])) {
			case "jpg" :
			case "jpeg" :
			case "jpe" :
				return "image/jpg";
			case "png" :
			case "gif" :
				return "image/".strtolower($fileSuffix[1]);
			default :
				return null;
		}
	}
}
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
 * Raven MarketDirect payment method
 */
class PacNet_Raven_Model_MarketDirect extends Mage_Payment_Model_Method_Abstract
{
	protected $_code = 'pacnet_raven_marketdirect';

	protected $_isGateway               = false;
	protected $_canAuthorize            = true;
	protected $_canCapture              = true;
	protected $_canVoid                 = false;
//	protected $_isInitializeNeeded      = true;
	protected $_canUseInternal          = false;
	protected $_canUseForMultishipping  = false;

	protected $_formBlockType = 'pacnet_raven/MarketDirect_Form';
	protected $_infoBlockType = 'pacnet_raven/MarketDirect_Info';

	/**
	 * Return Order place redirect url
	 *
	 * @return string
	 */
	public function getOrderPlaceRedirectUrl()
	{
		return Mage::getUrl('pacnet-raven/marketdirect/redirect', array('_secure' => true));
	}

	/**
	 * Instantiate state and set it to state object
	 *
	 * @param string $paymentAction
	 * @param Varien_Object $stateObject
	 */
	public function initialize($paymentAction, $stateObject)
	{
		return parent::initialize($paymentAction, $stateObject);
	}
}
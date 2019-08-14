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
 * Raven MarketDirect controller
 */
class PacNet_Raven_MarketdirectController extends Mage_Core_Controller_Front_Action
{
	/**
	 * Current Magento order
	 *
	 * @var Mage_Sales_Model_Order
	 */
	protected $_currentOrder;

	/**
	 * Payment gateway response action
	 *
	 * @return $this
	 */
	public function redirectAction()
	{
		if (!$order = $this->_getCurrentOrder()) {
			$this->_redirect('checkout/cart');
		} else {
			/** @var PacNet_Raven_Model_MarketDirect_Transaction $transaction */
			$transaction = Mage::getModel('pacnet_raven/MarketDirect_Transaction', $order);

			$this->loadLayout();
			/** @var Mage_Core_Block_Template $block */
			$block = $this->getLayout()->createBlock(
				'Mage_Core_Block_Template',
				'pacnet_raven_marketdirect_form',
				array(
					'template'          => 'pacnet/raven/marketdirect/redirect.phtml',
					'raven_transaction' => $transaction,
					'form_data'         => $transaction->generateInputs()
				)
			)->setCacheLifetime(null);
			$this->getLayout()->getBlock('content')->append($block);
			$this->renderLayout();
		}
	}

	/**
	 * Payment gateway response action
	 *
	 * @throws Exception
	 */
	public function responseAction()
	{
		$request = $this->getRequest();

		if($request->isPost()) {
			$paymentResponse = $request->getPost();
			$marketDirectResponse = Mage::getModel('pacnet_raven/MarketDirect_Response', $paymentResponse);

			try {
				if ($marketDirectResponse->isValid()) {
					if ($marketDirectResponse->isApproved()) {
						$payment = $marketDirectResponse->getOrder()->getPayment();
						$payment->setTransactionId($marketDirectResponse->getData('md_reference'))
						        ->setCurrencyCode($marketDirectResponse->getData('md_currency'))
						        ->setParentTransactionId($marketDirectResponse->getData('md_reference'))
						        ->setCcType($marketDirectResponse->getCcType());

						switch ($marketDirectResponse->getPaymentType()) {
							case 'debit':
								$payment
									//->setPreparedMessage('This message is prepended to the captured amount')
									->setShouldCloseParentTransaction(true)
									->setIsTransactionClosed(1)
									->registerCaptureNotification($marketDirectResponse->getAmountPaid());
								break;

							case 'preauth':
								$payment
									//->setPreparedMessage('This message is prepended to the captured amount')
									->setShouldCloseParentTransaction(true)
									->setIsTransactionClosed(0)
									->registerAuthorizationNotification($marketDirectResponse->getAmountPaid());
								break;
						}

						$marketDirectResponse->getOrder()
						                     ->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Raven MarketDirect has authorized the payment.')
						                     ->sendNewOrderEmail()
						                     ->setEmailSent(true)
						                     ->save();

						Mage::getSingleton( 'checkout/session' )->unsQuoteId();
						Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure' => true));
					} else {
						$this->cancelAction();
						Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure' => true));
					}
				}
			} catch (Exception $e) {
				Mage::logException($e);
				$this->_redirect('/');
			}
		}
		else
			$this->_redirect('/');
	}

	/**
	 * Payment cancelled action
	 *
	 * @throws Exception
	 */
	public function cancelAction() {
		if (Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
			$order = $this->_getCurrentOrder();
			if($order->getId()) {
				$order->cancel()->setState(
					Mage_Sales_Model_Order::STATE_CANCELED,
					true,
					'Gateway has declined the payment.'
				)->save();
			}
		}
	}

	/**
	 * Gets current order
	 *
	 * @param $orderIncrementId
	 *
	 * @return Mage_Sales_Model_Order
	 */
	protected function _getCurrentOrder($orderIncrementId=null)
	{
		if (null == $this->_currentOrder) {
			$orderIncrementId = $orderIncrementId
				? $orderIncrementId
				: Mage::getSingleton('checkout/session')->getLastRealOrderId();

			$this->_currentOrder = $orderIncrementId
				? Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId)
				: null;
		}

		return $this->_currentOrder;
	}
}
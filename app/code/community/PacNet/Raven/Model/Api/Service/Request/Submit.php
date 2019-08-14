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
 * Raven API submit service request
 */
class PacNet_Raven_Model_Api_Service_Request_Submit extends PacNet_Raven_Model_Api_Service_Request_Abstract
{
	/**
	 * Raven API request types
	 */
	const CC_DEBIT   = 'cc_debit';
    const CC_REFUND  = 'cc_refund';
    const CC_CREDIT  = 'cc_credit';
    const CC_OCT     = 'cc_oct';
    const CC_PREAUTH = 'cc_preauth';
    const CC_SETTLE  = 'cc_settle';
    const CC_UPDATE  = 'cc_update';
    const CC_VERIFY  = 'cc_verify';

	/**
	 * Name of service
	 *
	 * @var string
	 */
	protected $_serviceName = 'submit';

	/**
	 * Signature parameters
	 *
	 * @var array
	 */
	protected $_signatureParams = array (
        'UserName',
        'Timestamp',
        'RequestID',
        'PaymentType',
        'Amount',
        'Currency'
    );

	/**
	 * Allowed request properties
	 *
	 * @var array
	 */
	protected $_dataKeys = array (
        'PRN',
        'Currency',
        'AccountName',
        'CardNumber',
        'CVV2',
        'IssueNumber',
        'PaymentType',
        'ExpiryDate',
        'Amount',
        'Reference',
        'Reference2',
        'Reference3',
        'Reference4',
        'Reference5',
        'Reference6',
        'Reference7',
        'Reference8',
        'Reference9',
        'Reference10',
        'PreauthNumber',
        'TemplateNumber',
        'Description',
        'Description2',
        'Comment',
        'BillingAddressLine1',
        'BillingAddressLine2',
        'BillingAddressLine3',
        'BillingAddressLine4',
        'BillingCity',
        'BillingRegion',
        'BillingPostal',
        'BillingCountry',
        'ShippingAddressLine1',
        'ShippingAddressLine2',
        'ShippingAddressLine3',
        'ShippingAddressLine4',
        'ShippingCity',
        'ShippingRegion',
        'ShippingPostal',
        'ShippingCountry',
        'ContactEmail',
	    'CustomerIP',
        'CustomerPhone'
    );
} 

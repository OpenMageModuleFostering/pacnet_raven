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
 * Raven API payment status response codes
 */
class PacNet_Raven_Model_Api_Service_Response_Codes_Status
{
	/**
	 * Response status codes
	 *
	 * @var array
	 */
	protected $statuses = array(
        'InProgress'                => 'the payment has been received and will be processed.',
        'Pending3DS'                => 'the payment has been received and response included information to complete 3DS authentication.',
        'Submitted'                 => 'the payment has been submitted to the clearing system.',
        'Approved'                  => 'the request has been approved and the payment can be settled.',
        'Declined'                  => 'the request was declined and the payment may not be settled.',
        'RepeatDeclined'            => 'the request was declined four or more times and the payment may not be settled.',
        'PickupCard'                => 'payment has been declined and the merchant should confiscate the card if possible.',
        'ReferToIssuer'             => 'the payment could not be approved. Call the issuing bank for clarification.',
        'Voided'                    => 'the payment has been voided and will not be processed.',
        'Invalid:field'             => 'a field in the request is invalid',
        'Rejected:reason'           => 'the payment, while valid, has been rejected for another reason.',
        'ConfigError:error'         => 'due to a RAVEN configuration error the payment could not be processed.',
        'Error:error'               => 'an unusual error condition has occurred.',
        'UnexpectedResponse:value'  => 'the bank has returned an unexpected error condition.',
        'AccountValid'              => 'cc_verify only',
        'AccountInvalid'            => 'cc_verify only',
        'InsufficientFunds'         => 'insufficient funds',
        'InvalidMerchant'           => 'invalid merchant',
        'InvalidAccount'            => 'invalid account',
        'InvalidAmount:limit'       => 'limit on card exceeded',
        'OverDailyLimit'            => 'over daily limit',
        'RestrictedCard'            => 'registered card',
        'Retry'                     => 'retry',
        'SuspectedFraud'            => 'suspected fraud',
        'UnableToAuth'              => 'unable to auth',
        'ExpiredCard'               => 'expired card',
        'TooManyAuths'              => 'too many auths',
        'ViolatesLaw'               => 'violates law',
    );

	/**
	 * Parses a response status
	 *
	 * @return array
	 */
	public function parseStatus() {
        $status = array();
        list($statusCode, $statusValue) = explode(':', $this->getStatus());
        $status['code'] = $statusCode;
        $status['reason'] = $statusValue;
        $status['message'] = $this->getStatusMessage($statusCode);
        return $status;
    }

	/**
	 * Gets a status value using code
	 *
	 * @param $statusCode
	 *
	 * @return bool
	 */
	public function getStatusValue($statusCode) {
        foreach ($this->statuses as $status){
            list($statusCodeElement, $statusValueElement) = explode(':', $status);
            if ($statusCodeElement == $statusCode) {
                return $statusValueElement;
            }
        }

        return false;
    }

	/**
	 * Gets a status message using code
	 *
	 * @param $statusCode
	 *
	 * @return mixed|null
	 */
	public function getStatusMessage($statusCode) {
        if (! $this->isStatus($statusCode)) {
            return null;
        }
        return $this->statuses[$statusCode];
    }

	/**
	 * Checks response status
	 *
	 * @param $statusCode
	 *
	 * @return bool
	 */
	public function isStatus($statusCode) {
        foreach ($this->statuses as $status){
            list($statusCodeElement, $statusValueElement) = explode(':', $status);
            if ($statusCodeElement == $statusCode) {
                return true;
            }
        }

        return false;
    }
} 
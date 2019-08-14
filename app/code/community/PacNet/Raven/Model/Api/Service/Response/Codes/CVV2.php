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
 * Raven API payment CVV2 response codes
 */
class PacNet_Raven_Model_Api_Service_Response_Codes_CVV2
{
	/**
	 * CVV2 response codes
	 *
	 * @var array
	 */
	protected $responseCodes = array(
        'cvv2_matched' => 'Any payment were the CVV2 was checked and matched the CVV2 supplied.',
        'cvv2_not_matched' => 'Any payment where the CVV2 was checked and did not match the CVV2 supplied.',
        'cvv2_not_checked' => 'Any payment where either the CVV2 data was not supplied or rarely, where the CVV2 was not checked by the gateway even though the service was available.',
        'cvv2_unavailable' => 'Any payment where the CVV2 could not be checked because the service was not available.',
        'cvv2__response_unknown' => 'If the CVV2 was provided but no information on the match is provided by the acquiring bank.',
    );
} 
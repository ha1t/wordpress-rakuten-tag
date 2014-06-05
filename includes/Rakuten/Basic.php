<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * PHP versions 4 and 5
 *
 * LICENSE: Copyright 2007 Masashi Shinbara. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * o Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 * o Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE FREEBSD PROJECT "AS IS" AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO
 * EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
 * EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The views and conclusions contained in the software and documentation are
 * those of the authors and should not be interpreted as representing official
 * policies, either expressed or implied, of The PEAR Group.
 *
 * @category  Web Services
 * @package   Services_Rakuten
 * @author    Masashi Shinbara <shin1x1@gmail.com>
 * @copyright 2007 Masashi Shinbara
 * @license   http://www.freebsd.org/copyright/freebsd-license.html 2 Clause BSD License
 * @filesource
 * @see       http://webservice.rakuten.co.jp/
 */
 
require_once 'PEAR.php';
require_once 'HTTP/Request.php';
require_once 'XML/Unserializer.php';

/**
 * Class for accessing and retrieving information from Rakuten's Web API.
 *
 * @package Services_Rankuten
 * @author  Masashi Shinbara <shin1x1@gmail.com>
 * @access  public
 * @version Release: 0.1.0
 * @uses    PEAR
 * @uses    HTTP_Request
 * @uses    XML_Unserializer
 */
class Services_Rakuten_Basic
{
    /**
     * @var string
     */
    var $developerId = null;
    /**
     * @var string
     */
    var $affiliateId = null;
    /**
     * @var string
     */
    var $baseUrl = 'http://api.rakuten.co.jp/rws/1.7/rest';
    /**
     * @var string
     */
    var $apiVersion = '2007-04-11';
    /**
     * @var string
     */
    var $operation = null;

    /**
     * @var string
     */
    var $lastUrl = null;

    /**
     * @var string
     */
    var $status = null;
    /**
     * @var string
     */
    var $statusMessage = null;
    /**
     * @var array
     */
    var $data = null;

    /**
     * @var object
     */
    var $http = null;

    /**
     * Constructor
     *
     * @access public
     * @param  string $developerId
     * @param  string $affiliateId
     */
    function Services_Rakuten_Basic($developerId = null, $affiliateId = null) {
        if (!is_null($developerId)) {
            $this->developerId = $developerId;
        }
        if (!is_null($affiliateId)) {
            $this->affiliateId = $affiliateId;
        }

        $this->http =& new HTTP_Request($this->baseUrl);
    }

    /**
     * execute API
     *
     * @param  array   $options
     */
    function execute($options = array()) {
        $this->lastUrl = null;
        $this->sendRequest($this->operation, $options);
    }

    /**
     * Sends the request to Rakuten Web API.
     *
     * @param  string  $operation
     * @param  array   $options
     */
    function sendRequest($operation, $options = array()) {
        $this->responseCode = null;
        $this->data = null;
        $this->status = null;
        $this->statusMessage = null;

        $this->lastUrl = $this->makeUrl($operation, $options);

        $this->http->setURL($this->lastUrl);
        $this->http->addHeader('User-Agent', 'Services_Rankuten/' . $this->getVersion());
        $this->http->sendRequest();

        if ($this->http->getResponseCode() != 200) {
            return;
        }

        $result = $this->http->getResponseBody();

        $xml =& new XML_Unserializer();
        $xml->unserialize($result);
        $data = $xml->getUnserializedData();

        $this->parseResponseHeader($data);

        if ($this->status == 'Success') {
            $this->parseResponseBody($operation, $data);
        }
    }

    /**
     * make URL query
     *
     * @param  string  $operation
     * @param  array   $params
     */
    function makeUrl($operation, $options = array()) {
        if (is_null($this->developerId)) {
            trigger_error('Developers Id have not been set.');
        }

        $params = $options;
        $params['developerId'] = $this->developerId;
        $params['affiliateId'] = $this->affiliateId;
        $params['version']     = $this->getApiVersion();
        $params['operation']   = $operation;

        $query = "";
        foreach ($params as $key => $value) {
          if (!empty($query)) {
            $query .= "&";
          }
          $query .= sprintf("%s=%s", $key, urlencode($value));
        }

        return $this->baseUrl . '?' . $query;
    }

    /**
     * throw exception
     *
     * @param  string $message
     */
    function exception($message) {
        $this->lastUrl = null;
        $message = sprintf("[%s] %s", $this->operation, $message);
        trigger_error($message, E_USER_ERROR);
    }

    /**
     * parse response header
     *
     * @param  array $data
     */
    function parseResponseHeader($data) {
        $this->status = $data['header:Header']['Status'];
        $this->statusMessage = $data['header:Header']['StatusMsg'];
    }

    /**
     * parse response body
     *
     * @param  string $operation
     * @param  array $data
     */
    function parseResponseBody($operation, $data) {
        $str = strtolower(substr($operation, 0, 1)) . substr($operation, 1);
        $this->data = $data['Body'][($str) . ':' . $operation];
    }

    /**
     * get result status
     *
     * @return array
     */
    function getResultStatus() {
        return $this->status;
    }

    /**
     * get result status message
     *
     * @return array
     */
    function getResultStatusMessage() {
        return $this->statusMessage;
    }

    /**
     * get result body data
     *
     * @return array
     */
    function getResultData() {
        return $this->data;
    }

    /**
     * get last url
     *
     * @return string
     */
    function getLastUrl() {
        return $this->lastUrl;
    }

    /**
     * get version
     *
     * @return string
     */
    function getVersion() {
        return '0.2.0';
    }

    /**
     * get Rakuten API version
     *
     * @return string
     */
    function getApiVersion() {
        return $this->apiVersion;
    }
}
?>

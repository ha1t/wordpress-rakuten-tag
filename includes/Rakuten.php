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
 
require_once dirname(__FILE__) . '/Rakuten/Basic.php';

/**
 * Class for accessing and retrieving information from Rakuten's Web API.
 *
 * @package Services_Rankuten
 * @author  Masashi Shinbara <shin1x1@gmail.com>
 * @access  public
 * @version Release: 0.1.0
 * @uses    HTTP_Request
 * @uses    XML_Unserializer
 */
class Services_Rakuten
{
    /**
     * @deprecated
     * @var string
     */
    var $developerId = null;
    /**
     * @deprecated
     * @var string
     */
    var $affiliateId = null;
    /**
     * @deprecated
     * @var object Services_Rakuten_Basic
     */
    var $api = null;

    /**
     * Constructor
     *
     * @access public
     * @deprecated
     * @param  string $developerId
     * @param  string $affiliateId
     */
    function Services_Rakuten($developerId, $affiliateId = null) {
        $this->developerId = $developerId;
        $this->affiliateId = $affiliateId;
    }

    /**
     * factory
     *
     * @static
     * @param  string $operation
     * @param  string $developerId
     * @param  string $affiliateId
     * @return object  Services_Rakuten_Basic
     */
    function factory($operation, $developerId, $affiliateId = null) {
        if (!preg_match("/^[A-Za-z0-9_]+$/D", $operation)) {
            return null;
        }

        $path = dirname(__FILE__) . '/Rakuten/' . $operation . '.php';
        if (!file_exists($path)) {
            return null;
        }

        require_once($path);
        $class = 'Services_Rakuten_' . $operation;
        $obj = new $class($developerId, $affiliateId);
        return $obj;
    }

    /**
     * Send ItemSearch operation
     *
     * @deprecated
     * @param  string  $keyword
     * @param  integer $genreId
     * @param  array   $options
     * @see http://webservice.rakuten.co.jp/api/itemsearch/
     */
    function doItemSearch($keyword = null, $genreId = null, $options = array()) {
        $this->api = Services_Rakuten::factory('ItemSearch'
                                       , $this->developerId
                                       , $this->affiliateId);

        if (!is_null($keyword)) {
            $options['keyword'] = $keyword;
        }
        if (!is_null($genreId)) {
            $options['genreId'] = $genreId;
        }

        $this->api->execute($options);
    }

    /**
     * Send GenreSearch operation
     *
     * @deprecated
     * @param  integer $genreId
     * @param  array   $options
     * @see http://webservice.rakuten.co.jp/api/genresearch/
     */
    function doGenreSearch($genreId, $options = array()) {
        $this->api = Services_Rakuten::factory('GenreSearch'
                                       , $this->developerId
                                       , $this->affiliateId);

        if (!is_null($genreId)) {
            $options['genreId'] = $genreId;
        }

        $this->api->execute($options);
    }

    /**
     * Send ItemCodeSearch operation
     *
     * @deprecated
     * @param  string  $itemCode
     * @param  array   $options
     * @see http://webservice.rakuten.co.jp/api/itemcodesearch/
     */
    function doItemCodeSearch($itemCode, $options = array()) {
        $this->api = Services_Rakuten::factory('GenreSearch'
                                       , $this->developerId
                                       , $this->affiliateId);

        if (!is_null($itemCode)) {
            $options['itemCode'] = $itemCode;
        }

        $this->api->execute($options);
    }

    /**
     * Send BookSearch operation
     *
     * @deprecated
     * @param  integer $keyword
     * @param  array   $options
     * @see http://webservice.rakuten.co.jp/api/booksearch/
     */
    function doBookSearch($keyword = null, $genreId = null, $options = array()) {
        $this->api = Services_Rakuten::factory('BookSearch'
                                       , $this->developerId
                                       , $this->affiliateId);

        if (!is_null($keyword)) {
            $options['keyword'] = $keyword;
        }
        if (!is_null($genreId)) {
            $options['genreId'] = $genreId;
        }

        $this->api->execute($options);
    }

    /**
     * get result body data
     *
     * @deprecated
     * @return array
     */
    function &getResultData() {
        return $this->api->data;
    }

    /**
     * get last url
     *
     * @deprecated
     * @return string
     */
    function getLastUrl() {
        return $this->api->lastUrl;
    }
}
?>

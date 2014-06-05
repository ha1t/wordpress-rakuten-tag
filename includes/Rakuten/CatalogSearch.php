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

/**
 * Class for accessing and retrieving information from Rakuten's Web API.
 *
 * @package Services_Rankuten
 * @author  Masashi Shinbara <shin1x1@gmail.com>
 * @access  public
 * @version Release: 0.2.0
 */
class Services_Rakuten_CatalogSearch extends Services_Rakuten_Basic
{
    /**
     * Constructor
     *
     * @access public
     * @param  string $developerId
     * @param  string $affiliateId
     */
    function Services_Rakuten_CatalogSearch($developerId = null, $affiliateId = null) {
        parent::Services_Rakuten_Basic($developerId, $affiliateId);
        $this->operation = 'CatalogSearch';
    }

    /**
     * Send CatalogSearch operation
     *
     * @param  array   $options
     * @see http://webservice.rakuten.co.jp/api/catalogsearch/
     */
    function execute($options = array()) {
        if (empty($options['keyword']) && empty($options['genreId'])) {
            $this->exception('keyword or genreId have not been set.');
        }

        parent::execute($options);
    }
}
?>

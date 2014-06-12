<?php

/**
 * This file is part of Rakuten Web Service SDK
 *
 * (c) Rakuten, Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with source code.
 */

/**
 * BooksSoftwareSearch
 *
 * @package RakutenRws
 * @subpackage Api_Definition
 */
class RakutenRws_Api_Definition_BooksSoftwareSearch extends RakutenRws_Api_RwsApi
{
    protected
        $versionMap = array(
            '2013-05-22' => 'RakutenRws_Api_Definition_BooksSoftwareSearch2',
            '2012-11-28' => 'RakutenRws_Api_Definition_BooksSoftwareSearch2',
            '2011-12-01' => '3.0',
            '2011-07-07' => '3.0',
            '2010-03-18' => '3.0',
            '2009-04-15' => '2.0',
            '2009-03-26' => '2.0'
        ),
        $autoSetIterator = true;
}

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
 * GoraGolfCourseDetail
 *
 * @package RakutenRws
 * @subpackage Api_Definition
 */
class RakutenRws_Api_Definition_GoraGolfCourseDetail extends RakutenRws_Api_RwsApi
{
    protected
        $versionMap = array(
            '2013-11-13' => 'RakutenRws_Api_Definition_GoraGoraGolfCourseDetail',
            '2010-06-30' => '3.0'
        );

    public function execute($parameter)
    {
        $rwsresponse = parent::execute($parameter);

        if ($rwsresponse->isOk()) {
            $data = $rwsresponse->getData();
            if (!isset($data['Body'][$this->getOperationName()]['Item'])) {
                throw new RakutenRws_Exception();
            }

            $rwsresponse->setIterator($data['Body'][$this->getOperationName()]['Item']);
        }

        return $rwsresponse;
    }
}

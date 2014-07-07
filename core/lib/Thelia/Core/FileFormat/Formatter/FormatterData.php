<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace Thelia\Core\FileFormat\Formatter;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Map\TableMap;
use Thelia\Core\Translation\Translator;

/**
 * Class FormatterData
 * @package Thelia\Core\FileFormat\Formatter
 * @author Benjamin Perche <bperche@openstudio.fr>
 */
class FormatterData
{
    /** @var array */
    protected $data;

    /** @var  null|array */
    protected $aliases;

    /** @var Translator */
    protected $translator;

    /**
     * @param array $aliases
     *
     * $aliases is a associative array where the key represents propel TYPE_PHP_NAME of column if you use
     * loadModelCriteria, or your own aliases for setData, and the value
     * is the alias. It can be null or empty if you don't want aliases,
     * but remember to always define all the fields the you want:
     * non aliases fields will be ignored.
     */
    public function __construct(array $aliases = null)
    {
        $this->translator = Translator::getInstance();

        if (!is_array($aliases)) {
            $aliases = [];
        }

        /**
         * Lower all the values
         */
        foreach ($aliases as $key => $value) {
            $lowerKey = strtolower($key);
            $lowerValue = strtolower($value);
            if ($lowerKey !== $key) {
                $aliases[$lowerKey] = $lowerValue;
                unset($aliases[$key]);
            } else {
                $aliases[$key] = $lowerValue;
            }
        }

        $this->aliases = $aliases;
    }

    /**
     * @param array $data
     * @return $this
     *
     * Sets raw data with aliases
     */
    public function setData(array $data)
    {
        if (empty($this->aliases)) {
            $this->data = $data;
            return $this;
        }

        $this->data = $this->applyAliases($data, $this->aliases);

        return $this;
    }

    /**
     * @param ModelCriteria $criteria
     * @return $this|null
     *
     * Loads a model criteria.
     * Warning: if you want to do multi table export,
     * you'll have to use you own select and not the joinYourTable method.
     * For more details, please see the unit test
     * Thelia\Tests\FileFormat\Formatter\FormatterDataTest::testFormatSimpleMultipleTableQuery
     */
    public function loadModelCriteria(ModelCriteria $criteria)
    {
        $propelData = $criteria->find();

        if (empty($propelData)) {
            return null;
        }

        $asColumns = $propelData->getFormatter()->getAsColumns();

        /**
         * Format it correctly
         * After this pass, we MUST have a 2D array.
         * The first may be keyed with integers.
         */
        $formattedResult = $propelData
            ->toArray(null, false, TableMap::TYPE_COLNAME);

        if (count($asColumns) > 1) {
            /**
             * Request with multiple select
             * Apply propel aliases
             */
            $formattedResult = $this->applyAliases($formattedResult, $asColumns);
        } elseif (count($asColumns) === 1) {
            /**
             * Request with one select
             */
            $key = str_replace("\"", "", array_keys($asColumns)[0]);
            $formattedResult = [[$key => $formattedResult[0]]];
        }

        $data = $this->applyAliases($formattedResult, $this->aliases);

        /**
         * Then store it
         */
        $this->data = $data;

        return $this;
    }

    /**
     * @param array $data
     * @param array $aliases
     */
    protected function applyAliases(array $data, array $aliases)
    {
        $formattedData = [];

        foreach ($data as $key=>$entry) {
            $key = strtolower($key);

            if (is_array($entry)) {
                $formattedData[$key] = $this->applyAliases($entry, $aliases);
            } else {
                $alias = isset($aliases[$key]) ? $aliases[$key] : $key;
                $formattedData[$alias] = $entry;
            }
        }

        return $formattedData;
    }

    public function getData()
    {
        return $this->data;
    }
}

<?php
/**
 * Import Definitions.
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2016 W-Vision (http://www.w-vision.ch)
 * @license    https://github.com/w-vision/ImportDefinitions/blob/master/gpl-3.0.txt GNU General Public License version 3 (GPLv3)
 */

namespace ImportDefinitions\Model\Provider;

use ImportDefinitions\Model\AbstractProvider;
use ImportDefinitions\Model\Definition;
use ImportDefinitions\Model\Mapping\FromColumn;
use Pimcore\Model\Object\Concrete;

/**
 * Json Import Provider
 *
 * Class Json
 * @package ImportDefinitions\Provider
 */
class Json extends AbstractProvider
{

    /**
     * @var string
     */
    public $jsonExample;

    /**
     * @return string
     */
    public function getJsonExample()
    {
        return $this->jsonExample;
    }

    /**
     * @param string $jsonExample
     */
    public function setJsonExample($jsonExample)
    {
        $this->jsonExample = $jsonExample;
    }

    /**
     * Calculate depth
     *
     * @param array $arr
     * @return int
     */
    protected function getJsonDepth(array $arr)
    {
        $it = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($arr));
        $depth = 0;
        foreach ($it as $v) {
            $it->getDepth() > $depth and $depth = $it->getDepth();
        }
        return $depth;
    }

    /**
     * test data
     *
     * @return boolean
     * @throws \Exception
     */
    public function testData()
    {
        return $this->getJsonDepth(\Zend_Json::decode($this->getJsonExample())) === 1;
    }

    /**
     * Get Columns from data
     *
     * @return FromColumn[]
     */
    public function getColumns()
    {
        $rows = \Zend_Json::decode($this->getJsonExample());
        $returnHeaders = [];

        if (count($rows) > 0) {
            $firstRow = $rows[0];

            foreach ($firstRow as $key => $val) {
                $headerObj = new FromColumn();
                $headerObj->setIdentifier($key);
                $headerObj->setLabel($key);

                $returnHeaders[] = $headerObj;
            }
        }

        return $returnHeaders;
    }

    /**
     * @param Definition $definition
     * @param $params
     * @return Concrete[]
     */
    protected function runImport($definition, $params)
    {
        $file = PIMCORE_DOCUMENT_ROOT . "/" . $params['file'];
        $json = file_get_contents($file);

        $objects = [];

        $data = \Zend_Json::decode($json);

        foreach ($data as $row) {
            $objects[] = $this->importRow($definition, $row);
        }

        return $objects;
    }

    /**
     * @param Definition $definition
     * @param $data
     *
     * @return Concrete
     */
    private function importRow($definition, $data)
    {
        $object = $this->getObjectForPrimaryKey($definition, $data);

        foreach ($definition->getMapping() as $map) {
            $value = $data[$map->getFromColumn()];

            $this->setObjectValue($object, $map, $value, $data);
        }

        $object->save();

        return $object;
    }
}
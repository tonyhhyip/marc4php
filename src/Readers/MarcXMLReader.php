<?php

namespace Marc\Readers;


use Sabre\Xml\Service;
use Sabre\Xml\Reader as XMLReader;
use Marc\Collection;
use Marc\Record;
use Marc\Fields\ControlField;
use Marc\Fields\DataField;
use Marc\Fields\SubField;

class MarcXMLReader extends AbstractReader
{
    /**
     * @var Service
     */
    private $reader;

    /**
     * MarcXMLReader constructor.
     *
     * @param Service|null $service
     */
    public function __construct(Service $service = null)
    {
        $this->reader = $service ?: new Service();
        $this->configElementMap();
    }

    /**
     * @inheritdoc
     */
    public function parse($data)
    {
        $reader = $this->reader->getReader();
        $reader->xml($data);
        $valid = $reader->setSchema(__DIR__ . '/../../schema/MARC21slim.xsd');
        if (!$valid)
            throw new \InvalidArgumentException('Incorrect Schema');
        $result = $reader->parse($data)['value'];
        if ($result instanceof Collection) {
            return $result;
        }

        $record = new Record($result['leader'], $result['control'], $result['data']);
        return new Collection([$record]);
    }

    private function configElementMap()
    {
        $this->configCollection();
        $this->configDataField();
        $this->configRecord();
    }

    private function configRecord()
    {
        $this->reader->elementMap['{http://www.loc.gov/MARC21/slim}record'] = function (XMLReader $reader) {
            $children =  $reader->parseInnerTree();
            $result = [];
            foreach ($children as $child) {
                if ($child['name'] === '{http://www.loc.gov/MARC21/slim}leader') {
                    $result['leader'] = $child['value'];
                } elseif ($child['name'] === '{http://www.loc.gov/MARC21/slim}controlfield') {
                    $result['control'][] = new ControlField($child['attributes']['tag'], $child['value']);
                } elseif ($child['name'] === '{http://www.loc.gov/MARC21/slim}datafield') {
                    $result['data'][] = $child['value'];
                } else {
                    $result[] = $child;
                }
            }
            return $result;
        };
    }

    private function configCollection()
    {
        $this->reader->elementMap['{http://www.loc.gov/MARC21/slim}collection'] = function (XMLReader $reader) {
            $records = array_map(function ($child) {
                $value =  $child['value'];
                $record = new Record($value['leader'], $value['control'], $value['data']);
                return $record;
            }, $reader->parseInnerTree());
            return new Collection($records);
        };
    }

    private function configDataField()
    {
        $this->reader->elementMap['{http://www.loc.gov/MARC21/slim}datafield'] = function (XMLReader $reader) {
            $tag = $reader->getAttribute('tag');
            $ind1 = $reader->getAttribute('ind1');
            $ind2 = $reader->getAttribute('ind2');
            $field = new DataField($tag, [$ind1, $ind2]);
            $children = $reader->parseInnerTree();
            foreach ($children as $child) {
                $field->addSubField(new SubField($child['attributes']['code'], $child['value']));
            }
            return $field;
        };
    }
}

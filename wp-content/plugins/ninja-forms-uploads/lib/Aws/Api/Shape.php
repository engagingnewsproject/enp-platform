<?php

namespace NF_FU_LIB\Aws\Api;

/**
 * Base class representing a modeled shape.
 */
class Shape extends AbstractModel
{
    /**
     * Get a concrete shape for the given definition.
     *
     * @param array    $definition
     * @param ShapeMap $shapeMap
     *
     * @return mixed
     * @throws \RuntimeException if the type is invalid
     */
    public static function create(array $definition, ShapeMap $shapeMap)
    {
        static $map = ['structure' => 'NF_FU_LIB\\Aws\\Api\\StructureShape', 'map' => 'NF_FU_LIB\\Aws\\Api\\MapShape', 'list' => 'NF_FU_LIB\\Aws\\Api\\ListShape', 'timestamp' => 'NF_FU_LIB\\Aws\\Api\\TimestampShape', 'integer' => 'NF_FU_LIB\\Aws\\Api\\Shape', 'double' => 'NF_FU_LIB\\Aws\\Api\\Shape', 'float' => 'NF_FU_LIB\\Aws\\Api\\Shape', 'long' => 'NF_FU_LIB\\Aws\\Api\\Shape', 'string' => 'NF_FU_LIB\\Aws\\Api\\Shape', 'byte' => 'NF_FU_LIB\\Aws\\Api\\Shape', 'character' => 'NF_FU_LIB\\Aws\\Api\\Shape', 'blob' => 'NF_FU_LIB\\Aws\\Api\\Shape', 'boolean' => 'NF_FU_LIB\\Aws\\Api\\Shape'];
        if (isset($definition['shape'])) {
            return $shapeMap->resolve($definition);
        }
        if (!isset($map[$definition['type']])) {
            throw new \RuntimeException('Invalid type: ' . \print_r($definition, \true));
        }
        $type = $map[$definition['type']];
        return new $type($definition, $shapeMap);
    }
    /**
     * Get the type of the shape
     *
     * @return string
     */
    public function getType()
    {
        return $this->definition['type'];
    }
    /**
     * Get the name of the shape
     *
     * @return string
     */
    public function getName()
    {
        return $this->definition['name'];
    }
}

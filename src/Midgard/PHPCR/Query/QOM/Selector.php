<?php

namespace Midgard\PHPCR\Query\QOM;

use Midgard\PHPCR\Query\Utils\QuerySelectDataHolder;
use \MidgardSqlQueryColumn;
use \MidgardQueryProperty;
use \MidgardQueryValue;
use Midgard\PHPCR\Utils\NodeMapper;

/**
 * {@inheritDoc}
 */
class Selector extends QuerySelectHelper implements \PHPCR\Query\QOM\SelectorInterface 
{
    protected $nodeTypeName = null;
    protected $name = null;

    public function __construct($nodeTypeName, $name)
    {
        $this->nodeTypeName = $nodeTypeName;
        $this->name = $name;
    }
    /**
     * {@inheritDoc}
     */
    public function getNodeTypeName()
    {
        return $this->nodeTypeName;
    }

    /**
     * {@inheritDoc}
     */
    public function getSelectorName()
    {
        return $this->name;
    }

    public function addMidgard2Constraints(QuerySelectDataHolder $holder)
    {
        $column = new MidgardSqlQueryColumn(
            new MidgardQueryProperty('typename', $holder->getMidgard2QueryNodeStorage($this->name)),
            $holder->getNodeQualifier(),
            'midgard_node_name'
        );

        $cg = $holder->getDefaultConstraintGroup();
        $cg->add_constraint(
            new \MidgardSqlQueryConstraint($column,
                "=",
                new \MidgardQueryValue(NodeMapper::getMidgardName($this->nodeTypeName))
            )
        );

        echo "SELECTOR NAME : " . $this->getSelectorName() . "\n";
        echo "NODE TYPE NAME : " . $this->getNodeTypeName() . "\n"; 
    }
}

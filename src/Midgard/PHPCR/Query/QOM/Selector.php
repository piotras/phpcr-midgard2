<?php

namespace Midgard\PHPCR\Query\QOM;

use Midgard\PHPCR\Query\Utils\QuerySelectDataHolder;
use \MidgardSqlQueryColumn;
use \MidgardQueryProperty;
use \MidgardQueryValue;
use Midgard\PHPCR\Utils\NodeMapper;
use Midgard\PHPCR\Query\Utils\QueryNameMapper;

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
        if ($this->name == null) {
            $this->name = $this->getNodeTypeName();
        }
        return $this->name;
    }

    public function addMidgard2QSDConstraints(QuerySelectDataHolder $holder)
    {
        $column = new MidgardSqlQueryColumn(
            new MidgardQueryProperty('typename', $holder->getMidgard2QueryNodeStorage($this->name)),
            QueryNameMapper::getNodeQualifier(),
            'midgard_node_name'
        );

        $cg = $holder->getDefaultConstraintGroup();
        $cg->add_constraint(
            new \MidgardSqlQueryConstraint($column,
                "=",
                new \MidgardQueryValue(NodeMapper::getMidgardName($this->nodeTypeName))
            )
        );
    }

    public function getAllColumns(QuerySelectDataHolder $holder)
    {
        $nodeTypeName = $this->getNodeTypeName();
        $selectorName = $this->getSelectorName();
        if ($selectorName == '' || $selectorName == null) {
            $selectorName = NodeMapper::getMidgardName($nodeTypeName);
        }
        $ws = $holder->getSession()->getWorkspace();
        $ntm = $ws->getNodeTypeManager();
        $nt = $ntm->getNodeType($nodeTypeName);

        $ret = array();
        foreach ($nt->getDeclaredPropertyDefinitions() as $dpd) {
            /* Do not select multiple values.
             * Jackrabbit ignores them, not sure about JCR spec though */
            if ($dpd->isMultiple()) {
                continue;
            }
            $name = $dpd->getName();
            $ret[] = new Column($name, $name, $selectorName);
        }
        return $ret;
    }
}

<?php

namespace Midgard\PHPCR\Query\QOM;

use Midgard\PHPCR\Query\Utils\QuerySelectDataHolder;
use Midgard\PHPCR\Query\Utils\QueryNameMapper;
use Midgard\PHPCR\Utils\NodeMapper;
use \MidgardSqlQueryColumn;
use \MidgardQueryProperty;

/**
 * {@inheritDoc}
 */
class EquiJoinCondition extends ConditionHelper implements \PHPCR\Query\QOM\EquiJoinConditionInterface
{
    protected $selectorFirst = null;
    protected $selectorSecond = null;
    protected $nameFirst = null;
    protected $nameSecond = null;

    public function __construct($selector1Name, $property1Name, $selector2Name, $property2Name)
    {
        $this->selectorFirst = $selector1Name;
        $this->nameFirst = $property1Name;
        $this->selectorSecond = $selector2Name;
        $this->nameSecond = $property2Name;
    }

    /**
     * {@inheritDoc}
     */
    public function getSelector1Name()
    {
        return $this->selectorFirst;
    }

    /**
     * {@inheritDoc}
     */
    public function getProperty1Name()
    {
        return $this->nameFirst;
    }

    /**
     * {@inheritDoc}
     */
    public function getSelector2Name()
    {
        return $this->selectorSecond;
    }

    /**
     * {@inheritDoc}
     */
    public function getProperty2Name()
    {
        return $this->nameSecond;
    }

    private function findEqualRow($value, array $objects)
    {
        foreach ($objects as $o) 
        {
            if ($o->value == $value) {
                return $o;
            }
        }
        return null;
    }

    public function computeResults(array $selects)
    {
        $selector1Name = $this->getSelector1Name(); 
        $selector2Name = $this->getSelector2Name();

        $rows[0] = $selects[$selector1Name]['QuerySelect'];
        $rows[1] = $selects[$selector2Name]['QuerySelect'];

        $retTwo = $rows[1]->list_objects();
        $i = 0;
        $j = 0;
        $result = array();
        $selector1Name = $this->getSelector1Name(); 
        $selector2Name = $this->getSelector2Name(); 

        foreach ($rows[0]->list_objects() as $object) 
        {
            $objTwo = $this->findEqualRow($object->value, $retTwo);
            if ($objTwo != null) {
                $result[$j][$selector1Name][$object->name] = $selects[$selector1Name]['properties'][$object->name]; 
                $result[$j][$selector1Name][$object->name]['midgardNodeProperty'] =  $object;
                $result[$j][$selector2Name][$objTwo->name] = $selects[$selector2Name]['properties'][$objTwo->name]; 
                $result[$j][$selector2Name][$objTwo->name]['midgardNodeProperty'] =  $objTwo;
            }
            $i++;
        }
        return $result;
    }

    public function addMidgard2QSDConstraints(QuerySelectDataHolder $holder)
    {
        $selectorNameFirst = $this->selectorFirst;
        $nameFirst = $this->nameFirst;
        $selectorNameSecond = $this->selectorSecond;
        $nameSecond = $this->nameSecond;

        $selectorFirst = $holder->getSelectorByName($selectorNameFirst);
        $selectorSecond = $holder->getSelectorByName($selectorNameSecond);

        $storageProperty = $holder->getPropertyStorage();
        $storage = $holder->getDefaultNodeStorage();
        $cg = $holder->getDefaultConstraintGroup();

        /* Create Midgard columns */
        $firstColumn = new MidgardSqlQueryColumn(
            new MidgardQueryProperty('value', $storageProperty),
            $selectorNameFirst,
            $nameFirst
        );

        $secondColumn = new MidgardSqlQueryColumn(
            new MidgardQueryProperty('value', $storageProperty),
            $selectorNameSecond,
            $nameSecond
        );

        /* Add join on created columns */
        /* ON (source.value = target.value) */
        $querySelect = $holder->getQuerySelect();
        $querySelect->add_join(
            'INNER',
            $firstColumn,
            $secondColumn
        );

        /* Add property name constraints */
        /* AND (source.title = PROP1) AND (target.name = PROP2) */
        $column = new MidgardSqlQueryColumn(
            new MidgardQueryProperty('name', $storageProperty),
            $selectorNameFirst,
            $nameFirst
        );
        $cg->add_constraint(
            new \MidgardSqlQueryConstraint($column,
                "=",
                new \MidgardQueryValue(QuerySelectHelper::NormalizeName($nameFirst))
            )
        );
        $column = new MidgardSqlQueryColumn(
            new MidgardQueryProperty('name', $storageProperty),
            $selectorNameSecond,
            $nameSecond
        );
        $cg->add_constraint(
            new \MidgardSqlQueryConstraint($column,
                "=",
                new \MidgardQueryValue(QuerySelectHelper::NormalizeName($nameSecond))
            )
        );

        /* Join second property on second node */
        $secondColumn = new MidgardSqlQueryColumn(
            new MidgardQueryProperty('parent', $storageProperty),
            $selectorNameSecond,
            $nameSecond
        );
        
        $secondColumnNode = new MidgardSqlQueryColumn(
            new MidgardQueryProperty('id', $storage),
            $selectorNameSecond . "_node",
            'second_node_id'
        );

        /* Add join on created columns */
        /* ON (target.parent = target_node.id) */
        $querySelect->add_join(
            'INNER',
            $secondColumn,
            $secondColumnNode
        );

        /* Add typename constraints */
        /* AND (midgard_node_qualifier.typename = 'nt_unstructured') AND (target_node.typename = 'nt_unstructured')) */
        $firstColumnType = new MidgardSqlQueryColumn(
            new MidgardQueryProperty('typename', $storage),
            QueryNameMapper::NODE_QUALIFIER,
            'typename'
        );
        $secondColumnType = new MidgardSqlQueryColumn(
            new MidgardQueryProperty('typename', $storage),
            $secondColumnNode->get_qualifier(),
            'typename'
        );
 
        $cg->add_constraint(
            new \MidgardSqlQueryConstraint($firstColumnType,
                "=",
                new \MidgardQueryValue(NodeMapper::getMidgardName($selectorFirst->getNodeTypeName()))
            )
        );

        $cg->add_constraint(
            new \MidgardSqlQueryConstraint($secondColumnType,
                "=",
                new \MidgardQueryValue(NodeMapper::getMidgardName($selectorSecond->getNodeTypeName()))
            )
        );
    }
}

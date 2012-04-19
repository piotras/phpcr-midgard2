<?php

namespace Midgard\PHPCR\Query\QOM;

use Midgard\PHPCR\Query\Utils\QuerySelectHolder;
use Midgard\PHPCR\Query\Utils\QuerySelectDataHolder;
use Midgard\PHPCR\Query\Utils\QueryNameMapper;
use Midgard\PHPCR\Utils\NodeMapper;
use \MidgardSqlQueryColumn;
use \MidgardQueryProperty;
use \MidgardSqlQueryConstraint;
use \MidgardQueryValue;
use \MidgardQueryStorage;

class QuerySelectHelper
{
    protected $holder = null;

    public function setQuerySelectHolder(QuerySelectHolder $holder)
    {
        $this->holder = $holder;
        $this->holder->setMidgardStorageName(NodeMapper::getMidgardName($this->getNodeTypeName()));
    }

    public static function normalizeName($name)
    {
        $n = trim($name);
        if (strpos($n, '[') !== false) {
            $n =  strtr($n, array('[' => '', ']' => ''));
        }
        return NodeMapper::getMidgardPropertyName($n);
    }

    public static function mapJoinType($jcrJoin)
    {
        if ($jcrJoin == 'jcr.join.type.inner')
            return 'INNER';
        if ($jcrJoin == 'jcr.join.type.left.outer')
            return 'LEFT';
        if ($jcrJoin == 'jcr.join.type.right.outer')
            return 'RIGHT';
    }

    public static function mapOperatorType($type)
    {
        if ($type == 'jcr.operator.equal.to') {
            return "=";
        }
    }

    public function getNodeTypeName()
    {
        return null;
    }

    public function computeQuerySelectConstraints($holder)
    {
        $this->setQuerySelectHolder($holder);
        return;
    }

    public function addMidgard2QSDConstraints(QuerySelectDataHolder $holder) 
    {
        throw new \PHPCR\RepositoryException (get_class($this) . "::" . "addMidgard2QSDConstraints NOT IMPLEMENTED ");
    }

    public function getAllColumns(QuerySelectDataHolder $holder) 
    {
        throw new \PHPCR\RepositoryException (get_class($this) . "::" . "getAllColumns NOT IMPLEMENTED ");
    }

    public static function createMidgard2SQLColumn($propertyName, $storageName, $qualifierName, $columnName, $storage = null)
    {
        if ($storageName != null) {
            $storage = new MidgardQueryStorage($storageName);
        }
        return new MidgardSqlQueryColumn(
            new MidgardQueryProperty($propertyName, $storage),
                NodeMapper::getMidgardName($qualifierName),
                $columnName
            );
    }

    public static function createMidgard2SQLConstraint($column, $operator, $value)
    {
        return new MidgardSqlQueryConstraint($column,
            $operator,
            new MidgardQueryValue($value)
        );
    }
}

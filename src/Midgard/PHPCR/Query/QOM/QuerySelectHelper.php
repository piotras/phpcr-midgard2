<?php

namespace Midgard\PHPCR\Query\QOM;

use Midgard\PHPCR\Query\Utils\QuerySelectHolder;
use Midgard\PHPCR\Query\Utils\QuerySelectDataHolder;
use Midgard\PHPCR\Utils\NodeMapper;

class QuerySelectHelper
{
    protected $holder = null;

    public function setQuerySelectHolder(QuerySelectHolder $holder)
    {
        $this->holder = $holder;
        $this->holder->setMidgardStorageName(NodeMapper::getMidgardName($this->getNodeTypeName()));
    }

    public function normalizeName($name)
    {
        $n = trim($name);
        if (strpos($n, '[') !== false) {
            $n =  strtr($n, array('[' => '', ']' => ''));
        }
        return NodeMapper::getMidgardPropertyName($n);
    }

    public function mapJoinType($jcrJoin)
    {
        if ($jcrJoin == 'jcr.join.type.inner')
            return 'INNER';
        if ($jcrJoin == 'jcr.join.type.left.outer')
            return 'LEFT';
        if ($jcrJoin == 'jcr.join.type.right.outer')
            return 'RIGHT';
    }

    public function mapOperatorType($type)
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
}

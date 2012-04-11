<?php

namespace Midgard\PHPCR\Query\QOM;

use Midgard\PHPCR\Query\Utils\QuerySelectDataHolder;

class ConstraintHelper
{
    public function getMidgardConstraints($selectorName, \midgard_query_select $qs, \midgard_query_storage $nodeStorage)
    {
        return array();
    }

    public function removeQuotes($value)
    {
        return str_replace('"', '', $value);        
    }

    public function addMidgard2QSDConstraints(QuerySelectDataHolder $holder)
    {
        throw new \PHPCR\RepositoryException(get_class($this) . "::addMidgard2QSDCOnstraints NOT IMPLEMENTED");
    }
}

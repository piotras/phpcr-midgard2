<?php

namespace Midgard\PHPCR\Query\QOM;

/**
 * {@inheritDoc}
 */
class Column implements \PHPCR\Query\QOM\ColumnInterface
{
    protected $selectorName = null;
    protected $propertyName = null;
    protected $columnName = null;
    protected $query = null;

    public function __construct($propertyName, $columnName = null, $selectorName = null)
    {
        $this->propertyName = $propertyName;
        $this->columnName = $columnName;
        $this->selectorName = $selectorName;
    }

    /* Temporary workaround for Sql2ToQomQueryConverter bugs
     * QueryObjectModelFactory->column method accepts selector and property,
     * which are not set by converter */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * {@inheritDoc}
     */
    public function getSelectorName()
    {
        /* see setQuery method */
        if ($this->selectorName == null && $this->query != null) {
            $selectors = $this->query->getSelectors();
            if (!empty($selectors)) {
                $this->selectorName = $selectors[0]->getSelectorName();
            } 
        } 
        return $this->selectorName;
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyName()
    {
        if ($this->propertyName === null) {
            $this->propertyName = $this->columnName;
        }
        return $this->propertyName;
    }

    /**
     * {@inheritDoc}
     */
    public function getColumnName()
    {
        if ($this->columnName == null) {
            $this->columnName = $this->propertyName;
        }
        return $this->columnName;
    }
}

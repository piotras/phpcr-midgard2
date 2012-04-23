<?php

namespace Midgard\PHPCR\Query;

use Midgard\PHPCR\Query\Utils\QuerySelectDataHolder;
use Midgard\PHPCR\Query\Utils\QueryNameMapper;
use Midgard\PHPCR\Query\QuerySelectDataResult;
use \MidgardSqlQueryRow;
use Midgard\PHPCR\Utils\NodeMapper;

class QuerySelectDataRow extends Row
{
    protected $session = null;
    protected $midgardRow = null;
    protected $holder = null;
    protected $node = null;

    public function __construct(QuerySelectDataResult $result, $score, MidgardSqlQueryRow $midgardRow)
    {
        $this->queryResult = $result;
        $this->holder = $result->getHolder();
        $this->score = $score;
        $this->midgardRow = $midgardRow;
    }

    private function getSession()
    {
        if ($this->session != null) {
            return $this->session;
        }
        $this->session = $this->holder->getSession();
        return $this->session;
    }

    public function getNode($selectorName = null)
    {
        if ($this->node != null) {
            return $this->node;
        }
        $guid = $this->midgardRow->get_value(QueryNameMapper::NODE_GUID);
        $this->node = $this->holder->getSession()->getNodeRegistry()->getByMidgardGuid($guid);
        return $this->node;
    }

    public function getPath($selectorName = null)
    {
        return $this->getNode($selectorName)->getPath();
    }

    public function getScore($selectorName = null)
    {
        /* FIXME */
        return (float) $this->score;
    }

    public function getValue($columnName)
    {
        $colName = $columnName;
        if (strpos($columnName, '.') !== false) {
            $parts = explode('.', $columnName);
            $selectorName = $parts[0];
            $colName = $parts[1];
        }
        $name = NodeMapper::getMidgardPropertyName($colName);

        return $this->midgardRow->get_value($name);
    }

    protected function populateValues()
    {
        if ($this->values != null) {
            return;
        }
        $this->values = array();
        $this->indexes = array();
        $columns = $this->queryResult->getColumnNames();
        foreach ($columns as $name)
        {
            $this->values[$name] = $this->getValue($name);
            $this->indexes[$this->position] =& $name;
            $this->position++;
        }
    }

    public function getValues()
    {
        $this->populateValues();
        return $this->values;
    }
}

?>

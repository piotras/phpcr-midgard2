<?php
namespace Midgard\PHPCR\Query;

use Midgard\PHPCR\Utils\NodeMapper;
use Midgard\PHPCR\Query\SQLQuery;
use Midgard\PHPCR\Query\Utils\QuerySelectDataHolder;
use Midgard\PHPCR\Query\Utils\QueryNameMapper;
use \ArrayIterator;

class QuerySelectDataResult extends QueryResult
{
    protected $holder = null;
    protected $result = null;
    protected $rows = null;
    protected $midgardRows = null;
    protected $columns = null;
    protected $columnNames = null;
    protected $nodes = null;
    protected $selectorNames = null;
    protected $resultProperties = null;

    public function __construct(QuerySelectDataHolder $holder)
    {
        $this->holder = $holder;
    }

    public function getHolder()
    {
        return $this->holder;
    }

    private function getMidgard2QueryResult()
    {
        if ($this->result == null) {
            $this->result = $this->holder->getQuerySelect()->get_query_result();
        }
        return $this->result;
    }

    private function getMidgard2Columns()
    {
        if ($this->columns != null) {
            return $this->columns;
        }
        $this->columns = $this->getMidgard2QueryResult()->get_columns();
        return $this->columns;
    }

    public function getColumnNames()
    {
        if ($this->columnNames != null) {
            return $this->columnNames;
        }
        $this->columnNames = array();
        $columns = $this->getMidgard2Columns();
        foreach ($columns as $column) {
            $name = $column->get_name();
            if (QueryNameMapper::isReservedName($name)) {
                continue;
            }
            $this->columnNames[] = 
                NodeMapper::getPHPCRName($column->get_qualifier()) . "." . NodeMapper::getPHPCRProperty($name);
        }
        return $this->columnNames;
    }

    public function getNodes($prefetch = false)
    {
        if ($this->nodes != null) {
            return $this->nodes;
        }

        $rows = $this->getRows();
        $this->nodes = array();
        foreach($this->midgardRows as $row) {
            $guid = $row->get_value(QueryNameMapper::NODE_GUID);
            $node = $this->holder->getSession()->getNodeRegistry()->getByMidgardGuid($guid);
            $this->nodes[$node->getPath()] = $node;
        }
        return $this->nodes;
    }

    public function getRows()
    {
        if ($this->rows != null) {
            return $this->rows;
        }
        
        $this->getMidgard2QueryResult();
        $this->midgardRows = $this->result->get_rows();
        $ret = array();

        $score = 0;
        foreach ($this->midgardRows as $midgardRow) {
            $ret[] = new QuerySelectDataRow($this, ++$score, $midgardRow);
        }

        $this->rows = new \ArrayIterator($ret);
        return $this->rows;
    }

    public function getSelectorNames()
    {
        if ($this->selectorNames != null) {
            return $this->selectorNames;
        }
        $this->selectorNames = array();
        $columns = $this->getMidgard2Columns();
        foreach ($columns as $column) {
            $name = $column->get_name();
            if (QueryNameMapper::isReservedName($name)) {
                continue;
            }
            $name = NodeMapper::getPHPCRName($column->get_qualifier());
            if (in_array($name, $this->selectorNames) === false) {
                $this->selectorNames[] = $name;
            }
        }
        return $this->selectorNames;
    }

    public function getIterator()
    {
        return $this->getRows();
    }
}

?>

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
    protected $columns = null;
    protected $columnNames = null;
    protected $nodes = null;
    protected $selectorNames = null;
    protected $resultProperties = null;

    public function __construct(QuerySelectDataHolder $holder)
    {
        $this->holder = $holder;
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
        throw new \Exception ("TO IMPLEMENT");
    }

    public function getRows()
    {
        if ($this->rows != null) {
            return $this->rows;
        }
        
        $this->getMidgard2QueryResult();
        $midgardRows = $this->result->get_rows();
        $ret = array();

        $score = 0;
        foreach($midgardRows as $midgardRow) {
            $ret[] = new QuerySelectDataRow($this->holder, ++$score, $midgardRow);
        }

        $this->rows = new \ArrayIterator($ret);
        return $this->rows;
    }

    public function getSelectorNames()
    {
        throw new \Exception ("TO IMPLEMENT");
    }

    public function getIterator()
    {
        return $this->getRows();
    }
}

?>

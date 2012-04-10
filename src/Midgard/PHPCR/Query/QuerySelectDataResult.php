<?php
namespace Midgard\PHPCR\Query;

use Midgard\PHPCR\Utils\NodeMapper;
use Midgard\PHPCR\Query\SQLQuery;
use Midgard\PHPCR\Query\Utils\QuerySelectDataHolder;
use \ArrayIterator;

class QuerySelectDataResult extends QueryResult
{
    protected $holder = null;
    protected $result = null;
    protected $rows = null;
    protected $columns = null;
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

    public function getColumnNames()
    {
        throw new \Exception ("TO IMPLEMENT");
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

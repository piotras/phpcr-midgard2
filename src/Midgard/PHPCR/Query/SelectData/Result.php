<?php
namespace Midgard\PHPCR\Query\SelectData;

use Midgard\PHPCR\Utils\NodeMapper;
use Midgard\PHPCR\Query\SQLQuery;
use Midgard\PHPCR\Query\QueryResult;
use Midgard\PHPCR\Query\Utils\QuerySelectDataHolder;
use Midgard\PHPCR\Query\Utils\QueryNameMapper;
use \ArrayIterator;

class Result extends QueryResult
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

    public function __construct(QuerySelectDataHolder $holder, array $result)
    {
        $this->holder = $holder;
        $this->result = $result;
    }

    public function getHolder()
    {
        return $this->holder;
    }

    public function getColumnNames()
    {
        if ($this->columnNames != null) {
            return $this->columnNames;
        }
        $this->columnNames = array();
        return $this->columnNames;
    }

    public function getNodes($prefetch = false)
    {
        if ($this->nodes != null) {
            return $this->nodes;
        }

        $rows = $this->getRows();
        $ret = array();
        foreach($this->result as $guid => $p) {
            $node = $this->holder->getSession()->getNodeRegistry()->getByMidgardGuid($guid);
            $ret[$node->getPath()] = $node;
        }
        $this->nodes = new ArrayIterator($ret);
        return $this->nodes;
    }

    protected function orderResult()
    {
        $orderings = $this->holder->getSQLQuery()->getOrderings();
        if (empty($orderings)) {
            return;
        }

        $properties = array();
        foreach ($orderings as $ordering)
        {
            if (!($ordering->getOperand() instanceOf \Midgard\PHPCR\Query\QOM\PropertyValue)) {
                throw new \PHPCR\RepositoryException(get_class($ordering->getOperand()) ." operand not supported");
            }
            $properties[$ordering->getOperand()->getPropertyName()] = $ordering->getOrder();
        }

        $tmp = array();
        foreach ($properties as $property => $order) {
            foreach ($this->result as $guid => $p) {
                if (isset($this->result[$guid][$property])) {
                    $tmp[$guid] = $this->result[$guid][$property];
                } else {
                    $tmp[$guid] = null;
                }
            }
            if ($order == 'jcr.order.ascending') {
                asort($tmp, SORT_STRING);
            } else {
                arsort($tmp, SORT_STRING);
            }
        }
        foreach ($tmp as $guid => $p) {
            $tmp[$guid] = $this->result[$guid];
        }
        $this->result = $tmp;
    }

    public function getRows()
    {
        if ($this->rows != null) {
            return $this->rows;
        }

        $this->orderResult();

        $score = 0;
        $ret = array();
        foreach ($this->result as $guid => $p) {
            $ret[] = new Row($this, ++$score, $this->result);
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

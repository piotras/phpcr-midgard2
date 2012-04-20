<?php

namespace Midgard\PHPCR\Query\SelectData;

use Midgard\PHPCR\Query\Utils\QuerySelectDataHolder;
use Midgard\PHPCR\Query\QuerySelectDataResult;
use \MidgardSqlQueryRow;
use Midgard\PHPCR\Utils\NodeMapper;

class Row extends \Midgard\PHPCR\Query\Row
{
    protected $session = null;
    protected $row = null;
    protected $holder = null;
    protected $rows = null;

    public function __construct(Result $result, $score, array $rows)
    {
        $this->queryResult = $result; 
        $this->holder = $result->getHolder();
        $this->score = $score;
        $this->rows = $rows;
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
        throw new \Exception("TO IMPLEMENT");
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

        if (in_array($columnName, $this->queryResult->getColumnNames())) {
            throw new \PHPCR\ItemNotFoundException("Value for '{$columnName}' not found");
        }

        $ret = null;
        $i = 1;
        foreach ($this->rows as $guid => $p) {
            if ($i == $this->score) {
                $ret = $this->rows[$guid][$colName];
            }
            $i++;
        }
        return $ret;
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

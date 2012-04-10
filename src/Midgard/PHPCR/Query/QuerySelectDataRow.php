<?php

namespace Midgard\PHPCR\Query;

use Midgard\PHPCR\Query\Utils\QuerySelectDataHolder;
use \MidgardSqlQueryRow;

class QuerySelectDataRow extends Row
{
    protected $results = null;
    protected $session = null;
    protected $midgardRow = null;
    protected $holder = null;

    public function __construct(QuerySelectDataHolder $holder, $score, MidgardSqlQueryRow $midgardRow)
    {
        $this->holder = $holder;
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
        return $this->midgardRow->get_value($columnName);
    }

    private function populateValues()
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

<?php
namespace Midgard\PHPCR\Query\SelectData;

use Midgard\PHPCR\Query\Utils\QuerySelectDataHolder;
use \MidgardQuerySelect;
use \MidgardQueryStorage;
use \MidgardQueryConstraint;
use \MidgardQueryProperty;
use \MidgardQueryValue;
use Midgard\PHPCR\Utils\NodeMapper;
use Midgard\PHPCR\Query\Utils\QueryNameMapper;

class NodeExecutor extends Executor
{
    public function __construct(QuerySelectDataHolder $holder)
    {
        $this->holder = $holder;
        $this->query = $holder->getSQLQuery();
    }

    public function executeQuery()
    {
        if ($this->result != null) {
            return;
        }

        $this->executors = array();
        foreach ($this->query->getSelectors() as $selector) {
            $qs = new MidgardQuerySelect(new MidgardQueryStorage (QueryNameMapper::NODE));
            $qs->set_constraint(
                new MidgardQueryConstraint(
                    new MidgardQueryProperty("typename"),
                    "=",
                    new MidgardQueryValue(NodeMapper::getMidgardName($selector->getNodeTypeName()))
                )
            );
            $qs->execute();
            $this->executors[] = $qs;
        }
    }

    public function getExecutors()
    {
        return $this->executors;
    }

    public function getQueryResult()
    {
        if ($this->result != null) {
            return $this->result;
        }
        $this->result = array();
        foreach ($this->executors as $e) {
            $objects = $e->list_objects();
            foreach ($objects as $o) {
                $this->result[$o->guid] = null;
            }
        } 
        return $this->result;
    }

    public function mergeResult(Executor $executor)
    {
        $ret = $this->getQueryResult();
        $exret = $executor->getQueryResult();
        return array_merge($ret, $exret);
    }
}

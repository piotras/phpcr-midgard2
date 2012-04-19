<?php
namespace Midgard\PHPCR\Query\SelectData;

use Midgard\PHPCR\Query\Utils\QuerySelectDataHolder;
use Midgard\PHPCR\Query\QOM\QuerySelectHelper;
use Midgard\PHPCR\Utils\NodeMapper;
use Midgard\PHPCR\Query\Utils\QueryNameMapper;
use \MidgardQuerySelect;
use \MidgardQueryStorage;
use \MidgardQueryConstraint;
use \MidgardQueryConstraintGroup;
use \MidgardQueryProperty;
use \MidgardQueryValue;
use \MidgardSqlQuerySelectData;
use \MidgardConnection;

class PropertyExecutor extends Executor
{
    private $executors = null;

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

        $qs = new MidgardSqlQuerySelectData(MidgardConnection::get_instance());
        $cg = new MidgardQueryConstraintGroup("AND");
        $cgOR = new MidgardQueryConstraintGroup("OR");
        $i = 0;

        foreach ($this->query->getSelectors() as $selector) {
            foreach ($this->query->getColumns() as $column) {
                $column->setQuery($this->query);
                $columnName = $column->getColumnName();
                $propertyName = $column->getPropertyName();
                $selectorName = $column->getSelectorName();
                if ($selectorName != $selector->getSelectorName()) {
                    continue;
                }
                /* SELECT node's guid */
                $column = QuerySelectHelper::createMidgard2SqlColumn(
                    "parentguid", 
                    QueryNameMapper::NODE_PROPERTY,
                    $selectorName, 
                    QueryNameMapper::NODE_GUID
                );
                $qs->add_column($column);

                /* SELECT property's name */
                $column = QuerySelectHelper::createMidgard2SqlColumn(
                    "name", 
                    QueryNameMapper::NODE_PROPERTY,
                    $selectorName, 
                    "name"
                );
                $qs->add_column($column);

                /* Add constraint for given named property */
                $constraint = QuerySelectHelper::createMidgard2SqlConstraint($column, "=", $propertyName);
                $i > 1 ? $cgOR->add_constraint($constraint) : $cg->add_constraint($constraint);
                
                $constraint = QuerySelectHelper::createMidgard2SqlConstraint($column, "<>", "");
                $i > 1 ? $cgOR->add_constraint($constraint) : $cg->add_constraint($constraint);

                /* SELECT property's value */
                $column = QuerySelectHelper::createMidgard2SqlColumn(
                    "value", 
                    QueryNameMapper::NODE_PROPERTY, 
                    $selectorName, 
                    "value"
                );
                $qs->add_column($column);

                /* TODO, Add join on parent node and node's typename constraint */

                $i++;
            }
        }

        if ($i > 2) {
            $cg->add_constraint($cgOR);
        }

        $qs->set_constraint($cg);
        try {
            $qs->execute();
        } catch (\Exception $e) {
            echo $qs->get_query_string();
        }
        $this->executors[]= $qs;
    }

    public function getQueryResult()
    {
        $ret = array();
        foreach ($this->executors as $e) {
            try {
                $qr = $e->get_query_result();
            } catch (\Exception $e) {
                die ($e->getMessage());
            }
            foreach ($qr->get_rows() as $row) {
                $ret[$row->get_value(QueryNameMapper::NODE_GUID)] = $row->get_value("name");
            }
        }
        print_r($ret);
        return $ret;
    }
}

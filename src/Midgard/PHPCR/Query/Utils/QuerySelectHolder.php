<?php
namespace Midgard\PHPCR\Query\Utils;

use Midgard\PHPCR\Query\SQLQuery;
use \midgard_connection;
use \MidgardSqlQuerySelectData;
use \MidgardSqlQueryColumn;
use \MidgardQueryProperty;
use Midgard\PHPCR\Utils\NodeMapper;
use Midgard\PHPCR\Query\QueryResult;

class QuerySelectHolder
{
    protected $query = null;
    protected $querySelect = null;
    protected $querySelectData = null;
    protected $propertyStorage = null;
    protected $defaultNodeStorage = null;
    protected $defaultConstraintGroup = null;
    protected $midgardStorageName = null;
    protected $midgardQueryColumns = null;

    public function __construct (SQLQuery $query)
    {
        $this->query = $query;
    }

    public function getSQLQuery()
    {
        return $this->query;
    }

    public function setMidgardStorageName($name)
    {
        $this->midgardStorageName = $name;
    }

    public function getMidgardStorageName()
    {
        return $this->midgardStorageName;
    }

    public function getDefaultNodeStorage()
    {
        if ($this->defaultNodeStorage == null)
            $this->defaultNodeStorage = new \midgard_query_storage('midgard_node');
        return $this->defaultNodeStorage;
    }

    public function getQuerySelect()
    {
        if ($this->querySelect == null) {
            $this->querySelect = new \midgard_query_select($this->getDefaultNodeStorage());
        
            /* Implictly add nodetype constraint */
            $this->getDefaultConstraintGroup()->add_constraint(
                new \midgard_query_constraint(
                    new \midgard_query_property("typename"),
                    "=",
                    new \midgard_query_value($this->getMidgardStorageName())
                )
            );

            /* Workaround for 'invalid number of operands' */
            $this->getDefaultConstraintGroup()->add_constraint(
                new \midgard_query_constraint(
                    new \midgard_query_property("typename"),
                    "<>",
                    new \midgard_query_value("")
                )
            );
        }
        return $this->querySelect;
    }

    public function addMidgardQueryColumns() {
        $this->getMidgardQueryColumns();
    }

    public function getPropertyStorage()
    {
        if ($this->propertyStorage == null)
            $this->propertyStorage = new \midgard_query_storage('midgard_node_property');
        return $this->propertyStorage;
    }

    public function getDefaultConstraintGroup()
    {
        if ($this->defaultConstraintGroup == null)
            $this->defaultConstraintGroup = new \midgard_query_constraint_group("AND");
        return $this->defaultConstraintGroup;
    }

    protected function executeQuery()
    {
        $querySelect = $this->getQuerySelect();
        $querySelect->set_constraint($this->getDefaultConstraintGroup());

        /* Ugly hack to satisfy JCR Query.
         * We use SQL so offset without limit is RDBM provider specific.
         * In SQLite you can set negative limit which is invalid in MySQL for example. */

        if ($this->query->getOffset() > 0 && $this->query->getLimit() == 0) {
            $this->query->setLimit(9999);
        }

        //\midgard_connection::get_instance()->set_loglevel("debug");
        //\midgard_error::debug("EXECUTE QUERY : " . $this->query->getStatement() . "");
        try {
            $querySelect->execute();
        } catch (\Exception $e) {
            echo "CATCHED EXCEPTION \n";
            echo "QUERY TYPE : " . get_class ($querySelect) . "\n";
            throw $e;
        }
        //\midgard_connection::get_instance()->set_loglevel("warn");
    }

    public function getQueryResult()
    {
        $this->executeQuery();
        return new QueryResult($this->query, $this->querySelect, $this->query->getSession());
    }

    public function getSession()
    {
        return $this->query->getSession();
    }
}

<?php
namespace Midgard\PHPCR\Query\Utils;

use Midgard\PHPCR\Query\SQLQuery;
use \midgard_connection;
use \MidgardSqlQuerySelectData;
use \MidgardSqlQueryColumn;
use \MidgardQueryProperty;
use \MidgardQueryStorage;
use Midgard\PHPCR\Utils\NodeMapper;
use Midgard\PHPCR\Query\QueryResultDataSelector;

class QuerySelectDataHolder extends QuerySelectHolder
{
    protected $midgardQueryColumns = array();

    const NODE_QUALIFIER = 'midgard_node_qualifier';

    public function __construct (SQLQuery $query)
    {
        parent::__construct($query);
    }

    public function getQuerySelectTMP()
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

    public function getQuerySelect()
    {
        if ($this->querySelect === null) {
            $this->querySelect = new MidgardSqlQuerySelectData(midgard_connection::get_instance());
            $this->setMidgardQueryColumns();
        }
        return $this->querySelect;
    }

    protected function executeQuery()
    {
        echo "\n PHPCR QUERY : \n" . $this->query->getStatement() . "\n";
        $querySelect = $this->getQuerySelect();
        try {
            $querySelect->execute();
            print "\n MIDGARD QUERY : \n " . $querySelect->get_query_string() . " \n";
        } catch (\Exception $e) {
            print "\n EXCEPTION MIDGARD QUERY : \n " . $querySelect->get_query_string() . " \n";
            print $e->getMessage();
        }
    }

    public function getQueryResult()
    {
        /* Let every source add their constraints */
        $this->query->getSource()->addMidgard2Constraints($this);
        
        /* Execute the query */
        $this->executeQuery();

        /* Return Result */
        return new QueryResultDataSelector($this);
    }

    private function getSelectorByName($name) 
    {
        foreach ($this->query->getSelectors() as $selector) {
           if ($selector->getSelectorName() == $name) {
                return $selector;
            }
        }
    }

    private function isNativeProperty($classname, $property)
    {
        if (array_key_exists($property, \MidgardReflectorObject::list_defined_properties($classname))) {
            return true;
        }
        return false;
    }

    private function setMidgardQueryColumns()
    {
        if ($this->midgardQueryColumns != null) {
            return $this->midgardQueryColumns;
        } 

        $querySelect = $this->getQuerySelect();

        foreach ($this->query->getColumns() as $column) {
            $selectorName = $column->getSelectorName();
            $midgardName = NodeMapper::getMidgardPropertyName(str_replace(array('[', ']'), '', $column->getPropertyName()));
            $realPropertyName = $midgardName;
            $selector = $this->getSelectorByName($selectorName);
            $nodeTypeName = NodeMapper::getMidgardName(str_replace(array('[', ']'), '', $selector->getNodeTypeName()));
            $realClassName = $nodeTypeName;

            if ($this->isNativeProperty($realClassName, $midgardName) === false) {
                /* Fallback to default midgard_node_property storage */
                $realPropertyName = 'value';
                $realClassName = 'midgard_node_property';
                $addJoin = true;
            }

            if (!isset($this->midgardQueryColumns[$selectorName]['storage'])) {
                $this->midgardQueryColumns[$selectorName]['storage'] = new MidgardQueryStorage($realClassName);
            }

            $columnName = $column->getColumnName();
            if ($columnName == '' || $columnName == null) {
                $columnName = $midgardName;
            }
        
            $safeColumnName = str_replace(':', 'SEMICOLON', $midgardName);
            $safeSelectorName = $column->getSelectorName();
            if ($safeSelectorName == '' && $safeSelectorName == null) {
                $safeSelectorName = $nodeTypeName;
            }

            if (!isset($this->midgardQueryColumns[$selectorName]['join'])
                || $this->midgardQueryColumns[$selectorName]['join'] === false) { 
                /* Add implicit join so we can select proper value of proper node type */
                if (!isset($this->midgardQueryColumns[$selectorName]['nodeStorage'])) {
                    $this->midgardQueryColumns[$selectorName]['nodeStorage'] = new MidgardQueryStorage('midgard_node');
                }

                /* JOIN midgard_node AS midgard_node_qualifier ON (midgard_node.id = midgard_node_property.parent) */ 
                $propertyColumn = new MidgardSqlQueryColumn(
                    new MidgardQueryProperty('parent', $this->midgardQueryColumns[$selectorName]['storage']),
                    $safeSelectorName,
                    'midgard_node_property_parent_id'
                );
                $nodeColumn = new MidgardSqlQueryColumn(
                    new MidgardQueryProperty('id', $this->getMidgard2QueryNodeStorage($selectorName)),
                    self::NODE_QUALIFIER,
                    'midgard_node_id'
                );
                $querySelect->add_join(
                    'INNER',
                    $propertyColumn,
                    $nodeColumn
                );

                $querySelect->add_column($propertyColumn);
                $querySelect->add_column($nodeColumn);

                /* Add implicit midgard_guid column so we can easily get object if requested */
                $querySelect->add_column(
                    new MidgardSqlQueryColumn(
                        new MidgardQueryProperty('guid', $this->getMidgard2QueryNodeStorage($selectorName)),
                        self::NODE_QUALIFIER,
                        'midgard_node_guid'
                    )
                );

                //$cg = new \MidgardQueryConstraintGroup("AND");
                $cg = $this->getDefaultConstraintGroup();
                $cg->add_constraint(
                    new \MidgardSqlQueryConstraint($propertyColumn,
                        "<>", 
                        new \MidgardQueryValue("")
                    )
                );
                $cg->add_constraint(
                    new \MidgardSqlQueryConstraint($nodeColumn,
                        "<>", 
                        new \MidgardQueryValue("")
                    )
                );
                $querySelect->set_constraint($cg);
            }

            $midgardQueryColumn = new MidgardSqlQueryColumn(
                new MidgardQueryProperty($realPropertyName, $this->midgardQueryColumns[$selectorName]['storage']),
                $safeSelectorName,
                $safeColumnName 
            );
            $this->midgardQueryColumns[$selectorName]['columns'][] = $midgardQueryColumn;
            $this->midgardQueryColumns[$selectorName]['join'] = true;
        }

        foreach ($this->midgardQueryColumns as $name => $data) { 
            foreach ($data['columns'] as $col) {
                $querySelect->add_column($col);
            }
        }        
    }

    public function getMidgard2QueryNodeStorage($selectorName)
    {
        if (isset($this->midgardQueryColumns[$selectorName])) {
            return $this->midgardQueryColumns[$selectorName]['nodeStorage'];
        }
        return $this->getDefaultNodeStorage();
    }

    public function getNodeQualifier()
    {
        return self::NODE_QUALIFIER;
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
}

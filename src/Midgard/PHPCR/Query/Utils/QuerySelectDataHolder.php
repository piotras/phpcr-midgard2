<?php
namespace Midgard\PHPCR\Query\Utils;

use Midgard\PHPCR\Query\SQLQuery;
use \midgard_connection;
use \MidgardSqlQuerySelectData;
use \MidgardSqlQueryColumn;
use \MidgardQueryProperty;
use \MidgardQueryStorage;
use Midgard\PHPCR\Utils\NodeMapper;
use Midgard\PHPCR\Query\Utils\QueryNameMapper;
use Midgard\PHPCR\Query\QuerySelectDataResult;
use Midgard\PHPCR\Query\SelectData\NodeExecutor;
use Midgard\PHPCR\Query\SelectData\PropertyExecutor;
use Midgard\PHPCR\Query\SelectData\Result;

class QuerySelectDataHolder extends QuerySelectHolder
{
    protected $midgardQueryColumns = array();
    protected $nodeExecutor = null;
    protected $propertyExecutor = null;

    public function __construct (SQLQuery $query)
    {
        parent::__construct($query);
    }

    private function initializeMidgard2QuerySelectData()
    {
        if ($this->querySelect === null) {
            $this->querySelect = new MidgardSqlQuerySelectData(midgard_connection::get_instance());
            $this->setMidgardQueryColumns();
        }
    }

    public function getQuerySelect()
    {
        if ($this->querySelect == null) {
            $this->initializeMidgard2QuerySelectData();
        }
        return $this->querySelect;
    }

    protected function executeQuery()
    {
        echo "\n PHPCR QUERY : \n" . $this->query->getStatement() . "\n";
        $querySelect = $this->getQuerySelect();

        $this->nodeExecutor = new NodeExecutor($this);
        $this->nodeExecutor->executeQuery(); 

        $this->propertyExecutor = new PropertyExecutor($this);
        $this->propertyExecutor->executeQuery(); 

        /* Set limit */
        $limit = $this->query->getLimit();
        if ($limit > 0) {
            $querySelect->set_limit($limit);
        }

        /* Set offset */
        $offset = $this->query->getOffset();
        if ($offset > 0) {
            $querySelect->set_offset($offset);
        }

        $constraint = $this->query->getCOnstraint();
        if ($constraint != null) {
            $constraint->addMidgard2QSDCOnstraints($this);
        }

        /* Ugly hack to satisfy JCR Query.
         * We use SQL so offset without limit is RDBM provider specific.
         * In SQLite you can set negative limit which is invalid in MySQL for example. */
        if ($offset > 0 && $limit == 0) {
            $querySelect->set_limit(9999);
        }

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
        /* Initialize underlying QuerySelectData */
        $this->initializeMidgard2QuerySelectData();

        /* Let every source add their constraints */
        $this->query->getSource()->addMidgard2QSDConstraints($this);
        
        /* Execute the query */
        $this->executeQuery();

        $this->nodeExecutor->getQueryResult();
        $this->propertyExecutor->getQueryResult();

        return new Result($this, $this->nodeExecutor->mergeResult($this->propertyExecutor));
        /* Return Result */
        return new QuerySelectDataResult($this);
    }

    public function getSelectorByName($name) 
    {
        foreach ($this->query->getSelectors() as $selector) {
           if ($selector->getSelectorName() == $name) {
                return $selector;
            }
        }
        return null;
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
            return;
        } 

        if (count($this->query->getColumns()) < 1) {
            $this->setColumns($this->query->getSource()->getAllColumns($this));
            return;
        }

        $this->setColumns($this->query->getColumns());
    }

    private function setColumns($columns)
    {
        $querySelect = $this->getQuerySelect();
        foreach ($columns as $column) {
            $column->setQuery($this->query);
            $selectorName = $column->getSelectorName();
            $midgardName = NodeMapper::getMidgardPropertyName(str_replace(array('[', ']'), '', $column->getPropertyName()));
            $realPropertyName = $midgardName;
            $selector = $this->getSelectorByName($selectorName);
            $nodeTypeName = is_object($selector) ? $selector->getNodeTypeName() : $selectorName;
            $nodeTypeName = NodeMapper::getMidgardName(str_replace(array('[', ']'), '', $nodeTypeName));
            $realClassName = NodeMapper::getMidgardName($nodeTypeName);

            if ($this->isNativeProperty($realClassName, $midgardName) === false) {
                /* Fallback to default midgard_node_property storage */
                $realPropertyName = 'value';
                $realClassName = QueryNameMapper::NODE_PROPERTY;
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
            $safeSelectorName = NodeMapper::getMidgardName($safeSelectorName);

            if (!isset($this->midgardQueryColumns[$selectorName]['join']) || $this->midgardQueryColumns[$selectorName]['join'] === false) {

                /* Add implicit join so we can select proper value of proper node type */
                if ($realClassName != QueryNameMapper::NODE_PROPERTY) {
                    /* JOIN phpcr_classname AS phpcr_classname ON (phpcr_classname.guid = midgard_node.objectguid) */
                     $propertyColumn = new MidgardSqlQueryColumn(
                        new MidgardQueryProperty('guid', $this->midgardQueryColumns[$selectorName]['storage']),
                        $safeSelectorName,
                        QueryNameMapper::getGuidName($safeSelectorName)
                    );
                    $nodeColumn = new MidgardSqlQueryColumn(
                        new MidgardQueryProperty('objectguid', $this->getMidgard2QueryNodeStorage($selectorName)),
                        QueryNameMapper::NODE_QUALIFIER,
                        QueryNameMapper::NODE_ID
                    );
                    $querySelect->add_join(
                        'INNER',
                        $propertyColumn,
                        $nodeColumn
                    );                  
                } else {
                    /* JOIN midgard_node AS midgard_node_qualifier ON (midgard_node.id = midgard_node_property.parent) */ 
                    $propertyColumn = new MidgardSqlQueryColumn(
                        new MidgardQueryProperty('parent', $this->midgardQueryColumns[$selectorName]['storage']),
                        $safeSelectorName,
                        'midgard_node_property_parent_id'
                    );
                    $nodeColumn = new MidgardSqlQueryColumn(
                        new MidgardQueryProperty('id', $this->getMidgard2QueryNodeStorage($selectorName)),
                        QueryNameMapper::NODE_QUALIFIER,
                        QueryNameMapper::NODE_ID
                    );
                    $querySelect->add_join(
                        'INNER',
                        $propertyColumn,
                        $nodeColumn
                    );
                }

                $querySelect->add_column($propertyColumn);
                $querySelect->add_column($nodeColumn);

                if ($realClassName == QueryNameMapper::NODE_PROPERTY) {
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
            }
 
            $midgardQueryColumn = new MidgardSqlQueryColumn(
                new MidgardQueryProperty($realPropertyName, $this->midgardQueryColumns[$selectorName]['storage']),
                $safeSelectorName,
                $safeColumnName 
            );
            $this->midgardQueryColumns[$selectorName]['columns'][] = $midgardQueryColumn;
            $this->midgardQueryColumns[$selectorName]['join'] = true;
        }

        /* Add implicit midgard_guid column so we can easily get object if requested */
        $querySelect->add_column(
            new MidgardSqlQueryColumn(
                new MidgardQueryProperty('guid', $this->getMidgard2QueryNodeStorage($selectorName)),
                QueryNameMapper::NODE_QUALIFIER,
                QueryNameMapper::NODE_GUID 
            )
        );

        foreach ($this->midgardQueryColumns as $name => $data) { 
            foreach ($data['columns'] as $col) {
                $querySelect->add_column($col);
            }
        }        
    }

    public function getMidgard2QueryNodeStorage($selectorName)
    {
        if (isset($this->midgardQueryColumns[$selectorName])) {
            if (!isset($this->midgardQueryColumns[$selectorName]['nodeStorage'])) {
                $this->midgardQueryColumns[$selectorName]['nodeStorage'] = new MidgardQueryStorage('midgard_node');
            }
            return $this->midgardQueryColumns[$selectorName]['nodeStorage'];
        }
        return null;
    }

    public function getMidgard2QueryNodePropertyStorage($selectorName)
    {
        print_r(array_keys($this->midgardQueryColumns));
        if (isset($this->midgardQueryColumns[$selectorName])) {
            if (!isset($this->midgardQueryColumns[$selectorName]['storage'])) {
                $this->midgardQueryColumns[$selectorName]['storage'] = new MidgardQueryStorage('midgard_node_property');
            }
            return $this->midgardQueryColumns[$selectorName]['storage'];
        }
        return null;
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

<?php

namespace Midgard\PHPCR\Query\QOM;

use Midgard\PHPCR\Utils\NodeMapper;
use Midgard\PHPCR\Query\Utils\QuerySelectDataHolder;
use Midgard\PHPCR\Query\Utils\QueryNameMapper;
use \MidgardSqlQueryColumn;
use \MidgardQueryProperty;
use \MidgardSqlQueryConstraint;
use \MidgardQueryValue;

/**
 * {@inheritDoc}
 */
class DescendantNodeJoinCondition extends ConditionHelper implements \PHPCR\Query\QOM\DescendantNodeJoinConditionInterface
{
    protected $descendantSelector = null;
    protected $ancestorSelector = null;

    public function __construct($descendantSelectorName, $ancestorSelectorName)
    {
        $this->descendantSelector = $descendantSelectorName;
        $this->ancestorSelector = $ancestorSelectorName;
    }

    public function computeQuerySelectConstraints($holder)
    {
        parent::computeQuerySelectConstraints($holder);
        foreach ($this->holder->getSQLQuery()->getSelectors() as $selector) {
            if ($selector->getSelectorName() == $this->getDescendantSelectorName()) {
                $nodeTypeName = $selector->getNodeTypeName();
                $this->holder->setMidgardStorageName(NodeMapper::getMidgardName($nodeTypeName));
            }
        }

        /* TODO
         * Compute correct node type name.
         *
         * Before QuerySelectData is available in release, we do set descendant node type asdefault one.
         * This one should be compared with defined constraints and columns */
    }

    /**
     * {@inheritDoc}
     */
    public function getDescendantSelectorName()
    {
        return $this->descendantSelector;
    }

    /**
     * {@inheritDoc}
     */
    public function getAncestorSelectorName()
    {
        return $this->ancestorSelector;
    }

    /**
     * {@inheritDoc}
     */
    public function getLeft()
    {
        throw new PHPCR\RepositoryException("Not supported");
    }

    /**
     * {@inheritDoc}
     */
    public function getRight()
    {
        throw new PHPCR\RepositoryException("Not supported");
    }

    /**
     * {@inheritDoc}
     */
    public function getJoinType()
    {
        throw new PHPCR\RepositoryException("Not supported");
    }

    /**
     * {@inheritDoc}
     */
    public function getJoinCondition()
    {
        throw new \PHPCR\RepositoryException("Not supported");
    }

    public function addMidgard2QSDConstraints(QuerySelectDataHolder $holder)
    {
        $descendSelector = null;
        $ancestSelector = null;

        /* From query get selectors defined for this join */
        $selectors = $holder->getSQLQuery()->getSelectors();
        foreach ($selectors as $selector) {
            if ($selector->getSelectorName() == $this->descendantSelector) {
                $descendSelector = $selector;
            }
            if ($selector->getSelectorName() == $this->ancestorSelector) {
                $ancestSelector = $selector;
            }
        }

        /* Set descendant and ancestor identifiers used in join */
        $descendName = $descendSelector->getSelectorName();
        $descendNodeType = $descendSelector->getNodeTypeName();
        //echo "DESCENT  $descendName $descendNodeType  \n";
        $descendStorage = $holder->getMidgard2QueryNodeStorage($descendName);
        $descendColumn = new MidgardSqlQueryColumn(
            new MidgardQueryProperty('parent', $descendStorage),
            QueryNameMapper::NODE_QUALIFIER,
            'midgard_node_descendant_id'
        );

        $ancestName = $ancestSelector->getSelectorName();
        $ancestNodeType = $ancestSelector->getNodeTypeName();
        $ancestStorage = $holder->getMidgard2QueryNodeStorage($ancestName);
        if ($ancestStorage == null) {
            $ancestStorage = $holder->getMidgard2QueryNodeStorage($descendName);
        }
        //echo "ANCEST $ancestName $ancestNodeType \n";
        $ancestColumn = new MidgardSqlQueryColumn(
            new MidgardQueryProperty('id', $ancestStorage),
            $ancestName,
            'midgard_node_ancestor_id'
        );

        /* Join descendant and ancestor */
        $querySelect = $holder->getQuerySelect();
        $querySelect->add_join(
            'INNER',
            $descendColumn,
            $ancestColumn
        );

        $querySelect->add_column($descendColumn);
        $querySelect->add_column($ancestColumn);

        /* Add descendant and ancestor typename constraints */
        $descendColumnType = new MidgardSqlQueryColumn(
            new MidgardQueryProperty('typename', $descendStorage),
            $descendColumn->get_qualifier(),
            'midgard_node_descendant_type'
        );

        $ancestColumnType = new MidgardSqlQueryColumn(
            new MidgardQueryProperty('typename', $ancestStorage),
            $ancestName,
            'midgard_node_ancestor_type'
        );

        $cg = $holder->getDefaultConstraintGroup();
        $cg->add_constraint(
            new \MidgardSqlQueryConstraint($descendColumnType,
                "=",
                new \MidgardQueryValue(NodeMapper::getMidgardName($descendNodeType))
            )
        );
        $cg->add_constraint(
            new \MidgardSqlQueryConstraint($ancestColumnType,
                "=",
                new \MidgardQueryValue(NodeMapper::getMidgardName($ancestNodeType))
            )
        );
    }
}

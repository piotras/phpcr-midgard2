<?php

namespace Midgard\PHPCR\Query\QOM;

use Midgard\PHPCR\Utils\NodeMapper;
use Midgard\PHPCR\Query\Utils\QuerySelectDataHolder;
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
        echo "DESCENT " . $this->descendantSelector . "\n";
        echo "ANCEST " .  $this->ancestorSelector . "\n";

        $descendSelector = null;
        $ancestSelector = null;
        $selectors = $holder->getSQLQuery()->getSelectors();
        foreach ($selectors as $selector) {
            if ($selector->getSelectorName() == $this->descendantSelector) {
                $descendSelector = $selector;
            }
            if ($selector->getSelectorName() == $this->ancestorSelector) {
                $ancestSelector = $selector;
            }
        }

        $descendColumn = new MidgardSqlQueryColumn(
            new MidgardQueryProperty('parent'),
            $descendSelector->getSelectorName(),
            'midgard_node_descendant_parent_id'
        );

        $ancestColumn = new MidgardSqlQueryColumn(
            new MidgardQueryProperty('id'),
            $ancestSelector->getSelectorName(),
            'midgard_node_ancestor_id'
        );

        $holder->getQuerySelect()->add_join(
            'INNER',
            $descendColumn,
            $ancestColumn
        );

        $cg = $holder->getDefaultConstraintGroup();
        $cg->add_constraint(
            new \MidgardSqlQueryConstraint($descendColumn,
                "=",
                new \MidgardQueryValue(NodeMapper::getMidgardName($descendSelector->getSelectorName()))
            )
        );
        $cg->add_constraint(
            new \MidgardSqlQueryConstraint($ancestColumn,
                "=",
                new \MidgardQueryValue(NodeMapper::getMidgardName($descendSelector->getSelectorName()))
            )
        );

        //throw new \PHPCR\RepositoryException (get_class($this) . "::" . "addMidgard2QSDConstraints NOT IMPLEMENTED ");
    }
}

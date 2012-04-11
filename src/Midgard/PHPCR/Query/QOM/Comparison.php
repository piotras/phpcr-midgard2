<?php

namespace Midgard\PHPCR\Query\QOM;

use Midgard\PHPCR\Query\Utils\QuerySelectDataHolder;
use \MidgardSqlQueryColumn;
use \MidgardQueryProperty;
use \MidgardSqlQueryConstraint;
use \MidgardQueryValue;
use Midgard\PHPCR\Query\QOM\QuerySelectHelper;

/**
 * {@inheritDoc}
 */
class Comparison extends ConstraintHelper implements \PHPCR\Query\QOM\ComparisonInterface
{
    protected $operandFirst = null;
    protected $operator = null;
    protected $operandSecond = null;

    public function __construct(\PHPCR\Query\QOM\DynamicOperandInterface $operand1, $operator,
                \PHPCR\Query\QOM\StaticOperandInterface $operand2)
    {
        $this->operandFirst = $operand1;
        $this->operator = $operator;
        $this->operandSecond = $operand2;
    }

    /**
     * {@inheritDoc}
     */
    public function getOperand1()
    {
        return $this->operandFirst;
    }

    /**
     * {@inheritDoc}
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * {@inheritDoc}
     */
    public function getOperand2()
    {
        return $this->operandSecond;
    }

    public function addMidgard2QSDConstraints(QuerySelectDataHolder $holder)
    {
        $column = new MidgardSqlQueryColumn(
            new MidgardQueryProperty('value'),
            $this->operandFirst->getSelectorName(),
            $this->operandFirst->getPropertyName()
        );

        $cg = $holder->getDefaultConstraintGroup();
        $cg->add_constraint(
            new \MidgardSqlQueryConstraint($column,
                QuerySelectHelper::MapOperatorType($this->operator),
                new \MidgardQueryValue($this->operandSecond->getLiteralValue())
            )
        );
    }
}

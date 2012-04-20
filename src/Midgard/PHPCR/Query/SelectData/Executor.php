<?php
namespace Midgard\PHPCR\Query\SelectData;

abstract class Executor 
{
    protected $holder = null;
    protected $query = null;
    protected $result = null;
    protected $executors = null; 

    public abstract function executeQuery();
    public abstract function getQueryResult();
    public abstract function getExecutors(); 
    //public abstract function getGuid();
    //public abstract function getName();
    //public abstract function getValue();
}

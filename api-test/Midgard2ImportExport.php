<?php
require_once('Midgard2XMLImporter.php');

/**
 * Handles basic importing and exporting of fixtures into Midgard2
 */
class Midgard2ImportExport implements PHPCR\Test\FixtureLoaderInterface
{
    protected $fixturePath;

    /**
     * @param string $fixturePath path to the fixtures directory. defaults to dirname(__FILE__) . '/../fixtures/'
     */
    public function __construct($fixturePath = null)
    {
        if (is_null($fixturePath)) {
            $this->fixturePath = dirname(__FILE__) . '/../fixtures/';
        } else {
            $this->fixturePath = $fixturePath;
        }
        
        if (!is_dir($this->fixturePath)) {
            throw new Exception('Not a valid directory: ' . $this->fixturePath);
        }
    }

    private function cleanupChildren($object)
    {
        $mgd = \midgard_connection::get_instance();

        $children = $object->list();

        foreach ($children as $child)
        {
            self::cleanupChildren($child);
            $child->purge_attachments(true);
            if (!$child->purge(false))
            {
                //echo "Failed to purge child " . get_class($child) . " " . $child->guid . " " . $mgd->get_error_string() . "\n";
            }
        }

        if (is_a($object, 'midgard_node'))
        {
            $children = $object->list_children('midgard_node_property');
            foreach ($children as $child)
            {
                self::cleanupChildren($child);
                $child->purge_attachments(true);
                if (!$child->purge(false))
                {
                    //echo "Failed to purge child " . get_class($child) . " " . $child->guid . " " . $mgd->get_error_string() . "\n";
                }
            }
        }

        $object->purge_attachments(true);
        if (!$object->purge(false))
        {
            //echo "Failed to purge object " . get_class($object) . " " . $object->guid . " " . $mgd->get_error_string() . "\n";
        }
    }

    private function cleanup()
    { 
        $re = new ReflectionExtension('midgard2');
        $classes = $re->getClasses();
        $t = new \midgard_transaction();
        $t->begin();
        foreach ($classes as $refclass)
        {           
            $parent_class = $refclass->getParentClass();

            if (!$parent_class)
            {
                continue;
            }
            if ($parent_class->getName() != 'midgard_object')
            {
                continue;
            }
            
            $type = $refclass->getName();

            /* There's no table and nothing to purge if the type is mixin */
            $isMixin = \midgard_object_class::get_schema_value($type, 'isMixin');
            if ($isMixin == 'true')
            {
                continue;
            } 

            /* There's no table and nothing to purge if the type is abstract */
            $isAbstract = \midgard_object_class::get_schema_value($type, 'isAbstract');
            if ($isAbstract == 'true')
            {
                continue;
            } 

            $storage = new \midgard_query_storage($type);
            $qs = new \midgard_query_select($storage);
            $qs->toggle_readonly(true);

            try 
            {
                $qs->execute();
            }
            catch (\Exception $e)
            {
                continue;
            }

            if ($qs->resultscount == 0)
            {
                continue;
            }

            $ret = $qs->list_objects();
            foreach ($ret as $object)
            {
                if (is_a($object, 'midgard_node')
                    && property_exists($object, 'name')
                    && $object->name == 'jackalope')
                {
                    continue;
                }
                $object->purge_attachments(true);
                if (!$object->purge(false))
                {
                    if (\midgard_connection::get_instance()->get_error() == MGD_ERR_HAS_DEPENDANTS)
                    { 
                        self::cleanupChildren($object);
                    } 
                }
            }        
        }
        $t->commit();
    }

    /**
     * import the jcr dump into jackrabbit
     * @param string $fixture path to the fixture file, relative to fixturePath
     * @throws Exception if anything fails
     */
    public function import($fixture)
    {
        $fixture = $this->fixturePath . $fixture . ".xml";
     
        if (!is_readable($fixture)) {
            throw new Exception('Fixture not readable at: ' . $fixture);
        }

        self::cleanup(); 

        $importer = new Midgard2XMLImporter($fixture);
        $importer->execute();

        return true;
    }

    /**
     * export a document view to a file
     *
     * TODO: add path parameter so you can export just content parts (exporting / exports jcr:system too, which is huge and ugly)
     * @param $file path to the file, relative to fixturePath. the file may not yet exist
     * @throws Exception if the file already exists or if the export fails
     */
    public function exportdocument($file)
    {
        $fixture = $this->fixturePath . $file;

        if (is_readable($fixture)) {
            throw new Exception('File existing: ' . $fixture);
        }

        throw new Exception ("exportdocument not implemented yet");

        return true;
    }
}

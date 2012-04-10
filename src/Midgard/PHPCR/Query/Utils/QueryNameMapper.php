<?php
namespace Midgard\PHPCR\Query\Utils;

class QueryNameMapper 
{
    const NODE_QUALIFIER = 'midgard_node_qualifier';
    const NODE_GUID = 'midgard_node_guid';
    const NODE_ID = 'midgard_node_id';
    const NODE_PROPERTY = 'midgard_node_property';
    const MIDGARD_GUID_SUFFIX = '_midgard_guid';

    private static $midgard_reserved_names = array (
        self::NODE_QUALIFIER,
        self::NODE_GUID,
        self::NODE_ID,
        self::NODE_PROPERTY
    );

    public static function isReservedName($name)
    {
        if (in_array($name, self::$midgard_reserved_names)) {
            return true;
        }
        
        /* guid suffix */
        if (strpos($name, self::MIDGARD_GUID_SUFFIX) !== false) {
            return true;
        }

        return false;
    }

    public static function getNodeQualifier()
    {
        return self::NODE_QUALIFIER;
    }

    public static function getGuidName($name)
    {
        return $name . self::MIDGARD_GUID_SUFFIX;
    }
}

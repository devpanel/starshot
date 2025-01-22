<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: opentelemetry/proto/profiles/v1development/profiles.proto

namespace Opentelemetry\Proto\Profiles\V1development;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Represents a mapping between Attribute Keys and Units.
 *
 * Generated from protobuf message <code>opentelemetry.proto.profiles.v1development.AttributeUnit</code>
 */
class AttributeUnit extends \Google\Protobuf\Internal\Message
{
    /**
     * Index into string table.
     *
     * Generated from protobuf field <code>int32 attribute_key_strindex = 1;</code>
     */
    protected $attribute_key_strindex = 0;
    /**
     * Index into string table.
     *
     * Generated from protobuf field <code>int32 unit_strindex = 2;</code>
     */
    protected $unit_strindex = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $attribute_key_strindex
     *           Index into string table.
     *     @type int $unit_strindex
     *           Index into string table.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Opentelemetry\Proto\Profiles\V1Development\Profiles::initOnce();
        parent::__construct($data);
    }

    /**
     * Index into string table.
     *
     * Generated from protobuf field <code>int32 attribute_key_strindex = 1;</code>
     * @return int
     */
    public function getAttributeKeyStrindex()
    {
        return $this->attribute_key_strindex;
    }

    /**
     * Index into string table.
     *
     * Generated from protobuf field <code>int32 attribute_key_strindex = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setAttributeKeyStrindex($var)
    {
        GPBUtil::checkInt32($var);
        $this->attribute_key_strindex = $var;

        return $this;
    }

    /**
     * Index into string table.
     *
     * Generated from protobuf field <code>int32 unit_strindex = 2;</code>
     * @return int
     */
    public function getUnitStrindex()
    {
        return $this->unit_strindex;
    }

    /**
     * Index into string table.
     *
     * Generated from protobuf field <code>int32 unit_strindex = 2;</code>
     * @param int $var
     * @return $this
     */
    public function setUnitStrindex($var)
    {
        GPBUtil::checkInt32($var);
        $this->unit_strindex = $var;

        return $this;
    }

}


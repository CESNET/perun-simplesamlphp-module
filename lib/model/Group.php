<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\model;

/**
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class Group implements HasId
{
    private $id;

    private $voId;

    private $uuid;

    private $name;

    private $uniqueName;

    private $description;

    /**
     * Group constructor.
     *
     * @param $id
     * @param $voId
     * @param $name
     * @param $uniqueName
     * @param $description
     */
    public function __construct($id, $voId, $uuid, $name, $uniqueName, $description)
    {
        $this->id = $id;
        $this->voId = $voId;
        $this->uuid = $uuid;
        $this->name = $name;
        $this->uniqueName = $uniqueName;
        $this->description = $description;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getVoId()
    {
        return $this->voId;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getUniqueName()
    {
        return $this->uniqueName;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}

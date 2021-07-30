<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\model;

/**
 * Created by PhpStorm. User: pavel Date: 9.4.18 Time: 9:43
 */
class Facility implements HasId
{
    private $id;

    private $name;

    private $description;

    private $entityId;

    /**
     * Facility constructor.
     *
     * @param int $id
     * @param string $name
     * @param string $description
     * @param string $entityId
     */
    public function __construct($id, $name, $description, $entityId)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->entityId = $entityId;
    }

    public function getId()
    {
        return $this->id;
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
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getEntityId()
    {
        return $this->entityId;
    }
}

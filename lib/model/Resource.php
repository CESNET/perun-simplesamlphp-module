<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\model;

/**
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class Resource implements HasId
{
    private $id;

    private $voId;

    private $facilityId;

    private $name;

    /**
     * Resource constructor.
     *
     * @param $id
     * @param $voId
     * @param $facilityId
     * @param $name
     */
    public function __construct($id, $voId, $facilityId, $name)
    {
        $this->id = $id;
        $this->voId = $voId;
        $this->facilityId = $facilityId;
        $this->name = $name;
    }

    /**
     * @return int
     */
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
     * @return int
     */
    public function getFacilityId()
    {
        return $this->facilityId;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}

<?php

namespace SimpleSAML\Module\perun\model;

/**
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class Member implements HasId
{
    const VALID = 'VALID';
    const INVALID = 'INVALID';
    const EXPIRED = 'EXPIRED';
    const SUSPENDED = 'SUSPENDED';
    const DISABLED = 'DISABLED';

    private $id;
    private $voId;
    private $status;

    /**
     * Member constructor.
     * @param $id
     * @param $voId
     * @param $status
     */
    public function __construct($id, $voId, $status)
    {
        $this->id = $id;
        $this->voId = $voId;
        $this->status = $status;
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
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }
}

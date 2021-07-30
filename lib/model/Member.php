<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\model;

/**
 * @author Pavel Vyskocil <vyskocilpavel@muni.cz>
 */
class Member implements HasId
{
    public const VALID = 'VALID';

    public const INVALID = 'INVALID';

    public const EXPIRED = 'EXPIRED';

    public const SUSPENDED = 'SUSPENDED';

    public const DISABLED = 'DISABLED';

    private $id;

    private $voId;

    private $status;

    /**
     * Member constructor.
     *
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

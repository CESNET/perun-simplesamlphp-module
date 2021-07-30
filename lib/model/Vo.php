<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\model;

/**
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class Vo implements HasId
{
    private $id;

    private $name;

    private $shortName;

    /**
     * Vo constructor.
     *
     * @param int $id
     * @param string $name
     * @param string $shortName
     */
    public function __construct($id, $name, $shortName)
    {
        $this->id = $id;
        $this->name = $name;
        $this->shortName = $shortName;
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
    public function getShortName()
    {
        return $this->shortName;
    }
}

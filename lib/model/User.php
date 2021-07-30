<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\model;

/**
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class User implements HasId
{
    private $id;

    private $name;

    /**
     * User constructor.
     *
     * @param int $id
     * @param string $name
     */
    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
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
}

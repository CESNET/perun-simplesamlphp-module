<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\model;

interface HasId
{
    /**
     * @return int id of entity
     */
    public function getId();
}

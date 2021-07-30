<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun\model;

/**
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
interface HasId
{
    /**
     * @return int id of entity
     */
    public function getId();
}

<?php

declare(strict_types=1);

/**
 * @author Pavel Brousek <brousek@ics.muni.cz>
 * ───────────▄▄▄▄▄▄▄▄▄───────────
 * ────────▄█████████████▄────────
 * █████──█████████████████──█████
 * ▐████▌─▀███▄───────▄███▀─▐████▌
 * ─█████▄──▀███▄───▄███▀──▄█████─
 * ─▐██▀███▄──▀███▄███▀──▄███▀██▌─
 * ──███▄▀███▄──▀███▀──▄███▀▄███──
 * ──▐█▄▀█▄▀███─▄─▀─▄─███▀▄█▀▄█▌──
 * ───███▄▀█▄██─██▄██─██▄█▀▄███───
 * ────▀███▄▀██─█████─██▀▄███▀────
 * ───█▄─▀█████─█████─█████▀─▄█───
 * ───███────────███────────███───
 * ───███▄────▄█─███─█▄────▄███───
 * ───█████─▄███─███─███▄─█████───
 * ───█████─████─███─████─█████───
 * ───█████─████─███─████─█████───
 * ───█████─████─███─████─█████───
 * ───█████─████▄▄▄▄▄████─█████───
 * ────▀███─█████████████─███▀────
 * ──────▀█─███─▄▄▄▄▄─███─█▀──────
 * ─────────▀█▌▐█████▌▐█▀─────────
 * ────────────███████────────────
 */

namespace SimpleSAML\Module\perun;

abstract class AttributeTransformer
{
    /**
     * The construtor. Called only once.
     */
    abstract public function __construct(\SimpleSAML\Configuration $config);

    /**
     * Transform attributes (array with keys as attribute names). It is up to the transformer whether it works on each
     * supplied attribute separately or somehow combines them. The return array has the same form as input. The input
     * attributes are not automatically deleted. To delete an attribute, include it in the output with a null value. If
     * entityID is deleted, facility is NOT created.
     */
    abstract public function transform(array $attributes);

    /**
     * Get human readable description of the transformation performed on attributes. Optional, but useful for generating
     * documentation or instructions.
     *
     * @param array $attributes keys are attribute names, values are current description
     */
    public function getDescription(array $attributes)
    {
        return $attributes;
    }
}

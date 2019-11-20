<?php

/*
───────────▄▄▄▄▄▄▄▄▄───────────
────────▄█████████████▄────────
█████──█████████████████──█████
▐████▌─▀███▄───────▄███▀─▐████▌
─█████▄──▀███▄───▄███▀──▄█████─
─▐██▀███▄──▀███▄███▀──▄███▀██▌─
──███▄▀███▄──▀███▀──▄███▀▄███──
──▐█▄▀█▄▀███─▄─▀─▄─███▀▄█▀▄█▌──
───███▄▀█▄██─██▄██─██▄█▀▄███───
────▀███▄▀██─█████─██▀▄███▀────
───█▄─▀█████─█████─█████▀─▄█───
───███────────███────────███───
───███▄────▄█─███─█▄────▄███───
───█████─▄███─███─███▄─█████───
───█████─████─███─████─█████───
───█████─████─███─████─█████───
───█████─████─███─████─█████───
───█████─████▄▄▄▄▄████─█████───
────▀███─█████████████─███▀────
──────▀█─███─▄▄▄▄▄─███─█▀──────
─────────▀█▌▐█████▌▐█▀─────────
────────────███████────────────
*/

namespace SimpleSAML\Module\perun;

interface AttributeTransformer
{
    /**
     * The construtor. Called only once.
     * @param Configuration $config
     */
    public function __construct(Configuration $config);

    /**
     * Transform attributes (array with keys as attribute names).
     * It is up to the transformer whether it works on each supplied attribute separately or somehow combines them.
     * The return array has the same form as input.
     * The input attributes are not automatically deleted.
     * To delete an attribute, include it in the output with a null value.
     * If entityID is deleted, facility is NOT created.
     * @param array $attributes
     */
    public function transform(array $attributes);
}

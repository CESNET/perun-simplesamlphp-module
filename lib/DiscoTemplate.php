<?php

declare(strict_types=1);

namespace SimpleSAML\Module\perun;

use SimpleSAML\Configuration;
use SimpleSAML\XHTML\Template;

/**
 * This class extends basic SimpleSAML template class. It provides some utils functions used in templates specific for
 * Discovery services so template do not have to access directly $this->data field.
 *
 * Here should NOT be defined any view specific methods.
 *
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class DiscoTemplate extends Template
{
    public const UI_INFO = 'UIInfo';

    public const DISPLAY_NAME = 'DisplayName';

    public const NAME = 'name';

    /**
     * sspmod_perun_DiscoTemplate constructor.
     *
     * @param Configuration $configuration of SimpleSAMLphp
     */
    public function __construct(Configuration $configuration)
    {
        parent::__construct($configuration, 'perun:disco-tpl.php', 'disco');

        // Translate title in header
        $this->data['header'] = $this->t(isset($this->data['header']) ? $this->data['header'] : 'selectidp');
    }

    /**
     * @return array metadata of preferred IdP if exists or null if not
     */
    public function getPreferredIdp(): array
    {
        if (isset($this->data[Disco::PREFERRED_IDP]) && ! empty($this->data[Disco::PREFERRED_IDP])) {
            return $this->getAllIdps()[$this->data[Disco::PREFERRED_IDP]];
        }
        return [];
    }

    /**
     * @param string $tag desired tag. If not provided 'misc' is used for all untagged idps.
     * @return array list of idp metadatas from declared tag or untagged (misc) idps are returned.
     */
    public function getIdps($tag = 'misc'): array
    {
        if (isset($this->data[Disco::IDP_LIST][$tag])) {
            return $this->data[Disco::IDP_LIST][$tag];
        }
        return [];
    }

    /**
     * @return array structure of idp metadatas divided by tags.
     * example structure:
     *
     * [
     *        'social' => [
     *            [ ...metadata1... ],
     *            [ ...metadata2... ],
     *        ]
     *        // misc represents untagged idps
     *        'misc' => [
     *            [ ...metadata2... ],
     *            [ ...metadata3... ],
     *        ]
     * ]
     *
     * note: one idp can be placed in more tags
     */
    public function getTaggedIdps(): array
    {
        return $this->data[Disco::IDP_LIST];
    }

    /**
     * @return array list of all idp metadatas ignoring tagging
     */
    public function getAllIdps(): array
    {
        $allIdps = [];
        foreach ($this->data[Disco::IDP_LIST] as $tag => $idplist) {
            $allIdps = array_merge($idplist, $allIdps);
        }
        return $allIdps;
    }

    /**
     * @return bool TRUE if SP has property DiscoTemplate::DISCO_DO_NOT_FILTER_IDPS set to true in its metadata.
     *              FALSE otherwise.
     */
    public function isOriginalSpNonFilteringIdPs(): bool
    {
        return isset($this->data[Disco::ORIGINAL_SP][Disco::METADATA_DO_NOT_FILTER_IDPS]) &&
            $this->data[Disco::ORIGINAL_SP][Disco::METADATA_DO_NOT_FILTER_IDPS];
    }

    /**
     * @return bool TRUE if SP has property DiscoTemplate::DISCO_ADD_INSTITUTION_APP set to true in its metadata.
     *              FALSE otherwise.
     */
    public function isAddInstitutionApp(): bool
    {
        return isset($this->data[Disco::ORIGINAL_SP][Disco::METADATA_ADD_INSTITUTION_APP]) &&
            $this->data[Disco::ORIGINAL_SP][Disco::METADATA_ADD_INSTITUTION_APP];
    }

    /**
     * @return string url where user should be redirected when he choose idp
     */
    public function getContinueUrl(string $idpEntityId): string
    {
        return Disco::buildContinueUrl(
            $this->data[Disco::ENTITY_ID],
            $this->data[Disco::RETURN],
            $this->data[Disco::RETURN_ID_PARAM],
            $idpEntityId
        );
    }

    /**
     * @return string url where user should be redirected when he choose idp
     */
    public function getContinueUrlWithoutIdPEntityId(): string
    {
        return Disco::buildContinueUrlWithoutIdPEntityId(
            $this->data[Disco::ENTITY_ID],
            $this->data[Disco::RETURN],
            $this->data[Disco::RETURN_ID_PARAM]
        );
    }

    /**
     * @return string translated name of idp or sp based on its metadata information
     */
    public function getTranslatedEntityName(array $metadata): string
    {
        if (isset($metadata[self::UI_INFO][self::DISPLAY_NAME])) {
            $displayName = $metadata[self::UI_INFO][self::DISPLAY_NAME];
            assert(is_array($displayName)); // Should always be an array of language code -> translation
            if (! empty($displayName)) {
                return $this->getTranslation($displayName);
            }
        }

        if (array_key_exists(self::NAME, $metadata)) {
            if (is_array($metadata[self::NAME])) {
                return $this->getTranslation($metadata[self::NAME]);
            }
            return $metadata[self::NAME];
        }
        return $metadata[Disco::ENTITY_ID];
    }
}

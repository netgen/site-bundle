<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Templating\Twig\Extension;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use Netgen\Bundle\SiteBundle\Helper\PathHelper;
use Netgen\IbexaSiteApi\API\Exceptions\TranslationNotMatchedException;
use Netgen\IbexaSiteApi\API\LoadService;
use Netgen\IbexaSiteApi\API\Values\Content;
use Symfony\Component\Intl\Languages;

use function ceil;
use function mb_substr;
use function preg_match;
use function reset;
use function str_word_count;
use function ucwords;
use function usort;

final class SiteRuntime
{
    private const WORDS_PER_MINUTE = 230;
    private const GROUP_FIELDS_GROUP = 'group';
    private const GROUP_FIELDS_POSITION = 'position';
    private const GROUP_FIELDS_FIELD = 'field';

    public function __construct(private PathHelper $pathHelper, private LocaleConverterInterface $localeConverter, private LoadService $loadService)
    {
    }

    /**
     * Returns the path for specified location ID.
     *
     * @param array<string, mixed> $options
     *
     * @return array<int, array<string, mixed>>
     */
    public function getLocationPath(int $locationId, array $options = []): array
    {
        return $this->pathHelper->getPath($locationId, $options);
    }

    /**
     * Returns the language name for specified language code.
     */
    public function getLanguageName(string $languageCode): string
    {
        $posixLanguageCode = $this->localeConverter->convertToPOSIX($languageCode);
        if ($posixLanguageCode === null) {
            return '';
        }

        $posixLanguageCode = mb_substr($posixLanguageCode, 0, 2);
        $languageName = Languages::getName($posixLanguageCode, $posixLanguageCode);

        return ucwords($languageName);
    }

    /**
     * Returns the name of the content with provided ID.
     */
    public function getContentName(int $contentId): ?string
    {
        try {
            $content = $this->loadService->loadContent($contentId);
        } catch (UnauthorizedException|NotFoundException|TranslationNotMatchedException) {
            return null;
        }

        return $content->name;
    }

    /**
     * Returns the name of the content with located at location with provided ID.
     */
    public function getLocationName(int $locationId): ?string
    {
        try {
            $location = $this->loadService->loadLocation($locationId);
        } catch (UnauthorizedException|NotFoundException|TranslationNotMatchedException) {
            return null;
        }

        return $location->content->name;
    }

    public function calculateReadingTime(string $text): int
    {
        $wordCount = str_word_count($text);
        $readingTime = ceil($wordCount / self::WORDS_PER_MINUTE);

        return $readingTime < 1 ? 1 : (int) $readingTime;
    }

    /**
     * Returns grouped and sorted fields for selected content and field definition identifier prefix.
     *
     * @return array<string, array<string, \Netgen\IbexaSiteApi\API\Values\Field>>
     */
    public function groupFields(Content $content, string $prefix): array
    {
        $regex = '/^' . $prefix . '_(?<' . self::GROUP_FIELDS_GROUP . '>\d+)_(?<' . self::GROUP_FIELDS_FIELD . '>.*)$/';
        $groupedFields = [];

        foreach ($content->fields as $field) {
            if (preg_match($regex, $field->fieldDefIdentifier, $matches)) {
                $groupedFields[$matches[self::GROUP_FIELDS_GROUP]][$matches[self::GROUP_FIELDS_FIELD]] = $field;
            }
        }

        foreach ($groupedFields as $index => $group) {
            $empty = true;

            foreach ($group as $identifier => $field) {
                if ($identifier !== self::GROUP_FIELDS_POSITION && !$field->isEmpty()) {
                    $empty = false;

                    break;
                }
            }

            if ($empty) {
                unset($groupedFields[$index]);
            }
        }

        usort(
            $groupedFields,
            static function ($group1, $group2) {
                $identifiers1 = $group1[self::GROUP_FIELDS_POSITION]->value->identifiers ?? [];
                $identifier1 = $identifiers1 ? (int) reset($identifiers1) : 999;

                $identifiers2 = $group2[self::GROUP_FIELDS_POSITION]->value->identifiers ?? [];
                $identifier2 = $identifiers2 ? (int) reset($identifiers2) : 999;

                return $identifier1 <=> $identifier2;
            },
        );

        return $groupedFields;
    }
}

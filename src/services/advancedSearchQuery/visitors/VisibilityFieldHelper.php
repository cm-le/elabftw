<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services\AdvancedSearchQuery\Visitors;

use function array_intersect_key;
use function array_keys;
use function array_unique;
use function array_values;
use function implode;
use function preg_grep;
use function preg_quote;
use function str_replace;

class VisibilityFieldHelper
{
    public string $possibleInput;

    public function __construct(private string $userInput, private array $visArr)
    {
    }

    public function getArr(): array
    {
        // ToDo: add back team groups, add teams and users
        $this->possibleInput = "'" . implode("', '", array_keys($this->visArr)) . "'";

        // Emulate SQL LIKE search functionality so the user can use the same placeholders
        $pattern = '/^' . str_replace(array('%', '_'), array('.*', '.'), preg_quote($this->userInput, '/')) . '$/i';
        // Filter visibility entries based on user input
        $filteredArr = preg_grep($pattern, array_keys($this->visArr)) ?: array();

        // Return a unique list of visibility entries that can be used in the SQL where clause
        return array_unique(array_intersect_key(array_values($this->visArr), $filteredArr));
    }
}

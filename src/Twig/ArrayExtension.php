<?php

declare(strict_types=1);

namespace Bolt\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Bolt specific Twig functions and filters that provide array manipulation.
 *
 * @internal
 */
class ArrayExtension extends AbstractExtension
{
    private $orderOn;
    private $orderAscending;
    private $orderOnSecondary;
    private $orderAscendingSecondary;

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        $safe = ['is_safe' => ['html']];

        return [
            new TwigFunction('unique', [$this, 'unique'], $safe),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('order', [$this, 'order']),
            new TwigFilter('shuffle', [$this, 'shuffle']),
        ];
    }

    public function dummy($input = null)
    {
        return $input;
    }

    /**
     * Takes two arrays and returns a compiled array of unique, sorted values.
     *
     * @param array $arr1
     * @param array $arr2
     *
     * @return array
     */
    public function unique(array $arr1, array $arr2)
    {
        $merged = array_unique(array_merge($arr1, $arr2), SORT_REGULAR);
        $compiled = [];

        foreach ($merged as $key => $val) {
            if (is_array($val) && array_values($val) === $val) {
                $compiled[$key] = $val;
            } else {
                $compiled[$val] = $val;
            }
        }

        return $compiled;
    }

    /**
     * Randomly shuffle the contents of a passed array.
     *
     * @param array $array
     *
     * @return array
     */
    public function shuffle($array)
    {
        if (is_array($array)) {
            shuffle($array);
        }

        return $array;
    }

    /**
     * Sorts / orders items of an array.
     *
     * @param array  $array
     * @param string $on
     * @param string $onSecondary
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    public function order(array $array, $on, $onSecondary = null)
    {
        // If we don't get a string, we can't determine a sort order.
        if (!is_string($on)) {
            throw new \InvalidArgumentException(sprintf('Second parameter passed to %s must be a string, %s given', __METHOD__, gettype($on)));
        }
        if (!(is_string($onSecondary) || $onSecondary === null)) {
            throw new \InvalidArgumentException(sprintf('Third parameter passed to %s must be a string, %s given', __METHOD__, gettype($onSecondary)));
        }
        // Set the 'orderOn' and 'orderAscending', taking into account things like '-datepublish'.
        list($this->orderOn, $this->orderAscending) = $this->getSortOrder($on);

        // Set the secondary order, if any.
        if ($onSecondary) {
            list($this->orderOnSecondary, $this->orderAscendingSecondary) = $this->getSortOrder($onSecondary);
        } else {
            $this->orderOnSecondary = false;
            $this->orderAscendingSecondary = false;
        }

        uasort($array, [$this, 'orderHelper']);

        return $array;
    }

    /**
     * Get sorting order of name, stripping possible "DESC", "ASC", and also
     * return the sorting order.
     *
     * @param string $name
     *
     * @return array
     */
    private function getSortOrder($name = '-datepublish')
    {
        $parts = explode(' ', $name);
        $fieldName = $parts[0];
        $sort = 'ASC';
        if (isset($parts[1])) {
            $sort = $parts[1];
        }

        if ($fieldName[0] === '-') {
            $fieldName = mb_substr($fieldName, 1);
            $sort = 'DESC';
        }

        return [$fieldName, (mb_strtoupper($sort) === 'ASC')];
    }

    /**
     * Helper function for sorting an array of \Bolt\Legacy\Content.
     *
     * @param \Bolt\Legacy\Content|array $a
     * @param \Bolt\Legacy\Content|array $b
     *
     * @return bool
     */
    private function orderHelper($a, $b)
    {
        $aVal = $a[$this->orderOn];
        $bVal = $b[$this->orderOn];

        // Check the primary sorting criterion.
        if ($aVal < $bVal) {
            return !$this->orderAscending;
        } elseif ($aVal > $bVal) {
            return $this->orderAscending;
        }
        // Primary criterion is the same. Use the secondary criterion, if it is set. Otherwise return 0.
        if (empty($this->orderOnSecondary)) {
            return 0;
        }

        $aVal = $a[$this->orderOnSecondary];
        $bVal = $b[$this->orderOnSecondary];

        if ($aVal < $bVal) {
            return !$this->orderAscendingSecondary;
        } elseif ($aVal > $bVal) {
            return $this->orderAscendingSecondary;
        }

        // both criteria are the same. Whatever!
        return 0;
    }
}

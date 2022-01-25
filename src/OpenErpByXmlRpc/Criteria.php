<?php

namespace OpenErpByXmlRpc;

/**
 * Class to build a search criteria.
 *
 * @license MIT
 * @author  Simon Leblanc <contact@leblanc-simon.eu>
 */
class Criteria
{
    public const EQUAL = '=';
    public const LESS_THAN = '<';
    public const LESS_EQUAL = '<=';
    public const GREATER_THAN = '>';
    public const GREATER_EQUAL = '>=';
    public const LIKE = 'like';
    public const ILIKE = 'ilike';
    public const NOT_EQUAL = '!=';

    /**
     * List of criterion in the criteria.
     */
    private array $criterions = [];

    /**
     * Get an instance of Criteria.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Add a criterion in the criteria.
     *
     * @param string $field   The field name
     * @param mixed  $value   The value to search
     * @param string $compare The comparator
     *
     * @return $this
     */
    public function add(string $field, $value, string $compare = self::EQUAL): self
    {
        $this->criterions[] = [$field, $compare, $value];

        return $this;
    }

    /**
     * Get the criteria in the good format for OpenERP.
     *
     * @return array The criteria of search
     */
    public function get(): array
    {
        return $this->criterions;
    }

    /**
     * Add an equal criterion in the criteria.
     *
     * @param string $field The field name
     * @param mixed  $value The value to search
     *
     * @return $this
     */
    public function equal(string $field, $value): self
    {
        return $this->add($field, $value, self::EQUAL);
    }

    /**
     * Add a less than criterion in the criteria.
     *
     * @param string $field The field name
     * @param mixed  $value The value to search
     *
     * @return $this
     */
    public function lessThan(string $field, $value): self
    {
        return $this->add($field, $value, self::LESS_THAN);
    }

    /**
     * Add a less equal criterion in the criteria.
     *
     * @param string $field The field name
     * @param mixed  $value The value to search
     *
     * @return $this
     */
    public function lessEqual(string $field, $value): self
    {
        return $this->add($field, $value, self::LESS_EQUAL);
    }

    /**
     * Add a greater than criterion in the criteria.
     *
     * @param string $field The field name
     * @param mixed  $value The value to search
     *
     * @return $this
     */
    public function greaterThan(string $field, $value): self
    {
        return $this->add($field, $value, self::GREATER_THAN);
    }

    /**
     * Add a greater equal criterion in the criteria.
     *
     * @param string $field The field name
     * @param mixed  $value The value to search
     *
     * @return $this
     */
    public function greaterEqual(string $field, $value): self
    {
        return $this->add($field, $value, self::GREATER_EQUAL);
    }

    /**
     * Add a like criterion in the criteria.
     *
     * @param string $field The field name
     * @param mixed  $value The value to search
     *
     * @return $this
     */
    public function like(string $field, $value): self
    {
        return $this->add($field, $value, self::LIKE);
    }

    /**
     * Add a ilike criterion in the criteria.
     *
     * @param string $field The field name
     * @param mixed  $value The value to search
     *
     * @return $this
     */
    public function ilike(string $field, $value): self
    {
        return $this->add($field, $value, self::ILIKE);
    }

    /**
     * Add a not equal criterion in the criteria.
     *
     * @param string $field The field name
     * @param mixed  $value The value to search
     *
     * @return $this
     */
    public function notEqual(string $field, $value): self
    {
        return $this->add($field, $value, self::NOT_EQUAL);
    }
}

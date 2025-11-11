<?php

namespace Fereydooni\Unixtime\Contracts;

interface HasTimestampEquivalentsInterface
{
    /**
     * Get the datetime columns that should have timestamp equivalents.
     *
     * @return array
     */
    public function getTimestampEquivalentColumns(): array;

    /**
     * Get the datetime columns that should be excluded from timestamp equivalents.
     *
     * @return array
     */
    public function getExcludedTimestampColumns(): array;

    /**
     * Get the suffix to append to timestamp column names.
     *
     * @return string
     */
    public function getTimestampColumnSuffix(): string;
}

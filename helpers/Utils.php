<?php
function formatDecimal($value)
{
    $value = str_replace(',', '.', $value);

    if (!is_numeric($value)) {
        throw new \InvalidArgumentException('Value must be a valid number.');
    }

    return $value;
}

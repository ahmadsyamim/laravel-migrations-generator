<?php

namespace KitLoong\MigrationsGenerator\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class MultiPolygonType extends Type
{
    /**
     * Implement to respect the contract. Generator is not using this method.
     * Can safely ignore.
     *
     * @codeCoverageIgnore
     * @inheritDoc
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'MULTIPOLYGON';
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return '';
    }
}

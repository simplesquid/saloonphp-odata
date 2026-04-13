<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Support;

use ReflectionClass;
use SimpleSquid\SaloonOData\Attributes\DefaultODataQuery;
use SimpleSquid\SaloonOData\Attributes\ODataEntity;
use SimpleSquid\SaloonOData\Attributes\ODataVersion as ODataVersionAttribute;
use SimpleSquid\SaloonOData\Enums\ODataVersion;
use SimpleSquid\SaloonOData\ODataQueryBuilder;

/**
 * Cached reflection lookup for the package's class-level attributes.
 *
 * Each attribute is read once per class via reflection and the
 * resolved value is memoised in a static map keyed by class name.
 */
final class AttributeReader
{
    /** @var array<class-string, ODataVersion|null> */
    private static array $versions = [];

    /** @var array<class-string, string|null> */
    private static array $entities = [];

    /** @var array<class-string, DefaultODataQuery|null> */
    private static array $defaults = [];

    public static function version(object $target): ?ODataVersion
    {
        $class = $target::class;

        if (array_key_exists($class, self::$versions)) {
            return self::$versions[$class];
        }

        $attribute = self::firstAttribute($target, ODataVersionAttribute::class);

        return self::$versions[$class] = $attribute?->version;
    }

    public static function entity(object $target): ?string
    {
        $class = $target::class;

        if (array_key_exists($class, self::$entities)) {
            return self::$entities[$class];
        }

        $attribute = self::firstAttribute($target, ODataEntity::class);

        return self::$entities[$class] = $attribute?->name;
    }

    public static function defaults(object $target): ?DefaultODataQuery
    {
        $class = $target::class;

        if (array_key_exists($class, self::$defaults)) {
            return self::$defaults[$class];
        }

        return self::$defaults[$class] = self::firstAttribute($target, DefaultODataQuery::class);
    }

    /**
     * Apply any #[DefaultODataQuery] attribute on the target to a builder.
     */
    public static function applyDefaults(object $target, ODataQueryBuilder $builder): void
    {
        $defaults = self::defaults($target);

        if ($defaults === null) {
            return;
        }

        if ($defaults->select !== []) {
            $builder->select(...$defaults->select);
        }

        foreach ($defaults->expand as $expand) {
            $builder->expand($expand);
        }

        foreach ($defaults->orderBy as $property => $direction) {
            $builder->orderBy($property, $direction);
        }

        if ($defaults->top !== null) {
            $builder->top($defaults->top);
        }

        if ($defaults->skip !== null) {
            $builder->skip($defaults->skip);
        }

        if ($defaults->count) {
            $builder->count();
        }

        if ($defaults->search !== null) {
            $builder->search($defaults->search);
        }

        if ($defaults->format !== null) {
            $builder->format($defaults->format);
        }

        foreach ($defaults->params as $key => $value) {
            $builder->param($key, $value);
        }
    }

    /**
     * Reset the in-process cache. Test-only utility.
     */
    public static function flush(): void
    {
        self::$versions = [];
        self::$entities = [];
        self::$defaults = [];
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $attribute
     * @return T|null
     */
    private static function firstAttribute(object $target, string $attribute): ?object
    {
        $reflection = new ReflectionClass($target);

        // Walk up the parent chain so attributes on a base class are inherited.
        do {
            $attributes = $reflection->getAttributes($attribute);

            if ($attributes !== []) {
                /** @var T $instance */
                $instance = $attributes[0]->newInstance();

                return $instance;
            }

            $reflection = $reflection->getParentClass();
        } while ($reflection !== false);

        return null;
    }
}

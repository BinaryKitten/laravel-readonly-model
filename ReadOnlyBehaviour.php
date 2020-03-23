<?php

namespace App\Model\Behaviours;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait ReadOnlyBehaviour
{
    /**
     * list of events that will be cancelled, preventing DB storage.
     * @var string[]
     *
     * Possible Values:
     *      'creating', 'created',  'updating', 'updated',  'saving',
     *       'saved',   'deleting', 'deleted',  'restoring','restored'
     */
    protected static $readOnlyEvents = [];

    /**
     * list of attribute names that are marked as read Only -
     *    they won't accept changes through the typical setAttribute or external via __set
     *
     * @var string[]
     */
    protected $readOnlyAttributes = [];

    /**
     * Prevents external attribute setting for attributes not listed in $fillable
     *
     * @var bool $preventNewAssignments
     */
    protected $preventNewAssignments = false;

    /**
     * Trait boot,
     * @see Model::bootTraits()
     */
    public static function bootReadOnlyBehaviour()
    {
        if (count(static::$readOnlyEvents) === 0) {
            return;
        }

        static::$dispatcher->listen(
            array_map(
                static function ($eventName) {
                    return sprintf('eloquent.%s: %s', $eventName, static::class);
                },
                static::$readOnlyEvents
            ),
            static function () {
                return false;
            }
        );
    }

    /**
     * Set a given attribute on the model.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        $readOnlyAttributeCount = count($this->readOnlyAttributes);
        if ($this->preventNewAssignments === false && $readOnlyAttributeCount === 0) {
            return parent::setAttribute($key, $value);
        }

        $checks    = [
            mb_strtoupper($key),
            mb_strtolower($key),
            Str::studly($key),
            Str::camel($key),
            Str::snake($key),
        ];

        if ($this->preventNewAssignments) {
            $fillableIntersect = array_intersect($checks, $this->getFillable());
            if (count($fillableIntersect) === 0) {
                return $this;
            }
        }

        if ($readOnlyAttributeCount !== 0) {
            $readOnlyAttributeIntersect = array_intersect($this->readOnlyAttributes, $checks);
            if (count($readOnlyAttributeIntersect) !== 0) {
                return $this;
            }
        }

        return parent::setAttribute($key, $value);
    }
}

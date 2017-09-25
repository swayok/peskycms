<?php

namespace PeskyCMS\Db;

use Illuminate\Contracts\Container\BindingResolutionException;
use PeskyCMF\Db\CmfDbTable;
use PeskyORM\ORM\Record;
use PeskyORM\ORM\TableStructure;

class CmsDbTable extends CmfDbTable {

    /**
     * Class name of the DB Table Structure
     * @var string
     */
    static protected $tableStructureClass;

    /**
     * Class name of the DB Record
     * @var string
     */
    static protected $recordClass;

    static public function getSingletonInstanceOfDbClassFromServiceContainer($class) {
        if (!app()->bound($class)) {
            throw new BindingResolutionException(
                'Binding for class ' . $class . ' is not registered via app()->singleton(\'' . $class . '\', \Closure). ' .
                'You may need to review CmsServiceProvider or its child class.'
            );
        }
        try {
            return app($class);
        } catch (BindingResolutionException $exc) {
            if (stripos($exc->getMessage(), 'is not instantiable') !== false) {
                throw new BindingResolutionException('Binding for class ' . $class . ' must be declared as a singleton');
            }
        }
    }

    /**
     * @return TableStructure
     */
    public function getTableStructure() {
        if (empty(static::$tableStructureClass)) {
            throw new \UnexpectedValueException('You need to provide ' . static::class . '::$tableStructureClass property');
        }
        return static::getSingletonInstanceOfDbClassFromServiceContainer(static::$tableStructureClass);
    }

    /**
     * @return Record
     */
    public function newRecord() {
        if (empty(static::$recordClass)) {
            throw new \UnexpectedValueException('You need to provide ' . static::class . '::$recordClass property');
        }
        /** @var Record $class */
        $class = app(static::$recordClass);
        return $class::newEmptyRecord();
    }
}
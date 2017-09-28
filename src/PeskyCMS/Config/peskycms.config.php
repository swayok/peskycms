<?php

return [

    /**
     * List of DB classes to register inside service container as singletons.
     * Only classes that implement RecordInterface, TableInterface and TableStructureInterface are allowed.
     * You can map you own classes to any class registered by CMF or CMS. Just use CMF/CMS class name as key and your
     * class name as value.
     * Example: 'register_db_classes' => [CmfSettingsTableStructure::class => MySettingsTableStructure::class].
     * This way you can replace some CMF classes leaving other classes of same Record-Table-Structure group untouched.
     * Notes:
     * - Records will be registered as singleton that returns class name (not an instance of class because sometimes
     * system needs class name instead of instance + Table::newRecord() may go into infinite loop)
     * - Tables and TableStructures will be registered as singletons that return an instance of class calling
     * to getInstance() method
     */
    'register_db_classes' => [

    ],

    /**
     * List of CMS resources. Format:
     * - key = resource name (alt name: table name for routes)
     * - value = ScaffoldConfig class name
     * Note: resources with same names declared in peskycmf.config.php will overwrite resources declared here
     */
    'resources' => [

    ],
];
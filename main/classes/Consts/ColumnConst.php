<?php

namespace Consts;

interface ColumnConst {

    // column type
    const
        STRING = 1, // CHAR, VARCHAR, ENUM, TEXT, BLOB
        INTEGER = 2, // INT, TINYINT, SMALLINT, MEDIUMINT, BIGINT
        UINT = 8, // unsigned int, must be zero or positive
        DECIMAL = 3, // FLOAT, DOUBLE, NUMERIC, DECIMAL, DEC
        DATETIME = 4, // DATETIME, TIMESTAMP
        DATE = 5, // DATE
        TIME = 6, // TIME
        JSON = 7; // BLOB, developer will HAVE TO manually setup this column type

    // attributes
    const
        NAME = 'name',
        TYPE = 'type',
        PK = 'pk',
        LENGTH = 'length',
        NULLABLE = 'nullable',
        SHARD = 'shard',
        CHOICES = 'choices',
        DEFAULTS = 'default';

    // reserved column names
    const
        MTIME = 'mtime',
        CTIME = 'ctime';

}

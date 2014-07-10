<?php

namespace Consts;

/**
 * To make logger category under control, we define this const interface.
 * All categories are defined here.
 * Hard coded categories are FORBIDDEN!
 */
interface LoggerConst {

    const
        CATEGORY_INIT = 'init',
        CATEGORY_EXCEPTION = 'exception',
        CATEGORY_REQUEST = 'request',
        CATEGORY_RESPONSE = 'response',
        CATEGORY_DBQUERY = 'dbquery',
        CATEGORY_WARNING = 'warn',
        CATEGORY_BUG = 'bug',
        CATEGORY_SLOWREQUEST = 'slowreq',
        CATEGORY_CHEAT = 'cheat',
        CATEGORY_ERROR = 'error';

}

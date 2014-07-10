===============
Coding Standard
===============

.. contents:: Table Of Contents
.. section-numbering::

Overview
========

Don't make me think!


Dev Env
=======

- php
  php-5.4.18

- mysql

Encoding
========

php file
########

utf-8 without BOM

db table
########

utf-8

request/response
################

utf-8

PHP
===

design
######

- explicit 'public' declaration

  :: 

      class Foo {
    
        var $bar;       // bad
        public $baz;    // good
    
        function spam() {           // bad
        }
    
        public function spam() {    // good
        }
      }
    
- scope and visibility

  采用“作用域小”优先原则

  prefer private to protected
  
  prefer protected to public


- instance method called by static is forbidden

  although in PHP it is allowed

  ::

    class Foo {
        public function bar() {}
    }

    Foo::bar();  // never do like this

- use php type hint at best

  ::

    function foo(\Model\BarModel $bar) { // let php do the dirty job
    }

- never use old style constructor

  ::

    class Foo {
      public function Foo() {           // never do like this
      }

      public function __construct() {   // do like this
      }
    }

- use parentheses in 'new' operation

  ::

    $a = new A;     // bad
    $a = new A();   // good

- prefer array mapping to switch clause

  too many cases is hard to read, but in array, they
  are straightforward.

- var declaration should be closest to its first reference

- php files all has suffix of '.php'

  never use like '.inc'

- class only talk to immediate friends
  
  never talk to strangers

- never copy extra variables

  especially when a var is referenced only once

  ::

    // bad
    $desc = strip_tags($_POST['description']);
    echo $desc;

    // good
    echo strip_tags($_POST['description']);

format
######

- use <?php, never use <? for php file magic

- never use php ending symbol

  ::

    ?>  // never use this

- always add a space between the keyword and a operator

  ::

    $a = $a + 1; // good
    $a = $a+1;   // bad

- empty line
  - an empty line is reserved for seperation of different logical unit
    
    never overuse empty line

  - between method/function blocks
    
    there will be 1 and only 1 empty line

- indent

  4 spaces

  never use tab

- avoid line over 100 chars

- beginning bracelet will never occupy a whole line

  ::

    function foo() {    // do like this

    function foo() 
    {                   // never do like this

- never use the following tags in file header
  @author

naming
######

- never include data type info in var name

  ::

    $intUid;    // never do like this
    $uid;       // do like this

- private/protected var & methods all starts with '_'

  except for db column name in class

- camel case names

  used for class name, var name, method name

- lower case connected with underscore names

  used for function name

  ::

    function str_contains($haystack, $needle) {

- never use var name that ends with digits or new/old

  ::

    $uid2 = ''; // bad

    $ipNew = ''; // bad


- use adjective for interfaces

  ::

    interface Cacheable {}

- conventions

  - ModelClassName = {TableName} + 'Model'
    e,g. UserInfoModel

  - DataTableClassName = {TableName} + 'Table'
    e,g. UserInfoTable

- const use upper case with underscore connection

- do not reinvent an abbreviation unless it is really well known

comment
#######

It's a supplement for the statements, not a repitition.

- phpdoc if writen, write it correctly

- never comment out a code block without any comments.

- sync the logic with corresponding comments

  if the logic changes, change it's comment to

- keyword
  FIXME, TODO

- comments are placed directly above or directly right to the code block

- Chinese comments are encouraged

- each service call method must have '@In' tag

cache key
#########

    {namespace}:{id}

for example:

::

    uid:45

best practice
#############

- strings, prefer '' to ""

- namespace 'use' is discouraged

- never use 'eval' function

- add a blank line between switch's case statements

- how to declare a variant argument function

  ::

    protected function _getInstance(/* arg1, arg2, ... */) {
    }

- never use '@' operator in php

  we can't blindly ignore errors

- use string concatenation instead of sprintf

- never, ever trust players input

- true/false/null all use upper case

  ::

    $a = true; // bad
    $a = TRUE; // good

- use curly brace '{' after any length if/elseif

  ::

    if ($a > $b) {
        $a = 0;     // good
    }

    if ($a > $b)
        $a = 0;     // bad

- never use ':' syntax style if statement

  ::

    if ($a > 1):  // forbidden

- never use global variable

- never use 'define' 

- always add a semicolon after an entry in array

  ::

    $rules = array(
        'uid' => 12,  // the ',' 
    );


Unit Test
=========

- filename ends with Test.php

  - e,g. TableTest.php

- class extends FunTestCaseBase

- only test public interfaces

- sync between code and its unit test

- unit test readability is vital
  test code is a good documentation


Benchmark Test
==============

- filename ends with Bench.php

  - e,g. TableBench.php

- class extends FunBenchmark

- benchmark method starts with 'bench'

- tests/phpunit/Model/Base/TableBench.php is a good example usage


DB
==

naming
######

- table

- column

  use underscore seperated lower case words

- sql

  SQL keywords, e,g. AND WHERE OR, all use upper case

convention
##########

- mtime/ctime

- uid

Logging
=======

- if var name contained in log msg, it must absolutely match real var name

- will not end with period or other punctuations

- log msg/content begins with capital letter

- log msg/content can't be misleading

Request
=======

naming
######

lower case connected with underscore

::

    quest_id  // IS this form
    questId   // is NOT this form

conventions
###########

- uid

- opTime

- commands

- gver

  game version

- chan
  
  game channel, distribution channel name

- lang

- agent

- payload

- token

- ok
  0/1

- upgrade

Commit
======

- frequent comits is encouraged

  Commit as soon as your changes makes a logical unit

- be precise and exhaustive in your commit comments

- test code before you commit

- git diff before you commit

TERMS
=====

variables
#########

- row

  a row in db table, in php its array

- rows

- line

  a row in game data(config)

- event/job

  A delayed job

- refresh

- tile

- opTime


Tools
=====

gnu global
##########

::

    http://www.gnu.org/software/global/global.html

ack 
###

::

    http://beyondgrep.com/

automan
#######

::

    https://bitbucket.org/funkygao_/automan


FunGame
=======

A quick prototype game backend framework in php.

It is a framework, while being a scaffold.

    
    (  ____ \|\     /|( (    /|(  ____ \(  ___  )(       )(  ____ \
    | (    \/| )   ( ||  \  ( || (    \/| (   ) || () () || (    \/
    | (__    | |   | ||   \ | || |      | (___) || || || || (__    
    |  __)   | |   | || (\ \) || | ____ |  ___  || |(_)| ||  __)   
    | (      | |   | || | \   || | \_  )| (   ) || |   | || (      
    | )      | (___) || )  \  || (___) || )   ( || )   ( || (____/\
    |/       (_______)|/    )_)(_______)|/     \||/     \|(_______/
                                                                   

Features
========

*   standard scaffold for developping game backend in php

    - framework directory layout
    - phpunit layout
    - acceptance test layout
    - coding standard

*   best practice of coding standard

*   continuous integration ready

    - we're using apache ant with jenkins

*   code quality monitoring
    
    - we're using sonar

*   performance aware

    - we EXPLAIN each DB query to log
    - of course, we need xdebug/xhprof
    - request log is easy to replay
        - simulate real player requests

*   fully tested ActiveRecord lib

    - DB flush buffer
    - multiple level caching
    - phpunit complete
    - standard procedure of realizing Models
    - automatically generate Models for developers
        - you write sql, automodel helps you generate php code

*   base class for Service/Manager/Model/Driver layers

*   delayed job(timer) facility

*   logger facility

*   simple front controller
    - docroot/api/index.php

*   integration with automan

    - https://bitbucket.org/funkygao_/automan
    - add annotation(java) to php service layer
    - auto create Models
    - auto create api wiki page
    - auto create integration test scripts

-   best of all
    
    - it is easy to read and extend


LogicalLayer
============

        Unity3D/Flash/IOS/Android
               |
              http 
            ---------
              |  ^
          req |  | resp
              V  |
           Service/Controller.action            |
              |                                 |
    +---------|                                 |
    |         |                                 |
    |      Manager(facade of models)            |
    |         |                                 |
    +---------|-----------------+               |
              |                 |               | 
         +-------------+        |               V
         |   Model     |        |               |
         +-------------+        |               |
         | game data   |        |               |
         +-------------+        |               |
         | biz language|        |               |
         +-------------+        |               |
              |                 |               |
         +-------------+        |               |
         | ActiveRecord|        |               |
         +-------------+        |               |
              |                 |               | 
              |-----------------+               V
              |                 |               |
         +-------------+        |               |
         | Table       |        |               | 
         +-------------+        |               |
         | db language |        |               |
         +-------------+        |               |
              |                 |               |
         +-------------+        |               |
         | Column      |        |               | 
         +-------------+        |               |
                                |               |
                       +--------+               |
                       |                        |
            +-------+---------+                 V
            |       |         |
         System   Driver    Utils
                    |
        -------------------------------------------
                    |                       backend
            +--------------------+
            |    |      |        |
           DB  Cache  logger   redis


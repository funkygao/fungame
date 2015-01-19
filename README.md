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
    - best practices

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
    - https://github.com/funplus/fungame/blob/master/docroot/api/index.php

*   integration with automan

    - https://bitbucket.org/funkygao_/automan
    - add annotation(java) to php service layer
    - auto create Models
    - auto create api wiki page
        - build the bridge between frontend and backend dev
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

Patterns used
============

*   Active Record (Model/Base/ActiveRecord)

*   Mediator(PropertyManager)

*   Delayed Job (JobModel)

*   Facade Pattern(e,g. Manager)

*   Strategy Pattern (e,g. Model/ConsumableModel)

*   State Pattern (e,g. March state switching)

*   EDA
    Event Driven Architecture - March Event

*   Observer

          Observer              Subject
             ^                     ^
             |                     |
         XxxManager            ActiveRecord
             |                     ^
             |                     |
             |                  YyyModel
             |                     |
             |                     |
             |              attach(XxxManager)
             |                     |
             |                     |
             |               "Events Happen"
             |                     |
             |                     |
             |                   notify()
             |                     |
        update(YyyModel)<----------|
             |                     |
             V                     V


*   DB write buffer/Flusher

*   App Level Rollback
    - in-house redolog/undolog

*   Singleton

*   Factory Method


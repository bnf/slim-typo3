Slim framework integration for TYPO3
====================================

Introduction
------------

This extension provides a TYPO3 RequestHandler which runs a Slim App.
The Slim App will be executed when one of the routes match the request.
If not the default TYPO3 RequestHandler will be executed.

Note: The EIDRequestHandler has higher priority and will not
be influenced by this router. That means the slim app
cannot accept a parameter 'eID'.


TODO
----

Support generating multiple RequestsHandlers
with multiple App configs? (and maybe basePaths).
(Well, maybe not. Con: that add's another abstraction on top
of Slim\App which already knows about route groups – BUT
it would support different Middleware configs.)

Example:

```
App1: /api
App2: /api2
App3: /getFooData.xml
```

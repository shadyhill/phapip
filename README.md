# pHAPIp (happy)

Insipired by the work done on [Sandman][1] in Python, the aim of pHAPIp is to create an easy to use RESTful API controlled by a simple configuration file. Most of the work is done through inspection of the database to discover a model's fields and relations. The configuration file enables control over settings such as custom endpoints and required and excluded fields.

## Requirements

The initial prototype of pHAPIp was built with PHP 5.5.6, MySQL 5.5.14, and Apache 2.2.24, although it will most likely work on earlier versions of each technology. Mod rewrite is used to route the URL endpoints to the correct models. The initial database model runs off of PHP's PDO. Support for other database servers and interfaces is planned for the future.

## Getting Started

Copy config.api.sample.php to config.api.php and enter your database connection information.
Copy sample.htaccess to .htaccess and change the last line to point to your project's root directory

Create models in your database as you normally would. pHAPIp looks for foreign key relationships, so for related objects, be sure to establish foreign keys apprpriately. For each model would you like to expose in the api, create a class definition in config. For example:

    class Person{
        var $table = 'person';
    }

[1]: https://github.com/jeffknupp/sandman

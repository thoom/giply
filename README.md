Giply
=====

Giply is a git-based deployment server/receiver written in PHP. It's very much early stage, being used on several
personal/small projects, so the options are pretty rudimentary at the moment.

The library is split into 2 parts. The Giply class that you can use in your own scripts and a simple index.php file
that can be used to manage a Giply server. Read on to learn more about both!

Class: Giply.php
----------------

This class does all of the work pulling the latest data from the Git repo. It will look for a __giply.json__ file in the
project's working directory to overwrite any of the default variables. Additionally, if a __composer.json__ file exists
in the project's working directory, the class will attempt to run the composer.phar file (it assumes that the the phar is
located in the _/usr/local/bin_ directory).

Server: index.php
-----------------

This server script is included since I use the same server to host several small projects. This script allows me to
have a single domain (i.e. deploy.myserver.com) that I can use to manage POST deployments for all of my projects on the
server.

The server expects pretty URLs, in the format: __action/projectName/securityHash__. The only action supported at the moment
is _pull_. The other options: _projectName_ is the name of the working directory (in your /var/www folder). The security
hash is a simple md5 hash of the string _/var/www/projectName_. Other hashes may be supported in future revisions, and
it is really only there to add a little bit of simple security.

References
----------

There are two blog posts that have directly inspired Giply:

 1. http://seancoates.com/blogs/deploy-on-push-from-github
 2. http://brandonsummers.name/blog/2012/02/10/using-bitbucket-for-automated-deployments
_The stand-alone web server previously hosted here can now be found at https://github.com/dxw/whippet-server_

# Whippet

This project is a framework for building WordPress applications that eases deployment, plugin management and build steps. Whippet is part of dxw's work to build and host WordPress-based applications that conform more closely to 12-factor principles.

Whippet has a few basic goals:

1. Allowing proper build steps to take place, that automate build tasks both during development and deployment
2. Properly managing plugins and themes, allowing them to be version controlled and easily updated
3. Managing the creation of releases, including rollbacks
4. Automating the generation of commonly required objects like new applications and new themes
5. Facilitating automated testing
6. Allowing structured test data to be distributed as part of the codebase

At the moment, Whippet can manages plugins and themes and releases and compile stylesheets in Whippet-enabled themes.

During development, whippet is designed to be used in conjunction with [Whippet Server](https://github.com/dxw/whippet-server). These projects will be combined at some point in the future.

*Whippet is under development and should be considered alpha software. If you use it, we'd love to know what you think.*

# Getting started

## Install whippet

To install Whippet, clone this directory and install its dependencies:

```
$ git clone https://github.com/dxw/whippet.git
$ git submodule update --init --recursive
$ composer install
```

The following commands must be available on your system for Whippet to work correctly:

* git
* cp
* mkdir
* rm
* ln

If you intend to use the Whippet base theme, you will also need:

* npm

Using npm, you will need to install:

* grunt-cli
* bower

Further instructions for getting started with the base theme can be found in its [readme](https://github.com/dxw/whippet-theme-template/blob/master/README.md)

## Generate an application

To create a new application, run:

```
$ whippet generate app
```

This will create a new Whippet application in ```./whippet-app```. You can change the location with the -d option. The structure of this application is explained below.

## Configure your application

There are a few configuration steps you'll need to follow when you create a new application.

### Set your WordPress version

By default, Whippet uses the current development version of WordPress. If you want to specify a version to develop against, you'll need to edit ```/config/application.json```:

```
{
    "wordpress": {
        "repository": "https://github.com/WordPress/WordPress.git",
        "revision": "master"
    }
}
```

To change the version, replace "master" with the version you'd like:

```
        "revision": "4.1.1"
```

### Add plugins and themes

If you're using any plugins or themes from the codex, you should add them to your `whippet.json` file. For more information, see the [Plugins section](#plugins).

### Give yourself some credit!

Whippet contains a ```/public/humans.txt``` file that you should update with information about your project. You can also add other files to ```/public/``` that you'd like to
see in the root directory of your website, like the Google webmaster tools file, or a favicon.

### Add or generate a theme

Finally, add a theme to ```/wp-content/themes/``` (or [generate one](Generators) and get devving!

## Run your application

The recommended method for running a Whippet application is to use the ```server``` subcommand.

If you prefer, you can symlink ```/wp-content``` into a WordPress directory on your web server.

### ```whippet server``` usage

    # run this in a separate pane/window/tab/VT - shows all logged output
    $ whippet server run
    # or, to just start the server without showing logs
    $ whippet server start

    # check everything's running as it should
    $ whippet server ps
    CONTAINER ID   IMAGE                                 COMMAND                CREATED         STATUS                     PORTS                              NAMES
    f3ac460c4311   thedxw/whippet-server-custom:latest   "/bin/sh -c 'whippet   2 seconds ago   Up 2 seconds               80/tcp, 0.0.0.0:80->80/tcp     whippet_wordpress
    0a48ee8bd7f2   mysql:latest                          "/entrypoint.sh mysq   2 seconds ago   Up 2 seconds               3306/tcp                           whippet_mysql
    fc317cb34fb5   schickling/mailcatcher:latest         "mailcatcher -f --ip   2 seconds ago   Up 2 seconds               0.0.0.0:1080->1080/tcp, 1025/tcp   whippet_mailcatcher
    476916fe1919   mysql:latest                          "/entrypoint.sh /bin   2 seconds ago   Exited (0) 2 seconds ago                                      whippet_mysql_data__path_to_my_app

    # if there were any errors use this to debug
    $ whippet server logs wordpress
    $ whippet server logs mysql
    $ whippet server logs mailcatcher

    # view your application: http://localhost/

    # if you prefer to use a hostname like mymachine.local (WP_HOME is set automatically from WP_SITEURL)
    $ echo "<?php define('WP_SITEURL', 'http://mymachine.local');" > config/server-local.php

    # MySQL interactive prompt
    $ whippet server db
    # get a copy of your database
    $ whippet server db dump

    # By default whippet server "nerfs" passwords (allows any password for any account) for easier testing
    # If you're working on a plugin that fiddles with authentication you may want to define this constant
    $ echo "<?php define('DISABLE_PASSWORD_NERFING', true);" > config/server-local.php

Note that if you're running docker inside a VM (for example with boot2docker or docker-machine) you may need to forward port 80.

# Application structure

An application that uses Whippet must have the following directory structure, and must be a git repository:

```
- config      # Application configuration files
- public      # Non-WordPress files that should be available via the web
- seeds       # Seed data for initialising new checkouts and automated testing
- wp-content  # Your application's wp-content directory
  - mu-plugins  # Must-use plugins
  - plugins     # Plugins (Whippet managed and otherwise)
  - themes      # Themes, which cannot currently be Whippet-managed
```

# Commands

## Plugins

Note: At the moment, Whippet assumes it is running within dxw's infrastructure, and makes some assumptions accordingly. If you run into a problem where this may be the cause, please open an issue.

To manage plugins and themes using Whippet, you make entries in the `whippet.json` file in the root of the application.

The file should specify a source for plugins and themes. The source should be a base url for a git repo.

If you are a dxw customer, the sources will be `git@git.dxw.net:wordpress-plugins/` and `git@git.dxw.net:wordpress-themes/`. If not, we suggest using `https://github.com/wp-plugins` for plugins.

The rest of the file should specify plugins and themes that you want to install. Example:

```
{
    "src": {
        "plugins": "git@git.dxw.net:wordpress-plugins/",
        "themes": "git@git.dxw.net:wordpress-themes/"
    },
    "plugins": [
        {"name": "akismet"}
    ],
    "themes": [
        {"name": "twentyfourteen"},
        {"name": "twentysixteen"},
        {"name": "twentyten"}
    ]
}
```

The `{"name": "akismet"}` instructs Whippet (on request) to install the most recent version of Akismet available in the repo. Whippet will determine a valid repo URL for the akismet plugin by appending the name to the source. In this example:

```
git@git.dxw.net:wordpress-plugins/akismet
```

You can also specify a particular label or branch that you want to use. Generally, this will either be master (the default) or a tag (for a specific version), but you can use any git reference. So you can do:

```
{
    "name": "akismet",
    "ref": "v1.1"
}
```

Which will cause Whippet to install the plugin at the commit with that tag or branch.

Finally, you can also specify a repo for an individual plugin or theme explicitly:

- Pull version 3.0.0 from your own special repo:

```
{
    "name": "akismet",
    "ref": "v3.0.0",
    "src": "git@my-git-server.com:akismet"
}
```

- Or, pull master:

```
{
    "name": "akismet",
    "ref": "master",
    "src": "git@my-git-server.com:akismet"
}
```

- This works too:

```
{
    "name": "akismet",
    "src": "git@my-git-server.com:akismet"
}
```

### whippet deps update

This command will:

1. Check the commit hash for each ref of each repo specified in `whippet.json`
2. Update `whippet.lock`
3. Update `.gitignore` with the plugins/themes installed, and remove plugins/themes that are removed from `whippet.json`
4. Run `whippet deps install`

### whippet deps update [type]/[name]

e.g. `whippet deps update plugins/twitget`

This will:

1. Check the commit hash for the ref of the specified repo, provided it is in `whippet.json`
2. Update that repo in `whippet.lock`
3. Update `.gitignore` if the repo was no previously installed
4. Install the repo at the specified ref

### whippet plugins install

This command will run through the items in `whippet.lock` and clone any missing plugins/themes, or fetch and checkout.

## Deploys

To deploy applications using Whippet, first create a directory for your releases:

```
mkdir /var/local/myapp
```

Then create some subdirectories and a wp-config.php:

```
mkdir /var/local/myapp/shared
mkdir /var/local/myapp/shared/uploads
mkdir /var/local/myapp/releases
cp /path/to/your/wp-config.php /var/local/myapp/shared/
```

When you deploy, Whippet will make sure your app is up to date (per your `whippet.lock`), create a new release in releases, and create a symlink that points to it (in this example, at /var/local/myapp/current).

You can then configure your webserver to use the current symlink as your document root, and your application should be available.

### whippet deploy [-f] <directory>

This command will create a new release, using the base <directory> that you specify. In the example above, this would be:

```
$ whippet deploy /var/local/myapp
```

Note that this command must be run from within your Whippet application's repo.

Note also that Whippet, by default, will not deploy your application if the commit that you are on has already been deployed. To override this behaviour and force a redeploy, use -f:

```
$ whippet deploy -f /var/local/myapp
```

## Generators

Whippet can generate new whippet applications and whippet-aware themes.

### whippet generate <thing>

Use ```whippet generate -l``` to list available generators. At the time of writing, three are supported.

#### Whippet

Generates a new Whippet application with the directory structure in place.

#### Theme

Generates a Whippet-aware theme with an initial set of templates and tools such as grunt and scss pre-configured.

#### Migration

Generates a Whippet application from an old-style dxw wp-content repo. (This is probably not useful any more)

## Theme development

If you are developing within a Whippet-aware theme, whippet will make some things easier.

### whippet theme watch

This command is essentially syntactic sugar for running Grunt. Execute it within a Whippet-enabled theme directory and it will take care of running your grunt tasks as you develop your theme. It runs a variety of tools, including jslint, compiling scss, minification and image compression.

In the future, it will also run automated tests.

# Roadmap

## Reminders

- Deploy will use latest master WP if application.json specifies master, not whatever was current at time of last commit. That is probably bad?

- Cucumber - am now blocked on WP integration. It just sucks without that.
  - Test environment
  - Seeds
    - Seeds (or something) for clean install
      - whippet db reset?
      - At what point are we duplicating wp-cli? Should that be bundled in?
  - Database cleanup (database-cleaner)
  - WP integration (ruby-wpdb)
  - Can we avoid HTTP requests/whippet-server when running tests?
    - Quiet mode makes this much better already
    - Tests should be run on their own Whippet-Server, in quiet mode
      - requires allowing multiple whippet-servers
  - Only run tests for the activated theme
    - I think this is easy, just by doing cucumber path/to/
  - Some default features for common WordPress things would be helpful
    - index, single, page, category, archive, search, 404, analytics... delete whatever is inapplicable


## Next

- Refactor console I/O
- Sort out whippet-server
- whippet console (using php --auto-prepend-file=init.php -a?)
  - easier to do after we've sorted out whippet-server
  - No wait. wpcli does this. wp shell! Can we use that?
- Add some way to provide a helpful description in a generator
- whippet generate with no arguments should print an error message


## Later

- whippet generate theme
- whippet console --ruby
  - See also phpsh.org, which looks nicer than php -a but requires Python
- whippet server
  - Make sure it is compatible with other servers, like wp-cli?


## Much later

- Should we manage mu-plugins too? Perhaps with a flag in Plugins?
- Manage a system-wide shared directory of plugins and wordpresses that gets used by all my many projects, so I don't have lots of identical copies of things in application directories.

## Licence

[MIT](COPYING.txt)

_The stand-alone web server previously hosted here can now be found at https://github.com/dxw/whippet-server_

# Whippet

This project is a framework for building WordPress applications that eases deployment, plugin management and build steps. Whippet is part of dxw's work to build and host WordPress-based applications that conform more closely to 12-factor principles.

Whippet has a few basic goals:

1. Allowing proper build steps to take place, that automate build tasks both during development and deployment
2. Properly managing plugins, allowing them to be version controlled and easily updated
3. Managing the creation of releases, including rollbacks
4. Automating the generation of commonly required objects like new applications and new themes
5. Facilitating automated testing
6. Allowing structured test data to be distributed as part of the codebase

At the moment, Whippet can manages plugins and releases and compile stylesheets in Whippet-enabled themes.

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

### Add plugins

If you're using any plugins from the codex, you should add them to your ```plugins``` file. For more information, see the [Plugins section](#plugins).

### Give yourself some credit!

Whippet contains a ```/public/humans.txt``` file that you should update with information about your project. You can also add other files to ```/public/``` that you'd like to
see in the root directory of your website, like the Google webmaster tools file, or a favicon.

### Add or generate a theme

Finally, add a theme to ```/wp-content/themes/``` (or [generate one](Generators) and get devving!

## Run your application

The recommended method for running a Whippet application is to use [Whippet Server](https://github.com/dxw/whippet-server).

If you prefer, you can symlink ```/wp-content``` into a WordPress directory on your web server.

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

To manage plugins using Whippet, you make entries in the Plugins file in the root of the application.

The first line of the file should specify a source for plugins. The source should be a base url for a git repo.

If you are a dxw customer, the source will be ```git@git.dxw.net:wordpress-plugins/```. If not, we suggest using ```https://github.com/wp-plugins```.

Subsequent lines should specify plugins that you want to install. They consist of the name of the plugin followed by an equals sign, followed optionally by a tag or branch name. Example:

```
source = "git@git.dxw.net:wordpress-plugins/"

akismet=
```

The ```akismet=``` instructs Whippet (on request) to install the most recent version of Akismet available in the repo. Whippet will determine a valid repo URL for the akismet plugin by appending it to the source. In this example:

```
git@git.dxw.net:wordpress-plugins/akismet
```

You can also specify a particular label or branch that you want to use. Generally, this will either be master (the default) or a tag (for a specific version). So you can do:

```
akismet = v3.0.0
```

Which will cause Whippet to install the plugin at the commit with that tag. If you use a branch:

```
akismet = master
```

Then whippet's behaviour will vary depending on what command you run (see below).

Finally, you can also specify a repo for an individual plugin explicitly:

```
# Pull version 3.0.0 from your own special repo
akismet = 3.0.0, git@my-git-server.com:akismet

# Or, pull master:
akismet = master, git@my-git-server.com:akismet

# This works too:
akismet = , git@my-git-server.com:akismet
```

### whippet plugin install

When run for the first time, this command will install all the plugins in your Plugins file, at the most
recent commits that exist in the remote for branch or tag you specify (or master, if not specified.) The
hashes for these commits will be saved in plugins.lock, which you should commit into git.

When run on subsequent occasions, this command will:

1. Check for plugins that have been removed from your Plugins file, and delete them from the application
2. Check for changes to the Plugins file, and update, add or remove plugins as specified
3. Check for plugins that have been added to your Plugins file, and clone them

Critically, if no changes have been made to the Plugins file, whippet plugin install will always install
the commits specified in plugins.lock; ie, the most recent versions that were available at the time the
plugins were last installed.

### whippet plugins update <plugin>

This command checks to see if the branch or tag in the Plugins file has a newer commit on the remote repo than
is installed locally, and if so, updates the installed plugin to the newest one available on the remote.

It is used where the Plugins file refers to a branch (either explicitly, or by leaving it blank and
defaulting to master) and you wish to update the locally installed version to the newest one available.

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

When you deploy, Whippet will make sure your app is up to date (per your plugins.lock), create a new release in releases, and create a symlink that points to it (in this example, at /var/local/myapp/current).

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

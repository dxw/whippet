# Whippet

This project is a framework for building WordPress applications that eases deployment, plugin management and build steps. Whippet is part of dxw's work to build and host WordPress-based applications that conform more closely to 12-factor principles.

Whippet has a few basic goals:

1. Allowing proper build steps to take place, that automate build tasks both during development and deployment
2. Properly managing plugins and themes, allowing them to be version controlled and easily updated
3. Managing the creation of releases, including rollbacks
4. Automating the generation of commonly required objects like new applications and new themes

Whippet can manage plugins and themes and releases.

During development, whippet can be used in conjunction with [wpc](https://github.com/dxw/wpc).

*Whippet is under development and should be considered alpha software. If you use it, we'd love to know what you think.*

## Getting started

### Install whippet

To install Whippet, clone this directory and install its dependencies:

```
$ git clone https://github.com/dxw/whippet.git
$ composer install
```

You might also want to symlink whippet to somwhere in your path:

```
sudo ln -s $PWD/bin/whippet /usr/local/bin/whippet
```

### Generate an application

To create a new application, run:

```
$ whippet generate app
```

This will create a new Whippet application in `./whippet-app`. The structure of this application is explained below.

You can change the location with the `-d` option.

If you're using GitLab (e.g. within dxw infrastructure), use the `-c` option to generate a `.gitlab-ci.yml` template file for the app.

### Configure your application

There are a few configuration steps you'll need to follow when you create a new application.

#### Set your WordPress version

By default, Whippet uses the current development version of WordPress. If you want to specify a version to develop against, you'll need to edit `config/application.json`:

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

#### Add plugins and themes

If you're using any plugins or themes from the codex, you should add them to your `whippet.json` file. For more information, see the [Plugins section](#plugins).

#### Give yourself some credit!

Whippet contains a `public/humans.txt` file that you should update with information about your project. You can also add other files to `public/` that you'd like to
see in the root directory of your website, like the Google webmaster tools file, or a favicon.

#### Add or generate a theme

Finally, add a theme to `wp-content/themes/` (or [generate one](Generators) and get devving!

### Run your application

The recommended method for running a Whippet application is to use the [wpc](https://github.com/dxw/wpc) project.

## Application structure

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

## Commands

### Plugins

Note: At the moment, Whippet assumes it is running within dxw's infrastructure, and makes some assumptions accordingly. If you run into a problem where this may be the cause, please open an issue.

To manage plugins and themes using Whippet, you make entries in the `whippet.json` file in the root of the application.

The file should specify a source for plugins and themes. The source should be a base url for a git repo.

If you are a dxw customer, the source will be `git@git.govpress.com:wordpress-plugins/`. If not, we suggest using `https://github.com/wp-plugins` for plugins.

The rest of the file should specify plugins and themes that you want to install. Example:

```
{
    "src": {
        "plugins": "git@git.govpress.com:wordpress-plugins/",
        "themes": "git@git.govpress.com:wordpress-themes/"
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
git@git.govpress.com:wordpress-plugins/akismet
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

#### whippet deps update

This command will:

1. Check the commit hash for each ref of each repo specified in `whippet.json`
2. Update `whippet.lock`
3. Update `.gitignore` with the plugins/themes installed, and remove plugins/themes that are removed from `whippet.json`
4. Run `whippet deps install`

#### whippet deps update [type]/[name]

e.g. `whippet deps update plugins/twitget`

This will:

1. Check the commit hash for the ref of the specified repo, provided it is in `whippet.json`
2. Update that repo in `whippet.lock`
3. Update `.gitignore` if the repo was not previously installed
4. Install the repo at the specified ref

#### whippet deps install

This command will run through the items in `whippet.lock` and clone any missing plugins/themes, or fetch and checkout.

#### Checking for inspections

Both the `install` and `update` commands will both attempt to check that a
plugin has had a security inspection by checking the API on
https://security.dxw.com/

This API is only available to dxw employees, since it contains privately
published inspections which for various reasons cannot be published. To
disable these checks pass `-c` when running these commands.

To use a different host for the API (e.g. for development and testing) set an
environment variable, eg.

    export INSPECTIONS_API_HOST=http://localhost:8000

### Deploys

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

#### whippet deploy [-f] <directory>

This command will create a new release, using the base <directory> that you specify. In the example above, this would be:

```
$ whippet deploy /var/local/myapp
```

Note that this command must be run from within your Whippet application's repo.

Note also that Whippet, by default, will not deploy your application if the commit that you are on has already been deployed. To override this behaviour and force a redeploy, use -f:

```
$ whippet deploy -f /var/local/myapp
```

### Generators

Whippet can generate new whippet applications and whippet-aware themes.

#### whippet generate <thing>

Use `whippet generate -l` to list available generators. At the time of writing, three are supported.

##### App

Generates a new Whippet application with the directory structure in place.

##### Theme

Generates a Whippet-aware theme with an initial set of templates and tools such as grunt and scss pre-configured.

## Licence

[MIT](COPYING.txt)

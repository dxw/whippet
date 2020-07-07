# Generating Whippet applications and themes

Whippet can generate new Whippet-compliant applications and themes for you.

## Generating a Whippet application

To create a new Whippet application, run:

```
$ whippet generate app
```

This will create a new Whippet application in `./whippet-app`. The structure of this application is explained below.

You can change the location with the `-d` option.

You can change the location of the WordPress core repository set in `config/application.json` with the `-r` option. The default is `https://github.com/WordPress/WordPress.git`.

If you're using GitLab (e.g. within dxw infrastructure), use the `-c` option to generate a `.gitlab-ci.yml` template file for the app.

### Configure your application

There are a few configuration steps you'll need to follow when you create a new application.

#### Set your WordPress version

By default, Whippet uses the latest release of WordPress. To specify a version to develop against, you'll need to edit `config/application.json`:

```
{
    "wordpress": {
        "repository": "https://github.com/WordPress/WordPress.git",
        "revision": "5.4.2"
    }
}
```

You can also change the WordPress repository used here by setting the `-r` option when generating a new Whippet application.

To change the version, replace the "revision" value with the version you'd like:

```
        "revision": "4.1.1"
```

#### Add plugins and themes

If you're using any plugins or themes from the codex, you should add them to your `whippet.json` file. For more information, see [Managing themes and plugins](themesandplugins.md).

#### Give yourself some credit!

Whippet contains a `public/humans.txt` file that you should update with information about your project. You can also add other files to `public/` that you'd like to
see in the root directory of your website, like the Google webmaster tools file, or a favicon.

#### Add or generate a theme

Finally, add a theme to `wp-content/themes/` (or [generate one](#generating-a-whippet-theme) ) and get devving!

### Run your application

The recommended method for running a Whippet application is to use the [wpc](https://github.com/dxw/wpc) project.

### Application structure

An application that uses Whippet must have the following directory structure, and must be a git repository:

```
- config      # Application configuration files
- public      # Non-WordPress files that should be available via the web
- wp-content  # Your application's wp-content directory
  - mu-plugins  # Must-use plugins
  - plugins     # Plugins (Whippet managed and otherwise)
  - themes      # Themes (Whippet managed and otherwise)
```

## Generating a Whippet theme

To create a new Whippet theme, run:

```
$ whippet generate theme
```

This will generate a new Whippet-compliant WordPress theme in `./whippet-theme`.

You can change the location with the `-d` option.

The generated theme is based on [Whippet Theme Template](https://github.com/dxw/whippet-theme-template/).

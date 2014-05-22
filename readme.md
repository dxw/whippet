# Whippet

Whippet is a tool for managing WordPress applications. It has a few basic goals:

1. Facilitating automated testing
2. Allowing structured test data to be distributed as part of the codebase
3. Allowing proper build steps to take place, that automate build tasks both during development and deployment
4. Properly managing plugins, allowing them to be version controlled and easily updated
5. Automating the generation of commonly required objects like new applications and new themes

At the moment, Whippet just manages plugins.


# TODO

## Reminders

1. When a repo changes in the manifest, that should trigger a re-install
1. Remove .git etc from plugins


## Next

1. How are we going to handle these things?
  - wp-config.php
  - favicons
  - google webmaster shiz
  - mu-plugins
  - We need something that defines how and where these things are generated
    - Some things will be different on production/dev/test. I guess we need to add back nascent support for environments but just not support them in whippet plugin install|update
2. Refactor {wordpress,plugin}.class.php to make them DRYer, and improve console I/O
3. Integrate whippet-server back into the project
  - Make sure it is compatible with other servers, like wp-cli?


## Later

1. Implement whippet generate theme|app
2. Manage a system-wide shared directory of plugins and wordpresses that gets used by all my many projects, so I don' have lots of identical copies of things in application directories.


# Commands

## Plugins

Note: At the moment, Whippet assumes it is running within dxw's infrastructure, and makes assumptions accordingly.

To manage plugins using Whippet, you make entries in the Plugins file in the root of the application.

The first lines of the file should specify a source and a WordPress version.

The source should be a base url for a git repo. The WordPress version follows the same format as plugin entires: see "Specifying the WordPress version", below, for more information.

Subsequent lines should specify plugins that you want to install. They consist of the name of the plugin followed by an equals sign, followed optionally by a tag or branch name. Example:

```
source = "git@git.dxw.net:wordpress-plugins/"

akismet=
```

Whippet will determine a valid repo URL for the akismet plugin by appending it to the source. In this example:

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

Finally, you can also specify a repo explicitly:

```
# Pull version 3.0.0 from your own special repo
akismet = 3.0.0, git@my-git-server.com:akismet

# Or, pull master:
akismet = 3.0.0, git@my-git-server.com:akismet

# This works too:
akismet = , git@my-git-server.com:akismet
```

### whippet plugin install

When run for the first time, this command will install all the plugins in your Plugins file, at the most
recent commits that exist in the remote for branch or tag you specify (or master, if not specified.) The
hashes for these commits will be saved in plugins.lock, which you should commit into source control.

When run on subsequent occasions, this command will:

1. Check for plugins that have been removed from your Plugins file, and delete them from the application
2. Check for changes to the Plugins file, and update, add or remove plugins as specified
3. Check for plugins that have been added to your Plugins file, and clone them

Critically, if no changes have been made to the Plugins file, whippet plugin install will always install
the commits specified in plugins.lock; ie, the most recent versions that were available at the time the
plugins were installed. Updated versions will not be installed unless you lock the plugin to the updated
version using its tag.

### whippet plugins update <plugin>

This command checks to see if the branch or tag in the Plugins file has a newer commit on the remote than
it does on the local, and if so, updates the local commit to the newest one available on the remote.

It is used where the Plugins file refers to a branch (either explicitly, or by leaving it blank and
defaulting to master) and you wish to update the locally installed version to the newest one available.


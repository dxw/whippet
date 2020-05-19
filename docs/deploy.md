# Deploying a Whippet application

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

## whippet deploy [-f] <directory>

This command will create a new release, using the base <directory> that you specify. In the example above, this would be:

```
$ whippet deploy /var/local/myapp
```

Note that this command must be run from within your Whippet application's repo.

Note also that Whippet, by default, will not deploy your application if the commit that you are on has already been deployed. To override this behaviour and force a redeploy, use -f:

```
$ whippet deploy -f /var/local/myapp
```

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

You will need:

* [PHP](https://www.php.net/)
* [Composer](https://getcomposer.org/)
* [git](https://git-scm.com/)

### Install whippet

#### Via homebrew (recommended)

You can install Whippet via Homebrew by running:

```
brew install dxw/tap/whippet
```

#### From source

Whippet implements the [scripts to rule them all](https://github.com/github/scripts-to-rule-them-all)
pattern. To install Whippet from source, clone this repository:

```shell
$ git clone https://github.com/dxw/whippet.git
```

Then run the relevant script to setup your environment and install dependencies:

```shell
./script/setup
```

You might also want to symlink whippet to somewhere in your path:

```shell
sudo ln -s $PWD/bin/whippet /usr/local/bin/whippet
```

## Using Whippet

The main things you can use Whippet to do are:

* [Generating a Whippet application or theme](docs/generate.md)
* [Managing themes and plugins](docs/themesandplugins.md)
* [Deploying a Whippet application](docs/deploy.md)

## Support

For dxw developers who will encounter Whippet in a support context.

See the [Support](docs/support.md) doc.

## Licence

[MIT](COPYING.txt)

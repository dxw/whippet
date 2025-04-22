# 2. Use Rust for development

Date: 2025-04-22

## Status

Accepted

## Context

Previous versions of Whippet were written in PHP, which is not well suited
to desktop CLI apps. Moreover, Whippet has variously been a server a local
development environment and various other sorts of tooling. The accretion of
code over the years has led to a mis-match in coding styles and architectural
decisions and, most noticeably, the use of several different testing frameworks.
This has meant that it is difficult to add significant new features or to
refactor the code we already have.

This means that the cost of re-engineering the application from scratch is not
likely to be much more expensive than refactoring the code we have.

Rust is a reasonable choice for a new Whippet for the following reasons:

1. Rust is type safe, memory safe and has strong tooling for linting, formatting
   and a built in testing library.
2. Rust is _fast_. It is a compiled language and binaries are portable, so can
   be used on MacOS and also Ubuntu (which we use for CI pipelines) without
   cross-compiling.
3. It is relatively easy to use threads in Rust, which we would like to try
   in order to run I/O operations such as Git clones or checkouts, in parallel.

## Decision

We will use Rust for a first attempt at re-writing Whippet and review the choice
once 'whippet deps update' has been implemented, so that we can run Whippet v2
and v3 side-by-side and compare them.

## Consequences

Both the tooling and the Whippet app will need replacing. The re-written Whippet
will be safer, changes will be easier to make but there will be a steep learning
curve for anyone working on this repository, which may slow down development.

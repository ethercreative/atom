# Atom
Adding enhanced modularity to Craft CMS Twig templating

## Installation

```shell
$ composer require ether/atom
```

## Usage

Create a folder called `_atoms` in your `templates` directory (this can be 
customised, see [Config](#config)).

### Basic

In this folder you can create re-usable twig templates, or atoms (a.k.a. 
modules, components, molecules). You can access the atoms in twig using the 
following syntax:

```twig
{% x:my-atom %}
```

The above will include `_atoms/my-atom` in your template. If `my-atom` 
doesn't exist then nothing will be output.

### Parameters

You can pass parameters to your atom which will be exposed within the atom. The
current template context is NOT passed to the atom, so any global variables will
have to be passed manually.

```twig
{% x:my-atom { heading: "Hello world!" } %}
```

In the above example, `my-atom` will be given access to the `heading` variable.
If `heading` isn't passed then the variable will be undefined. You'll want to 
check variable availability with `is defined` or `|default`.

### Children

Children can also be passed to atoms:

```twig
{% x:my-atom { heading: "Hello world!" } %}
    <p>This is my atom</p>
    <p>There are many like it, but this is mine</p>
    <p>{{ myVariable }}</p>
{% endx %}
```

Children are rendered in the parent context, not the atoms. This means any 
variables you pass to the atom will not be available in the children (unless 
they are also available in the parent context).

Children are rendered within the atom using the `children` variable, which will
contain the rendered contents of the children or `null` if no children are 
defined.

```twig
{# Contents of `_atoms/my-atom` #}
<div>
    <h1>{{ heading }}</h1>
    {{ children }}
</div>
```

### Nested

Atoms can be nested inside other atoms!

```twig
{% x:my-atom %}
    {% x:another-atom %}
{% endx %}
```

### Sub-folders

You can store atoms in folders within your `_atoms` directory. For example, if
you had an atom at `_atoms/cards/news`, you could access it using the following
syntax:

```twig
{% x:cards/news %}
```

### Dynamic Atoms

You can render atoms with dynamic names by wrapping the atom name in square 
brackets:

```twig
{% set myVar = 'example-atom' %}

{% x:[myVar] %}
```

## Config

You can configure Atom by creating a `atom.php` file in your `config` folder.
See [config.php](./src/config.php) for the available settings.

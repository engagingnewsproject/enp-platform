# Shared tasks library docs

## Project conventions

1. Make sure your plugin main .php file is named like your project name (`name` property of your `package.json` file).
2. If your project keeps plugin version number reference outside the file header PHP comment, please make sure it's in a define. E.g. like this:
	`define('SNAPSHOT_VERSION', '3.1.5');`
If your project uses a variable to keep track of the current version, assign the value of this define to it. See snapshot project for an example of this.

## Gruntfile conventions and practices
1. Require the shared task loader at the top of your `Gruntfile.js`, passing `grunt` variable to the require, like this:
	`var wpmudev = require('./shared-tasks/loader')(grunt);`
2. Automatic fileset types:
	- Externals: if your project uses external libraries outside submodules (i.e. stuff that should be included in a release, but not e.g. processed via linters), add those paths in yourself. See smartcrawl project for an example of this. Note: git submodules will be added to the list automatically.
	- Free (wordpress.org) project releases: WPMU DEV Dashboard will be automatically excluded from free project release packaging. To omit other paths, add them to the `free` files queue manually.
	- Full (DEV proper) project releases: `readme.txt` and any screenshot images in the root project folder will be excluded from full project release packaging. To omit other paths, add them to the `full` files queue manually.
	- Cleanup: the `dist` folder will be treated as an intermediate area and will be cleaned up before releasing, as well as any `.zip` files in the root directory. To include other paths in automated cleanup, add them to the `clean` queue manually.
3. Shared tasks enforces a certain interface - namely, your `Gruntfile.js` has to expose a task named `release`. Running this task has to end up with newly created, self-sufficient, versioned distributable archive in the `builds` directory.
4. Use the supplied `wpmudev_release` task in your `release` task body, and supply any release- and project- specific info in its configuration property. See smartcrawl and snapshot projects for an example of this. The `wpmudev_release` task will also automatically update your project's version numbers, check changelog, update translation catalogs and, if everything's up to spec, commit changes and tag the release.
5. It is recommended to maintain a separate, simpler packaging task, to produce a simplified current work snapshot archive. See snapshot and smartcrawl projects for an example of this.
6. You do not need to maintain a separate project `makepot` task - this comes with the library, and will be called automatically from `wpmudev_release` task.
7. You can - but do not have to - maintain separate `phpcs` and `jshint` tasks, the library will try to use system linters if it can via the `wpmudev_lint` task. It will first try to use the default configuration, if it's present.

## Work conventions and tools

The project comes with grunt tasks to facilitate adhering to conventions when working on bugs and features.

To make use of them, you will need an ID for your task (`TASKID`). If you are working on an Asana task, your task ID will be the last numeric portion of the Asana task URL. For an example, for this task: https://app.asana.com/0/46496453944769/467725328362050/f, the `TASKID` would be `467725328362050`.

If you are *not* working on an Asana task, consider creating one :) Otherwise, use a quoted sentence that briefly, yet sufficiently explains what you're working on as your `TASKID`.

### Examples

Fixing a bug with Asana task assigned:
	`grunt bugfix:467725328362050`

Adding a feature with Asana task assigned:
	`grunt featureadd:467725328362050`

Fixing a bug with *no* Asana task assigned:
	`grunt bugfix:"SEO Analysis"`

Adding a feature with *no* Asana task assigned:
	`grunt featureadd:"Flying saucers"`

#### What this does

Runing this task will:

1. ... create an appropriately named branch (`fix/<TASKID>` for `bugfix` task, `new/<TASKID>` for `featureadd` task)
2. ... add a temporary changelog entry at the top of your `changelog.txt` with task info. This serves multiple purposes:
	- It is easier for QA staff to run their tests with efficiency.
	- When it is release time, it will be easier to compile the proper changelog entry with this info there.
3. ... make an initial commit in your new branch and leave to do your work.

These auto-created branches will not be pushed to BitBucket unless you do so yourself. If your work is going to take longer than just an hour or two, consider pushing it.

#### Implementing fixes and features atop of a previous release

The newly auto-created branch will be based off of your current branch and commit. If you want to base it off of a different source (e.g. you want to fix a bug in a released version but the main work already progressed on), you can include an optional second parameter to tasks. For an example, to start working on a fix for bug report 467725328362050 in v2.0.1 release, use this command: `grunt bugfix:467725328362050:2.0.1`

### Once I'm done...

When you're done working and the fix or feature is to be incorporated into the different branch, either merge it, or push your branch and submit a pull request.

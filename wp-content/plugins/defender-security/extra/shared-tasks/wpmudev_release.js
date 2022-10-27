module.exports = function (grunt, loader) {
	'use strict';

	grunt.registerTask('wpmudev_release', function () {
		var wpmudev = grunt.config.get('wpmudev_release') || {},
			version = wpmudev.version || false,
			version_define = wpmudev.version_define || false,
			type = wpmudev.type || false,
			tasks = wpmudev.build || [],
			error = ''
		;

		if (!version && !type) {
			grunt.fail.fatal("This task needs at the very least one argument (type), ideally two (version and type).");
			return false;
		}

		if (version) {
			grunt.log.subhead("Attempting to update version number to " + version);

			grunt.log.writeln("Checking changelog");
			if (grunt.file.exists("changelog.txt")) {
				var changelog = grunt.file.read("changelog.txt");
				if (!changelog.match(new RegExp('^\\s*' + version + '\\s+', 'm'))) {
					grunt.log.warn("Changelog doesn't know about the current version!".red.bold);
					error = ':error';
				} else {
					grunt.log.writeln("Changelog seems OK");
				}
			} else {
				grunt.log.error("Couldn't find changelog.txt!");
				return false;
			}

			grunt.log.writeln("Updating version info in package.json");
			var pkg = grunt.file.readJSON('package.json');
			pkg.version = version;
			grunt.config.set("pkg", pkg);
			grunt.file.write('package.json', JSON.stringify(pkg, null, 2));

			grunt.log.writeln("Updating version info in main plugin file");
			if (grunt.file.exists((pkg.main || pkg.name) + ".php")) {
				var plugin = grunt.file.read((pkg.main || pkg.name) + '.php');

				grunt.log.writeln("\t* Updating version info in plugin header");
				plugin = plugin.replace(/^(\s?\* )?Version:.*$/m, '$1Version: ' + version);

				if (version_define) {
					var vdrx = new RegExp("'" + version_define + "',\\s['\"].*?['\"]");
					grunt.log.writeln("\t* Updating version info in version define");
					plugin = plugin.replace(vdrx, "'" + version_define + "', '" + version + "'");
				}

				grunt.log.writeln("Done updating info in main plugin file, writing changes");
				grunt.file.write((pkg.main || pkg.name) + '.php', plugin);
			} else {
				grunt.log.warn("Could not find main plugin file".yellow.bold);
			}
		}

		tasks.unshift('wpmudev_makepot'); // Preparing for l10n is always a part of the release
		tasks.push('wpmudev_postrelease' + error);
		grunt.task.run(tasks);
	});



	grunt.registerTask('wpmudev_postrelease', function (error) {
		var done = this.async(),
			wpmudev = grunt.config.get('wpmudev_release') || {},
			type = wpmudev.type || 'full',
			tasks = wpmudev.clean || []
		;

		if (tasks && tasks.length) grunt.task.run(tasks);

		var pkg = grunt.config.get('pkg'),
			version = (pkg || {}).version,
			name = (pkg || {}).name,
			out = function (cmderr, result) {
				if (cmderr) {
					grunt.log.error("Encountered an error with post-release git work:", result);
				} else {
					grunt.log.writeln("Post-release git work (commit+tag) successful *locally*, remember to git push --tags".white.bold);
				}

				grunt.log.subhead('Prepared a release package for ' + version);
				grunt.log.writeln('The releasable archive is in builds/' + name + '-' + version + ('full' !== type ? '-free' : '') + '.zip');
				if ('full' === type) {
					var msg = 'Upload the archive to https://wpmudev.com/wp-admin/edit.php?post_type=project&page=projects-manage&manage_files=';
					msg += loader.environment.get_wdp_id();
					grunt.log.ok(msg.green.bold);
				}

				if (error) {
					grunt.log.error("Apparently, we also encountered some errors along the way. Please, inspect the output to see what's up.".red.bold);
				}
				done();
			}
		;

		// Commit all the stuff that's left hanging out and add version tag
		if (!error) {
			grunt.util.spawn({
				cmd: 'git',
				args: ['commit', '-am', 'Prepared v' + version + ' release']
			}, function (cmderr, result) {
				if (cmderr) {
					grunt.log.error("Encountered an error committing changes", result);
					return out(true);
				}
				grunt.util.spawn({
					cmd: 'git',
					args: ['tag', version]
				}, out);
			});
		} else {
			error = true;
			out(true, "Please, make sure your changelog is prepared");
		}
	});
};

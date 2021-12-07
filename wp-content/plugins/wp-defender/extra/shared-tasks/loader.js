module.exports = function (grunt) {
	'use strict';

	var wpmudev = {};

	wpmudev.files = require('./files')(grunt);
	wpmudev.environment = require('./environment')(grunt);

	wpmudev.loader = {
		dependencies: {
			"makepot": "grunt-wp-i18n",
			"clean": "grunt-contrib-clean",
			"compress": "grunt-contrib-compress",
			"copy": "grunt-contrib-copy"
		},
		load: function () {
			grunt.verbose.subhead("Loading WPMU DEV tasks");

			grunt.file.expand({
				filter: 'isFile'
			}, wpmudev.files.stem_path('external', 'shared-tasks') + '/**/wpmudev_*.js').forEach(function (task) {
				grunt.verbose.writeln("Loading task definition file", task);
				require('../' + task)(grunt, wpmudev);
			});
		},
		setup: function () {
			grunt.verbose.subhead("Checking WPMU DEV task dependencies");
			var spawn = require('child_process').spawnSync,
				which = require('which').sync,
				has_missing = false
			;

			for (var task in wpmudev.loader.dependencies) {
				if (wpmudev.environment.has_task(task)) continue;

				var dep = wpmudev.loader.dependencies[task];

				has_missing = true;
				grunt.log.warn("Missing task dependency:", dep);

				spawn(
					which('npm'),
					['install', '--save-dev', dep],
					{
						stdio: 'inherit',
						shell: true
					}
				);
				grunt.verbose.writeln("Loading npm-defined tasks for", dep);
				grunt.task.loadNpmTasks(dep);
			}

			if (has_missing) {
				grunt.log.writeln("Some missing dependencies installed, please make sure you commit your package.json".red.bold);
			}

			return wpmudev.loader.load();
		},
	};

	wpmudev.loader.setup();

	return wpmudev;

};

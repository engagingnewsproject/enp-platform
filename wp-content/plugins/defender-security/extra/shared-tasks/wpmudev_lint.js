module.exports = function (grunt, wpmudev) {
	'use strict';

	var run_linter = function (cmd, done, fail, orig_args) {
		fail = !!fail;
		var task = (cmd || {}).cmd;
		if (grunt.task.exists(task)) {
			grunt.verbose.writeln("Has project-specific task " + task + ", defaulting to that");
			var args = Array.prototype.slice.call(orig_args || {}).map(function (a) { return ':' + a; });
			grunt.task.run(task + args.join(''));
			return done();
		}

		grunt.log.write("Attempting to lint via system " + task + "... ");
		var which = require('which').sync,
			has_binary = false
		;
		try {
			has_binary = !!which(task);
		} catch (e) { has_binary = false; }

		if (!has_binary) {
			grunt.log.writeln("SKIP".yellow);
			return done();
		}
		grunt.log.writeln("OK".green);

		grunt.util.spawn(cmd, function (error, result) {
			if (error) {
				(fail ? grunt.fail.fatal : grunt.log.error)("Linting failed".red);
			}
			done();
		});

	};

	grunt.registerTask('wpmudev_lint', function () {
		grunt.task.run('wpmudev_jshint');
		grunt.task.run('wpmudev_phpcs');
	});

	grunt.registerTask('wpmudev_phpcs', function () {
		var done = this.async(),
			files = grunt.file.expand({ filter: 'isFile' }, ['**/*.php'].concat(wpmudev.files.not_meta())),
			standard = grunt.file.exists('phpcs.ruleset.xml') ? ['--standard=phpcs.ruleset.xml'] : []
		;
		run_linter({
			cmd: 'phpcs',
			args: standard.concat(files),
			opts: { stdio: 'inherit' }
		}, done, true, arguments);
	});

	grunt.registerTask('wpmudev_jshint', function () {
		var done = this.async(),
			files = grunt.file.expand({ filter: 'isFile' }, ['**/*.js'].concat(wpmudev.files.not_meta()))
		;
		run_linter({
			cmd: 'jshint',
			args: files,
			opts: { stdio: 'inherit' }
		}, done, true, arguments);
	});

};

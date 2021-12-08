module.exports = function (grunt) {
	'use strict';

	var has_task = function (task) {
		grunt.verbose.write("Checking " + task + " task existence... ");
		if (grunt.task.exists(task)) {
			grunt.verbose.writeln("OK".green);
			return true;
		}
		grunt.verbose.writeln("MISSING".red);
		return false;
	};

	var get_wdp_id = function () {
		var pkg = grunt.file.readJSON('package.json'),
			wdp_id = (pkg || {}).wdp_id || false
		;
		if (wdp_id) return true;

		var plugin = grunt.file.read(pkg.name + '.php'),
			result = plugin.match(/(\*\s+)?WDP ID:\s*(\d+)/);
		;
		if (result && result[2]) {
			wdp_id = parseInt(result[2], 10);
		}

		return wdp_id;
	}

	var sanity_check = function () {
		grunt.verbose.subhead("Checking WPMU DEV task environment");

		['release'].forEach(function (task) {
			if (has_task(task)) return true;
			var error = "WPMU DEV interface required task [" + task + "] is missing";
			grunt.log.writeln(error.red.bold);
		});
	};

	grunt.registerTask('wpmudev_preflight_check', sanity_check);

	grunt.cli.tasks = grunt.cli.tasks.unshift('wpmudev_preflight_check');

	return {
		has_task: has_task,
		sanity_check: sanity_check,
		get_wdp_id: get_wdp_id
	};

};

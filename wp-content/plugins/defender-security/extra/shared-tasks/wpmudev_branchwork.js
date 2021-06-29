module.exports = function (grunt, wpmudev) {
	'use strict';

	grunt.registerTask('wpmudev_branchwork', function (type, task_id, version) {
		var done = this.async();

		if (arguments.length < 1) {
			grunt.log.error("The task requires at least 1 param: bug ID (e.g. Asana bug IDREF - the last numeric portion of the URL path");
			grunt.log.writeln("You can also specify the source version to work off as second argument (git tag) - otherwise, the task works off current branch, latest commit");
			done();
		}
		if (arguments.length < 2) {
			grunt.log.writeln("No version (git tag) specified - working off current branch, latest commit");
		}

		version = version || false;
		task_id = task_id || false;
		var branch = task_id.replace(/[^-0-9a-zA-Z]/g, '-').replace('/-+/', '-').toLowerCase(),
			is_asana_taskid = !!task_id.match(/^[0-9]+$/),
			cmdparts = (version ? ['checkout', version, '-b'] : ['checkout', '-b']),
			task_ref = (is_asana_taskid ? 'https://app.asana.com/0/' + task_id + '/' + task_id : task_id),
			chlog_msg = task_ref + ' (' + grunt.template.today('yyyy-mm-dd') + ')',
			commit_msg = task_ref
		;

		if ('fix' === type) {
			chlog_msg = '- Fix: ' + chlog_msg;
			commit_msg = 'Fixes: ' + commit_msg;
			branch = 'fix/' + branch;
		} else if ('feature' === type) {
			chlog_msg = '- Add: ' + chlog_msg;
			commit_msg = 'Implements: ' + commit_msg;
			branch = 'new/' + branch;
		} else {
			chlog_msg = '- Task: ' + chlog_msg;
			commit_msg = 'Task: ' + commit_msg;
			branch = 'other/' + branch;
		}
		cmdparts.push(branch);

		if (!is_asana_taskid) {
			grunt.log.warn("Your task description does NOT seem to be an Asana task ID!".red.bold);
			grunt.log.writeln("Ideally, all work should have an Asana tasks reference. Please, consider creating an Asana task first.".yellow);
			grunt.log.writeln("Your task branch will still be created now, though.");
		}

		// First up, create a branch and use that:
		grunt.log.writeln("Creating the target work branch");
		grunt.util.spawn({
			cmd: 'git',
			args: cmdparts
		}, function (error, result) {
			if (error) {
				grunt.log.error("We encountered an error creating the target branch", result);
				grunt.log.error("Aborting");
				done();
			}
			// Next up, add our fix to the changelog:
			if (grunt.file.exists("changelog.txt")) {
				grunt.log.writeln("Updating the changelog with temporary (versionless) info...");
				var changelog = grunt.file.read("changelog.txt");

				// Find the most recent release line and stuff our changelog message above
				var res = changelog.replace(/\r\n/g, "\n").replace(/\r/, "\n").replace(/(\n\d\.\d.*?\n-{5,})/, chlog_msg + "\n$1");
				grunt.file.write("changelog.txt", res);
			}

			// Make the initial commit for the task - just the changelog change
			grunt.log.writeln("Making the initial commit in your work branch");
			grunt.util.spawn({
				cmd: 'git',
				args: ['commit', '-am', commit_msg]
			}, function (error, result) {
				if (error) {
					grunt.log.error("We encountered an error making the initial commit in bugfix branch", result);
					grunt.log.error("Aborting");
					done();
				}
				grunt.log.writeln("All good, let's get on with the actual work now!");
				done();
			});
		});

	});

	grunt.registerTask('bugfix', function (bug_id, version) {
		version = version ? ':' + version : '';
		grunt.task.run('wpmudev_branchwork:fix:' + bug_id + version);
	});

	grunt.registerTask('featureadd', function (task_id, version) {
		version = version ? ':' + version : '';
		grunt.task.run('wpmudev_branchwork:feature:' + task_id + version);
	});

};

module.exports = function (grunt) {
	'use strict';

	var _lists = ['all', 'free', 'full', 'dev', 'temp', 'submodules', 'clean', 'external'],
		_raw_lists = {
			all: ['**/*'],
			free: [],
			full: ['readme.txt', 'screenshot*.png'],
			dev: [
				'bin/**/*', 'tests/**/*', 'logs/**/*', 'doc/**/*', 'docs/**/*', // Dev directories
				'Gruntfile.js', 'GruntFile.js', 'gulpfile.js', 'Gulpfile.js', 'package.json', 'package-lock.json', 'README.md', // Dev files
				'phpunit.xml', 'phpcs.ruleset.xml', // Dev files
				'.git*', 'bitbucket-pipelines.yml' // Repo-specific stuff
			],
			temp: [
				'.tags*', 'tags*',
				'dist/**/*', '*.zip', 'builds/**/*', // Build artifacts
				'.sass-cache/**/*', 'node_modules/**/*', // Cache and transient stuff
			],
			clean: ['dist', '*.zip'],
			external: ['**/vendor/**/*'],
		},
		_files_get = function (list, cback) {
			cback = cback || function (a) { return a; };
			return function () {
				return ((list || {}).files || []).map(cback);
			};
		},
		_files_add = function (list) {
			return function (path) {
				list.files = list.files || [];
				list.files.push(path);
			};
		},

		_file_lists = {},
		_mkpath = function (raw) {
			var sfx = !!raw.match(/\/[^\/]+\.[a-zA-Z]{2,4}/) ? '' : '/**/*';
			return raw.replace(/\/$/, '') + sfx;
		},
		_from_module = function (what, modules) {
			var rx = new RegExp('submodule "([^"]+)"[^]+?' + what + '\\.git'),
				found = ''
			;
			modules.split('[').forEach(function (line) {
				if (found) return true;
				var path = line.match(rx);
				if (path && path[1]) found = path[1];
			});
			return found;
		}
	;

	_lists.forEach(function (list) {
		_file_lists[list] = _raw_lists[list] ? {files:  _raw_lists[list]} : {files: []};
		_file_lists[list].get = _files_get(_file_lists[list]);
		_file_lists[list].not = _files_get(_file_lists[list], function (a) { return '!' + a; });
		_file_lists[list].add = _files_add(_file_lists[list]);
	});

	var files = {
		gather: function () {
			var result = [],
				lists = _lists,
				method = 'get'
			;
			if (arguments.length > 1) {
				// lists, method
				lists = typeof [] === typeof arguments[0] && arguments[0] ? arguments[0] : lists;
				method = typeof '' === typeof arguments[1] && arguments[1] ? arguments[1] : method;
			} else if (arguments.length === 1) {
				if (typeof [] === typeof arguments[0]) lists = arguments[0];
				if (typeof '' === typeof arguments[0]) method = arguments[0];
			}
			lists.forEach(function (list) {
				result = result.concat(_file_lists[list][method]());
			});
			return result;
		},
		get_meta: function () {
			return files.gather([
				'free', 'full',
				'dev', 'temp', 'clean',
				'external'
			]);
		},
		not_meta: function () {
			return files.gather([
				'free', 'full',
				'dev', 'temp', 'clean',
				'external'
			], 'not');
		},
		get: function (lst) {
			return _file_lists[lst].get();
		},
		not: function (lst) {
			return _file_lists[lst].not();
		},
		add: function (list, what) {
			return _file_lists[list].add(what);
		},
		find: function (list, what) {
			var finds = [],
				rx = new RegExp(what)
			;
			_file_lists[list].get().forEach(function (path) {
				if (path.match(rx)) finds.push(path);
			});
			return finds;
		},
		find_path: function (list, what) {
			var finds = files.find(list, what);
			if (!finds.length || !finds[0]) return false;

			return finds[0];
		},
		stem_path: function (list, what) {
			var path = files.find_path(list, what);
			if (!path) return false;

			return path.replace(/\*\*\/\*/g, '');
		}
	};

	grunt.verbose.subhead("Dynamically populate exclusion paths");

	grunt.verbose.write("Adding source SASS files to dev list...");
	grunt.file.expand(['**/*.scss', '!node_modules/**/*']).map(_mkpath).forEach(function (path) {
		files.add('dev', path);
	});
	grunt.verbose.writeln("OK".green);

	if (grunt.file.exists(".gitmodules")) {
		var modules = grunt.file.read(".gitmodules").replace(/\r\n/, "\n").replace(/\r/, "\n"),
			target = false
		;

		grunt.verbose.write("Adding WPMU DEV Dashboard to free distribution exclusions...");
		target = _from_module('wpmudev-dashboard-notification', modules);
		if (target) {
			files.add('free', _mkpath(target));
			grunt.verbose.writeln("OK".green);
		} else {
			grunt.verbose.writeln("Skip".yellow);
		}

		grunt.verbose.write("Adding shared tasks to non-dev distribution exclusions...");
		target = _from_module('shared-tasks', modules);
		if (target) {
			files.add('dev', _mkpath(target));
			grunt.verbose.writeln("OK".green);
		} else {
			grunt.verbose.writeln("Skip".yellow);
		}

		grunt.verbose.writeln("Adding all stuff from git submodules to externals...");
		modules.split('[').forEach(function (sbm) {
			if (!sbm) return true;
			var path = sbm.match(/submodule ('|")([^'"]+?)('|")/);
			if (path && path[2]) {
				files.add('external', _mkpath(path[2]));
				grunt.verbose.writeln("Adding git submodule path", path[2], "OK".green);
			}
		});
	}

	grunt.verbose.writeln("Exclusions populated".blue);

	return files;

};

module.exports = function (grunt, wpmudev) {
	'use strict';

	grunt.registerTask('wpmudev_compress', function (type) {
		var suffix = 'free' === type ? '-free' : '';
		grunt.config.set('compress', {
			wpmudev: {
				options: {
					archive: 'builds/<%= pkg.name %>-<%= pkg.version %>' + suffix + '.zip',
					level: 9
				},
				expand: true,
				cwd: 'dist/',
				src: ['**/*'],
				dest: '<%= pkg.name %>/'
			}
		});
		grunt.task.run('compress:wpmudev');
	});
};

module.exports = function (grunt, wpmudev) {
	'use strict';

	grunt.registerTask('wpmudev_copy', function (type) {
		var files = wpmudev.files.get('all').concat(wpmudev.files.gather(['dev', 'temp'], 'not'));

		if (type) {
			files = files.concat(wpmudev.files.not(type));
		}

		grunt.config.set('copy', {
			wpmudev: {
				files: [{
					expand: true,
					cwd: './',
					src: files,
					dest: 'dist',
					filter: 'isFile'
				}]
			}
		});

		grunt.task.run('copy:wpmudev');

	});
};

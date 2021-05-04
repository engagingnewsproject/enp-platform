module.exports = function (grunt, wpmudev) {
	'use strict';

	grunt.registerTask('wpmudev_cleanup', function () {
		grunt.config.set('clean', {
			wpmudev: wpmudev.files.get('clean')
		});
		grunt.task.run('clean');
	});
};

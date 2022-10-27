module.exports = function (grunt, wpmudev) {
	'use strict';

	grunt.registerTask('wpmudev_makepot', function () {
		var exclusions = wpmudev.files.not_meta(),
			files = [],
			filename = grunt.template.process('<%= pkg.textdomain %>') || grunt.template.process('<%= pkg.name %>')
		;
		grunt.file.expand({filter: 'isFile' }, wpmudev.files.get('all').concat(exclusions)).forEach(function (path) {
			files.push(path);
		});

		grunt.config.set('makepot', {
			wpmudev: {
				options: {
					domainPath: 'languages/',
					type: 'wp-plugin',
					include: files,
					potFilename: filename + '.pot'
				},
				dest: filename
			}
		});

		grunt.task.run('makepot:wpmudev');
	});
};

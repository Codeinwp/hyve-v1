/* eslint-disable camelcase */
/* jshint node:true */
/* global require */

module.exports = function( grunt ) {
	grunt.loadNpmTasks( 'grunt-version' );
	grunt.initConfig(
		{
			version: {
				project: {
					src: [
						'package.json'
					]
				},
				composer: {
					src: [
						'composer.json'
					]
				},
				metatag: {
					options: {
						prefix: 'Version:\\s*',
						flags: ''
					},
					src: [ 'hyve.php' ]
				},
				php: {
					options: {
						prefix: 'HYVE_VERSION\', \'',
						flags: ''
					},
					src: [ 'hyve.php' ]
				}
			}
		}
	);
};

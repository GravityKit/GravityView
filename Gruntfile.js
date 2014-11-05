module.exports = function(grunt) {

	grunt.initConfig({

		pkg: grunt.file.readJSON('package.json'),

		sass: {
			options: {
				outputStyle: 'compressed'
			},
			dist: {
				files: [{
		          expand: true,
		          cwd: 'includes/css/scss',
		          src: ['*.scss','!admin-merge-tags.scss','!admin-tooltips.scss'],
		          dest: 'includes/css',
		          ext: '.css'
		      }]
			},
			templates: {
				files: [{
		          expand: true,
		          cwd: 'templates/css/source/',
		          src: ['*.scss','!search.scss'],
		          dest: 'templates/css/',
		          ext: '.css'
		      }]
			}
		},


		uglify: {
			options: { mangle: false },
			main: {
				files: [{
		          expand: true,
		          cwd: 'includes/js',
		          src: ['**/*.js','!**/*.min.js'],
		          dest: 'includes/js',
		          ext: '.min.js'
		      }]
			},
			searchExt: {
				files: [{
		          expand: true,
		          cwd: 'includes/extensions/search-widget/assets/js/source/',
		          src: ['*.js','!*.min.js'],
		          dest: 'includes/extensions/search-widget/assets/js/',
		          ext: '.min.js'
		      }]
			}
		},

		watch: {
			main: {
				files: ['includes/js/*.js','!includes/js/*.min.js'],
				tasks: ['uglify:main']
			},
			extension_js: {
				files: ['includes/extensions/**/*.js','!includes/extensions/**/*.min.js'],
				tasks: ['uglify:searchExt']
			},
			templates: {
				files: ['templates/css/**/*.scss','!templates/css/**/*.css'],
				tasks: ['sass:templates']
			},
			scss: {
				files: ['includes/css/scss/*.scss'],
				tasks: ['sass:dist']
			},
		}
	});

	grunt.loadNpmTasks('grunt-sass');
	grunt.loadNpmTasks('grunt-contrib-watch');
	//grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-contrib-uglify');


	grunt.registerTask( 'default', [ 'sass', 'uglify','watch'] );

};
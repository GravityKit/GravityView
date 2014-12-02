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
		          src: ['*.scss','!admin-merge-tags.scss','!admin-tooltips.scss','!font.scss'],
		          dest: 'includes/css',
		          ext: '.css'
		      }]
			},
			templates: {
				files: [{
		          expand: true,
		          cwd: 'templates/css/source/',
		          src: ['*.scss','!search.scss','!edit.scss','!font.scss','!notice.scss'],
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
				files: ['includes/js/*.js','!includes/js/*.min.js','readme.txt'],
				tasks: ['uglify:main','wp_readme_to_markdown']
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
		},

		dirs: {
			lang: 'languages'
		},

		// Convert the .po files to .mo files
		potomo: {
			dist: {
				options: {
					poDel: false
				},
				files: [{
					expand: true,
					cwd: '<%= dirs.lang %>',
					src: ['*.po'],
					dest: '<%= dirs.lang %>',
					ext: '.mo',
					nonull: true
				}]
			}
		},

		// Pull in the latest translations
		exec: {
			transifex: 'tx pull -a',

			// Create a ZIP file
			zip: 'python /usr/bin/git-archive-all ../gravityview.zip'
		},

		wp_readme_to_markdown: {
			your_target: {
				files: {
					'readme.md': 'readme.txt'
				},
			},
		}
	});

	grunt.loadNpmTasks('grunt-sass');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-wp-readme-to-markdown');
	grunt.loadNpmTasks('grunt-potomo');
	grunt.loadNpmTasks('grunt-exec');


	grunt.registerTask( 'default', [ 'sass', 'uglify', 'exec:transifex','potomo', 'watch'] );

};

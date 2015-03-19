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
		          cwd: 'assets/css/scss',
		          src: ['*.scss','!admin-merge-tags.scss','!admin-tooltips.scss'],
		          dest: 'assets/css',
		          ext: '.css'
		      }]
			},
			templates: {
				files: [{
		          expand: true,
		          cwd: 'templates/css/source/',
		          src: ['*.scss','!search.scss','!edit.scss','!font.scss','!notice.scss','!oembed.scss'],
		          dest: 'templates/css/',
		          ext: '.css'
		      }]
			}
		},

		jshint: [
			"assets/js/admin-views.js",
			"assets/js/admin-post-edit.js",
			"assets/js/admin-widgets.js",
			"assets/js/admin-entries-list.js",
			"assets/js/fe-views.js"
		],

        imagemin: {
            dynamic: {
                files: [{
                    options: {
                        optimizationLevel: 7
                    },
                    expand: true,
                    cwd: 'assets/images',
                    src: ['**/*.{png,jpg,gif}'],
                    dest: 'assets/images',
                }]
            }
        },

		uglify: {
			options: { mangle: false },
			main: {
				files: [{
		          expand: true,
		          cwd: 'assets/js',
		          src: ['**/*.js','!**/*.min.js'],
		          dest: 'assets/js',
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
			},
		},

		watch: {
			scripts: {
				files: ['assets/js/*.js','!assets/js/*.min.js'],
				tasks: ['uglify:main','newer:jshint']
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
				files: ['assets/css/scss/*.scss'],
				tasks: ['sass:dist']
			}
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
		}
	});

	grunt.loadNpmTasks('grunt-sass');
	grunt.loadNpmTasks("grunt-contrib-jshint");
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-wp-readme-to-markdown');
	grunt.loadNpmTasks('grunt-potomo');
	grunt.loadNpmTasks('grunt-exec');
    grunt.loadNpmTasks('grunt-contrib-imagemin');
    grunt.loadNpmTasks('grunt-newer');

	grunt.registerTask( 'default', [ 'sass', 'uglify', 'exec:transifex','potomo', 'imagemin', 'watch' ] );

};

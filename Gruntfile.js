module.exports = function(grunt) {

	// Suppress shelljs warnings for Node 20
	process.removeAllListeners('warning');

	// Only need to install one package and this will load them all for you. Run:
	// npm install --save-dev load-grunt-tasks
	require('load-grunt-tasks')(grunt);

	const sass = require('node-sass');

	grunt.initConfig({

		pkg: grunt.file.readJSON('package.json'),

		sass: {
			options: {
				implementation: sass,
				outputStyle: 'compressed',
				sourceMap: false
			},
			dist: {
				files: [{
		          expand: true,
		          cwd: 'assets/css/scss',
		          src: ['*.scss','!gf-merge-tags-*.scss','!admin-merge-tags.scss','!admin-tooltips.scss','!admin-metabox-panel.scss','!admin-metabox.scss','!admin-metabox-placeholder.scss','!admin-members-plugin.scss','!variables.scss'],
		          dest: 'assets/css',
		          ext: '.css'
		      }]
			},
			extensions: {
				files: [{
					expand: true,
					cwd: 'includes/extensions/entry-notes/assets/css/source',
					src: ['*.scss'],
					dest: 'includes/extensions/entry-notes/assets/css',
					ext: '.css'
				}]
			},
			templates: {
				files: [{
		          expand: true,
		          cwd: 'templates/css/source/',
		          src: ['*.scss','!search-flexbox.scss','!edit.scss','!font.scss','!notice.scss','!oembed.scss','!responsive.scss'],
		          dest: 'templates/css/',
		          ext: '.css'
		      }]
			},
			docs: {
				files: [{
					expand: true,
					cwd: 'docs/',
					src: ['*.scss'],
					dest: 'docs/',
					ext: '.css'
				}]
			}
		},

		postcss: {
			options: {
				map: false,
				processors: [
					require('autoprefixer')()
				]
			},
			dist: {
				src: 'assets/css/*.css'
			}
		},

		jshint: {
			options: {
				esversion: 11,
				laxbreak: true,
			},
			all: [
				"assets/js/admin-views.js",
				"assets/js/admin-view-dropdown.js",
				"assets/js/admin-grid.js",
				"assets/js/admin-post-edit.js",
				"assets/js/admin-widgets.js",
				"assets/js/admin-entries-list.js",
				"assets/js/fe-views.js",
				"includes/extensions/entry-notes/assets/js/entry-notes.js",
				"includes/widgets/search-widget/assets/js/source/admin-widgets.js"
			]
		},

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
			options: {
				mangle: false
			},
			main: {
				files: [{
		          expand: true,
		          cwd: 'assets/js',
		          src: ['**/*.js','!**/*.min.js', '!**/node_modules/**'],
		          dest: 'assets/js',
		          ext: '.min.js'
		      }]
			},
			bower: {
				files: [{
					expand: true,
					cwd: 'assets/lib',
					extDot: 'last', // Process extension as the last dot (jquery.cookie.js)
					src: ['**/*.js', '!**/build.js', '!**/dist/*.js', '!**/*.min.js', '!**/flexibility.js'],
					dest: 'assets/lib',
					ext: '.min.js'
				}]
			},
			entryNotes: {
				files: [{
					expand: true,
					cwd: 'includes/extensions/entry-notes/assets/js/',
					dest: 'includes/extensions/entry-notes/assets/js/',
					src: ['*.js','!*.min.js'],
					ext: '.min.js'
				}]
			},
			searchExt: {
				files: [{
		          expand: true,
		          cwd: 'includes/widgets/search-widget/assets/js/source/',
		          src: ['*.js','!*.min.js'],
		          dest: 'includes/widgets/search-widget/assets/js/',
		          ext: '.min.js'
		      }]
			}
		},

		watch: {
			scripts: {
				files: ['assets/js/*.js','!assets/js/*.min.js'],
				tasks: ['uglify:main','newer:jshint']
			},
			notes_js: {
				files: ['includes/extensions/entry-notes/assets/js/*.js','!includes/extensions/entry-notes/assets/js/*.min.js'],
				tasks: ['uglify:entryNotes','newer:jshint']
			},
			extension_js: {
				files: ['includes/widgets/**/*.js','!includes/widgets/**/*.min.js'],
				tasks: ['uglify:searchExt','newer:jshint']
			},
			extension_scss: {
				files: ['includes/extensions/**/*.scss'],
				tasks: ['sass:extensions', 'postcss:dist']
			},
			templates: {
				files: ['templates/css/**/*.scss','!templates/css/**/*.css'],
				tasks: ['sass:templates']
			},
			scss: {
				files: ['assets/css/scss/*.scss'],
				tasks: ['sass:dist', 'postcss:dist']
			},
			docs: {
				files: ['docs/*.scss'],
				tasks: ['sass:docs']
			},
			options: {
				spawn: false,
				interrupt: true
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

		exec: {
			blocks: 'cd future/includes/gutenberg && npm i && npm run build',

			// Generate POT file.
			makepot: {
				cmd: function () {
					var fileComments = [
						'Copyright (C) ' + new Date().getFullYear() + ' GravityKit',
						'This file is distributed under the GPLv2 or later',
					];

					var headers = {
						'Last-Translator': 'GravityKit <support@gravitykit.com>',
						'Language-Team': 'GravityKit <support@gravitykit.com>',
						'Language': 'en_US',
						'Plural-Forms': 'nplurals=2; plural=(n != 1);',
						'Report-Msgid-Bugs-To': 'https://www.gravitykit.com/support',
					};

					var command = 'wp i18n make-pot . translations.pot';

					command += ' --file-comment="' + fileComments.join( '\n' ) + '"';

					command += ' --headers=\'' + JSON.stringify( headers ) + '\'';

					return command;
				}
			},

			bower: 'bower install'
		},

		// Add text domain to all strings, and modify existing text domains in included packages.
		addtextdomain: {
			options: {
				textdomain: 'gk-gravityview',    // Project text domain.
				updateDomains: [ 'gravityview', 'gk-foundation', 'trustedlogin' ]  // List of text domains to replace.
			},
			target: {
				files: {
					src: [
						'*.php',
						'templates/**/*.php',
						'future/**/*.php',
						'includes/**/*.php',
						'vendor_prefixed/gravitykit/**',
						'vendor_prefixed/trustedlogin/**',
						'!node_modules/**',
						'!tests/**',
						'!tmp/**',
						'!vendor/**',
						'!vendor_prefixed/**',
						'!includes/lib/xml-parsers/**',
						'!includes/lib/jquery-cookie/**',
						'!.test_dependencies/**',
					]
				}
			}
		},
	});

	// Still have to manually add this one...
	grunt.loadNpmTasks('grunt-wp-i18n');

	// Regular CSS/JS/Image Compression stuff
	grunt.registerTask( 'default', [ 'exec:bower', 'sass', 'postcss', 'uglify', 'imagemin', 'exec:blocks', 'translate' ] );

	// Translation stuff
	grunt.registerTask( 'translate', [ 'addtextdomain', 'exec:makepot' ] );

};

module.exports = function(grunt) {

	// Only need to install one package and this will load them all for you. Run:
	// npm install --save-dev load-grunt-tasks
	require('load-grunt-tasks')(grunt);

	grunt.initConfig({

		pkg: grunt.file.readJSON('package.json'),

		sass: {
			options: {
				style: 'compressed',
				sourcemap: 'none'
			},
			dist: {
				files: [{
		          expand: true,
		          cwd: 'assets/css/scss',
		          src: ['*.scss','!admin-merge-tags.scss','!admin-tooltips.scss','!admin-metabox-panel.scss','!admin-metabox.scss','!admin-members-plugin.scss'],
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

		jshint: [
			"assets/js/admin-views.js",
			"assets/js/admin-edd-license.js",
			"assets/js/admin-post-edit.js",
			"assets/js/admin-widgets.js",
			"assets/js/admin-entries-list.js",
			"assets/js/fe-views.js",
			"includes/extensions/entry-notes/assets/js/entry-notes.js",
			"includes/widgets/search-widget/assets/js/source/admin-widgets.js"
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
			options: {
				mangle: false
			},
			main: {
				files: [{
		          expand: true,
		          cwd: 'assets/js',
		          src: ['**/*.js','!**/*.min.js'],
		          dest: 'assets/js',
		          ext: '.min.js'
		      }]
			},
			bower: {
				files: [{
					expand: true,
					cwd: 'assets/lib',
					extDot: 'last', // Process extension as the last dot (jquery.cookie.js)
					src: ['**/*.js', '!**/build.js', '!**/dist/*.js', '!**/*.min.js'],
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
				tasks: ['sass:extensions']
			},
			templates: {
				files: ['templates/css/**/*.scss','!templates/css/**/*.css'],
				tasks: ['sass:templates']
			},
			scss: {
				files: ['assets/css/scss/*.scss'],
				tasks: ['sass:dist']
			},
			docs: {
				files: ['docs/*.scss'],
				tasks: ['sass:docs']
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
			zip: 'git-archive-all ../gravityview.zip',

			bower: 'bower install'
		},

		// Build translations without POEdit
		makepot: {
			target: {
				options: {
					mainFile: 'gravityview.php',
					type: 'wp-plugin',
					domainPath: '/languages',
					updateTimestamp: false,
					exclude: ['node_modules/.*', 'assets/.*', 'tmp/.*', 'vendor/.*', 'includes/lib/xml-parsers/.*', 'includes/lib/jquery-cookie/.*', 'includes/lib/standalone-phpenkoder/.*' ],
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true
					},
					processPot: function( pot, options ) {
						pot.headers['language'] = 'en_US';
						pot.headers['language-team'] = 'Katz Web Services, Inc. <support@katz.co>';
						pot.headers['last-translator'] = 'Katz Web Services, Inc. <support@katz.co>';
						pot.headers['report-msgid-bugs-to'] = 'https://gravityview.co/support/';

						var translation,
							excluded_meta = [
								'GravityView',
								'The best, easiest way to display Gravity Forms entries on your website.',
								'https://gravityview.co',
								'Katz Web Services, Inc.',
								'https://www.katzwebservices.com'
							];

						for ( translation in pot.translations[''] ) {
							if ( 'undefined' !== typeof pot.translations[''][ translation ].comments.extracted ) {
								if ( excluded_meta.indexOf( pot.translations[''][ translation ].msgid ) >= 0 ) {
									console.log( 'Excluded meta: ' + pot.translations[''][ translation ].msgid );
									delete pot.translations[''][ translation ];
								}
							}
						}

						return pot;
					}
				}
			}
		},

		// Add textdomain to all strings, and modify existing textdomains in included packages.
		addtextdomain: {
			options: {
				textdomain: 'gravityview',    // Project text domain.
				updateDomains: [ 'gravityview', 'gravity-view', 'gravityforms', 'edd_sl', 'edd' ]  // List of text domains to replace.
			},
			target: {
				files: {
					src: [
						'*.php',
						'**/*.php',
						'!node_modules/**',
						'!tests/**',
						'!tmp/**',
						'!includes/lib/xml-parsers/**',
						'!includes/lib/jquery-cookie/**',
						'!includes/lib/standalone-phpenkoder/**'
					]
				}
			}
		}
	});

	// Still have to manually add this one...
	grunt.loadNpmTasks('grunt-wp-i18n');

	// Regular CSS/JS/Image Compression stuff
	grunt.registerTask( 'default', [ 'exec:bower', 'sass', 'uglify', 'imagemin', 'watch' ] );

	// Translation stuff
	grunt.registerTask( 'translate', [ 'exec:transifex', 'potomo', 'addtextdomain', 'makepot' ] );

};

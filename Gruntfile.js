module.exports = function(grunt) {

	// Only need to install one package and this will load them all for you. Run:
	// npm install --save-dev load-grunt-tasks
	require('load-grunt-tasks')(grunt);

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
		          src: ['*.scss','!search.scss','!edit.scss','!font.scss','!notice.scss','!oembed.scss','!responsive.scss'],
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
		},

		// Build translations without POEdit
		makepot: {
			target: {
				options: {
					mainFile: 'gravityview.php',
					type: 'wp-plugin',
					domainPath: '/languages',
					updateTimestamp: false,
					exclude: ['node_modules/.*', 'assets/.*', 'vendor/.*', 'includes/lib/xml-parsers/.*', 'includes/lib/jquery-cookie/.*', 'includes/lib/standalone-phpenkoder/.*' ],
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
								'Create directories based on a Gravity Forms form, insert them using a shortcode, and modify how they output.',
								'http://gravityview.co',
								'Katz Web Services, Inc.',
								'http://www.katzwebservices.com'
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
				updateDomains: [ 'gravityview', 'gravityforms', 'edd_sl', 'edd' ]  // List of text domains to replace.
			},
			target: {
				files: {
					src: [
						'*.php',
						'**/*.php',
						'!node_modules/**',
						'!tests/**',
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
	grunt.registerTask( 'default', [ 'sass', 'uglify', 'imagemin', 'watch' ] );

	// Translation stuff
	grunt.registerTask( 'translate', [ 'exec:transifex', 'potomo', 'addtextdomain', 'makepot' ] );

};

module.exports = function (grunt) {
    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        // Minify AMD modules from amd/src to amd/build with sourcemaps
        uglify: {
            options: {
                compress: true,
                mangle: {
                    // Moodle/AMD: do not mangle these identifiers
                    reserved: ['M', 'Y', 'require', 'define', 'module', 'exports']
                },
                sourceMap: true,
                sourceMapIncludeSources: true,
                output: {
                    // Keep license comments (/*! ... */)
                    comments: /^!/
                }
            },
            amd: {
                files: [{
                    expand: true,
                    cwd: 'amd/src',
                    src: ['**/*.js'],
                    dest: 'amd/build',
                    ext: '.min.js'
                }]
            }
        },

        // Compress plugin into ZIP excluding unnecessary files
        compress: {
            main: {
                options: {
                    archive: 'local_localcustomadmin.zip',
                    mode: 'zip'
                },
                files: [{
                    expand: true,
                    cwd: '.',
                    src: [
                        '**/*',
                        '!node_modules/**',
                        '!.git/**',
                        '!tests/**',
                        '!*.log',
                        '!*.tmp',
                        '!cache/**',
                        '!moodledata/**',
                        '!vendor/**',
                        '!*.DS_Store',
                        '!Thumbs.db',
                        '!*.swp',
                        '!*.swo',
                        '!build/**',
                        '!dist/**',
                        '!Gruntfile.js',
                        '!package.json',
                        '!package-lock.json'
                    ],
                    dest: 'local_localcustomadmin/'
                }]
            }
        },

        // Watch for changes and optionally trigger LiveReload
        watch: {
            options: {
                livereload: true, // Use a LiveReload browser extension or inject script manually for auto-refresh
                spawn: false
            },
            scripts: {
                files: [
                    '**/*.js',
                    '!node_modules/**',
                    '!amd/build/**'
                ],
                tasks: []
            },
            templates: {
                files: [
                    'templates/**/*.mustache'
                ],
                tasks: []
            },
            styles: {
                files: [
                    'styles/**/*.css'
                ],
                tasks: []
            },
            php: {
                files: [
                    '**/*.php',
                    '!node_modules/**'
                ],
                tasks: []
            },
            amd: {
                files: [
                    'amd/src/**/*.js'
                ],
                tasks: ['amd']
            }
        }
    });

    // Load plugins
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-compress');
    grunt.loadNpmTasks('grunt-contrib-watch');

    // Helpful log when files change
    grunt.event.on('watch', function (action, filepath, target) {
        grunt.log.writeln('[' + target + '] File ' + filepath + ' was ' + action + '.');
    });

    // Default task
    grunt.registerTask('default', ['watch']);
    // Moodle-style AMD build task
    grunt.registerTask('amd', ['uglify:amd']);
    // ZIP compression task
    grunt.registerTask('zip', ['compress:main']);
};

/**
 * Gruntfile for Local Custom Admin plugin
 * Used to compile AMD modules
 */

module.exports = function(grunt) {
    "use strict";

    // Project configuration.
    grunt.initConfig({
        exec: {
            amd: {
                cmd: 'php ../../../admin/tool/grunt/cli/amd.php',
                callback: function(error, stdout, stderr) {
                    // Print any AMD build messages
                    if (stdout) {
                        grunt.log.write(stdout);
                    }
                }
            }
        }
    });

    // Load NPM tasks
    grunt.loadNpmTasks("grunt-exec");

    // Register tasks
    grunt.registerTask("amd", ["exec:amd"]);
    grunt.registerTask("default", ["amd"]);
};
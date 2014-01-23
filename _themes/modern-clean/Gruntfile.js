module.exports = function(grunt) {

    // Config
    grunt.initConfig({
        favicons: {
            options: {},
            icons: {
                src: 'img/favicon.png',
                dest: 'img/favicons'
            }
        },
        imagemin: {
            themeDynamic: {
                files: [{
                    expand: true,
                    cwd: 'img/',
                    src: ['**/*.{png,jpg,gif}'],
                    dest: 'img/'
                }]
            },
            contentDynamic: {
                files: [{
                    expand: true,
                    cwd: '../../assets/',
                    src: ['**/*.{png,jpg,gif}'],
                    dest: '../../assets/'
                }]
            }
        }
    });

    // Load plugins
    grunt.loadNpmTasks('grunt-favicons');
    grunt.loadNpmTasks('grunt-contrib-imagemin');

    // Tasks
    grunt.registerTask('default', ['favicons', 'imagemin']);

};
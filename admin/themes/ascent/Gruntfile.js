/*global module:false*/
module.exports = function(grunt) {

  grunt.initConfig({

    pkg: grunt.file.readJSON('package.json'),
    banner: '/* =========================================================\n' +
      ' * <%= pkg.title || pkg.name %> v<%= pkg.version %>\n ' +
      '<%= pkg.homepage ? "* " + pkg.homepage + "\\n" : "" %>' +
      ' * =========================================================\n' +
      ' * Copyright (c) <%= grunt.template.today("yyyy") %> <%= pkg.author %>\n' +
      ' *\n' +
      ' * This file built on <%= grunt.template.today("yyyy-mm-dd") %>\n' +
      ' * ========================================================= */\n\n\n',
    concat: {
      options: {
        separator: ';',
        banner: '<%= banner %>',
        stripBanners: true
      },
      dist: {
        src: [
          // Manual dependency ordering
          'js/vendor/jquery.min.js',
          'js/vendor/bootstrap.min.js',
          'js/vendor/jquery-ui-custom.min.js',
          'js/vendor/jquery.easing.min.js',
          'js/vendor/*.js',
          'js/ascent.js'
        ],
        dest: 'js/ascent.min.js'
      }
    },
    uglify: {
      options: {
        banner: '<%= banner %>'
      },
      dist: {
        src: '<%= concat.dist.dest %>',
        dest: '<%= concat.dist.dest %>' // Stomp over the file
      }
    },
    jshint: {
      options: {
        curly: true,
        eqeqeq: true,
        immed: true,
        latedef: true,
        newcap: true,
        noarg: true,
        sub: true,
        undef: true,
        unused: true,
        boss: true,
        eqnull: true,
        browser: true,
        globals: {}
      },
      gruntfile: {
        src: 'Gruntfile.js'
      }
    },
    sass: {
      dist: {
        options: {
          style: 'compressed',
        },
        files: {
           'css/ascent.min.css': 'scss/ascent.scss'
        }
      }
    },
    watch: {
      options: { livereload: true },
      css: {
        files: ['scss/**'],
        tasks: ['sass']
      },
      scripts: {
        files: ['!js/ascent.min.js', 'js/**'],
        tasks: ['jshint', 'concat', 'uglify']
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-concat');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-jshint');
  grunt.loadNpmTasks('grunt-contrib-sass');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-livereload');

  // Default task.
  grunt.registerTask('default', ['jshint', 'concat', 'sass', 'uglify']);

};

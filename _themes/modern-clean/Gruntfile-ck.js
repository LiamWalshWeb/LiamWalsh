module.exports=function(e){e.initConfig({favicons:{options:{},icons:{src:"img/favicon.png",dest:"img/favicons"}},imagemin:{themeDynamic:{files:[{expand:!0,cwd:"img/",src:["**/*.{png,jpg,gif}"],dest:"img/"}]},contentDynamic:{files:[{expand:!0,cwd:"../../assets/",src:["**/*.{png,jpg,gif}"],dest:"../../assets/"}]}}});e.loadNpmTasks("grunt-favicons");e.loadNpmTasks("grunt-contrib-imagemin");e.registerTask("default",["favicons","imagemin"])};
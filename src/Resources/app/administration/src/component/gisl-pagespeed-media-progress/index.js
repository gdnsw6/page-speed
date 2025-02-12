const { Component, Mixin } = Shopware;
import template from './gisl-pagespeed-media-progress.html.twig';

Component.register('gisl-pagespeed-media-progress', {
    template,

    props: ['label'],
    inject: ['GISLPageSpeedMediaProgressApiService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            webp: 0,
            images: -2,
            webpString: 0,
            imagesString: 0,
            init: false,
            interval: false,
            finish: 0
        };
    },

    created() {
        this.createComponent();
    },

    methods: {
        createComponent() {

            var me = this;

            var update = document.getElementById("GISLPageSpeedMediaProgressUpdate");
            if(!update) {

                var GISLPageSpeedMediaProgressUpdateInput = document.createElement("input");
                GISLPageSpeedMediaProgressUpdateInput.type = "hidden";
                GISLPageSpeedMediaProgressUpdateInput.id = "GISLPageSpeedMediaProgressUpdate";
                GISLPageSpeedMediaProgressUpdateInput.value = 0;
                document.body.appendChild(GISLPageSpeedMediaProgressUpdateInput);

                var GISLPageSpeedMediaProgressFinishInput = document.createElement("input");
                GISLPageSpeedMediaProgressFinishInput.type = "hidden";
                GISLPageSpeedMediaProgressFinishInput.id = "GISLPageSpeedMediaProgressFinish";
                GISLPageSpeedMediaProgressFinishInput.value = 0;
                document.body.appendChild(GISLPageSpeedMediaProgressFinishInput);
                
                var GISLPageSpeedMediaProgressImagesInput = document.createElement("input");
                GISLPageSpeedMediaProgressImagesInput.type = "hidden";
                GISLPageSpeedMediaProgressImagesInput.id = "GISLPageSpeedMediaProgressImages";
                GISLPageSpeedMediaProgressImagesInput.value = 0;
                document.body.appendChild(GISLPageSpeedMediaProgressImagesInput);

            } else {
                var GISLPageSpeedMediaProgressUpdateInput = document.getElementById("GISLPageSpeedMediaProgressUpdate");
                var GISLPageSpeedMediaProgressImagesInput = document.getElementById("GISLPageSpeedMediaProgressImages");
            }

            this.GISLPageSpeedMediaProgressApiService.check().then(response => {
                if(response.success==true) {
                    GISLPageSpeedMediaProgressUpdateInput.value = response.webp;
                    GISLPageSpeedMediaProgressUpdateInput.name = "GISLPageSpeedMediaProgressUpdate";
                    this.webp = response.webp;
                    this.images = response.images;
                    GISLPageSpeedMediaProgressImagesInput.value = this.images;
                    this.webpString = response.webp.toLocaleString();
                    this.imagesString = response.images.toLocaleString();
                    this.init = false;
                } else {
                    this.images = -1;
                    this.init = true;
                }
                if(this.interval===false) {
                    this.interval = true;
                    window.setInterval(function() {
                        me.checkForUpdate();
                    },500);
                }
            });
        },
        checkForUpdate() {
            var update = document.getElementById("GISLPageSpeedMediaProgressUpdate");
            var update_value = update.value*1;
            if(this.webp!=update_value) {
                this.webp = update_value;
                this.webpString = update_value.toLocaleString();
            }
            var finish = document.getElementById("GISLPageSpeedMediaProgressFinish");
            var finish_value = finish.value*1;
            if(this.finish!=finish_value) {
                this.finish = finish_value;
            }
            var images = document.getElementById("GISLPageSpeedMediaProgressImages");
            var images_value = images.value*1;
            if(this.images!=images_value) {
                this.images = images_value;
                this.imagesString = images_value.toLocaleString();
            }
        }
    }
});
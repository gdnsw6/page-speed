const { Component, Mixin } = Shopware;
import template from './gisl-pagespeed-media-reload.html.twig';

Component.override('sw-media-quickinfo', {

    template,

    inject: ['GISLPageSpeedMediaGenerateApiService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    props: {
        item: {
            required: true,
            type: Object,
            validator(value) {
                return value.getEntityName() === 'media';
            },
        }
    },

    watch: {
        'item.id': {
            handler() {
                this.fileNameError = null;
            },
        },
    },

    created() {
        this.createComponent();
    },

    methods: {

        reload() {
            if (this.item) {
                var urlArr = this.item.url.split("/media");
                if(urlArr.length>1) {
                    this.GISLPageSpeedMediaGenerateApiService.reload(urlArr[1]).then((res) => {
                        if(typeof(res.status)!=typeof(this_is_not_defined)) {
                            this.createNotificationSuccess({
                                title: this.$tc('gisl-pagespeed.media.reload.title'),
                                message: this.$tc('gisl-pagespeed.media.reload.success')
                            });
                        } else {
                            this.createNotificationSuccess({
                                title: this.$tc('gisl-pagespeed.media.reload.title'),
                                message: this.$tc('gisl-pagespeed.media.reload.error')
                            });
                        }
                    });
                }
            }
        },

        button() {
            if(document.getElementsByClassName("quickaction--webp").length>0) {
                var element = document.getElementsByClassName("quickaction--webp")[0];
                element.getElementsByClassName("sw-icon")[0].style.padding = 0;
                element.setAttribute("title",element.getElementsByClassName("quickaction--webp-title")[0].innerHTML);
            } else {
                var me = this;
                window.setTimeout(function() {
                    me.button();
                },1000);
            }
        },

        createComponent() {
            this.button();
        }

    }
    
});

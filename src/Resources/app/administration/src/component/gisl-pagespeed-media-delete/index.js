const { Component, Mixin } = Shopware;
import template from './gisl-pagespeed-media-delete.html.twig';

Component.register('gisl-pagespeed-media-delete', {
    template,

    props: ['label'],
    inject: ['GISLPageSpeedMediaDeleteApiService','GISLPageSpeedMediaUpgrade'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
        };
    },

    methods: {
        saveFinish() {
            this.isSaveSuccessful = false;
        },

        check() {
            this.isLoading = true;
            var me = this;
            this.GISLPageSpeedMediaDeleteApiService.check().then((res) => {
                if (typeof(res.success)!=typeof(this_is_not_defined)) {
                    this.isSaveSuccessful = true;
                    if(res.success!="empty"&&res.success!="0") {
                        this.createNotificationSuccess({
                            title: this.$tc('gisl-pagespeed.media.delete.title'),
                            message: this.$tc('gisl-pagespeed.media.delete.success').replace("[images]",res.success.toLocaleString())
                        });
                        document.getElementById("GISLPageSpeedMediaProgressUpdate").value = 0;
                        document.getElementById("GISLPageSpeedMediaProgressFinish").value = 0;
                        window.setTimeout(function() {
                            me.createNotificationSuccess({
                                title: me.$tc('gisl-pagespeed.media.delete.title'),
                                message: me.$tc('gisl-pagespeed.media.delete.cache')
                            });
                        },1500);
                    } else {
                        this.createNotificationSuccess({
                            title: this.$tc('gisl-pagespeed.media.delete.title'),
                            message: this.$tc('gisl-pagespeed.media.delete.empty')
                        });
                    }
                    window.setTimeout(function() {
                        me.upgrade();
                    },5000);
                } else {
                    this.createNotificationError({
                        title: this.$tc('gisl-pagespeed.media.delete.title'),
                        message: this.$tc('gisl-pagespeed.media.delete.error')
                    });
                }
                this.isLoading = false;
                this.isSaveSuccessful = false;
            });
        },

        upgrade() {
            var me = this;
            if(document.getElementsByClassName("sw-system-config__card--0")[0].classList.contains("sw-card--visible")) {
                this.GISLPageSpeedMediaUpgrade.init().then((res) => {
                    me.images = res.images;
                    if(res.images==0) {
                        me.createNotificationSuccess({
                            title: me.$tc('gisl-pagespeed.media.upgrade.success.title'),
                            message: me.$tc('gisl-pagespeed.media.upgrade.success.info')
                        });
                        window.setTimeout(function() {
                            me.createNotificationSuccess({
                                title: me.$tc('gisl-pagespeed.media.upgrade.success.title'),
                                message: me.$tc('gisl-pagespeed.media.upgrade.success.reload')
                            });
                            window.setTimeout(function() {
                                location.reload(true);
                            },2000);
                        },3000);
                    }
                });
            }
        }
    }
});
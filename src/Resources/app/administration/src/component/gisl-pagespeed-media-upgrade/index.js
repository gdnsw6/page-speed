const { Component, Mixin } = Shopware;
import template from './gisl-pagespeed-media-upgrade.html.twig';

Component.register('gisl-pagespeed-media-upgrade', {
    template,

    props: ['label'],
    inject: ['GISLPageSpeedMediaUpgradeApiService','systemConfigApiService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            webp: 0,
            images: 0,
            webpString: 0,
            imagesString: 0,
            start: false,
            isLoading: false,
            isUpgradeSuccessful: false,
            domain: 'GISLPageSpeed',
            salesChannelId: undefined
        };
    },

    created() {
        
        document.getElementsByClassName("sw-system-config")[0].classList.add("gisl-pagespeed-config");
        this.readAll().then((values) => {
            window.GISLPageSpeedConfig = this.mapValues(values);
            if(GISLPageSpeedConfig.upgrade==0) {
                this.init();
            }
        });

    },

    methods: {
        
        upgradeFinish() {
            this.isUpgradeSuccessful = false;
        },

        readAll() {
            return this.systemConfigApiService.getValues(
                this.domain,
                this.salesChannelId,
            );
        },

        init() {
            var me = this;
            this.GISLPageSpeedMediaUpgradeApiService.init().then((res) => {
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
                } else {
                    document.getElementsByClassName("sw-system-config__card--0")[0].classList.add("sw-card--visible");
                }
            });
        },

        reload() {
            var me = this;
            me.isUpgradeSuccessful = true;
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
        },

        upgrade() {
            this.isLoading = true;
            this.start = true;
            var config = document.getElementsByClassName("sw-system-config");
            config[0].classList.add("gisl-pagespeed-media-upgrade-started");
            this.GISLPageSpeedMediaUpgradeApiService.upgrade().then((res) => {
                this.images = res.images;
                this.imagesString = res.images.toLocaleString();
                this.repeat();
            });
        },

        upgradeDelete() {
            this.isLoading = true;
            this.start = true;
            var config = document.getElementsByClassName("sw-system-config");
            config[0].classList.add("gisl-pagespeed-media-upgrade-started");
            this.GISLPageSpeedMediaUpgradeApiService.delete().then((res) => {
                config[0].classList.add("gisl-pagespeed-media-upgrade-stopped");
                config[0].classList.remove("gisl-pagespeed-media-upgrade-started");
                this.reload();
            });
        },

        repeat() {
            var me = this;
            window.setTimeout(function() {
                me.check();
            },1000);
        },

        check() {
            var me = this;
            me.GISLPageSpeedMediaUpgradeApiService.check().then((res) => {
                if(res.images==0) {
                    me.reload();
                } else {
                    me.webp = me.webp+res.images;
                    if(me.webp<me.images) {
                        me.webpString = me.webp.toLocaleString();
                        me.repeat();
                    } else {
                        me.webpString = me.images.toLocaleString();
                        me.upgradeDelete();
                    }
                }
            });
        },

        mapValues: function(values) {
            const config = {};
            Object.keys(values).forEach((key) => {
                const newKey = key.replace('GISLPageSpeed.config.', '');
                config[newKey] = values[key];
            });
            return config;
        }

    }
});
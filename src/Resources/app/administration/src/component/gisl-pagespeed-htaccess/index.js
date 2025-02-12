const { Component, Mixin } = Shopware;
import template from './gisl-pagespeed-htaccess.html.twig';

Component.register('gisl-pagespeed-htaccess', {
    template,

    props: ['label'],
    inject: ['GISLPageSpeedHtaccessApiService', 'systemConfigApiService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            config: {},
            storeKeyElem: {},
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
            this.GISLPageSpeedHtaccessApiService.check().then((res) => {
                if (typeof(res.success)!=typeof(this_is_not_defined)) {
                    this.isSaveSuccessful = true;
                    this.createNotificationSuccess({
                        title: this.$tc('gisl-pagespeed.htaccess.button.title'),
                        message: this.$tc('gisl-pagespeed.htaccess.button.success')
                    });
                } else {
                    this.createNotificationError({
                        title: this.$tc('gisl-pagespeed.htaccess.button.title'),
                        message: this.$tc('gisl-pagespeed.htaccess.button.error')
                    });
                }

                this.isLoading = false;
                this.isSaveSuccessful = false;
            });
        }
    }
});
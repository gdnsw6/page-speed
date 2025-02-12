const { Component, Mixin } = Shopware;
import template from './gisl-pagespeed-media-skip.html.twig';

Component.register('gisl-pagespeed-media-skip', {
    template,

    props: ['label'],
    inject: ['GISLPageSpeedMediaSkipApiService'],

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
            this.GISLPageSpeedMediaSkipApiService.check().then((res) => {
                this.createNotificationSuccess({
                    title: this.$tc('gisl-pagespeed.media.skip.title'),
                    message: this.$tc('gisl-pagespeed.media.skip.success')
                });
                this.isLoading = false;
                this.isSaveSuccessful = false;
            });
        }
    }
});
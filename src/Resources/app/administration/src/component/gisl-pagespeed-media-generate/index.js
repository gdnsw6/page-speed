const { Component, Mixin } = Shopware;
import template from './gisl-pagespeed-media-generate.html.twig';

Component.register('gisl-pagespeed-media-generate', {
    template,

    props: ['label'],
    inject: ['GISLPageSpeedMediaGenerateApiService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
            scan: null,
            images: 0,
            webp: 0
        };
    },

    created() {
        this.createComponent();
    },

    methods: {
        saveFinish() {
            this.isSaveSuccessful = false;
        },

        check() {
            this.isLoading = true;
            this.GISLPageSpeedMediaGenerateApiService.check().then((res) => {
                this.info(res,false);
            });
        },

        all() {
            this.isLoading = true;
            this.GISLPageSpeedMediaGenerateApiService.all().then((res) => {
                this.info(res,true);
            });
        },

        scanImages() {
            this.isLoading = true;
            this.GISLPageSpeedMediaGenerateApiService.scan().then((res) => {
                if (typeof(res.images)!=typeof(this_is_not_defined)) {
                    if(res.images*1>this.images*1) {
                        this.images = res.images;
                        document.getElementById("GISLPageSpeedMediaProgressImages").value = this.images;
                        this.scan = false;
                        this.createNotificationSuccess({
                            title: this.$tc('gisl-pagespeed.media.generate.scan.button'),
                            message: this.$tc('gisl-pagespeed.media.generate.scan.new')
                        });
                        document.getElementById("GISLPageSpeedMediaProgressFinish").value = 0;
                    } else {
                        this.createNotificationSuccess({
                            title: this.$tc('gisl-pagespeed.media.generate.scan.button'),
                            message: this.$tc('gisl-pagespeed.media.generate.scan.nope')
                        });
                    }
                }
                this.isLoading = false;
                this.isSaveSuccessful = true;
            });
        },

        progress() {
            var me = this;
            if(typeof(document.getElementById("GISLPageSpeedMediaProgressUpdate"))!==typeof(this_is_not_defined)) {
                var update = document.getElementById("GISLPageSpeedMediaProgressUpdate");
                if(update.name=="GISLPageSpeedMediaProgressUpdate") {
                    this.images = document.getElementById("GISLPageSpeedMediaProgressImages").value;
                    this.webp = update.value;
                    this.scan = false;
                    if(document.getElementById("GISLPageSpeedMediaProgressImages").value==update.value) {
                        if(update.value==0) {
                            this.scan = 2;
                        } else {
                            this.scan = 3;
                            document.getElementById("GISLPageSpeedMediaProgressFinish").value = 1;
                        }
                    }
                    window.setTimeout(function() {
                        me.progress();
                    },1500);
                } else {
                    window.setTimeout(function() {
                        me.progress();
                    },250);
                }
            } else {
                window.setTimeout(function() {
                    me.progress();
                },250);
            }
        },

        repeat() {
            var me = this;
            window.setTimeout(function() {
                me.all();
            },1000);
        },

        info(res,all) {
            if(typeof(res)=="string") {
                if(res.indexOf('}{"errors"')>-1) {
                    var resBug = res.split('{"errors');
                    res = JSON.parse(resBug[0]);
                }
            }
            if (typeof(res.success)!=typeof(this_is_not_defined)) {
                this.isSaveSuccessful = true;
                if(res.success!="empty") {
                    if(res.success!==true) {
                        if(res.images==0) {
                            document.getElementById("GISLPageSpeedMediaProgressUpdate").value = document.getElementById("GISLPageSpeedMediaProgressImages").value;
                            this.scan = true;
                        } else {
                            document.getElementById("GISLPageSpeedMediaProgressUpdate").value = (document.getElementById("GISLPageSpeedMediaProgressUpdate").value*1)+(res.images*1);
                            if(res.error!=0) {
                                document.getElementById("GISLPageSpeedMediaProgressImages").value = (document.getElementById("GISLPageSpeedMediaProgressImages").value*1)-(res.error*1);
                                document.getElementById("GISLPageSpeedMediaProgressUpdate").value = (document.getElementById("GISLPageSpeedMediaProgressUpdate").value*1)-(res.error*1);
                            }
                        }
                        if(res.success=="time") {
                            this.createNotificationSuccess({
                                title: this.$tc('gisl-pagespeed.media.generate.title'),
                                message: this.$tc('gisl-pagespeed.media.generate.time').replace("[images]",res.images.toLocaleString()).replace("[time]",res.typeValue)
                            });
                        } else if(res.success=="memory") {
                            this.createNotificationSuccess({
                                title: this.$tc('gisl-pagespeed.media.generate.title'),
                                message: this.$tc('gisl-pagespeed.media.generate.memory').replace("[images]",res.images.toLocaleString()).replace("[memory]",res.typeValue)
                            });
                        } else {
                            if(res.images==0) {
                                this.createNotificationSuccess({
                                    title: this.$tc('gisl-pagespeed.media.generate.title'),
                                    message: this.$tc('gisl-pagespeed.media.generate.all')
                                });
                                this.scan = true;
                                document.getElementById("GISLPageSpeedMediaProgressFinish").value = 1;
                            } else {
                                this.createNotificationSuccess({
                                    title: this.$tc('gisl-pagespeed.media.generate.title'),
                                    message: this.$tc('gisl-pagespeed.media.generate.success').replace("[images]",res.images.toLocaleString())
                                });
                                if(document.getElementById("GISLPageSpeedMediaProgressUpdate").value==document.getElementById("GISLPageSpeedMediaProgressImages").value) {
                                    this.scan = true;
                                    if(document.getElementById("GISLPageSpeedMediaProgressUpdate").value!=0) {
                                        document.getElementById("GISLPageSpeedMediaProgressFinish").value = 1;
                                    }
                                }
                            }
                        }
                        if(document.getElementById("GISLPageSpeedMediaProgressUpdate").value==document.getElementById("GISLPageSpeedMediaProgressImages").value) {
                            this.scan = true;
                            if(document.getElementById("GISLPageSpeedMediaProgressUpdate").value!=0) {
                                document.getElementById("GISLPageSpeedMediaProgressFinish").value = 1;
                            }
                        }
                        if(all===true) {
                            if(res.images>0) {
                                if(document.getElementById("GISLPageSpeedMediaProgressUpdate").value!=document.getElementById("GISLPageSpeedMediaProgressImages").value) {
                                    this.repeat();
                                }
                            }
                        }
                    } else {
                        this.createNotificationSuccess({
                            title: this.$tc('gisl-pagespeed.media.generate.title'),
                            message: this.$tc('gisl-pagespeed.media.generate.all')
                        });
                        this.scan = true;
                    }
                } else {
                    this.createNotificationSuccess({
                        title: this.$tc('gisl-pagespeed.media.generate.title'),
                        message: this.$tc('gisl-pagespeed.media.generate.empty')
                    });
                }
            } else {
                this.createNotificationError({
                    title: this.$tc('gisl-pagespeed.media.generate.title'),
                    message: this.$tc('gisl-pagespeed.media.generate.error')
                });
            }
            this.isLoading = false;
            this.isSaveSuccessful = false;
        },

        createComponent() {
            var me = this;
            window.setTimeout(function() {
                me.progress();
            },250);
        },

    }
});
const { Component, Mixin } = Shopware;
import GISLPageSpeedPreloadFontsApiService from '../../service/gisl-pagespeed-preload-fonts';
import template from './gisl-pagespeed-preload-fonts.html.twig';

Component.register('gisl-pagespeed-preload-fonts', {
    template,

    props: ['label'],
    inject: ['GISLPageSpeedPreloadFontsApiService','systemConfigApiService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            domain: 'GISLPageSpeed',
            value: false,
            input: false,
            isLoading: false,
            isSaveSuccessful: false,
            channelId: false,
            config: false
        };
    },

    created() {
        
        this.channelId = this.getChannelId();
        this.readAll().then((values) => {
            this.config = this.mapValues(values);
            this.input = document.getElementsByName("GISLPageSpeed.config.preload")[0].getElementsByTagName("textarea")[0];
            if(typeof(GISLPageSpeedPreloadFontsValue)!==typeof(this_is_not_defined)) {
                this.value = GISLPageSpeedPreloadFontsValue;
            } else {
                this.value = this.config.preload;
            }
            this.input.value = this.value;
        });
        var saveButton = document.getElementsByClassName("sw-extension-config__save-action");
        if(saveButton.length>0) {
            if(!saveButton[0].classList.contains("gisl-pagespeed-preload-fonts-save")) {
                saveButton[0].classList.add("gisl-pagespeed-preload-fonts-save");
                saveButton[0].addEventListener('click', this.setFonts);
            }
        }
    },

    computed: {
        innerModel: {
            get() { 
                return this.value;
            },
            set(v) {
                this.value = v;
                this.input.value = v;
                this.$emit('change', v);
            }
        }
    },

    methods: {

        setFonts() {
            var value = this.value.replace(/\n/gi,"gislLineBreak");
            window.GISLPageSpeedPreloadFontsValue = this.value;
            var me = this;
            if(location.hash=="#/sw/extension/config/GISLPageSpeed") {
                var saveButton = document.getElementsByClassName("sw-extension-config__save-action");
                if(saveButton.length>0) {
                    if(saveButton[0].classList.contains("gisl-pagespeed-preload-fonts-save")) {
                        saveButton[0].classList.remove("gisl-pagespeed-preload-fonts-save");
                        saveButton[0].removeEventListener('click', this.setFonts);
                    }
                }
                window.setTimeout(function() {
                    me.GISLPageSpeedPreloadFontsApiService.save(value).then((res) => {
                        console.log(res.value);
                        me.value = res.value;
                        me.input.value = res.value;
                        me.$emit('change', res.value);
                    });
                },200);
            }
        },

        saveFinish() {
            this.isLoading = false;
            this.isSaveSuccessful = false;
        },

        getChannelId() {
            if(typeof(document.getElementById("salesChannelSelect"))!==typeof(this_is_not_defined)) {
                var GISLPageSpeedConfigElement = document.getElementsByClassName("gisl-pagespeed-config");
                var selectedChannel = document.getElementById("salesChannelSelect").getElementsByClassName("sw-entity-single-select__selection-text");
                var selectedChannelText = selectedChannel[0].textContent.replace(/[\n\r]+|[\s]{2,}/g, ' ').trim();
                if(selectedChannel.length>0) {
                    var salesChannelMenu = document.getElementsByClassName("sw-sales-channel-menu");
                    if(salesChannelMenu.length>0) {
                        var salesChannelMenuLinks = salesChannelMenu[0].getElementsByClassName("sw-admin-menu__navigation-link");
                        if(salesChannelMenuLinks.length>0) {
                            for(var i=0;i<salesChannelMenuLinks.length;i++) {
                                var salesChannelMenuLinkText = salesChannelMenuLinks[i].getElementsByClassName("sw-admin-menu__navigation-link-label");
                                if(salesChannelMenuLinkText.length>0) {
                                    salesChannelMenuLinkText = salesChannelMenuLinkText[0].textContent.replace(/[\n\r]+|[\s]{2,}/g, ' ').trim();
                                    if(selectedChannelText==salesChannelMenuLinkText) {
                                        var salesChannelMenuLinkHref = salesChannelMenuLinks[i].href.split("/");
                                        if(GISLPageSpeedConfigElement.length>0) {
                                            GISLPageSpeedConfigElement[0].classList.add("sales-channel-is-selected");
                                        }
                                        return salesChannelMenuLinkHref[salesChannelMenuLinkHref.length-1];
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if(GISLPageSpeedConfigElement.length>0) {
                GISLPageSpeedConfigElement[0].classList.remove("sales-channel-is-selected");
            }
            return undefined;
        },

        check() {
            /*
            this.isLoading = true;
            this.GISLPageSpeedPreloadFontsApiService.check(this.channelId).then((res) => {
                this.isLoading = false;
                var fonts = [];
                if(res.files!="") {
                    var files = JSON.parse(res.files);
                    for (const key in files){
                        if(files.hasOwnProperty(key)){
                            for(var i=0;i<files[key].length;i++) {
                                var url = new URL(files[key][i], key).href;
                                var urlArr = url.split("theme/");
                                if(urlArr.length>1) {
                                    var urlArr2 = urlArr[1].split("assets/");
                                    if(urlArr2.length>1) {
                                        url = "assets/"+urlArr2[1];
                                    }
                                }
                                fonts.push(url);
                            };
                        }
                    }
                }
                var preload = fonts.filter(this.onlyUnique);
                var input = "";
                for(var i=0;i<preload.length;i++) {
                    if(i>0) {
                        input+= "\n";
                    }
                    input+= preload[i];
                }
                this.value = input;
                this.input.value = this.value;
                this.$emit('change', this.value);
                document.getElementById("GISLPageSpeed.config.preload").focus();
                if(preload.length>0) {
                    this.createNotificationSuccess({
                        title: this.$tc('gisl-pagespeed.preload.scan.title'),
                        message: this.$tc('gisl-pagespeed.preload.scan.found').replace("[files]",preload.length)
                    });
                } else {
                    this.createNotificationSuccess({
                        title: this.$tc('gisl-pagespeed.preload.scan.title'),
                        message: this.$tc('gisl-pagespeed.preload.scan.empty').replace("[files]",preload.length)
                    });
                }
            });
            */
        },

        mergeURL(url, concat) {
            var url1 = url.split('/');
            var url2 = concat.split('/');
            var url3 = [ ];
            for (var i = 0, l = url1.length; i < l; i ++) {
                if (url1[i] == '..') {
                    url3.pop();
                } else if (url1[i] == '.') {
                    continue;
                } else {
                    url3.push(url1[i]);
                }
            }
            for (var i = 0, l = url2.length; i < l; i ++) {
                if (url2[i] == '..') {
                    url3.pop();
                } else if (url2[i] == '.') {
                    continue;
                } else {
                    url3.push(url2[i]);
                }
            }
            return url3.join('/');
        },

        onlyUnique(value, index, array) {
            return array.indexOf(value) === index;
        },

        readAll() {
            return this.systemConfigApiService.getValues(
                this.domain,
                this.channelId,
            );
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
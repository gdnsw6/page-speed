const { Component, Mixin } = Shopware;
import template from './gisl-pagespeed-media-options.html.twig';

Component.register('gisl-pagespeed-media-options', {
    template,
    props: ['label']
});
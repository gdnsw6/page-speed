const ApiService = Shopware.Classes.ApiService;

class GISLPageSpeedPreloadFontsApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'gisl/pagespeed') {
        super(httpClient, loginService, apiEndpoint);
    }

    /**
     * Check for fonts to preload
     *
     * @param {String|null} salesChannelId
     *
     * @returns {Promise}
     */
    check(salesChannelId = null) {
        const apiRoute = `_action/${this.getApiBasePath()}/preload/fonts/check`;

        return this.httpClient.post(
            apiRoute,
            {
                salesChannelId,
            },
            {
                headers: this.getBasicHeaders({
                    Saleschannelid: salesChannelId
                }),
            },
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    /**
     * Save preload field
     *
     * @param {String|null} value
     *
     * @returns {Promise}
     */
    save(value) {
        const apiRoute = `_action/${this.getApiBasePath()}/preload/fonts/save`;

        return this.httpClient.post(
            apiRoute,
            {
                value,
            },
            {
                headers: this.getBasicHeaders({
                    GISLpreloadvalue: value
                }),
            },
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

export default GISLPageSpeedPreloadFontsApiService;

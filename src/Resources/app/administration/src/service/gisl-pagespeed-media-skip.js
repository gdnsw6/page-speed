const ApiService = Shopware.Classes.ApiService;

class GISLPageSpeedMediaSkipApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'gisl/pagespeed') {
        super(httpClient, loginService, apiEndpoint);
    }

    /**
     * Skip next file
     *
     * @param {String|null} salesChannelId
     *
     * @returns {Promise}
     */
    check(salesChannelId = null) {
        const apiRoute = `_action/${this.getApiBasePath()}/media/skip/run`;

        return this.httpClient.post(
            apiRoute,
            {
                salesChannelId,
            },
            {
                headers: this.getBasicHeaders(),
            },
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

export default GISLPageSpeedMediaSkipApiService;

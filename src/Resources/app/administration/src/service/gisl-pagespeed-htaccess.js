const ApiService = Shopware.Classes.ApiService;

class GISLPageSpeedHtaccessApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'gisl/pagespeed') {
        super(httpClient, loginService, apiEndpoint);
    }

    /**
     * Saves the htaccess file
     *
     * @param {String|null} salesChannelId
     *
     * @returns {Promise}
     */
    check(salesChannelId = null) {
        const apiRoute = `_action/${this.getApiBasePath()}/htaccess/run`;

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

export default GISLPageSpeedHtaccessApiService;

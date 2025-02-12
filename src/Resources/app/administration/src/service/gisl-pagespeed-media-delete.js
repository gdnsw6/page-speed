const ApiService = Shopware.Classes.ApiService;

class GISLPageSpeedMediaDeleteApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'gisl/pagespeed') {
        super(httpClient, loginService, apiEndpoint);
    }

    /**
     * Removes all media files
     *
     * @param {String|null} salesChannelId
     *
     * @returns {Promise}
     */
    check(salesChannelId = null) {
        const apiRoute = `_action/${this.getApiBasePath()}/media/delete/run`;

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

export default GISLPageSpeedMediaDeleteApiService;

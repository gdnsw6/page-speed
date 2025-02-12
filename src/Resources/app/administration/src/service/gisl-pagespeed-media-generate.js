const ApiService = Shopware.Classes.ApiService;

class GISLPageSpeedMediaGenerateApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'gisl/pagespeed') {
        super(httpClient, loginService, apiEndpoint);
    }

    /**
     * Generates media files
     *
     * @param {String|null} salesChannelId
     *
     * @returns {Promise}
     */
    check(salesChannelId = null) {
        const apiRoute = `_action/${this.getApiBasePath()}/media/generate/run`;

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

    /**
     * Generates all media files
     *
     * @param {String|null} salesChannelId
     *
     * @returns {Promise}
     */
    all(salesChannelId = null) {
        const apiRoute = `_action/${this.getApiBasePath()}/media/generate/all`;

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
    
    /**
     * Scans media files
     *
     * @param {String|null} salesChannelId
     *
     * @returns {Promise}
     */
    scan(salesChannelId = null) {
        const apiRoute = `_action/${this.getApiBasePath()}/media/generate/scan`;

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
    
    /**
     * Reload media files
     *
     * @param {String|null} salesChannelId
     *
     * @returns {Promise}
     */
    reload(imageURL,salesChannelId = null) {
        const apiRoute = `_action/${this.getApiBasePath()}/media/generate/reload`;
        return this.httpClient.post(
            apiRoute,
            {
                salesChannelId,
            },
            {
                headers: this.getBasicHeaders({
                    Imageurl: imageURL
                }),
            },
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

export default GISLPageSpeedMediaGenerateApiService;

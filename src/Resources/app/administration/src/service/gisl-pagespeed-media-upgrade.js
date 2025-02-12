const ApiService = Shopware.Classes.ApiService;

class GISLPageSpeedMediaUpgradeApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'gisl/pagespeed') {
        super(httpClient, loginService, apiEndpoint);
    }

    /**
     * Checks for upgrade
     *
     * @param {String|null} salesChannelId
     *
     * @returns {Promise}
     */
    check(salesChannelId = null) {
        const apiRoute = `_action/${this.getApiBasePath()}/media/upgrade/check`;

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
     * Delete media files during upgrade
     *
     * @param {String|null} salesChannelId
     *
     * @returns {Promise}
     */
    delete(salesChannelId = null) {
        const apiRoute = `_action/${this.getApiBasePath()}/media/upgrade/delete`;

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
     * Init upgrade
     *
     * @param {String|null} salesChannelId
     *
     * @returns {Promise}
     */
    init(salesChannelId = null) {
        const apiRoute = `_action/${this.getApiBasePath()}/media/upgrade/init`;

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
     * Start upgrade
     *
     * @param {String|null} salesChannelId
     *
     * @returns {Promise}
     */
    upgrade(salesChannelId = null) {
        const apiRoute = `_action/${this.getApiBasePath()}/media/upgrade/upgrade`;

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

export default GISLPageSpeedMediaUpgradeApiService;

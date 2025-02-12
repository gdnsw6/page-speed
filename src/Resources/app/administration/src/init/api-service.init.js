import GISLPageSpeedMediaDeleteApiService from '../service/gisl-pagespeed-media-delete';
import GISLPageSpeedMediaGenerateApiService from '../service/gisl-pagespeed-media-generate';
import GISLPageSpeedMediaProgressApiService from '../service/gisl-pagespeed-media-progress';
import GISLPageSpeedHtaccessApiService from '../service/gisl-pagespeed-htaccess';
import GISLPageSpeedMediaUpgradeApiService from '../service/gisl-pagespeed-media-upgrade';
import GISLPageSpeedMediaSkipApiService from '../service/gisl-pagespeed-media-skip';
import GISLPageSpeedPreloadFontsApiService from '../service/gisl-pagespeed-preload-fonts';

const { Application } = Shopware;

const initContainer = Application.getContainer('init');

Application.addServiceProvider(
    'GISLPageSpeedMediaDeleteApiService',
    (container) => new GISLPageSpeedMediaDeleteApiService(initContainer.httpClient, container.loginService),
);

Application.addServiceProvider(
    'GISLPageSpeedMediaGenerateApiService',
    (container) => new GISLPageSpeedMediaGenerateApiService(initContainer.httpClient, container.loginService),
);

Application.addServiceProvider(
    'GISLPageSpeedMediaProgressApiService',
    (container) => new GISLPageSpeedMediaProgressApiService(initContainer.httpClient, container.loginService),
);

Application.addServiceProvider(
    'GISLPageSpeedHtaccessApiService',
    (container) => new GISLPageSpeedHtaccessApiService(initContainer.httpClient, container.loginService),
);

Application.addServiceProvider(
    'GISLPageSpeedMediaUpgradeApiService',
    (container) => new GISLPageSpeedMediaUpgradeApiService(initContainer.httpClient, container.loginService),
);

Application.addServiceProvider(
    'GISLPageSpeedMediaSkipApiService',
    (container) => new GISLPageSpeedMediaSkipApiService(initContainer.httpClient, container.loginService),
);

Application.addServiceProvider(
    'GISLPageSpeedPreloadFontsApiService',
    (container) => new GISLPageSpeedPreloadFontsApiService(initContainer.httpClient, container.loginService),
);
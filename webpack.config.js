import Encore from '@symfony/webpack-encore';

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .addEntry('app', './assets/app.js')
    .splitEntryChunks()
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableSassLoader((options) => {
        // dart-sass при не-ASCII в выводе (compressed) пишет BOM вместо @charset.
        // BOM в середине бандла делает селектор невалидным и браузер выбрасывает
        // правило (например, universal box-sizing) — запрещаем эмиссию BOM.
        options.sassOptions = { ...options.sassOptions, charset: false };
    })
    .enableVersioning(Encore.isProduction())
;

export default Encore.getWebpackConfig();
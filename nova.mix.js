const mix = require('laravel-mix')
const webpack = require('webpack')
const path = require('path')

class NovaExtension {
  name() {
    return 'nova-extension'
  }

  register(name) {
    this.name = name
  }

  webpackPlugins() {
    return new webpack.ProvidePlugin({
      _: 'lodash',
    })
  }

  webpackConfig(webpackConfig) {
    webpackConfig.externals = {
      vue: 'Vue',
    }

    // WARNING: this bakes Nova's own mixins (FormField, vuex, ...) into dist/js/field.js, so the built
    // bundle is tied to whichever Nova version is installed here. A bundle built against Nova 4 breaks
    // on Nova 5 — `field.fields` ends up holding undefined entries and the form field stops rendering.
    // Always build the shipped assets in a Nova 5 environment.
    webpackConfig.resolve.alias = {
      ...(webpackConfig.resolve.alias || {}),
      'laravel-nova': path.join(
        __dirname,
        '../../vendor/laravel/nova/resources/js/mixins/packages.js'
      ),
    }

    webpackConfig.output = {
      uniqueName: this.name,
    }
  }
}

mix.extend('nova', new NovaExtension())

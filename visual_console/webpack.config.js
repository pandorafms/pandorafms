// eslint-disable-next-line @typescript-eslint/no-var-requires
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const dev = process.env.NODE_ENV !== "production";

module.exports = {
  mode: dev ? "development" : "production",
  entry: __dirname + "/src/index.ts", // Start from this file.
  output: {
    path: __dirname + "/dist", // The files will be created here.
    filename: dev
      ? "visual-console-client.min.js"
      : "visual-console-client.[hash].min.js",
    publicPath: dev ? "" : "pandora_console/include/visual_console/"
  },
  devtool: "source-map",
  resolve: {
    extensions: [".ts", ".js", ".json"]
  },
  module: {
    rules: [
      // Loader for the Typescript compiler.
      {
        test: /\.ts$/,
        loader: "awesome-typescript-loader"
      },
      // This loader builds a main CSS file from all the CSS imports across the files.
      {
        test: /\.css$/,
        loader: [
          // https://github.com/webpack-contrib/mini-css-extract-plugin
          {
            loader: MiniCssExtractPlugin.loader,
            options: {
              hot: true, // if you want HMR - we try to automatically inject hot reloading but if it's not working, add it to the config
              reloadAll: true // when desperation kicks in - this is a brute force HMR flag
            }
          },
          // https://webpack.js.org/loaders/css-loader
          {
            loader: "css-loader",
            options: {
              sourceMap: true
            }
          },
          // To post process CSS and add some things like prefixes to the rules. e.g.: -webkit-...
          // https://github.com/postcss/postcss-loader
          {
            loader: "postcss-loader",
            options: {
              plugins: () => [
                // To improve the support for old browsers.
                require("autoprefixer")({
                  browsers: ["> 1%", "last 2 versions"]
                })
              ]
            }
          }
        ]
      },
      // To allow the use of file imports. The imported files are transformed into
      // data uris if they are small enough or it returns a path to the file.
      // https://webpack.js.org/loaders/url-loader
      {
        test: /\.(png|jpg|gif|svg|eot|ttf|woff|woff2)$/,
        loader: "url-loader",
        options: {
          limit: 10000
        }
      }
    ]
  },
  plugins: [
    // Options for the plugin which extract the CSS files to build a main file.
    new MiniCssExtractPlugin({
      // Options similar to the same options in webpackOptions.output
      // both options are optional
      filename: dev
        ? "visual-console-client.css"
        : "visual-console-client.[hash].css",
      // Disable to remove warnings about conflicting order between imports.
      orderWarning: true
    })
  ],
  // Static server which runs the playground on npm start.
  devServer: {
    open: true,
    contentBase: "playground"
  }
};

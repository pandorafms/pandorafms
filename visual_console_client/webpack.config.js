// eslint-disable-next-line @typescript-eslint/no-var-requires
const path = require("path");
// eslint-disable-next-line @typescript-eslint/no-var-requires
const CleanWebpackPlugin = require("clean-webpack-plugin");
// eslint-disable-next-line @typescript-eslint/no-var-requires
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

const dev = process.env.NODE_ENV !== "production";
const entry = path.join(__dirname, "src", "index.ts");
const buildPath = path.join(
  __dirname,
  "..",
  process.env.BUILD_PATH && process.env.BUILD_PATH.length > 0
    ? process.env.BUILD_PATH
    : "build"
);

module.exports = {
  mode: dev ? "development" : "production",
  entry, // Start from this file.
  output: {
    path: buildPath, // The files will be created here.
    // filename: dev ? "vc.[name].min.js" : "vc.[name].[chunkhash:8].min.js"
    filename: "vc.[name].min.js"
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
          limit: 10000,
          // name: "[name].[hash:8].[ext]"
          name: "[name].[ext]"
        }
      }
    ]
  },
  plugins: [
    // This plugin will remove all files inside Webpack's output.path directory,
    // as well as all unused webpack assets after every successful rebuild.
    new CleanWebpackPlugin(),
    // Options for the plugin which extract the CSS files to build a main file.
    new MiniCssExtractPlugin({
      // Options similar to the same options in webpackOptions.output
      // both options are optional
      // filename: dev ? "vc.[name].css" : "vc.[name].[contenthash:8].css",
      filename: "vc.[name].css",
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

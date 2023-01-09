// eslint-disable-next-line @typescript-eslint/no-var-requires
const path = require("path");
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
    filename: "vc.[name].min.js",
    assetModuleFilename: "[name][ext]",
    clean: true
  },
  devtool: "source-map",
  resolve: {
    extensions: [".ts", ".js", ".json"]
  },
  module: {
    rules: [
      // Loader for the Typescript compiler.
      {
        test: /\.(ts)x?$/,
        exclude: /node_modules|\.d\.ts$/, // this line as well
        use: {
          loader: "ts-loader",
          options: {
            compilerOptions: {
              noEmit: false
            }
          }
        }
      },
      // This loader builds a main CSS file from all the CSS imports across the files.
      {
        test: /\.css$/,
        use: [
          // https://github.com/webpack-contrib/mini-css-extract-plugin
          {
            loader: MiniCssExtractPlugin.loader,
            options: {
              //hot: true // if you want HMR - we try to automatically inject hot reloading but if it's not working, add it to the config
              //reloadAll: true // when desperation kicks in - this is a brute force HMR flag
            }
          },
          // https://webpack.js.org/loaders/css-loader
          {
            loader: "css-loader",
            options: {
              sourceMap: true
            }
          }
        ]
      },
      // To allow the use of file imports. The imported files are transformed into
      // data uris if they are small enough or it returns a path to the file.
      // https://webpack.js.org/loaders/url-loader
      {
        test: /\.(png|jpg|gif|svg|eot|ttf|woff|woff2)$/,
        type: "asset",
        generator: {
          filename: "[name][ext]"
        }
      }
    ]
  },
  plugins: [
    // Options for the plugin which extract the CSS files to build a main file.
    new MiniCssExtractPlugin({
      // Options similar to the same options in webpackOptions.output
      // both options are optional
      // filename: dev ? "vc.[name].css" : "vc.[name].[contenthash:8].css",
      filename: "vc.[name].css"
    })
  ],
  // Static server which runs the playground on npm start.
  devServer: {
    open: true,
    contentBase: "playground"
  }
};

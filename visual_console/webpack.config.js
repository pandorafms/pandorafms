module.exports = {
  mode: process.env.NODE_ENV,
  entry: __dirname + "/src/index.ts",
  devtool: "source-map",
  resolve: {
    extensions: [".ts", ".js", ".json"]
  },
  module: {
    rules: [
      {
        test: /\.ts$/,
        loader: "awesome-typescript-loader"
      }
    ]
  },
  output: {
    path: __dirname + "/dist",
    filename: "visual-console.bundle.js"
  },
  devServer: {
    open: true,
    contentBase: "static"
  }
};

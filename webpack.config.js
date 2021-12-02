const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
    mode: "development",
    entry: path.resolve(__dirname, './js/main.js'),
    devtool: "eval-source-map",
    module: {
        rules: [
          {
             test: /\.(js|jsx)$/,
             exclude: /node_modules/,
             use: ['babel-loader']
          },
          {
             test: /\.(s(a|c)ss)$/,
             use: [MiniCssExtractPlugin.loader,'css-loader', 'sass-loader']
          }
        ]
    },
    resolve: {
        extensions: ['*', '.js', '.jsx']
    },
    plugins: [
        new MiniCssExtractPlugin(
            {
                filename: 'app.css'
            }
        )
    ],
    output: {
        path: path.resolve(__dirname, './www/dist'),
        filename: 'app.js',
    },
    devServer: {
        contentBase: path.resolve(__dirname, './www/dist'),
        hot: true
    },
};
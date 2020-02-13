const defaultConfig = require( './node_modules/@wordpress/scripts/config/webpack.config.js' );
const path = require( 'path' );
const PostcssPresetEnv = require( 'postcss-preset-env' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const IgnoreEmitPlugin = require( 'ignore-emit-webpack-plugin' );

const production = process.env.NODE_ENV === '';

module.exports = {
	...defaultConfig,
	entry: {
		'gv-gutenberg': path.resolve( process.cwd(), 'assets/src/js', 'blocks.js' ),
		'style': path.resolve( process.cwd(), 'assets/src/css', 'blocks.scss' ),
	},
	output: {
		filename: '[name].js',
		path: path.resolve( process.cwd(), 'assets/js' ),
	},
	resolve: {
		alias: {
			...defaultConfig.resolve.alias,
			Blocks: path.resolve( process.cwd(), 'blocks/' ),
			AssetSources: path.resolve( process.cwd(), 'assets/src' ),
		},
	},
	optimization: {
		...defaultConfig.optimization,
		splitChunks: {
			cacheGroups: {
				editor: {
					name: 'style',
					test: /blocks\.scss$/,
					chunks: 'all',
					enforce: true,
				},
				default: false,
			},
		},
	},
	module: {
		...defaultConfig.module,
		rules: [
			...defaultConfig.module.rules,
			{
				test: /\.scss$/,
				exclude: /node_modules/,
				use: [
					{
						loader: MiniCssExtractPlugin.loader,
					},
					{
						loader: 'css-loader',
						options: {
							sourceMap: ! production,
						},
					},
					{
						loader: 'postcss-loader',
						options: {
							ident: 'postcss',
							plugins: () => [
								PostcssPresetEnv( {
									stage: 3,
									features: {
										'custom-media-queries': {
											preserve: false,
										},
										'custom-properties': {
											preserve: true,
										},
										'nesting-rules': true,
									},
								} ),
							],
						},
					},
					{
						loader: 'sass-loader',
						options: {
							sourceMap: ! production,
						},
					},
				],
			},
		],
	},
	plugins: [
		...defaultConfig.plugins,
		new MiniCssExtractPlugin( {
			filename: '../css/gv-gutenberg.css',
		} ),
		new IgnoreEmitPlugin( [ 'style.js' ] ),
	],
};

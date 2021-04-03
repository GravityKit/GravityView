const defaultConfig = require( '@wordpress/scripts/config/webpack.config.js' );
const path = require( 'path' );
const PostcssPresetEnv = require( 'postcss-preset-env' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const IgnoreEmitPlugin = require( 'ignore-emit-webpack-plugin' );

const production = process.env.NODE_ENV === '';

module.exports = {
	...defaultConfig,
	entry: {
		'gv-blocks': path.resolve( process.cwd(), 'includes/gutenberg/src/js', 'blocks.js' ),
		'gv-blocks-style': path.resolve( process.cwd(), 'includes/gutenberg/src/css', 'blocks.scss' ),
	},
	output: {
		filename: '[name].js',
		path: path.resolve( process.cwd(), 'assets/js' ),
	},
	resolve: {
		alias: {
			...defaultConfig.resolve.alias,
			Blocks: path.resolve( process.cwd(), 'includes/gutenberg/blocks/' ),
			AssetSources: path.resolve( process.cwd(), 'includes/gutenberg/src' ),
		},
	},
	optimization: {
		...defaultConfig.optimization,
		splitChunks: {
			cacheGroups: {
				editor: {
					name: 'gv-blocks-style',
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
			filename: '../css/gv-blocks.css',
		} ),
		new IgnoreEmitPlugin( [ 'gv-blocks-style.js' ] ),
	],
};

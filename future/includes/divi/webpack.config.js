/**
 * Webpack configuration for GravityView Divi Visual Builder integration.
 *
 * This compiles the React component for use in Divi's Visual Builder.
 * The output is registered at window.ET_Builder.Modules.gk_gravityview
 *
 * @package GravityKit\GravityView\Extensions\Divi
 */

const path = require( 'path' );

module.exports = {
	entry: './includes/modules/GravityView/GravityView.jsx',
	output: {
		path: path.resolve( __dirname, 'build' ),
		filename: 'bundle.min.js',
		// No library assignment needed - we use et_builder_api_ready event
	},
	// Use React provided by Divi (don't bundle it)
	externals: {
		react: 'React',
		'react-dom': 'ReactDOM',
	},
	module: {
		rules: [
			{
				test: /\.jsx?$/,
				exclude: /node_modules/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: [ '@babel/preset-react' ],
					},
				},
			},
		],
	},
	resolve: {
		extensions: [ '.js', '.jsx' ],
	},
	// Disable source maps for production
	devtool: false,
};

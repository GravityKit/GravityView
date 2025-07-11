// eslint.config.cjs
const js = require('@eslint/js');
const prettier = require('eslint-config-prettier');

/** @type {import('eslint').Linter.FlatConfig} */
module.exports = [
	js.configs.recommended,
	prettier,
	{
		files: ['**/*.{js,ts}'],
		languageOptions: {
			ecmaVersion: 2023,
			sourceType: 'module',
			globals: {
				global: 'readonly',
				require: 'readonly',
				module: 'readonly',
				process: 'readonly',
				console: 'readonly',
				__dirname: 'readonly',
				__filename: 'readonly',
				document: 'readonly',
				window: 'readonly',
				location: 'readonly',
				setTimeout: 'readonly',
				URL: 'readonly',
				URLSearchParams: 'readonly',
			},
		},
		rules: {
			'no-unused-vars': ['error', { args: 'all', argsIgnorePattern: '^_', varsIgnorePattern: '^_' }],
			'indent': [ 'warn', 'tab', { "SwitchCase": 1 } ],
		},
	},
];

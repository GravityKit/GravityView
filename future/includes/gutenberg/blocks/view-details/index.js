import { registerBlockType } from '@wordpress/blocks';

import blockMeta from './block.json';
import './style.scss';
import Edit from './edit';

const { name, ...settings } = blockMeta;

registerBlockType(
	name,
	{
		...settings,
		icon: (
			<svg width="24" height="22" viewBox="0 0 24 22" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path fillRule="evenodd" clipRule="evenodd" d="M3 0C1.34315 0 0 1.34315 0 3V19C0 20.6569 1.34315 22 3 22H17C18.6569 22 20 20.6569 20 19V18H18V19C18 19.5523 17.5523 20 17 20H3C2.44772 20 2 19.5523 2 19V3C2 2.44772 2.44772 2 3 2H17C17.5523 2 18 2.44772 18 3V4H20V3C20 1.34315 18.6569 0 17 0H3Z" fill="#2B292B" />
				<path fillRule="evenodd" clipRule="evenodd" d="M11 4H4V6H11V4ZM7 8H4V10H7V8ZM4 12H7V14H4V12ZM11 16H4V18H11V16ZM16 17C20.707 17 23.744 11.716 23.871 11.492C24.042 11.188 24.043 10.816 23.872 10.512C23.746 10.287 20.731 5 16 5C11.245 5 8.25101 10.289 8.12601 10.514C7.95701 10.817 7.95801 11.186 8.12801 11.489C8.25401 11.713 11.269 17 16 17ZM16 7C18.839 7 21.036 9.835 21.818 11C21.034 12.166 18.837 15 16 15C13.159 15 10.962 12.162 10.181 10.999C10.958 9.835 13.146 7 16 7ZM18 11C18 12.1046 17.1046 13 16 13C14.8954 13 14 12.1046 14 11C14 9.89543 14.8954 9 16 9C17.1046 9 18 9.89543 18 11Z" fill="#2B292B" />
			</svg>
		),
		edit: Edit,
		save: () => null,
		transforms: {
			from: [
				{
					type: 'shortcode',
					tag: [ 'gravityview' ],
					attributes: {
						viewId: {
							type: 'string',
							shortcode: ( ref ) => ref.named.viewId
						},
						detail: {
							type: 'string',
							shortcode: ( ref ) => ref.named.detail
						},
					},
				},
			],
		},
	}
);

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
			<svg width="20" height="22" viewBox="0 0 20 22" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path fillRule="evenodd" clipRule="evenodd" d="M0 3C0 1.34315 1.34315 0 3 0H17C18.6569 0 20 1.34315 20 3V19C20 20.6569 18.6569 22 17 22H3C1.34315 22 0 20.6569 0 19V3ZM3 2C2.44772 2 2 2.44772 2 3V19C2 19.5523 2.44772 20 3 20H17C17.5523 20 18 19.5523 18 19V3C18 2.44772 17.5523 2 17 2H3ZM4 4H16V6H4V4ZM13 8H4V10H13V8ZM4 12H8V14H4V12ZM16 12H9V14H16V12ZM12 16V18H4V16H12ZM16 16H13V18H16V16Z" fill="#2B292B" />
			</svg>
		),
		edit: Edit,
		save: () => null,
		transforms: {
			from: [
				{
					type: 'shortcode',
					tag: [ 'gventry' ],
					attributes: {
						viewId: {
							type: 'string',
							shortcode: ( { named: { viewId } } ) => viewId
						},
						entryId: {
							type: 'string',
							shortcode: ( { named: { entryId } } ) => entryId
						},
					},
				},
			],
		},
	}
);

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
			<svg width="24" height="20" viewBox="0 0 24 20" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path fillRule="evenodd" clipRule="evenodd" d="M0 0H8V2H0V0ZM0 4H1H23H24V5V19V20H23H1H0V19V5V4ZM2 6V18H22V6H2ZM18 11H5V13H18V11Z" fill="#2B292B" />
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

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
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path fillRule="evenodd" clipRule="evenodd" d="M3 0C1.34315 0 0 1.34314 0 3V21C0 22.6569 1.34314 24 3 24H21C22.6569 24 24 22.6569 24 21V3C24 1.34315 22.6569 0 21 0H3ZM2 3C2 2.44772 2.44771 2 3 2H21C21.5523 2 22 2.44771 22 3V21C22 21.5523 21.5523 22 21 22H3C2.44772 22 2 21.5523 2 21V3ZM5 4C4.44772 4 4 4.44772 4 5C4 5.55228 4.44772 6 5 6H8C8.55228 6 9 5.55228 9 5C9 4.44772 8.55228 4 8 4H5ZM4 9C4 8.44771 4.44772 8 5 8H19C19.5523 8 20 8.44771 20 9C20 9.55228 19.5523 10 19 10H5C4.44772 10 4 9.55228 4 9ZM12 4C11.4477 4 11 4.44772 11 5C11 5.55228 11.4477 6 12 6H19C19.5523 6 20 5.55228 20 5C20 4.44772 19.5523 4 19 4H12Z" fill="#2B292B" />
				<path fillRule="evenodd" clipRule="evenodd" d="M5 12C4.44772 12 4 12.4477 4 13V19C4 19.5523 4.44772 20 5 20H19C19.5523 20 20 19.5523 20 19V13C20 12.4477 19.5523 12 19 12H5ZM9 15C8.44771 15 8 15.4477 8 16C8 16.5523 8.44771 17 9 17H15C15.5523 17 16 16.5523 16 16C16 15.4477 15.5523 15 15 15H9Z" fill="#2B292B" />
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
							shortcode: ( { named: { viewId } } ) => viewId
						},
						postId: {
							type: 'number',
							shortcode: ( { named: { postId } } ) => postId
						},
						pageSize: {
							type: 'string',
							shortcode: ( { named: { pageSize } } ) => pageSize
						},
						sortField: {
							type: 'string',
							shortcode: ( { named: { sortField } } ) => sortField
						},
						sortDirection: {
							type: 'string',
							shortcode: ( { named: { sortDirection } } ) => sortDirection
						},
						searchField: {
							type: 'string',
							shortcode: ( { named: { searchField } } ) => searchField
						},
						searchValue: {
							type: 'string',
							shortcode: ( { named: { searchValue } } ) => searchValue
						},
						searchOperator: {
							type: 'string',
							shortcode: ( { named: { searchOperator } } ) => searchOperator
						},
						startDate: {
							type: 'string',
							shortcode: ( { named: { startDate } } ) => startDate
						},
						endDate: {
							type: 'string',
							shortcode: ( { named: { endDate } } ) => endDate
						},
						class: {
							type: 'string',
							shortcode: ( { named: { classValue } } ) => classValue
						},
						offset: {
							type: 'string',
							shortcode: ( { named: { offset } } ) => offset

						},
						singleTitle: {
							type: 'string',
							shortcode: ( { named: { singleTitle } } ) => singleTitle
						},
						backLinkLabel: {
							type: 'string',
							shortcode: ( { named: { backLinkLabel } } ) => backLinkLabel
						},
						detail: {
							type: 'string',
							shortcode: ( { named: { detail } } ) => detail
						},
					},
				},
			],
		},
	}
);

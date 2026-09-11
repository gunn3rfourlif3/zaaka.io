/**
 * Editor registration for the two server-rendered case-study blocks. Plain
 * ES5 against the wp globals, so the theme still has no build step. The front
 * end never loads this file.
 */
( function ( wp ) {
	var el = wp.element.createElement;
	var SSR = wp.serverSideRender;

	function register( name, title, empty ) {
		wp.blocks.registerBlockType( name, {
			apiVersion: 3,
			title: title,
			category: 'theme',
			icon: 'portfolio',
			usesContext: [ 'postId' ],
			supports: { html: false },
			edit: function ( props ) {
				return el( 'div', wp.blockEditor ? wp.blockEditor.useBlockProps() : {},
					el( SSR, {
						block: name,
						urlQueryArgs: props.context.postId ? { post_id: props.context.postId } : {},
						EmptyResponsePlaceholder: function () {
							return el( 'p', { style: { padding: '1rem', border: '1px dashed #999', fontSize: '13px' } }, empty );
						}
					} )
				);
			},
			save: function () { return null; }
		} );
	}

	register( 'zaaka/case-summary', 'Case study summary', 'Case study summary — filled from the "Case study summary" fields on each project.' );
	register( 'zaaka/case-quote', 'Case study testimonial', 'Testimonial — appears once a quote and name are added to the project.' );
} )( window.wp );

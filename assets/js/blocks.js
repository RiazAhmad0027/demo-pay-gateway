( function () {
	const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
	const { getSetting } = window.wc.wcSettings;
	const { createElement } = window.wp.element;
	const { decodeEntities } = window.wp.htmlEntities;

	// Data sent from PHP (get_payment_method_data).
	const settings = getSetting( 'demo_pay_data', {} );
	const title = decodeEntities( settings.title || 'Demo Pay' );
	const description = decodeEntities( settings.description || '' );

	const Content = () => createElement( 'p', null, description );
	const Label = () => createElement( 'span', null, title );

	registerPaymentMethod( {
		name: 'demo_pay',
		label: createElement( Label ),
		content: createElement( Content ),
		edit: createElement( Content ),
		canMakePayment: () => true,
		ariaLabel: title,
		supports: { features: settings.supports || [ 'products' ] },
	} );
} )();
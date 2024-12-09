const path = require( 'path' );
const fs = require( 'fs' );
const defaults = require('@wordpress/scripts/config/webpack.config');
var webkitConf = [];


// Push default config.
webkitConf.push( {
	...defaults,
	entry      : {
		'wc-blocks': "./includes/builders/wc-blocks/assets/js/index.js"
	},
	output     : {
		filename     : "./[name]/index.js",
		libraryTarget: 'this'
	},
	externals : {
		'@wordpress/api-fetch'    	    : { this: ['wp', 'apiFetch'] },
		'@wordpress/element'      	    : { this: ['wp', 'element'] },
		'@wordpress/data'         	    : { this: ['wp', 'data'] },
		'@wordpress/hooks'        	    : { this: ['wp', 'hooks'] },
		'@wordpress/url'          	    : { this: ['wp', 'url'] },
		'@wordpress/html-entities'	    : { this: ['wp', 'htmlEntities'] },
		'@wordpress/i18n'         	    : { this: ['wp', 'i18n'] },
		'@wordpress/date'         	    : { this: ['wp', 'date'] },
		'@woocommerce/settings'   	    : { this: ['wc', 'wcSettings'] },
		'@woocommerce/components' 	    : { this: ['wc', 'components'] },
		'@woocommerce/navigation' 	    : { this: ['wc', 'navigation'] },
		'@woocommerce/date'       	    : { this: ['wc', 'date'] },
		'@woocommerce/number'     	    : { this: ['wc', 'number'] },
		'@wordpress/block-editor'       : { this: ['wp', 'blockEditor'] },
		'@wordpress/blocks'             : { this: ['wp', 'blocks'] },
		'@wordpress/components'         : { this: ['wp', 'components'] },
		'@wordpress/compose'            : { this: ['wp', 'compose'] },
		'@wordpress/editor'             : { this: ['wp', 'editor'] },
		'@wordpress/jest-preset-default': { this: ['wp', 'default'] },
		'@wordpress/scripts'            : { this: ['wp', 'scripts'] },
		react                           : 'React',
		lodash                          : 'lodash',
		'react-dom'                     : 'ReactDOM'
	}
} );

module.exports = webkitConf;

/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports) {

var __ = wp.i18n.__;
var _wp$blocks = wp.blocks,
    registerBlockType = _wp$blocks.registerBlockType,
    RichText = _wp$blocks.RichText,
    PlainText = _wp$blocks.PlainText,
    Editable = _wp$blocks.Editable,
    AlignmentToolbar = _wp$blocks.AlignmentToolbar,
    BlockControls = _wp$blocks.BlockControls,
    InspectorControls = _wp$blocks.InspectorControls,
    MediaUpload = _wp$blocks.MediaUpload;
var _wp$components = wp.components,
    Button = _wp$components.Button,
    withAPIData = _wp$components.withAPIData;


function getRecipe(id, props) {
    var url = '/wp-json/zip-recipes/v1/recipe/' + id;
    return fetch(url, {
        credentials: 'same-origin',
        method: 'get',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json'
            // 'X-WP-Nonce': zrdn_recipe_block.nonce
        } }).then(handleFetchErrors).then(function (response) {
        return response.json();
    }).then(function (json) {
        props.setAttributes({
            title: json.title,
            ingredients: json.ingredients,
            instructions: json.instructions,
            mediaURL: json.image_url
        });
    }).catch(function (e) {
        console.log(e);
    });
}

function handleFetchErrors(response) {
    if (!response.ok) {
        console.log('fetch error, status: ' + response.statusText);
    }
    return response;
}

registerBlockType('zip-recipes/recipe-block', {
    title: __('Zip Recipes'),
    icon: 'carrot',
    category: 'common',
    keywords: [__('zip recipes'), __('recipe'), __('zip')],
    attributes: {
        title: {
            type: 'array',
            source: 'children',
            selector: 'h2'
        },
        mediaID: {
            type: 'number'
        },
        mediaURL: {
            type: 'string',
            source: 'attribute',
            selector: 'img',
            attribute: 'src'
        },
        ingredients: {
            type: 'array',
            source: 'children',
            selector: '.ingredients'
        },
        instructions: {
            type: 'array',
            source: 'children',
            selector: '.steps'
        }
    },
    useOnce: true,
    supports: {
        html: false
    },

    edit: function edit(props) {
        var focusedEditable = props.focus ? props.focus.editable || 'title' : null;
        var attributes = props.attributes;

        var shortcode = '[wp-form id="45" title="My Test Form"]';
        // use of regex to extract id , title
        //var shortcodeArry  = /id\=\"(.*?)\".*title=\"(.*?)\"/.exec(shortcode);
        // var id = shortcodeArry[1];
        // var title = shortcodeArry[2];
        // console.log(shortcodeArry);

        // getRecipe(45, props);

        var onChangeTitle = function onChangeTitle(value) {
            props.setAttributes({ title: value });
        };
        var onFocusTitle = function onFocusTitle(focus) {
            props.setFocus(_.extend({}, focus, { editable: 'title' }));
        };
        var onSelectImage = function onSelectImage(media) {
            props.setAttributes({
                mediaURL: media.url,
                mediaID: media.id
            });
        };
        var onChangeIngredients = function onChangeIngredients(value) {
            props.setAttributes({ ingredients: value });
        };
        var onFocusIngredients = function onFocusIngredients(focus) {
            // props.setFocus( _.extend( {}, focus, { editable: 'ingredients' } ) );
        };
        var onChangeInstructions = function onChangeInstructions(value) {
            props.setAttributes({ instructions: value });
        };
        var onFocusInstructions = function onFocusInstructions(focus) {
            // props.setFocus( _.extend( {}, focus, { editable: 'instructions' } ) );
        };

        return wp.element.createElement(
            'div',
            { className: props.className },
            wp.element.createElement(PlainText, {
                tagname: 'h2',
                placeholder: __('Write Recipe title…'),
                value: attributes.title,
                onChange: onChangeTitle,
                focus: focusedEditable === 'title',
                onFocus: onFocusTitle
            }),
            wp.element.createElement(
                'div',
                { className: 'recipe-image' },
                wp.element.createElement(MediaUpload, {
                    onSelect: onSelectImage,
                    type: 'image',
                    value: attributes.mediaID,
                    render: function render(_ref) {
                        var open = _ref.open;
                        return wp.element.createElement(
                            Button,
                            { className: attributes.mediaURL ? 'image-button' : 'button button-large', onClick: open },
                            !attributes.mediaURL ? __('Upload Image') : wp.element.createElement('img', { src: attributes.mediaURL })
                        );
                    }
                })
            ),
            wp.element.createElement(
                'h3',
                null,
                __('Ingredients')
            ),
            wp.element.createElement(PlainText, {
                tagname: 'ul',
                multiline: 'li',
                placeholder: __('Write a list of ingredients…'),
                value: attributes.ingredients,
                onChange: onChangeIngredients,
                focus: focusedEditable === 'ingredients',
                onFocus: onFocusIngredients,
                className: 'ingredients'
            }),
            wp.element.createElement(
                'h3',
                null,
                __('Instructions')
            ),
            wp.element.createElement(PlainText, {
                tagname: 'div',
                multiline: 'p',
                className: 'steps',
                placeholder: __('Write the instructions…'),
                value: attributes.instructions,
                onChange: onChangeInstructions,
                focus: focusedEditable === 'instructions',
                onFocus: onFocusInstructions
            })
        );
    },

    save: function save(props) {

        return null;
    }
});

/***/ })
/******/ ]);
/**
 *  block.js file is where all the action happens
 *
 * @param {type} blocks
 * @param {type} i18n
 * @param {type} element
 * @returns {undefined}
 * php jquery update long pool
 */

( function(blocks, components, i18n, element, _ ) {

    var __ = wp.i18n.__; // The __() function for internationalization.

    var el = element.createElement; // The wp.element.createElement() function to create elements.
    var registerBlockType = blocks.registerBlockType; // The registerBlo

    /**
     * Gutenberg components
     */
    var BlockControls = wp.blocks.BlockControls;
    var AlignmentToolbar = wp.blocks.AlignmentToolbar;
    var MediaUploadButton = wp.blocks.MediaUploadButton;
    var InspectorControls = wp.blocks.InspectorControls;
    var TextControl = wp.blocks.InspectorControls.TextControl;

    /**
     * Register Recipe Block.
     *
     * Registers a new block provided a unique name and an object defining its
     * behavior. Once registered, the block is made available as an option to any
     * editor interface where blocks are implemented.
     *
     * @param  {string}   name     Block name.
     * @param  {Object}   settings Block settings.
     * @return {?WPBlock}          The block, if it has been successfully
     *                             registered; otherwise `undefined`.
     */
    registerBlockType( 'zip-recipes/recipe-block', { // The name of our block. Must be a string with prefix. Example: my-plugin/my-custom-block.
        title: __( 'Zip Recipes' ), // Block title. __() function allows for internationalization.
        icon: 'carrot', // Dashicon icon for our block. Custom icons can be added using inline SVGs.
        category: 'common', // The category of the block.  (common, formatting, layout widgets, embed).
        keywords: [ __( 'Zip Recipes' ), __( 'Recipe' ),  __( 'Recipe Notes' )], // Make it easier to discover a block with keyword aliases
        /**
         * Attribute matchers!
         *
         * Attribute matchers are used to define the strategy by which block
         * attribute values are extracted from saved post content. They provide
         * a mechanism to map from the saved markup to a JavaScript representation
         * of a block.
         *
         * children() — Use children to extract child nodes of the matched element,
         * returned as an array of virtual elements. This is most commonly used in
         * combination with the Editable component.
         *
         * Example: Extract child nodes from a paragraph of rich text.
         */
        attributes: {
            title: {
                type: 'array',
                source: 'children',
                selector: 'h2',
            },
            mediaID: {
                type: 'number',
            },
            mediaURL: {
                type: 'string',
                source: 'attribute',
                selector: 'img',
                attribute: 'src',
            },
            ingredients: {
                type: 'array',
                source: 'children',
                selector: '.ingredients',
            },
            instructions: {
                type: 'array',
                source: 'children',
                selector: '.steps',
            },
        },

        /**
         *  The "edit" property must be a valid function.
         *
         * @param props
         */
        edit: function( props ) {
            var focusedEditable = props.focus ? props.focus.editable || 'title' : null;
            var attributes = props.attributes;

            var onSelectImage = function( media ) {
                return props.setAttributes( {
                    mediaURL: media.url,
                    mediaID: media.id
                } );
            };

            return (
                el( 'div', { className: props.className },
                    el( blocks.PlainText, {
                        tagName: 'h2',
                        inline: true,
                        placeholder: __( 'Write Recipe title…' ),
                        value: attributes.title,
                        onChange: function( value ) {
                            props.setAttributes( { title: value } );
                        },
                        focus: focusedEditable === 'title' ? focus : null,
                        onFocus: function( focus ) {
                            props.setFocus( _.extend( {}, focus, { editable: 'title' } ) );
                        }
                    } ),
                    el( 'div', { className: 'recipe-image' },
                        el( blocks.MediaUpload, {
                            onSelect: onSelectImage,
                            type: 'image',
                            value: attributes.mediaID,
                            render: function( obj ) {
                                return el( components.Button, {
                                        className: attributes.mediaID ? 'image-button' : 'button button-large',
                                        onClick: obj.open
                                    },
                                    ! attributes.mediaID ? __( 'Upload Image' ) : el( 'img', { src: attributes.mediaURL } )
                                );
                            }
                        } )
                    ),
                    el( 'h3', {}, i18n.__( 'Ingredients' ) ),
                    el( blocks.RichText, {
                        tagName: 'ul',
                        multiline: 'li',
                        placeholder: __( 'Write a list of ingredients…' ),
                        value: attributes.ingredients,
                        onChange: function( value ) {
                            props.setAttributes( { ingredients: value } );
                        },
                        focus: focusedEditable === 'ingredients' ? focus : null,
                        onFocus: function( focus ) {
                            props.setFocus( _.extend( {}, focus, { editable: 'ingredients' } ) );
                        },
                        className: 'ingredients',
                    } ),
                    el( 'h3', {}, i18n.__( 'Instructions' ) ),
                    el( blocks.RichText, {
                        tagName: 'div',
                        inline: false,
                        placeholder: __( 'Write instructions…' ),
                        value: attributes.instructions,
                        onChange: function( value ) {
                            props.setAttributes( { instructions: value } );
                        },
                        focus: focusedEditable === 'instructions' ? focus : null,
                        onFocus: function( focus ) {
                            props.setFocus( _.extend( {}, focus, { editable: 'instructions' } ) );
                        }
                    } )
                )
            );
        },

        /**
         *  Save
         *
         * @param props
         */
        save: function( props ) {
            var attributes = props.attributes;

            return (
                el( 'div', { className: props.className },
                    el( 'h2', {}, attributes.title ),
                    attributes.mediaURL &&
                    el( 'div', { className: 'recipe-image' },
                        el( 'img', { src: attributes.mediaURL } )
                    ),
                    el( 'h3', {}, __( 'Ingredients' ) ),
                    el( 'ul', { className: 'ingredients' }, attributes.ingredients ),
                    el( 'h3', {}, __( 'Instructions' ) ),
                    el( 'div', { className: 'steps' }, attributes.instructions )
                )
            );
        }
    } );

} )(
    window.wp.blocks,
    window.wp.components,
    window.wp.i18n,
    window.wp.element,
    window._
);

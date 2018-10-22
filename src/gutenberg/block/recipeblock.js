
const { __ } = wp.i18n;
const {
    registerBlockType,
    RichText,
    PlainText,
    Editable,
    AlignmentToolbar,
    BlockControls,
    InspectorControls,
    MediaUpload
} = wp.blocks;
const { Button, withAPIData } = wp.components;

function getRecipe(id, props) {
    var url = '/wp-json/zip-recipes/v1/recipe/'+id;
    return fetch( url, {
        credentials: 'same-origin',
        method: 'get',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json'
           // 'X-WP-Nonce': zrdn_recipe_block.nonce
        }})
        .then( handleFetchErrors )
        .then( ( response ) => response.json() )
        .then( ( json ) => {
            props.setAttributes( {
                title: json.title,
                ingredients: json.ingredients,
                instructions: json.instructions,
                mediaURL: json.image_url,
            } );
        })
        .catch(function(e) {
            console.log(e);
        });
}

function handleFetchErrors( response ) {
    if (!response.ok) {
        console.log('fetch error, status: ' + response.statusText);
    }
    return response;
}

registerBlockType( 'zip-recipes/recipe-block', {
    title: __( 'Zip Recipes' ),
    icon: 'carrot',
    category: 'common',
    keywords: [ __( 'zip recipes' ), __( 'recipe' ),__( 'zip' )],
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
    useOnce: true,
    supports:   {
        html: false,
    },

    edit: props => {
        const focusedEditable = props.focus ? props.focus.editable || 'title' : null;
        const attributes = props.attributes;

        var shortcode = '[wp-form id="45" title="My Test Form"]';
        // use of regex to extract id , title
        //var shortcodeArry  = /id\=\"(.*?)\".*title=\"(.*?)\"/.exec(shortcode);
       // var id = shortcodeArry[1];
       // var title = shortcodeArry[2];
       // console.log(shortcodeArry);

        // getRecipe(45, props);

        const onChangeTitle = value => {
            props.setAttributes( { title: value } );
        };
        const onFocusTitle = focus => {
            props.setFocus( _.extend( {}, focus, { editable: 'title' } ) );
        };
        const onSelectImage = media => {
            props.setAttributes( {
                mediaURL: media.url,
                mediaID: media.id,
            } );
        };
        const onChangeIngredients = value => {
            props.setAttributes( { ingredients: value } );
        };
        const onFocusIngredients = focus => {
            // props.setFocus( _.extend( {}, focus, { editable: 'ingredients' } ) );
        };
        const onChangeInstructions = value => {
            props.setAttributes( { instructions: value } );
        };
        const onFocusInstructions = focus => {
           // props.setFocus( _.extend( {}, focus, { editable: 'instructions' } ) );
        };

        return (
            <div className={ props.className }>
                <PlainText
                    tagname="h2"
                    placeholder={ __( 'Write Recipe title…' ) }
                    value={ attributes.title }
                    onChange={ onChangeTitle }
                    focus={ focusedEditable === 'title' }
                    onFocus={ onFocusTitle }
                />
                <div className="recipe-image">
                    <MediaUpload
                        onSelect={ onSelectImage }
                        type="image"
                        value={ attributes.mediaID }
                        render={ ( { open } ) => (
                            <Button className={ attributes.mediaURL ? 'image-button' : 'button button-large' } onClick={ open }>
                                { ! attributes.mediaURL ? __( 'Upload Image' ) : <img src={ attributes.mediaURL } /> }
                            </Button>
                        ) }
                    />
                </div>
                <h3>{ __( 'Ingredients' ) }</h3>
                <PlainText
                    tagname="ul"
                    multiline="li"
                    placeholder={ __( 'Write a list of ingredients…' ) }
                    value={ attributes.ingredients }
                    onChange={ onChangeIngredients }
                    focus={ focusedEditable === 'ingredients' }
                    onFocus={ onFocusIngredients }
                    className="ingredients"
                />
                <h3>{ __( 'Instructions' ) }</h3>
                <PlainText
                    tagname="div"
                    multiline="p"
                    className="steps"
                    placeholder={ __( 'Write the instructions…' ) }
                    value={ attributes.instructions }
                    onChange={ onChangeInstructions }
                    focus={ focusedEditable === 'instructions' }
                    onFocus={ onFocusInstructions }
                />
            </div>
        );
    },

    save: props => {

       return null;
    }
} );
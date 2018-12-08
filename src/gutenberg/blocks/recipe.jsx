const {__} = wp.i18n;
const {
    registerBlockType
} = wp.blocks;

const {withState} = wp.compose;

let blocks = window.wp.blocks;
let editor = window.wp.editor;
let i18n = window.wp.i18n;
let element = window.wp.element;
let components = window.wp.components;
let _ = window._;

let el = element.createElement;

let RichText = editor.RichText;
let PlainText = editor.PlainText;
var MediaUpload = editor.MediaUpload;
var Modal = editor.Modal;
var Button = editor.Button;
var InspectorControls = editor.InspectorControls;


const {data, apiFetch} = wp;
const {registerStore, withSelect, withDispatch} = data;

const DEFAULT_STATE = {
    isDirty: false,
    'recipe': {
        'id': '',
        'post_id': '',
        'title': '',
        'image_url': '',
        'description': '',
        'prep_time': '',
        'cook_time': '',
        'yield': '',
        'category': '',
        'cuisine': '',
        'ingredients': '',
        'instructions': '',
        // 'nutrition': {}, needs to be expanded out
        'notes': ''
    },
    isFetching: false,
    isSaving: false
};

const RECIPE_REQUEST = 'RECIPE_REQUEST';
const RECIPE_REQUEST_SUCCESS = 'RECIPE_REQUEST_SUCCESS';
const RECIPE_SAVE_REQUEST = 'RECIPE_SAVE_REQUEST';
const RECIPE_SAVING = 'RECIPE_SAVING';
const RECIPE_SAVE_SUCCESS = 'RECIPE_SAVE_SUCCESS';
const SET_TITLE = 'SET_TITLE';
const SET_IMAGE_URL = 'SET_IMAGE_URL';
const SET_DESCRIPTION = 'SET_DESCRIPTION';
const SET_PREP_TIME = 'SET_PREP_TIME';
const SET_COOK_TIME = 'SET_COOK_TIME';
const SET_SERVINGS = 'SET_SERVINGS';
const SET_CATEGORY = 'SET_CATEGORY';
const SET_CUISINE = 'SET_CUISINE';
const SET_INGREDIENTS = 'SET_INGREDIENTS';
const SET_INSTRUCTIONS = 'SET_INSTRUCTIONS';
const SET_NOTES = 'SET_NOTES';

// These are action creators, actually
const actions = {
    requestRecipe(id) {
        return {
            type: RECIPE_REQUEST,
            id
        }
    },

    requestRecipeSuccess(recipe) {
        return {
            type: RECIPE_REQUEST_SUCCESS,
            recipe
        }
    },

    * saveRecipe(id, title) {
        const recipe = yield {
            type: RECIPE_SAVE_REQUEST,
            id,
            title
        };
        return recipe;
    },

    setRecipeSaving() {
        return {
            type: RECIPE_SAVING
        }
    },

    saveRecipeSuccess(recipe) {
        return {
            type: RECIPE_SAVE_SUCCESS,
            recipe
        };
    },

    setTitle(title) {
        return {
            type: SET_TITLE,
            title
        };
    },

    setImageUrl(url) {
        return {
            type: SET_IMAGE_URL,
            url
        }
    },

    setDescription(description) {
        return {
            type: SET_DESCRIPTION,
            description
        }
    },

    setPrepTime(prepTime) {
        return {
            type: SET_PREP_TIME,
            prepTime
        }
    },

    setCookTime(cookTime) {
        return {
            type: SET_COOK_TIME,
            cookTime
        }
    },

    setServings(servings) {
        return {
            type: SET_SERVINGS,
            servings
        }
    },

    setCategory(category) {
        return {
            type: SET_CATEGORY,
            category
        }
    },

    setCuisine(cuisine) {
        return {
            type: SET_CUISINE,
            cuisine
        }
    },

    setIngredients(ingredients) {
        return {
            type: SET_INGREDIENTS,
            ingredients
        }
    },

    setInstructions(instructions) {
        return {
            type: SET_INSTRUCTIONS,
            instructions
        }
    },

    setNotes(notes) {
        return {
            type: SET_NOTES,
            notes
        }
    },

    postToAPI(path) {
        return {
            type: 'POST_TO_API',
            path,
        };
    },

    fetchFromAPI(path) {
        return {
            type: 'FETCH_FROM_API',
            path,
        };
    },
};

registerStore('zip-recipes-store', {
    reducer(state = DEFAULT_STATE, action) {
        switch (action.type) {
            case RECIPE_REQUEST:
                return {
                    ...state,
                    isFetching: true
                };
            case RECIPE_REQUEST_SUCCESS:
                return {
                    ...state,
                    isFetching: false,
                    recipe: action.recipe
                };
            case RECIPE_SAVING:
                return {
                    ...state,
                    isSaving: true
                };
            case RECIPE_SAVE_SUCCESS:
                return {
                    ...state,
                    lastSavedAt: Date.now(),
                    isSaving: false
                };
            case SET_TITLE:
                return {
                    ...state,
                    recipe: {...state.recipe, title: action.title},
                    isDirty: true
                };
            case SET_IMAGE_URL:
                return {
                    ...state,
                    recipe: {...state.recipe, imageUrl: action.imageUrl}
                };
            case SET_DESCRIPTION:
                return {
                    ...state,
                    recipe: {...state.recipe, description: action.description}
                };
            case SET_PREP_TIME:
                return {
                    ...state,
                    recipe: {...state.recipe, prepTime: action.prepTime}
                };
            case SET_COOK_TIME:
                return {
                    ...state,
                    recipe: {...state.recipe, cookTime: action.cookTime}
                };
            case SET_SERVINGS:
                return {
                    ...state,
                    recipe: {...state.recipe, servings: action.servings}
                };
            case SET_CATEGORY:
                return {
                    ...state,
                    recipe: {...state.recipe, category: action.category}
                };
            case SET_CUISINE:
                return {
                    ...state,
                    recipe: {...state.recipe, cuisine: action.cuisine}
                };
            case SET_INGREDIENTS:
                return {
                    ...state,
                    recipe: {...state.recipe, ingredients: action.ingredients}
                };
            case SET_INSTRUCTIONS:
                return {
                    ...state,
                    recipe: {...state.recipe, instructions: action.instructions}
                };
            case SET_NOTES:
                return {
                    ...state,
                    recipe: {...state.recipe, notes: action.notes}
                };
        }

        return state;
    },

    actions,

    selectors: {
        getTitle(state) {
            const {recipe} = state;
            return recipe;
        },

        getTitle(state) {
            const {title} = state.recipe;
            return title;
        },
        getIsSaving(state) {
            const {isSaving} = state;
            return isSaving;
        }
    },

    controls: {
        RECIPE_SAVE_REQUEST(action) {
            let recipe = apiFetch({
                path: `/zip-recipes/v1/recipe/${action.id}`, method: 'POST', data: {
                    title: action.title
                }
            });

            return recipe;
        },

        POST_TO_API(action) {
            let recipe = apiFetch({
                path: action.path, method: 'POST', data: {
                    title: 'Gizmo 10000'
                }
            });
            return recipe;
        },
        FETCH_FROM_API(action) {
            let recipe = apiFetch({path: action.path});
            recipe.title += Date.now();
            return recipe;
        },
    },

    resolvers: {
        * getTitle(state, id) {
            const path = '/zip-recipes/v1/recipe/2';
            const recipe = yield actions.fetchFromAPI(path);
            return actions.setTitle(recipe.title)
        }
    }
});

registerBlockType('zip-recipes/recipe-block', {
    title: __('Zip Recipes'),
    description: __('Create a recipe card.'),
    icon: {
        src: 'carrot',
        foreground: '#4AB158',
    },
    category: 'widgets',
    keywords: [
        __('Zip Recipes'),
        __('Recipe'),
        __('Recipe Card'),
    ],
    //
    // attributes: {
    //     multiples: false,
    //     url: {
    //         type: 'string',
    //     }
    // },


    attributes: {
        id: {
            type: 'string'
        },
        // Only purpose of lastUpdatedAt is to mark our state dirty. This happens when you update a property
        // in your component in Gutenberg.
        lastUpdatedAt: {
            type: 'integer'
        }
    },
    reusable: false,
    multiple: false,
    edit: withDispatch((dispatch, ownProps) => {
        const {saveRecipe, saveRecipeSuccess, setRecipeSaving, setTitle} = dispatch('zip-recipes-store');

        return {
            onTitleChange(setAttributes, {target: {value}}) {
                setTitle(value);
                setAttributes({lastUpdatedAt: Date.now()});
                // setRecipeSaving();
                // const recipe = await saveRecipe(2, value);
                // saveRecipeSuccess(recipe);
            }
        };
    })(withSelect((select, ownProps) => {
        const {getTitle, getIsSaving} = select('zip-recipes-store');

        return {
            title: getTitle(),
            isSaving: getIsSaving()
        };
    })(({attributes, setAttributes, onTitleChange, isSaving, title, setState, className}) => {
        let uploadButton = (obj) => {
            return <components.Button>
                Hello
            </components.Button>;
        };


        const markDirtyAndPass = (handler) => {
            // for some reason this cannot be a function of () => {}
            // handler doesn't get executed properly, it seems. For example, for title change, title doesn't change
            // at all if you make this change.
            return function() {
                handler.call(null, setAttributes, ...arguments);
            }
        };

        const saving = isSaving ? "Saving..." : '';

        return (<div className={className}>
            {/* Title and image start --> */}
            <div className="zrdn-columns zrdn-is-mobile">
                {saving}
                <div className="zrdn-column zrdn-is-three-quarters-tablet zrdn-is-two-thirds-mobile">
                    <div className="zrdn-field">
                        <div className="zrdn-control" id="title-container">
                            <input id="recipe-title" name='recipe_title' className="zrdn-input zrdn-is-size-3"
                                   type="text" value={title} onChange={markDirtyAndPass(onTitleChange)}
                                   placeholder={i18n.__('Recipe Title…', 'gutenberg-examples')}/>
                        </div>
                    </div>
                </div>
                <div className="zrdn-column">
                    <label className="zrdn-label">Image</label>
                    {uploadButton}
                    {/*/!* {% if is_featured_post_image %} TODO: fix this *!/*/}
                    {/*<input type='hidden' id="recipe_image" name='recipe_image' value=''/>*/}
                    {/*/!*{% else %} *!/*/}
                    {/*/!*<input type='hidden' id="recipe_image" name='recipe_image' value=''/>*!/*/}
                    {/*/!*{% endif %}*!/*/}
                    {/*<div id="upload-recipe-image-button-container">*/}
                    {/*<a className="zrdn-button zrdn-is-small" id="upload-btn" href="#">Add Image</a>*/}
                    {/*</div>*/}
                    {/*<div id="recipe-image-preview-container" style={{"display": "none"}}>*/}
                    {/*<img id="recipe-image-preview" src="" style={{"display": "block"}}/>*/}
                    {/*<a href="">Remove Image</a>*/}
                    {/*</div>*/}
                </div>
            </div>
            {/* Title and image end --> */}
        </div>);
    })),
    // edit: withState({
    //     title: '',
    //     loading: false
    // })(({attributes, setAttributes, loading, title, setState, className}) => {
    //     if (attributes.id) {
    //         setState({
    //             loading: true
    //         });
    //
    //         apiFetch({path: '/zip-recipes/v1/recipe/2'}).then(recipe => {
    //             return setState(
    //                 {
    //                     loading: false,
    //                     title: recipe.title,
    //                     // description: recipe.description,
    //                     // // prepTimeHours: recipe.prep_time,
    //                     // servingSize: recipe.servingSize,
    //                     // category: recipe.category,
    //                     // cuisine: recipe.cuisine,
    //                     // ingredients: recipe.ingredients.join('\n'),
    //                     // instructions: recipe.instructions.join('\n'),
    //                     // notes: recipe.notes
    //                 }
    //             );
    //         });
    //     }
    //
    //     let loadingComponent = loading ? <div>Loading...</div> : '';
    //
    //     // if id in attributes:
    //     //  set state.loading = true
    //     //  fetch recipe with attributes.id from API
    //     //  call set state with new recipe props
    //     // else:
    //     //  render blank recipe form
    //
    //
    //     let uploadButton = (obj) => {
    //         return <components.Button>
    //             Hello
    //         </components.Button>;
    //     };
    //
    //     return (
    //         <div className={className}>
    //             {loadingComponent}
    //             {/* Title and image start --> */}
    //             <div className="zrdn-columns zrdn-is-mobile">
    //                 <div className="zrdn-column zrdn-is-three-quarters-tablet zrdn-is-two-thirds-mobile">
    //                     <div className="zrdn-field">
    //                         <div className="zrdn-control" id="title-container">
    //                             <input id="recipe-title" name='recipe_title' className="zrdn-input zrdn-is-size-3"
    //                                    type="text" value={title} onChange={function (event) {
    //                                 setState({title: event.target.value});
    //                             }}
    //                                    placeholder={i18n.__('Recipe Title…', 'gutenberg-examples')}/>
    //                         </div>
    //                     </div>
    //                 </div>
    //                 <div className="zrdn-column">
    //                     <label className="zrdn-label">Image</label>
    //                     {uploadButton}
    //                     {/*/!* {% if is_featured_post_image %} TODO: fix this *!/*/}
    //                     {/*<input type='hidden' id="recipe_image" name='recipe_image' value=''/>*/}
    //                     {/*/!*{% else %} *!/*/}
    //                     {/*/!*<input type='hidden' id="recipe_image" name='recipe_image' value=''/>*!/*/}
    //                     {/*/!*{% endif %}*!/*/}
    //                     {/*<div id="upload-recipe-image-button-container">*/}
    //                     {/*<a className="zrdn-button zrdn-is-small" id="upload-btn" href="#">Add Image</a>*/}
    //                     {/*</div>*/}
    //                     {/*<div id="recipe-image-preview-container" style={{"display": "none"}}>*/}
    //                     {/*<img id="recipe-image-preview" src="" style={{"display": "block"}}/>*/}
    //                     {/*<a href="">Remove Image</a>*/}
    //                     {/*</div>*/}
    //                 </div>
    //             </div>
    //             {/* Title and image end --> */}
    //         </div>);
    // }),
    // edit: function (props) {
    //     var attributes = props.attributes;
    //
    //     apiFetch( { path: '/zip-recipes/v1/recipe/2' } ).then( recipe => {
    //         return props.setAttributes(
    //             {
    //                 title: recipe.title,
    //                 description: recipe.description,
    //                 // prepTimeHours: recipe.prep_time,
    //                 servingSize: recipe.servingSize,
    //                 category: recipe.category,
    //                 cuisine: recipe.cuisine,
    //                 ingredients: recipe.ingredients.join('\n'),
    //                 instructions: recipe.instructions.join('\n'),
    //                 notes: recipe.notes
    //             }
    //         );
    //     } );
    //
    //     var onSelectImage = function (media) {
    //         return props.setAttributes({
    //             mediaURL: media.url,
    //             mediaID: media.id,
    //         });
    //     };
    //
    //     let insideButton = !attributes.mediaID ? i18n.__('Upload Image', 'gutenberg-examples') :
    //         <img src={attributes.mediaURL}/>;
    //
    //     let uploadButton = (obj) => {
    //         return <components.Button>
    //             Hello
    //         </components.Button>;
    //     };
    //
    //     return (
    //         <div className={props.className}>
    //             {/* Title and image start --> */}
    //             <div className="zrdn-columns zrdn-is-mobile">
    //                 <div className="zrdn-column zrdn-is-three-quarters-tablet zrdn-is-two-thirds-mobile">
    //                     <div className="zrdn-field">
    //                         <div className="zrdn-control" id="title-container">
    //                             <input id="recipe-title" name='recipe_title' className="zrdn-input zrdn-is-size-3"
    //                                    type="text" value={attributes.title} onChange={function (event) {
    //                                 props.setAttributes({title: event.target.value});
    //                             }}
    //                                    placeholder={i18n.__('Recipe Title…', 'gutenberg-examples')}/>
    //                         </div>
    //                     </div>
    //                 </div>
    //                 <div className="zrdn-column">
    //                     <label className="zrdn-label">Image</label>
    //                     {uploadButton}
    //                     {/*/!* {% if is_featured_post_image %} TODO: fix this *!/*/}
    //                     {/*<input type='hidden' id="recipe_image" name='recipe_image' value=''/>*/}
    //                     {/*/!*{% else %} *!/*/}
    //                     {/*/!*<input type='hidden' id="recipe_image" name='recipe_image' value=''/>*!/*/}
    //                     {/*/!*{% endif %}*!/*/}
    //                     {/*<div id="upload-recipe-image-button-container">*/}
    //                         {/*<a className="zrdn-button zrdn-is-small" id="upload-btn" href="#">Add Image</a>*/}
    //                     {/*</div>*/}
    //                     {/*<div id="recipe-image-preview-container" style={{"display": "none"}}>*/}
    //                         {/*<img id="recipe-image-preview" src="" style={{"display": "block"}}/>*/}
    //                         {/*<a href="">Remove Image</a>*/}
    //                     {/*</div>*/}
    //                 </div>
    //             </div>
    //             {/* Title and image end --> */}
    //
    //             <div id="ingredients-container" className="zrdn-field">
    //                 <label className="zrdn-label" htmlFor="ingredients">Ingredients</label>
    //                 <p className="zrdn-help">
    //                     Put each ingredient on a separate line. There is no need to use bullets for your
    //                     ingredients.<br/>
    //                     You can also create labels, hyperlinks, bold/italic effects and even add images!<br/>
    //                     <a href="https://www.ziprecipes.net/docs/installing/" target="_blank">Learn how here</a>
    //                 </p>
    //                 <div className="zrdn-control">
    //                     <textarea className="zrdn-textarea clean-on-paste" name='ingredients' onChange={function (event) {
    //                         props.setAttributes({ingredients: event.target.value});
    //                     }}
    //                               id='ingredients'>{attributes.ingredients}</textarea>
    //                 </div>
    //             </div>
    //
    //             <div className="zrdn-field">
    //                 <label className="zrdn-label" htmlFor="instructions">Instructions</label>
    //                 <p className="zrdn-help">
    //                     Press return after each instruction. There is no need to number your instructions.<br/>
    //                     You can also create labels, hyperlinks, bold/italic effects and even add images!<br/>
    //                     <a href="https://www.ziprecipes.net/docs/installing/" target="_blank">Learn how here</a>
    //                 </p>
    //                 <div className="zrdn-control">
    //                     <textarea className="zrdn-textarea clean-on-paste" id="instructions" onChange={function (event) {
    //                         props.setAttributes({instructions: event.target.value});
    //                     }}
    //                               name='instructions'>{attributes.instructions}</textarea>
    //                 </div>
    //             </div>
    //
    //             <div className="zrdn-columns zrdn-is-mobile">
    //                 <div className="zrdn-column">
    //                     <div className="zrdn-field">
    //                         <label htmlFor="category" className="zrdn-label">Category</label>
    //                         <div className="zrdn-control">
    //                             <input className="zrdn-input zrdn-is-small" id="category" onChange={function (event) {
    //                                 props.setAttributes({category: event.target.value});
    //                             }}
    //                                    placeholder="e.g. appetizer, entree, etc." type='text' name='category' value={attributes.category}/>
    //                         </div>
    //                     </div>
    //                 </div>
    //                 <div className="zrdn-column">
    //                     <div className="zrdn-field">
    //                         <label htmlFor="cuisine" className="zrdn-label">Cuisine</label>
    //                         <div className="zrdn-control">
    //                             <input className="zrdn-input zrdn-is-small" placeholder="e.g. French, Ethiopian, etc."
    //                                    onChange={function (event) {
    //                                        props.setAttributes({cuisine: event.target.value});
    //                                    }}
    //                                    type='text' id="cuisine" name='cuisine' value={attributes.cuisine} />
    //                         </div>
    //                     </div>
    //                 </div>
    //             </div>
    //
    //             <div className="zrdn-field">
    //                 <label className="zrdn-label" htmlFor="summary">Description</label>
    //                 <div className="zrdn-control">
    //                     <textarea className="zrdn-textarea" id='summary' name='summary'
    //                               onChange={function (event) {
    //                                   props.setAttributes({description: event.target.value});
    //                               }}
    //                               data-caption="true">{attributes.description}</textarea>
    //                 </div>
    //             </div>
    //
    //             <div className="zrdn-columns zrdn-is-tablet">
    //                 <div className="zrdn-column">
    //                     <label htmlFor="prep_hours" className="zrdn-label">Prep Time</label>
    //                     <div className="zrdn-field zrdn-is-grouped">
    //                         <div>
    //                             <input className="zrdn-input zrdn-is-small" type='number' min="0" id="prep_hours"
    //                                    onChange={function (event) {
    //                                        props.setAttributes({prepTimeHours: event.target.value});
    //                                    }}
    //                                    name='prep_time_hours' value={attributes.prepTimeHours} />hours
    //                         </div>
    //                         <div>
    //                             <input className="zrdn-input zrdn-is-small" type='number' min="0" id="prep_minutes"
    //                                    onChange={function (event) {
    //                                        props.setAttributes({prepTimeMinutes: event.target.value});
    //                                    }}
    //                                    name='prep_time_minutes' value={attributes.prepTimeMinutes}/>minutes
    //                         </div>
    //                     </div>
    //                 </div>
    //                 <div className="zrdn-column">
    //                     <label htmlFor="cook_hours" className="zrdn-label">Cook Time</label>
    //                     <div className="zrdn-field zrdn-is-grouped">
    //                         <div>
    //                             <input className="zrdn-input zrdn-is-small" type='number' min="0" id="cook_hours"
    //                                    onChange={function (event) {
    //                                        props.setAttributes({cookTimeHours: event.target.value});
    //                                    }}
    //                                    name='cook_time_hours' value={attributes.cookTimeHours} />hours
    //                         </div>
    //                         <div>
    //                             <input className="zrdn-input zrdn-is-small" type='number' min="0" id="cook_minutes"
    //                                    onChange={function (event) {
    //                                        props.setAttributes({cookTimeMinutes: event.target.value});
    //                                    }}
    //                                    name='cook_time_minutes' value={attributes.cookTimeMinutes} />minutes
    //                         </div>
    //                     </div>
    //                 </div>
    //             </div>
    //
    //             <div className="zrdn-field">
    //                 <label className="zrdn-label" htmlFor="notes">Notes</label>
    //                 <div className="zrdn-control">
    //                     <textarea className="zrdn-textarea" id="notes" name='notes'
    //                               onChange={function (event) {
    //                                   props.setAttributes({notes: event.target.value});
    //                               }}>{attributes.notes}</textarea>
    //                 </div>
    //             </div>
    //
    //             <InspectorControls>
    //                 <div className="zrdn-field">
    //                     <label className="zrdn-label" htmlFor="serving_size">Serving Size</label>
    //                     <div className="zrdn-control">
    //                         <input className="zrdn-input zrdn-is-small" id="serving_size"
    //                                type='text'
    //                                onChange={function (event) {
    //                                    props.setAttributes({servingSize: event.target.value});
    //                                }}
    //                                name='serving_size' value={attributes.servingSize} />
    //                     </div>
    //                 </div>
    //
    //                 <div className="zrdn-columns zrdn-is-gapless zrdn-is-mobile">
    //                     <div className="zrdn-column">
    //                         <div className="zrdn-field-label">
    //                             <label className="zrdn-label" htmlFor="calories">Calories</label>
    //                         </div>
    //                     </div>
    //                     <div className="zrdn-column">
    //                         <div className="zrdn-field-body">
    //                             <div className="zrdn-field zrdn-is-narrow">
    //                                 <div className="zrdn-control">
    //                                     <input className="zrdn-input zrdn-is-small" type='text' id="calories"
    //                                            name='calories'
    //                                            onChange={function (event) {
    //                                                props.setAttributes({calories: event.target.value});
    //                                            }}
    //                                            value={attributes.calories} />
    //                                 </div>
    //                             </div>
    //                         </div>
    //                     </div>
    //                 </div>
    //
    //                 <div className="zrdn-columns zrdn-is-gapless zrdn-is-mobile">
    //                     <div className="zrdn-column">
    //                         <div className="zrdn-field-label">
    //                             <label className="zrdn-label" htmlFor="carbs">Carbs</label>
    //                         </div>
    //                     </div>
    //                     <div className="zrdn-column">
    //                         <div className="zrdn-field-body">
    //                             <div className="zrdn-field zrdn-is-narrow">
    //                                 <div className="zrdn-control">
    //                                     <input className="zrdn-input zrdn-is-small" type='text' id='carbs'
    //                                            name='carbs'
    //                                            onChange={function (event) {
    //                                                props.setAttributes({carbs: event.target.value});
    //                                            }}
    //                                            value={attributes.carbs} />
    //                                 </div>
    //                             </div>
    //                         </div>
    //                     </div>
    //                 </div>
    //
    //                 <div className="zrdn-columns zrdn-is-gapless zrdn-is-mobile">
    //                     <div className="zrdn-column">
    //                         <div className="zrdn-field-label">
    //                             <label className="zrdn-label" htmlFor="protein">Protein</label>
    //                         </div>
    //                     </div>
    //                     <div className="zrdn-column">
    //                         <div className="zrdn-field-body">
    //                             <div className="zrdn-field zrdn-is-narrow">
    //                                 <div className="zrdn-control">
    //                                     <input className="zrdn-input zrdn-is-small" type='text' id="protein"
    //                                            name='protein'
    //                                            onChange={function (event) {
    //                                                props.setAttributes({protein: event.target.value});
    //                                            }}
    //                                            value={attributes.protein} />
    //                                 </div>
    //                             </div>
    //                         </div>
    //                     </div>
    //                 </div>
    //
    //                 <div className="zrdn-columns zrdn-is-gapless zrdn-is-mobile">
    //                     <div className="zrdn-column">
    //                         <div className="zrdn-field-label">
    //                             <label className="zrdn-label" htmlFor="fiber">Fiber</label>
    //                         </div>
    //                     </div>
    //                     <div className="zrdn-column">
    //                         <div className="zrdn-field-body">
    //                             <div className="zrdn-field zrdn-is-narrow">
    //                                 <div className="zrdn-control">
    //                                     <input className="zrdn-input zrdn-is-small" type='text' id="fiber"
    //                                            name='fiber'
    //                                            onChange={function (event) {
    //                                                props.setAttributes({fiber: event.target.value});
    //                                            }}
    //                                            value={attributes.fiber} />
    //                                 </div>
    //                             </div>
    //                         </div>
    //                     </div>
    //                 </div>
    //
    //                 <div className="zrdn-columns zrdn-is-gapless zrdn-is-mobile">
    //                     <div className="zrdn-column">
    //                         <div className="zrdn-field-label">
    //                             <label className="zrdn-label" htmlFor="sugar">Sugar</label>
    //                         </div>
    //                     </div>
    //                     <div className="zrdn-column">
    //                         <div className="zrdn-field-body">
    //                             <div className="zrdn-field zrdn-is-narrow">
    //                                 <div className="zrdn-control">
    //                                     <input className="zrdn-input zrdn-is-small" type='text' id="sugar"
    //                                            name='sugar'
    //                                            onChange={function (event) {
    //                                                props.setAttributes({sugar: event.target.value});
    //                                            }}
    //                                            value={attributes.sugar} />
    //                                 </div>
    //                             </div>
    //                         </div>
    //                     </div>
    //                 </div>
    //
    //                 <div className="zrdn-columns zrdn-is-gapless zrdn-is-mobile">
    //                     <div className="zrdn-column">
    //                         <div className="zrdn-field-label">
    //                             <label className="zrdn-label" htmlFor="sodium">Sodium</label>
    //                         </div>
    //                     </div>
    //                     <div className="zrdn-column">
    //                         <div className="zrdn-field-body">
    //                             <div className="zrdn-field zrdn-is-narrow">
    //                                 <div className="zrdn-control">
    //                                     <input className="zrdn-input zrdn-is-small" type='text' id="sodium"
    //                                            name='sodium'
    //                                            onChange={function (event) {
    //                                                props.setAttributes({sodium: event.target.value});
    //                                            }}
    //                                            value={attributes.sodium} />
    //                                 </div>
    //                             </div>
    //                         </div>
    //                     </div>
    //                 </div>
    //
    //                 <div className="zrdn-columns zrdn-is-gapless zrdn-is-mobile">
    //                     <div className="zrdn-column">
    //                         <div className="zrdn-field-label">
    //                             <label className="zrdn-label" htmlFor="fat">Fat</label>
    //                         </div>
    //                     </div>
    //                     <div className="zrdn-column">
    //                         <div className="zrdn-field-body">
    //                             <div className="zrdn-field zrdn-is-narrow">
    //                                 <div className="zrdn-control">
    //                                     <input className="zrdn-input zrdn-is-small" type='text' id="fat" name='fat'
    //                                            onChange={function (event) {
    //                                                props.setAttributes({fat: event.target.value});
    //                                            }}
    //                                            value={attributes.fat} />
    //                                 </div>
    //                             </div>
    //                         </div>
    //                     </div>
    //                 </div>
    //
    //                 <div className="zrdn-columns zrdn-is-gapless zrdn-is-mobile">
    //                     <div className="zrdn-column">
    //                         <div className="zrdn-field-label zrdn-is-left">
    //                             <label className="zrdn-label" htmlFor="saturated_fat">Saturated Fat</label>
    //                         </div>
    //                     </div>
    //                     <div className="zrdn-column">
    //                         <div className="zrdn-field-body">
    //                             <div className="zrdn-field zrdn-is-narrow">
    //                                 <div className="zrdn-control">
    //                                     <input className="zrdn-input zrdn-is-small" type='text' id="saturated_fat"
    //                                            onChange={function (event) {
    //                                                props.setAttributes({saturatedFat: event.target.value});
    //                                            }}
    //                                            name='saturated_fat' value={attributes.saturatedFat} />
    //                                 </div>
    //                             </div>
    //                         </div>
    //                     </div>
    //                 </div>
    //
    //
    //                 <div className="zrdn-columns zrdn-is-gapless zrdn-is-mobile">
    //                     <div className="zrdn-column">
    //                         <div className="zrdn-field-label zrdn-is-left">
    //                             <label className="zrdn-label" htmlFor="trans_fat">Trans. Fat</label>
    //                         </div>
    //                     </div>
    //                     <div className="zrdn-column">
    //                         <div className="zrdn-field-body">
    //                             <div className="zrdn-field zrdn-is-narrow">
    //                                 <div className="zrdn-control">
    //                                     <input className="zrdn-input zrdn-is-small" type='text' id="trans_fat"
    //                                            onChange={function (event) {
    //                                                props.setAttributes({transFat: event.target.value});
    //                                            }}
    //                                            name='trans_fat' value={attributes.transFat} />
    //                                 </div>
    //                             </div>
    //                         </div>
    //                     </div>
    //                 </div>
    //
    //
    //                 <div className="zrdn-columns zrdn-is-gapless zrdn-is-mobile">
    //                     <div className="zrdn-column">
    //                         <div className="zrdn-field-label zrdn-is-left">
    //                             <label className="zrdn-label" htmlFor="cholesterol">Cholesterol</label>
    //                         </div>
    //                     </div>
    //                     <div className="zrdn-column">
    //                         <div className="zrdn-field-body">
    //                             <div className="zrdn-field zrdn-is-narrow">
    //                                 <div className="zrdn-control">
    //                                     <input className="zrdn-input zrdn-is-small" type='text' id="cholesterol"
    //                                            onChange={function (event) {
    //                                                props.setAttributes({cholesterol: event.target.value});
    //                                            }}
    //                                            name='cholesterol' value={attributes.cholesterol} />
    //                                 </div>
    //                             </div>
    //                         </div>
    //                     </div>
    //                 </div>
    //             </InspectorControls>
    //         </div>
    //     );
    // },

    // edit: withSelect( function( select ) {
    //     return {
    //         posts: select( 'core' ).getEntityRecords( 'postType', 'post' )
    //     };
    // } )( function( props ) {
    //
    //     if ( ! props.posts ) {
    //         return "Loading...";
    //     }
    //
    //     if ( props.posts.length === 0 ) {
    //         return "No posts";
    //     }
    //     let className = props.className;
    //     let post = props.posts[ 0 ];
    //
    //     return <a className={className} href={post.link}>{post.title.rendered}</a>;
    // } ),

    save: async function (attributes) {
        console.log("in Save L 941");
        const { getTitle }  = wp.data.select('zip-recipes-store');
        let actions = wp.data.dispatch('zip-recipes-store');
        await actions.saveRecipe(2, getTitle());
    },

    //
    // edit: function( props ) {
    //     let url = props.attributes.url || '',
    //         focus = props.focus;
    //     console.log(url,'======', props.attributes);
    //     // retval is our return value for the callback.
    //     let retval = [];
    //     // When the block is focus or there's no URL value,
    //     // show the text input control so the user can enter a URL.
    //     if ( !! focus || ! url.length ) {
    //         // Instantiate a TextControl element
    //         let controlOptions = {
    //             // Existing 'url' value for the block.
    //             value: url,
    //             // When the text input value is changed, we need to
    //             // update the 'url' attribute to propagate the change.
    //             onChange: function( newVal ) {
    //                 props.setAttributes({
    //                     url: newVal
    //                 });
    //             },
    //             placeholder: __( 'Enter a GitHub Gist URL' ),
    //         };
    //         retval.push(
    //             // el() is a function to instantiate a new element.
    //             el( Components.TextControl, controlOptions )
    //         );
    //     }
    //     // Only add preview UI when there's a URL entered.
    //     if ( url.length ) {
    //         let id = 'gist-' + props.id;
    //         // setTimeout is used to delay the GitHub JSON API request
    //         // until after the block is initially rendered. From the response,
    //         // we update the rendered div.
    //         setTimeout(function(){
    //             jQuery.getJSON( url.trim(/\/$/) + '.json?callback=?',
    //                 function(data){
    //                     let div = jQuery('#'+id);
    //                     div.html('');
    //                     let stylesheet = jQuery('<link />');
    //                     stylesheet.attr('ref', 'stylesheet');
    //                     stylesheet.attr('href', data.stylesheet);
    //                     stylesheet.attr('type', 'text/css');
    //                     div.append(stylesheet);
    //                     div.append(data.div);
    //                 }
    //             );
    //         }, 10 );
    //         retval.push( el( 'div', { id: id } ) );
    //     }
    //     return retval;
    // },
    //
    //
    // save: function( props ) {
    //     let url = props.attributes.url || '';
    //     // If there's no URL, don't save any inline HTML.
    //     if ( ! url.length ) {
    //         return null;
    //     }
    //     // Include a fallback link for non-JS contexts
    //     // and for when the plugin is not activated.
    //     //  return el( 'a', { href: url }, __( 'View Gist on GitHub' ) );
    // }
});
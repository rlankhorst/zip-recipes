const {data, apiFetch} = wp;
const {registerStore, withSelect, withDispatch, select} = data;

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
const SET_ID = 'SET_ID';
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

        * saveRecipe({id, post_id, title, create = false}) {
            const recipe = yield {
                type: RECIPE_SAVE_REQUEST,
                id,
                title,
                create,
                post_id
            };

            actions.setTitle(title);

            return recipe;
        }
        ,

        setRecipeSaving() {
            return {
                type: RECIPE_SAVING
            }
        }
        ,

        saveRecipeSuccess(recipe) {
            return {
                type: RECIPE_SAVE_SUCCESS,
                recipe
            };
        }
        ,

        setId(id) {
            return {
                type: SET_ID,
                id
            };
        }
        ,

        setTitle(title) {
            return {
                type: SET_TITLE,
                title
            };
        }
        ,

        setImageUrl(url) {
            return {
                type: SET_IMAGE_URL,
                url
            }
        }
        ,

        setDescription(description) {
            return {
                type: SET_DESCRIPTION,
                description
            }
        }
        ,

        setPrepTime(prepTime) {
            return {
                type: SET_PREP_TIME,
                prepTime
            }
        }
        ,

        setCookTime(cookTime) {
            return {
                type: SET_COOK_TIME,
                cookTime
            }
        }
        ,

        setServings(servings) {
            return {
                type: SET_SERVINGS,
                servings
            }
        }
        ,

        setCategory(category) {
            return {
                type: SET_CATEGORY,
                category
            }
        }
        ,

        setCuisine(cuisine) {
            return {
                type: SET_CUISINE,
                cuisine
            }
        }
        ,

        setIngredients(ingredients) {
            return {
                type: SET_INGREDIENTS,
                ingredients
            }
        }
        ,

        setInstructions(instructions) {
            return {
                type: SET_INSTRUCTIONS,
                instructions
            }
        }
        ,

        setNotes(notes) {
            return {
                type: SET_NOTES,
                notes
            }
        }
        ,

        postToAPI(path) {
            return {
                type: 'POST_TO_API',
                path,
            };
        }
        ,

        fetchFromAPI(path) {
            return {
                type: 'FETCH_FROM_API',
                path,
            };
        }
        ,
    }
;

registerStore('zip-recipes-store', {
    reducer(state = DEFAULT_STATE, action) {
        switch (action.type) {
            case RECIPE_REQUEST:
                return {
                    ...state,
                    isFetching:
                        true
                }
                    ;
            case
            RECIPE_REQUEST_SUCCESS:
                return {
                    ...state,
                    isFetching:
                        false,
                    recipe:
                    action.recipe
                }
                    ;
            case
            RECIPE_SAVING:
                return {
                    ...state,
                    isSaving:
                        true
                }
                    ;
            case
            RECIPE_SAVE_SUCCESS:
                return {
                    ...state,
                    lastSavedAt:
                        Date.now(),
                    isSaving:
                        false
                }
                    ;
            case
            SET_ID:
                return {
                    ...state,
                    id:
                    action.id
                }
                    ;
            case
            SET_TITLE:
                return {
                    ...state,
                    recipe:
                        {
                            ...
                                state.recipe, title:
                            action.title
                        }
                    ,
                    isDirty: true
                }
                    ;
            case
            SET_IMAGE_URL:
                return {
                    ...state,
                    recipe:
                        {
                            ...
                                state.recipe, imageUrl:
                            action.imageUrl
                        }
                }
                    ;
            case
            SET_DESCRIPTION:
                return {
                    ...state,
                    recipe:
                        {
                            ...
                                state.recipe, description:
                            action.description
                        }
                }
                    ;
            case
            SET_PREP_TIME:
                return {
                    ...state,
                    recipe:
                        {
                            ...
                                state.recipe, prepTime:
                            action.prepTime
                        }
                }
                    ;
            case
            SET_COOK_TIME:
                return {
                    ...state,
                    recipe:
                        {
                            ...
                                state.recipe, cookTime:
                            action.cookTime
                        }
                }
                    ;
            case
            SET_SERVINGS:
                return {
                    ...state,
                    recipe:
                        {
                            ...
                                state.recipe, servings:
                            action.servings
                        }
                }
                    ;
            case
            SET_CATEGORY:
                return {
                    ...state,
                    recipe:
                        {
                            ...
                                state.recipe, category:
                            action.category
                        }
                }
                    ;
            case
            SET_CUISINE:
                return {
                    ...state,
                    recipe:
                        {
                            ...
                                state.recipe, cuisine:
                            action.cuisine
                        }
                }
                    ;
            case
            SET_INGREDIENTS:
                return {
                    ...state,
                    recipe:
                        {
                            ...
                                state.recipe, ingredients:
                            action.ingredients
                        }
                }
                    ;
            case
            SET_INSTRUCTIONS:
                return {
                    ...state,
                    recipe:
                        {
                            ...
                                state.recipe, instructions:
                            action.instructions
                        }
                }
                    ;
            case
            SET_NOTES:
                return {
                    ...state,
                    recipe:
                        {
                            ...
                                state.recipe, notes:
                            action.notes
                        }
                }
                    ;
        }

        return state;
    },

    actions,

    selectors: {
        getId(state) {
            const {id} = state.recipe;
            return id;
        },
        getTitle(state, id) {
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
            let recipe = {};
            if (action.create && action.title && action.post_id) {
                recipe = apiFetch({
                    path: `/zip-recipes/v1/recipe`, method: 'POST', data: {
                        title: action.title,
                        post_id: action.post_id
                    }
                });
            }
            else if (action.id) {
                recipe = apiFetch({
                    path: `/zip-recipes/v1/recipe/${action.id}`, method: 'POST', data: {
                        title: action.title
                    }
                });
            }

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
        * getTitle(id) {
            if (id) {
                const path = `/zip-recipes/v1/recipe/${id}`;
                const recipe = yield actions.fetchFromAPI(path);
                if (recipe) {
                    return actions.setTitle(recipe.title); // this must return
                }
            }
        }
    }
});

export default {actions};

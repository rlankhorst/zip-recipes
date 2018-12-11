const {data, apiFetch} = wp;
const {registerStore, withSelect, withDispatch, select} = data;

const DEFAULT_STATE = {
  recipe: {
    id: '',
    post_id: '',
    title: '',
    image_url: '',
    description: '',
    prep_time: '',
    cook_time: '',
    yield: '',
    category: '',
    cuisine: '',
    ingredients: '',
    instructions: '',
    notes: '',
    nutrition: {
      calories: '',
      carbs: '',
      protein: '',
      fiber: '',
      sugar: '',
      sodium: '',
      fat: '',
      saturated_fat: '',
      trans_fat: '',
      cholesterol: '',
    },
  },
  isFetching: false,
  isSaving: false,
};

const RECIPE_REQUEST = 'RECIPE_REQUEST';
const RECIPE_REQUEST_SUCCESS = 'RECIPE_REQUEST_SUCCESS';
const RECIPE_SAVE_REQUEST = 'RECIPE_SAVE_REQUEST';
const RECIPE_SAVING = 'RECIPE_SAVING';
const RECIPE_SAVE_SUCCESS = 'RECIPE_SAVE_SUCCESS';
const SET_RECIPE = 'SET_RECIPE';
const SET_ID = 'SET_ID';
const SET_TITLE = 'SET_TITLE';
const SET_IMAGE_URL = 'SET_IMAGE_URL';
const SET_DESCRIPTION = 'SET_DESCRIPTION';
const SET_PREP_TIME_HOURS = 'SET_PREP_TIME_HOURS';
const SET_PREP_TIME_MINUTES = 'SET_PREP_TIME_MINUTES';
const SET_COOK_TIME_HOURS = 'SET_COOK_TIME_HOURS';
const SET_COOK_TIME_MINUTES = 'SET_COOK_TIME_MINUTES';
const SET_CATEGORY = 'SET_CATEGORY';
const SET_CUISINE = 'SET_CUISINE';
const SET_INGREDIENTS = 'SET_INGREDIENTS';
const SET_INSTRUCTIONS = 'SET_INSTRUCTIONS';
const SET_NOTES = 'SET_NOTES';
const SET_SERVINGS = 'SET_SERVINGS';
const SET_CALORIES = 'SET_CALORIES';
const SET_CARBS = 'SET_CARBS';
const SET_PROTEIN = 'SET_PROTEIN';
const SET_FIBER = 'SET_FIBER';
const SET_SUGAR = 'SET_SUGAR';
const SET_SODIUM = 'SET_SODIUM';
const SET_FAT = 'SET_FAT';
const SET_SATURATED_FAT = 'SET_SATURATED_FAT';
const SET_TRANS_FAT = 'SET_TRANS_FAT';
const SET_CHOLESTEROL = 'SET_CHOLESTEROL';

// These are action creators, actually
const actions = {
  requestRecipe (id) {
    return {
      type: RECIPE_REQUEST,
      id,
    };
  },

  requestRecipeSuccess (recipe) {
    return {
      type: RECIPE_REQUEST_SUCCESS,
      recipe,
    };
  },

  *saveRecipe({create = false, recipe}) {
    const newRecipe = yield {
      type: RECIPE_SAVE_REQUEST,
      create,
      recipe: {...recipe},
    };

    // IS this even needed?
    // if (recipe.title) {
    //     actions.setTitle (newRecipe.title);
    // }

    return newRecipe;
  },

  setRecipeSaving () {
    return {
      type: RECIPE_SAVING,
    };
  },
  saveRecipeSuccess (recipe) {
    return {
      type: RECIPE_SAVE_SUCCESS,
      recipe,
    };
  },
  setId (id) {
    return {
      type: SET_ID,
      id,
    };
  },
  setTitle (title) {
    return {
      type: SET_TITLE,
      title,
    };
  },
  setImageUrl (url) {
    return {
      type: SET_IMAGE_URL,
      url,
    };
  },
  setDescription (description) {
    return {
      type: SET_DESCRIPTION,
      description,
    };
  },
  setPrepTimeHours (prepTimeHours) {
    return {
      type: SET_PREP_TIME_HOURS,
      prepTimeHours,
    };
  },
  setPrepTimeMinutes (prepTimeMinutes) {
    return {
      type: SET_PREP_TIME_MINUTES,
      prepTimeMinutes,
    };
  },
  setCookTimeHours (cookTimeHours) {
    return {
      type: SET_COOK_TIME_HOURS,
      cookTimeHours,
    };
  },
  setCookTimeMinutes (cookTimeMinutes) {
    return {
      type: SET_COOK_TIME_MINUTES,
      cookTimeMinutes,
    };
  },
  setCategory (category) {
    return {
      type: SET_CATEGORY,
      category,
    };
  },
  setCuisine (cuisine) {
    return {
      type: SET_CUISINE,
      cuisine,
    };
  },
  setIngredients (ingredients) {
    return {
      type: SET_INGREDIENTS,
      ingredients,
    };
  },
  setInstructions (instructions) {
    return {
      type: SET_INSTRUCTIONS,
      instructions,
    };
  },
  setNotes (notes) {
    return {
      type: SET_NOTES,
      notes,
    };
  },
  setServings (servings) {
    return {
      type: SET_SERVINGS,
      servings,
    };
  },
  setCalories (servingSize) {
    return {
      type: SET_CALORIES,
      servingSize,
    };
  },
  setCarbs (carbs) {
    return {
      type: SET_CARBS,
      carbs,
    };
  },
  setProtein (protein) {
    return {
      type: SET_PROTEIN,
      protein,
    };
  },
  setFiber (fiber) {
    return {
      type: SET_FIBER,
      fiber,
    };
  },
  setSugar (sugar) {
    return {
      type: SET_SUGAR,
      sugar,
    };
  },
  setSodium (sodium) {
    return {
      type: SET_SODIUM,
      sodium,
    };
  },
  setFat (fat) {
    return {
      type: SET_FAT,
      fat,
    };
  },
  setSaturatedFat (saturatedFat) {
    return {
      type: SET_SATURATED_FAT,
      saturatedFat,
    };
  },
  setTransFat (transFat) {
    return {
      type: SET_TRANS_FAT,
      transFat,
    };
  },
  setCholesterol (cholesterol) {
    return {
      type: SET_CHOLESTEROL,
      cholesterol,
    };
  },
  fetchFromAPI (path) {
    return {
      type: 'FETCH_FROM_API',
      path,
    };
  },
};

registerStore ('zip-recipes-store', {
  reducer (state = DEFAULT_STATE, action) {
    switch (action.type) {
      case RECIPE_REQUEST:
        return {
          ...state,
          isFetching: true,
        };
      case RECIPE_REQUEST_SUCCESS:
        return {
          ...state,
          isFetching: false,
          recipe: action.recipe,
        };
      case RECIPE_SAVING:
        return {
          ...state,
          isSaving: true,
        };
      case RECIPE_SAVE_SUCCESS:
        return {
          ...state,
          lastSavedAt: Date.now (),
          isSaving: false,
        };
      case SET_RECIPE:
        return {
          ...state,
          recipe: action.recipe,
        };
      case SET_ID:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            id: action.id,
          },
        };
      case SET_TITLE:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            title: action.title,
          },
        };
      case SET_IMAGE_URL:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            imageUrl: action.imageUrl,
          },
        };
      case SET_DESCRIPTION:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            description: action.description,
          },
        };
      // TODO: figure out what to do with ours and how we're storing them
      //   case SET_PREP_TIME_HOURS:
      //     return {
      //       ...state,
      //       recipe: {
      //         ...state.recipe,
      //         prepTime: action.prepTimeHours,
      //       },
      //     };
      //   case SET_COOK_TIME_HOURS:
      //     return {
      //       ...state,
      //       recipe: {
      //         ...state.recipe,
      //         cookTimeHours: action.cookTimeHours,
      //       },
      //     };
      case SET_SERVINGS:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            servings: action.servings,
          },
        };
      case SET_CATEGORY:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            category: action.category,
          },
        };
      case SET_CUISINE:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            cuisine: action.cuisine,
          },
        };
      case SET_INGREDIENTS:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            ingredients: action.ingredients,
          },
        };
      case SET_INSTRUCTIONS:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            instructions: action.instructions,
          },
        };
      case SET_NOTES:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            notes: action.notes,
          },
        };
      case SET_SERVINGS:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            servings: action.servings,
          },
        };
      case SET_CALORIES:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            nutrition: {
              ...state.recipe.nutrition,
              calories: action.calories,
            },
          },
        };
      case SET_CARBS:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            nutrition: {
              ...state.recipe.nutrition,
              carbs: action.carbs,
            },
          },
        };
      case SET_PROTEIN:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            nutrition: {
              ...state.recipe.nutrition,
              protein: action.protein,
            },
          },
        };
      case SET_FIBER:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            nutrition: {
              ...state.recipe.nutrition,
              fiber: action.fiber,
            },
          },
        };
      case SET_SUGAR:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            sugar: action.sugar,
          },
        };
      case SET_SODIUM:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            sodium: action.sodium,
          },
        };
      case SET_FAT:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            fat: action.fat,
          },
        };
      case SET_SATURATED_FAT:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            saturated_fat: action.saturatedFat,
          },
        };
      case SET_TRANS_FAT:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            trans_fat: action.transFat,
          },
        };
      case SET_CHOLESTEROL:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            cholesterol: action.cholesterol,
          },
        };
    }

    return state;
  },

  actions,

  selectors: {
    getRecipe (state, id) {
      const {recipe} = state;
      return recipe;
    },
    getCategory (state) {
      const {category} = state.recipe;
      return category;
    },
    getCuisine (state) {
      const {cuisine} = state.recipe;
      return cuisine;
    },
    getDescription (state) {
      const {description} = state.recipe;
      return description;
    },

    getId (state) {
      const {id} = state.recipe;
      return id;
    },
    getTitle (state) {
      const {title} = state.recipe;
      return title;
    },
    getNotes(state) {
      const {notes} = state.recipe;
      return notes;
    },
    getIsSaving (state) {
      const {isSaving} = state;
      return isSaving;
    },
  },

  controls: {
    RECIPE_SAVE_REQUEST (action) {
      let newRecipe = null;
      console.log ('L524 action:action:', action);
      if (action.create && action.recipe.title && action.recipe.post_id) {
        // title and post_id are required by API
        newRecipe = apiFetch ({
          path: `/zip-recipes/v1/recipe`,
          method: 'POST',
          data: {
            ...action.recipe,
          },
        });
      } else if (action.recipe.id) {
        newRecipe = apiFetch ({
          path: `/zip-recipes/v1/recipe/${action.recipe.id}`,
          method: 'POST',
          data: {
            ...action.recipe,
          },
        });
      }

      return newRecipe;
    },

    FETCH_FROM_API (action) {
      let recipe = apiFetch ({path: action.path});
      recipe.title += Date.now ();
      return recipe;
    },
  },

  resolvers: {
    *getRecipe (id) {
      if (id) {
        const path = `/zip-recipes/v1/recipe/${id}`;
        const recipe = yield actions.fetchFromAPI (path);
        yield actions.setTitle (recipe.title);
        yield actions.setDescription (recipe.description);
        yield actions.setCategory (recipe.category);
        yield actions.setCuisine (recipe.cuisine);
        yield actions.setIngredients (recipe.ingredients);
        yield actions.setInstructions (recipe.instructions);
        yield actions.setServings (recipe.yield); // yield is a reserved keyword in JS
        yield actions.setNotes (recipe.notes);
        yield actions.setCalories (recipe.calories);
        yield actions.setCarbs (recipe.nutrition.carbs);
        yield actions.setProtein (recipe.nutrition.protein);
        yield actions.setFiber (recipe.nutrition.fiber);
        yield actions.setSugar (recipe.nutrition.sugar);
        yield actions.setSodium (recipe.nutrition.sodium);
        yield actions.setFat (recipe.nutrition.fat);
        yield actions.setSaturatedFat (recipe.nutrition.saturated_fat);
        yield actions.setTransFat (recipe.nutrition.trans_fat);
        return actions.setCholesterol (recipe.nutrition.cholesterol);
      }
    },
  },
});

export default {actions};

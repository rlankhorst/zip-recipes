const {data, apiFetch} = wp;
const {registerStore, withSelect, withDispatch, select} = data;

const DEFAULT_STATE = {
  recipe: {
    id: '',
    post_id: '',
    title: '',
    image_url: '',
    is_featured_post_image: false,
    description: '',
    prep_time_hours: '',
    prep_time_minutes: '',
    cook_time_hours: '',
    cook_time_minutes: '',
    servings: '',
    serving_size: '',
    category: '',
    cuisine: '',
    ingredients: [],
    instructions: [],
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
const SEND_RECIPE = 'SEND_RECIPE';
const SAVE_IMAGE_REQUEST = 'SAVE_IMAGE_REQUEST';
const RECIPE_SAVING = 'RECIPE_SAVING';
const RECIPE_SAVE_SUCCESS = 'RECIPE_SAVE_SUCCESS';
const SET_RECIPE = 'SET_RECIPE';
const SET_ID = 'SET_ID';
const SET_TITLE = 'SET_TITLE';
const SET_IMAGE_URL = 'SET_IMAGE_URL';
const SET_IS_FEATURED_POST_IMAGE = 'SET_IS_FEATURED_POST_IMAGE';
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
const SET_SERVING_SIZE = 'SET_SERVING_SIZE';
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
  requestRecipe () {
    return {
      type: RECIPE_REQUEST
    };
  },

  requestRecipeSuccess (recipe) {
    return {
      type: RECIPE_REQUEST_SUCCESS
    };
  },

  *saveRecipe({create = false, recipe}) {
    const newRecipe = yield {
      type: SEND_RECIPE,
      create,
      recipe: {...recipe},
    };

    return newRecipe;
  },
  *saveImage (id, imageUrl) {
    const newRecipe = yield {
      type: SAVE_IMAGE_REQUEST,
      recipe: {
        id,
        image_url: imageUrl,
      },
    };

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
  setTitleFromPostTitle(postTitle) {
    return {
      type: SET_TITLE,
      title: postTitle,
    };
  },
  setImageUrl (url) {
    return {
      type: SET_IMAGE_URL,
      url,
    };
  },
  setIsFeaturedPostImage (isFeaturedPostImage) {
    return {
      type: SET_IS_FEATURED_POST_IMAGE,
      isFeaturedPostImage,
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
  setServingSize (servingSize) {
    return {
      type: SET_SERVING_SIZE,
      servingSize,
    };
  },
  setCalories (calories) {
    return {
      type: SET_CALORIES,
      calories,
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
          isFetching: false
        };
      case RECIPE_SAVING:
        return {
          ...state,
          isSaving: true,
        };
      case RECIPE_SAVE_SUCCESS:
        return {
          ...state,
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
            image_url: action.url,
          },
        };
      case SET_IS_FEATURED_POST_IMAGE:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            is_featured_post_image: action.isFeaturedPostImage,
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
      case SET_PREP_TIME_HOURS:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            prep_time_hours: action.prepTimeHours,
          },
        };
      case SET_PREP_TIME_MINUTES:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            prep_time_minutes: action.prepTimeMinutes,
          },
        };
      case SET_COOK_TIME_HOURS:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            cook_time_hours: action.cookTimeHours,
          },
        };
      case SET_COOK_TIME_MINUTES:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            cook_time_minutes: action.cookTimeMinutes,
          },
        };
      case SET_SERVING_SIZE:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            serving_size: action.servingSize,
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
        let ingredientsArray = action.ingredients;
        if (typeof action.ingredients == typeof '') {
          // string
          ingredientsArray = action.ingredients.split ('\n');
        }

        return {
          ...state,
          recipe: {
            ...state.recipe,
            ingredients: ingredientsArray,
          },
        };
      case SET_INSTRUCTIONS:
        let instructionsArray = action.instructions;
        if (typeof action.instructions == typeof '') {
          // string
          instructionsArray = action.instructions.split ('\n');
        }
        return {
          ...state,
          recipe: {
            ...state.recipe,
            instructions: instructionsArray,
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
            nutrition: {
              ...state.recipe.nutrition,
              sugar: action.sugar,
            },
          },
        };
      case SET_SODIUM:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            nutrition: {
              ...state.recipe.nutrition,
              sodium: action.sodium,
            },
          },
        };
      case SET_FAT:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            nutrition: {
              ...state.recipe.nutrition,
              fat: action.fat,
            },
          },
        };
      case SET_SATURATED_FAT:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            nutrition: {
              ...state.recipe.nutrition,
              saturated_fat: action.saturatedFat,
            },
          },
        };
      case SET_TRANS_FAT:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            nutrition: {
              ...state.recipe.nutrition,
              trans_fat: action.transFat,
            },
          },
        };
      case SET_CHOLESTEROL:
        return {
          ...state,
          recipe: {
            ...state.recipe,
            nutrition: {
              ...state.recipe.nutrition,
              cholesterol: action.cholesterol,
            },
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
    getNotes (state) {
      const {notes} = state.recipe;
      return notes;
    },
    getIngredients (state) {
      const {ingredients} = state.recipe;
      return ingredients;
    },
    getInstructions (state) {
      const {instructions} = state.recipe;
      return instructions;
    },
    getImageUrl (state) {
      const {image_url} = state.recipe;
      return image_url;
    },
    getIsFeaturedPostImage (state) {
      const {is_featured_post_image} = state.recipe;
      return is_featured_post_image;
    },
    getPrepTimeHours (state) {
      const {prep_time_hours} = state.recipe;
      return prep_time_hours;
    },
    getPrepTimeMinutes (state) {
      const {prep_time_minutes} = state.recipe;
      return prep_time_minutes;
    },
    getCookTimeHours (state) {
      const {cook_time_hours} = state.recipe;
      return cook_time_hours;
    },
    getCookTimeMinutes (state) {
      const {cook_time_minutes} = state.recipe;
      return cook_time_minutes;
    },
    getServings (state) {
      const {servings} = state.recipe;
      return servings;
    },
    getServingSize (state) {
      const {serving_size} = state.recipe;
      return serving_size;
    },
    getCalories (state) {
      const {calories} = state.recipe.nutrition;
      return calories;
    },
    getCarbs (state) {
      const {carbs} = state.recipe.nutrition;
      return carbs;
    },
    getProtein (state) {
      const {protein} = state.recipe.nutrition;
      return protein;
    },
    getFiber (state) {
      const {fiber} = state.recipe.nutrition;
      return fiber;
    },
    getSugar (state) {
      const {sugar} = state.recipe.nutrition;
      return sugar;
    },
    getSodium (state) {
      const {sodium} = state.recipe.nutrition;
      return sodium;
    },
    getFat (state) {
      const {fat} = state.recipe.nutrition;
      return fat;
    },
    getSaturatedFat (state) {
      const {saturated_fat} = state.recipe.nutrition;
      return saturated_fat;
    },
    getTransFat (state) {
      const {trans_fat} = state.recipe.nutrition;
      return trans_fat;
    },
    getCholesterol (state) {
      const {cholesterol} = state.recipe.nutrition;
      return cholesterol;
    },
    getIsSaving (state) {
      const {isSaving} = state;
      return isSaving;
    },
    getIsFetching(state) {
      const {isFetching} = state;
      return isFetching;
    }
  },

  controls: {
    SEND_RECIPE (action) {
      let newRecipe = null;
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
    SAVE_IMAGE_REQUEST (action) {
      let newRecipe = null;
      if (action.recipe.id && action.recipe.image_url) {
        newRecipe = apiFetch ({
          path: `/zip-recipes/v1/recipe/${action.recipe.id}`,
          method: 'POST',
          data: {
            image_url: action.recipe.image_url,
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
        yield actions.requestRecipe();
        const recipe = yield actions.fetchFromAPI (path);
        yield actions.requestRecipeSuccess();
        yield actions.setTitle (recipe.title);
        yield actions.setImageUrl (recipe.image_url);
        yield actions.setIsFeaturedPostImage (recipe.is_featured_post_image);
        yield actions.setDescription (recipe.description);
        yield actions.setCategory (recipe.category);
        yield actions.setCuisine (recipe.cuisine);
        yield actions.setIngredients (recipe.ingredients);
        yield actions.setInstructions (recipe.instructions);
        yield actions.setPrepTimeHours (recipe.prep_time_hours);
        yield actions.setPrepTimeMinutes (recipe.prep_time_minutes);
        yield actions.setCookTimeHours (recipe.cook_time_hours);
        yield actions.setCookTimeMinutes (recipe.cook_time_minutes);
        yield actions.setServings (recipe.servings);
        yield actions.setServingSize (recipe.serving_size);
        yield actions.setNotes (recipe.notes);
        yield actions.setCalories (recipe.nutrition.calories);
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

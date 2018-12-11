const {__} = wp.i18n;
const {registerBlockType} = wp.blocks;

const {actions} = require ('../store/zip-recipes-store');

const {withState} = wp.compose;

let blocks = window.wp.blocks;
let editor = window.wp.editor;
let i18n = window.wp.i18n;
let element = window.wp.element;
let components = window.wp.components;
let _ = window._;

const {
  RichText,
  PlainText,
  MediaUpload,
  InspectorControls,
  BlockControls,
  AlignmentToolbar,
} = editor;

const {Button, Modal} = wp.components;

var el = wp.element.createElement, Fragment = wp.element.Fragment;

const {data} = wp;
const {registerStore, withSelect, withDispatch, select} = data;

registerBlockType ('zip-recipes/recipe-block', {
  title: __ ('Zip Recipes'),
  description: __ ('Create a recipe card.'),
  icon: {
    src: 'carrot',
    foreground: '#4AB158',
  },
  category: 'widgets',
  keywords: [__ ('Zip Recipes'), __ ('Recipe'), __ ('Recipe Card')],

  attributes: {
    id: {
      type: 'string',
    },
  },
  supports: {
    reusable: false,
    multiple: false,
  },
  edit: withDispatch ((dispatch, ownProps) => {
    const creators = dispatch ('zip-recipes-store');
    const {getCurrentPost} = select ('core/editor');
    const store = select ('zip-recipes-store');

    return {
      onTitleChange({target: {value}}) {
        creators.setTitle (value);
      },
      onIngredientsChange({target: {value}}) {
        creators.setIngredients (value);
      },
      onInstructionsChange({target: {value}}) {
        creators.setInstructions (value);
      },
      onCategoryChange({target: {value}}) {
        creators.setCategory (value);
      },
      onCuisineChange({target: {value}}) {
        creators.setCuisine (value);
      },
      onDescriptionChange({target: {value}}) {
        creators.setDescription (value);
      },
      onPrepTimeHoursChange({target: {value}}) {
        creators.setPrepTimeHours (value);
      },
      onPrepTimeMinutesChange({target: {value}}) {
        creators.setPrepTimeMinutes (value);
      },
      onCookTimeHoursChange({target: {value}}) {
        creators.setCookTimeHours (value);
      },
      onCookTimeMinutesChange({target: {value}}) {
        creators.setCookTimeMinutes (value);
      },
      onNotesChange({target: {value}}) {
        creators.setNotes (value);
      },
      onServingsChange({target: {value}}) {
        creators.setServings (value);
      },
      onCaloriesChange({target: {value}}) {
        creators.setCalories (value);
      },
      onCarbsChange({target: {value}}) {
        creators.setCarbs (value);
      },
      onProteinChange({target: {value}}) {
        creators.setProtein (value);
      },
      onFiberChange({target: {value}}) {
        creators.setFiber (value);
      },
      onSugarChange({target: {value}}) {
        creators.setSugar (value);
      },
      onSodiumChange({target: {value}}) {
        creators.setSodium (value);
      },
      onFatChange({target: {value}}) {
        creators.setFat (value);
      },
      onSaturatedFatChange({target: {value}}) {
        creators.setSaturatedFat (value);
      },
      onTransFatChange({target: {value}}) {
        creators.setTransFat (value);
      },
      onCholesterolChange({target: {value}}) {
        creators.setCholesterol (value);
      },

      async onSave (setAttributes, setState, id) {
        const recipe = {
          post_id: getCurrentPost ().id,
          title: store.getTitle (),
          category: store.getCategory (),
          cuisine: store.getCuisine (),
          description: store.getDescription(),
          notes: store.getNotes(),
        };
        if (id) {
          // update recipe
          try {
            console.log ('updating recipe L 156');
            await creators.saveRecipe ({
              recipe: {...recipe, id: id},
            });

            // close modal
            setState ({isOpen: false});
          } catch (e) {
            // TODO: show this as an error somewhere
            console.log ('Failed to update recipe id:', id, '. Error:', e);
          }
        } else {
          // create new recipe
          try {
            let newRecipe = await creators.saveRecipe ({
              recipe: {...recipe}, // we don't have an ID to set here...we wait for server to send one for us back
              create: true,
            });

            console.log (
              'L 176 setting new ID attribute:',
              newRecipe.id,
              ' whole recipe:',
              newRecipe
            );
            setAttributes ({
              id: newRecipe.id,
            });

            // close modal
            setState ({isOpen: false});
          } catch (e) {
            // TODO: show this as an error somewhere
            console.log ('Failed to create new recipe:', e);
          }
        }
      },
    };
  }) (
    withSelect ((select, props) => {
      const store = select (
        'zip-recipes-store'
      );

      return {
        id: store.getId (),
        recipe: store.getRecipe (props.attributes.id),
        title: store.getTitle (),
        category: store.getCategory (),
        cuisine: store.getCuisine (),
        description: store.getDescription(),
        notes: store.getNotes(),
      };
    }) (
      withState ({
        isOpen: false,
      }) (
        (props) => {
          let uploadButton = obj => {
            return <Button>Hello</Button>;
          };

          return (
            <div>
              {props.attributes.id
                ? <Button isDefault onClick={() => props.setState ({isOpen: true})}>
                    Edit Recipe
                  </Button>
                : <Button isDefault onClick={() => props.setState ({isOpen: true})}>
                    Create Recipe
                  </Button>}

              {props.isOpen
                ? <Modal
                    style={{width: '100%', height: '100%'}}
                    title={props.attributes.id ? `Edit ${props.title}` : 'Create Recipe'}
                    onRequestClose={() => props.setState ({isOpen: false})}
                  >
                    <div className={props.className}>
                      {/* Title and image start --> */}
                      <div className="zrdn-columns zrdn-is-mobile">
                        <div className="zrdn-column zrdn-is-three-quarters-tablet zrdn-is-two-thirds-mobile">
                          <div className="zrdn-field">
                            <div className="zrdn-control" id="title-container">
                              <input
                                id="recipe-title"
                                name="recipe_title"
                                className="zrdn-input zrdn-is-size-3"
                                type="text"
                                value={props.title}
                                onChange={props.onTitleChange}
                                placeholder={i18n.__ (
                                  'Recipe Title…',
                                  'gutenberg-examples'
                                )}
                              />
                            </div>
                          </div>
                        </div>
                        <div className="zrdn-column">
                          <label className="zrdn-label">
                            Image
                          </label>
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
                    </div>
                    <div id="ingredients-container" className="zrdn-field">
                      <label className="zrdn-label" htmlFor="ingredients">
                        Ingredients
                      </label>
                      <p className="zrdn-help">
                        Put each ingredient on a separate
                        line. There is no need to use
                        bullets for your ingredients.
                        <br />
                        You can also create labels,
                        hyperlinks, bold/italic effects and
                        even add images!
                        <br />
                        <a
                          href="https://www.ziprecipes.net/docs/installing/"
                          target="_blank"
                        >
                          Learn how here
                        </a>
                      </p>
                      <div className="zrdn-control">
                        <textarea
                          className="zrdn-textarea clean-on-paste"
                          name="ingredients"
                          onChange={props.onIngredientsChange}
                          id="ingredients"
                        >
                          {props.attributes.ingredients}
                        </textarea>
                      </div>
                    </div>

                    <div className="zrdn-field">
                      <label className="zrdn-label" htmlFor="instructions">
                        Instructions
                      </label>
                      <p className="zrdn-help">
                        Press return after each instruction.
                        There is no need to number your
                        instructions.
                        <br />
                        You can also create labels,
                        hyperlinks, bold/italic effects and
                        even add images!
                        <br />
                        <a
                          href="https://www.ziprecipes.net/docs/installing/"
                          target="_blank"
                        >
                          Learn how here
                        </a>
                      </p>
                      <div className="zrdn-control">
                        <textarea
                          className="zrdn-textarea clean-on-paste"
                          id="instructions"
                          onChange={props.onInstructionsChange}
                          name="instructions"
                        >
                          {props.attributes.instructions}
                        </textarea>
                      </div>
                    </div>

                    <div className="zrdn-columns zrdn-is-mobile">
                      <div className="zrdn-column">
                        <div className="zrdn-field">
                          <label htmlFor="category" className="zrdn-label">
                            Category
                          </label>
                          <div className="zrdn-control">
                            <input
                              className="zrdn-input zrdn-is-small"
                              id="category"
                              onChange={props.onCategoryChange}
                              placeholder="e.g. appetizer, entree, etc."
                              type="text"
                              name="category"
                              value={props.category}
                            />
                          </div>
                        </div>
                      </div>
                      <div className="zrdn-column">
                        <div className="zrdn-field">
                          <label htmlFor="cuisine" className="zrdn-label">
                            Cuisine
                          </label>
                          <div className="zrdn-control">
                            <input
                              className="zrdn-input zrdn-is-small"
                              placeholder="e.g. French, Ethiopian, etc."
                              onChange={props.onCuisineChange}
                              type="text"
                              id="cuisine"
                              name="cuisine"
                              value={props.cuisine}
                            />
                          </div>
                        </div>
                      </div>
                    </div>

                    <div className="zrdn-field">
                      <label className="zrdn-label" htmlFor="summary">
                        Description
                      </label>
                      <div className="zrdn-control">
                        <textarea
                          className="zrdn-textarea"
                          id="summary"
                          name="summary"
                          onChange={props.onDescriptionChange}
                          data-caption="true"
                        >
                          {props.description}
                        </textarea>
                      </div>
                    </div>

                    <div className="zrdn-columns zrdn-is-tablet">
                      <div className="zrdn-column">
                        <label htmlFor="prep_hours" className="zrdn-label">
                          Prep Time
                        </label>
                        <div className="zrdn-field zrdn-is-grouped">
                          <div>
                            <input
                              className="zrdn-input zrdn-is-small"
                              type="number"
                              min="0"
                              id="prep_hours"
                              name="prep_time_hours"
                              value={props.prepTimeHours}
                            />
                            hours
                          </div>
                          <div>
                            <input
                              className="zrdn-input zrdn-is-small"
                              type="number"
                              min="0"
                              id="prep_minutes"
                              name="prep_time_minutes"
                              value={props.prepTimeMinutes}
                            />
                            minutes
                          </div>
                        </div>
                      </div>
                      <div className="zrdn-column">
                        <label htmlFor="cook_hours" className="zrdn-label">
                          Cook Time
                        </label>
                        <div className="zrdn-field zrdn-is-grouped">
                          <div>
                            <input
                              className="zrdn-input zrdn-is-small"
                              type="number"
                              min="0"
                              id="cook_hours"
                              name="cook_time_hours"
                              value={props.cookTimeHours}
                            />
                            hours
                          </div>
                          <div>
                            <input
                              className="zrdn-input zrdn-is-small"
                              type="number"
                              min="0"
                              id="cook_minutes"
                              name="cook_time_minutes"
                              value={props.cookTimeMinutes}
                            />
                            minutes
                          </div>
                        </div>
                      </div>
                    </div>

                    <div className="zrdn-field">
                      <label className="zrdn-label" htmlFor="notes">
                        Notes
                      </label>
                      <div className="zrdn-control">
                        <textarea
                          className="zrdn-textarea"
                          id="notes"
                          name="notes"
                          onChange={props.onNotesChange}
                        >
                          {props.notes}
                        </textarea>
                      </div>
                    </div>
                    <div className="zrdn-field">
                      <label className="zrdn-label" htmlFor="serving_size">
                        Serving Size
                      </label>
                      <div className="zrdn-control">
                        <input
                          className="zrdn-input zrdn-is-small"
                          id="serving_size"
                          type="text"
                          onChange={props.onServingsChange}
                          name="serving_size"
                          value={props.servingSize}
                        />
                      </div>
                    </div>

                    <div className="zrdn-columns zrdn-is-gapless zrdn-is-mobile">
                      <div className="zrdn-column">
                        <div className="zrdn-field-label">
                          <label className="zrdn-label" htmlFor="calories">
                            Calories
                          </label>
                        </div>
                      </div>
                      <div className="zrdn-column">
                        <div className="zrdn-field-body">
                          <div className="zrdn-field zrdn-is-narrow">
                            <div className="zrdn-control">
                              <input
                                className="zrdn-input zrdn-is-small"
                                type="text"
                                id="calories"
                                name="calories"
                                onChange={props.onCaloriesChange}
                                value={props.calories}
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div className="zrdn-columns zrdn-is-gapless zrdn-is-mobile">
                      <div className="zrdn-column">
                        <div className="zrdn-field-label">
                          <label className="zrdn-label" htmlFor="carbs">
                            Carbs
                          </label>
                        </div>
                      </div>
                      <div className="zrdn-column">
                        <div className="zrdn-field-body">
                          <div className="zrdn-field zrdn-is-narrow">
                            <div className="zrdn-control">
                              <input
                                className="zrdn-input zrdn-is-small"
                                type="text"
                                id="carbs"
                                name="carbs"
                                onChange={props.onCarbsChange}
                                value={props.carbs}
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div className="zrdn-columns zrdn-is-gapless zrdn-is-mobile">
                      <div className="zrdn-column">
                        <div className="zrdn-field-label">
                          <label className="zrdn-label" htmlFor="protein">
                            Protein
                          </label>
                        </div>
                      </div>
                      <div className="zrdn-column">
                        <div className="zrdn-field-body">
                          <div className="zrdn-field zrdn-is-narrow">
                            <div className="zrdn-control">
                              <input
                                className="zrdn-input zrdn-is-small"
                                type="text"
                                id="protein"
                                name="protein"
                                onChange={props.onProteinChange}
                                value={props.protein}
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div className="zrdn-columns zrdn-is-gapless zrdn-is-mobile">
                      <div className="zrdn-column">
                        <div className="zrdn-field-label">
                          <label className="zrdn-label" htmlFor="fiber">
                            Fiber
                          </label>
                        </div>
                      </div>
                      <div className="zrdn-column">
                        <div className="zrdn-field-body">
                          <div className="zrdn-field zrdn-is-narrow">
                            <div className="zrdn-control">
                              <input
                                className="zrdn-input zrdn-is-small"
                                type="text"
                                id="fiber"
                                name="fiber"
                                onChange={props.onFiberChange}
                                value={props.fiber}
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div className="zrdn-columns zrdn-is-gapless zrdn-is-mobile">
                      <div className="zrdn-column">
                        <div className="zrdn-field-label">
                          <label className="zrdn-label" htmlFor="sugar">
                            Sugar
                          </label>
                        </div>
                      </div>
                      <div className="zrdn-column">
                        <div className="zrdn-field-body">
                          <div className="zrdn-field zrdn-is-narrow">
                            <div className="zrdn-control">
                              <input
                                className="zrdn-input zrdn-is-small"
                                type="text"
                                id="sugar"
                                name="sugar"
                                onChange={props.onSugarChange}
                                value={props.sugar}
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div className="zrdn-columns zrdn-is-gapless zrdn-is-mobile">
                      <div className="zrdn-column">
                        <div className="zrdn-field-label">
                          <label className="zrdn-label" htmlFor="sodium">
                            Sodium
                          </label>
                        </div>
                      </div>
                      <div className="zrdn-column">
                        <div className="zrdn-field-body">
                          <div className="zrdn-field zrdn-is-narrow">
                            <div className="zrdn-control">
                              <input
                                className="zrdn-input zrdn-is-small"
                                type="text"
                                id="sodium"
                                name="sodium"
                                onChange={props.onSodiumChange}
                                value={props.sodium}
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div className="zrdn-columns zrdn-is-gapless zrdn-is-mobile">
                      <div className="zrdn-column">
                        <div className="zrdn-field-label">
                          <label className="zrdn-label" htmlFor="fat">
                            Fat
                          </label>
                        </div>
                      </div>
                      <div className="zrdn-column">
                        <div className="zrdn-field-body">
                          <div className="zrdn-field zrdn-is-narrow">
                            <div className="zrdn-control">
                              <input
                                className="zrdn-input zrdn-is-small"
                                type="text"
                                id="fat"
                                name="fat"
                                onChange={props.onFatChange}
                                value={props.fat}
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div className="zrdn-columns zrdn-is-gapless zrdn-is-mobile">
                      <div className="zrdn-column">
                        <div className="zrdn-field-label zrdn-is-left">
                          <label className="zrdn-label" htmlFor="saturated_fat">
                            Saturated Fat
                          </label>
                        </div>
                      </div>
                      <div className="zrdn-column">
                        <div className="zrdn-field-body">
                          <div className="zrdn-field zrdn-is-narrow">
                            <div className="zrdn-control">
                              <input
                                className="zrdn-input zrdn-is-small"
                                type="text"
                                id="saturated_fat"
                                onChange={props.onSaturatedFatChange}
                                name="saturated_fat"
                                value={props.saturatedFat}
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div className="zrdn-columns zrdn-is-gapless zrdn-is-mobile">
                      <div className="zrdn-column">
                        <div className="zrdn-field-label zrdn-is-left">
                          <label className="zrdn-label" htmlFor="trans_fat">
                            Trans. Fat
                          </label>
                        </div>
                      </div>
                      <div className="zrdn-column">
                        <div className="zrdn-field-body">
                          <div className="zrdn-field zrdn-is-narrow">
                            <div className="zrdn-control">
                              <input
                                className="zrdn-input zrdn-is-small"
                                type="text"
                                id="trans_fat"
                                onChange={props.onTransFatChange}
                                name="trans_fat"
                                value={props.transFat}
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div className="zrdn-columns zrdn-is-gapless zrdn-is-mobile">
                      <div className="zrdn-column">
                        <div className="zrdn-field-label zrdn-is-left">
                          <label className="zrdn-label" htmlFor="cholesterol">
                            Cholesterol
                          </label>
                        </div>
                      </div>
                      <div className="zrdn-column">
                        <div className="zrdn-field-body">
                          <div className="zrdn-field zrdn-is-narrow">
                            <div className="zrdn-control">
                              <input
                                className="zrdn-input zrdn-is-small"
                                type="text"
                                id="cholesterol"
                                onChange={props.onCholesterolChange}
                                name="cholesterol"
                                value={props.cholesterol}
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <Button
                      isPrimary
                      isLarge
                      onClick={props.onSave.bind (
                        null,
                        props.setAttributes,
                        props.setState,
                        props.attributes.id
                      )}
                    >
                      {props.attributes.id ? 'Update Recipe' : 'Save Recipe'}
                    </Button>
                  </Modal>
                : <div>Title: {props.title}</div>}
            </div>
          );
        }
      )
    )
  ),

  save: function (props) {
    return null;
  }
});

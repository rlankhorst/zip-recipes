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

const {data, apiFetch} = wp;
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
    const {
      setTitle,
      setIngredients,
      setInstructions,
      setCategory,
      setCuisine,
      setDescription,
      //   setPrepTimeHours,
      //   setPrepTimeMinutes,
      //   setCookTimeHours,
      //   setCookTimeMinutes,
      setNotes,
      setServings,
      setCalories,
      setCarbs,
      setProtein,
      setFiber,
      setSugar,
      setSodium,
      setFat,
      setSaturatedFat,
      setTransFat,
      setCholesterol,
      saveRecipe,
    } = dispatch ('zip-recipes-store');
    const {getCurrentPost} = select ('core/editor');
    const {getTitle, getCategory} = select ('zip-recipes-store');

    return {
      onTitleChange({target: {value}}) {
        setTitle (value);
      },
      onIngredientsChange({target: {value}}) {
        setIngredients (value);
      },
      onInstructionsChange({target: {value}}) {
        setInstructions (value);
      },
      onCategoryChange({target: {value}}) {
        setCategory (value);
      },
      onCuisineChange({target: {value}}) {
        setCuisine (value);
      },
      onDescriptionChange({target: {value}}) {
        setDescription (value);
      },
      onPrepTimeHoursChange({target: {value}}) {
        setPrepTimeHours (value);
      },
      onPrepTimeMinutesChange({target: {value}}) {
        setPrepTimeMinutes (value);
      },
      onCookTimeHoursChange({target: {value}}) {
        setCookTimeHours (value);
      },
      onCookTimeMinutesChange({target: {value}}) {
        setCookTimeMinutes (value);
      },
      onNotesChange({target: {value}}) {
        setNotes (value);
      },
      onServingsChange({target: {value}}) {
        setServings (value);
      },
      onCaloriesChange({target: {value}}) {
        setCalories (value);
      },
      onCarbsChange({target: {value}}) {
        setCarbs (value);
      },
      onProteinChange({target: {value}}) {
        setProtein (value);
      },
      onFiberChange({target: {value}}) {
        setFiber (value);
      },
      onSugarChange({target: {value}}) {
        setSugar (value);
      },
      onSodiumChange({target: {value}}) {
        setSodium (value);
      },
      onFatChange({target: {value}}) {
        setFat (value);
      },
      onSaturatedFatChange({target: {value}}) {
        setSaturatedFat (value);
      },
      onTransFatChange({target: {value}}) {
        setTransFat (value);
      },
      onCholesterolChange({target: {value}}) {
        setCholesterol (value);
      },

      async onSave (setAttributes, setState, id) {
        const recipe = {
          post_id: getCurrentPost ().id,
          title: getTitle (),
          category: getCategory(),
        };
        if (id) {
          // update recipe
          try {
              console.log("updating recipe L 156");
            await saveRecipe ({
              recipe: {...recipe, id: id},
            });

            // close modal
            setState ({isOpen: false});
          } catch (e) {
            // TODO: show this as an error somewhere
            console.log ('Failed to update recipe id:', id, ". Error:", e);
          }
        } else {
          // create new recipe
          try {
            let recipe = await saveRecipe ({
                recipe: {...recipe, id: id},
              create: true,
            });
            setAttributes ({
              id: recipe.id,
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
      const {getRecipe, getTitle, getCategory, getId} = select (
        'zip-recipes-store'
      );

      return {
        id: getId (),
        recipe: getRecipe (props.attributes.id),
        title: getTitle (),
        category: getCategory (),
      };
    }) (
      withState ({
        isOpen: false,
      }) (({attributes, setAttributes,
        //actions
        onTitleChange, onIngredientsChange, onInstructionsChange, onCategoryChange, onCuisineChange, onDescriptionChange, onPrepTimeHoursChange, onPrepTimeMinutesChange, onCookTimeHoursChange, onCookTimeMinutesChange, onNotesChange, onServingsChange, onCaloriesChange, onCarbsChange, onProteinChange, onFiberChange, onSugarChange, onSodiumChange, onFatChange, onSaturatedFatChange, onTransFatChange, onCholesterolChange,
        // variables
        isOpen, onSave, category, title, setState, className}) => {
        let uploadButton = obj => {
          return <components.Button>Hello</components.Button>;
        };

        return (
          <div>
            {attributes.id
              ? <Button isDefault onClick={() => setState ({isOpen: true})}>
                  Edit Recipe
                </Button>
              : <Button isDefault onClick={() => setState ({isOpen: true})}>
                  Create Recipe
                </Button>}

            {isOpen
              ? <Modal
                  style={{width: '100%', height: '100%'}}
                  title={attributes.id ? `Edit ${title}` : 'Create Recipe'}
                  onRequestClose={() => setState ({isOpen: false})}
                >
                  <div className={className}>
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
                              value={title}
                              onChange={onTitleChange}
                              placeholder={i18n.__ (
                                'Recipe Title…',
                                'gutenberg-examples'
                              )}
                            />
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
                  </div>
                  <div id="ingredients-container" className="zrdn-field">
                    <label className="zrdn-label" htmlFor="ingredients">
                      Ingredients
                    </label>
                    <p className="zrdn-help">
                      Put each ingredient on a separate line. There is no need
                      to use bullets for your ingredients.
                      <br />
                      You can also create labels, hyperlinks, bold/italic
                      effects and even add images!
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
                        onChange={onIngredientsChange}
                        id="ingredients"
                      >
                        {attributes.ingredients}
                      </textarea>
                    </div>
                  </div>

                  <div className="zrdn-field">
                    <label className="zrdn-label" htmlFor="instructions">
                      Instructions
                    </label>
                    <p className="zrdn-help">
                      Press return after each instruction. There is no need to
                      number your instructions.
                      <br />
                      You can also create labels, hyperlinks, bold/italic
                      effects and even add images!
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
                        onChange={onInstructionsChange}
                        name="instructions"
                      >
                        {attributes.instructions}
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
                            onChange={onCategoryChange}
                            placeholder="e.g. appetizer, entree, etc."
                            type="text"
                            name="category"
                            value={category}
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
                            onChange={onCuisineChange}
                            type="text"
                            id="cuisine"
                            name="cuisine"
                            value={attributes.cuisine}
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
                        onChange={onDescriptionChange}
                        data-caption="true"
                      >
                        {attributes.description}
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
                            value={attributes.prepTimeHours}
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
                            value={attributes.prepTimeMinutes}
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
                            value={attributes.cookTimeHours}
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
                            value={attributes.cookTimeMinutes}
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
                        onChange={onNotesChange}
                      >
                        {attributes.notes}
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
                        onChange={onServingsChange}
                        name="serving_size"
                        value={attributes.servingSize}
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
                              onChange={onCaloriesChange}
                              value={attributes.calories}
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
                              onChange={onCarbsChange}
                              value={attributes.carbs}
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
                              onChange={onProteinChange}
                              value={attributes.protein}
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
                              onChange={onFiberChange}
                              value={attributes.fiber}
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
                              onChange={onSugarChange}
                              value={attributes.sugar}
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
                              onChange={onSodiumChange}
                              value={attributes.sodium}
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
                              onChange={onFatChange}
                              value={attributes.fat}
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
                              onChange={onSaturatedFatChange}
                              name="saturated_fat"
                              value={attributes.saturatedFat}
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
                              onChange={onTransFatChange}
                              name="trans_fat"
                              value={attributes.transFat}
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
                              onChange={onCholesterolChange}
                              name="cholesterol"
                              value={attributes.cholesterol}
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <Button
                    isPrimary
                    isLarge
                    onClick={onSave.bind (
                      null,
                      setAttributes,
                      setState,
                      attributes.id
                    )}
                  >
                    {attributes.id ? 'Update Recipe' : 'Save Recipe'}
                  </Button>
                </Modal>
              : <div>Title: {title}</div>}
          </div>
        );
      })
    )
  ),
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

  save: function (props) {
    return null;
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

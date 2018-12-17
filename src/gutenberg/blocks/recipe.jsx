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
  MediaUploadCheck,
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
    const noticeActions = dispatch ('core/notices');

    return {
      setInitialTitle () {
        creators.setTitleFromPostTitle (getCurrentPost ().title);
      },
      onTitleChange({target: {value}}) {
        creators.setTitle (value);
      },
      async onImageChange (id, {url}) {
        creators.setImageUrl (url);

        if (id) {
          // set image url
          try {
            await creators.saveImage (id, url);
          } catch (e) {
            noticeActions.createErrorNotice (
              `Failed to set image on recipe recipe id: ${id}`
            );

            console.log (
              'Failed to set image on recipe recipe id:',
              id,
              '. Error:',
              e
            );
          }
        } else {
          noticeActions.createErrorNotice (
            `No recipe id present. Did you save the recipe?`
          );

          console.log ('Image saved on a recipe that has no id yet.');
        }
      },
      onIngredientsChange({target: {value}}) {
        creators.setIngredients (value);
      },
      /**
       * Handle paste so we can clean up some stuff.
       */
      onIngredientsPaste () {
        window.setTimeout (function () {
          let existingLines = store.getIngredients ();
          let newLines = [];
          for (var i = 0; i < existingLines.length; i++) {
            if (/\S/.test (existingLines[i])) {
              newLines.push ($.trim (existingLines[i]));
            }
          }
          creators.setIngredients (newLines);
        }, 500);
      },
      onInstructionsChange({target: {value}}) {
        creators.setInstructions (value);
      },
      /**
       * Handle paste so we can clean up some stuff.
       */
      onInstructionsPaste () {
        window.setTimeout (function () {
          let existingLines = store.getInstructions ();
          let newLines = [];
          for (var i = 0; i < existingLines.length; i++) {
            if (/\S/.test (existingLines[i])) {
              newLines.push ($.trim (existingLines[i]));
            }
          }
          creators.setInstructions (newLines);
        }, 500);
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
      onServingSizeChange({target: {value}}) {
        creators.setServingSize (value);
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

      onCancel (setState) {
        console.log ('in onCancel L181');
        setState ({isOpen: false});
      },

      async onSave (setAttributes, setState, id) {
        const recipe = {
          post_id: getCurrentPost ().id,
          title: store.getTitle (),
          category: store.getCategory (),
          cuisine: store.getCuisine (),
          description: store.getDescription (),
          notes: store.getNotes (),
          ingredients: store.getIngredients (),
          instructions: store.getInstructions (),
          image_url: store.getImageUrl (),
          prep_time_hours: store.getPrepTimeHours (),
          prep_time_minutes: store.getPrepTimeMinutes (),
          cook_time_hours: store.getCookTimeHours (),
          cook_time_minutes: store.getCookTimeMinutes (),
          serving_size: store.getServingSize (),
          servings: store.getServings (),
          nutrition: {
            calories: store.getCalories (),
            carbs: store.getCarbs (),
            protein: store.getProtein (),
            fiber: store.getFiber (),
            sugar: store.getSugar (),
            sodium: store.getSodium (),
            fat: store.getFat (),
            saturated_fat: store.getSaturatedFat (),
            trans_fat: store.getTransFat (),
            cholesterol: store.getCholesterol (),
          },
        };
        if (id) {
          // update recipe
          try {
            creators.setRecipeSaving ();
            await creators.saveRecipe ({
              recipe: {...recipe, id: id},
            });
            creators.saveRecipeSuccess ();

            // close modal
            setState ({isOpen: false});
          } catch (e) {
            noticeActions.createErrorNotice (
              `Failed to update recipe id: ${id}`
            );
            console.log ('Failed to update recipe id:', id, '. Error:', e);
          }
        } else {
          // create new recipe
          try {
            creators.setRecipeSaving ();
            let newRecipe = await creators.saveRecipe ({
              recipe: {...recipe}, // we don't have an ID to set here...we wait for server to send one for us back
              create: true,
            });
            creators.saveRecipeSuccess ();

            setAttributes ({
              id: newRecipe.id,
            });

            // close modal
            setState ({isOpen: false});
          } catch (e) {
            noticeActions.createErrorNotice (`Failed to create recipe`);
            console.log ('Failed to create new recipe:', e);
          }
        }
      },
    };
  }) (
    withSelect ((select, props) => {
      const store = select ('zip-recipes-store');
      const {getCurrentPost} = select ('core/editor');

      return {
        id: store.getId (),
        recipe: store.getRecipe (props.attributes.id),
        title: store.getTitle (),
        postTitle: getCurrentPost ().title,
        imageUrl: store.getImageUrl (),
        isFeaturedPostImage: store.getIsFeaturedPostImage (),
        category: store.getCategory (),
        cuisine: store.getCuisine (),
        description: store.getDescription (),
        notes: store.getNotes (),
        ingredients: store.getIngredients (),
        instructions: store.getInstructions (),
        prepTimeHours: store.getPrepTimeHours (),
        prepTimeMinutes: store.getPrepTimeMinutes (),
        cookTimeHours: store.getCookTimeHours (),
        cookTimeMinutes: store.getCookTimeMinutes (),
        servings: store.getServings (),
        servingSize: store.getServingSize (),
        calories: store.getCalories (),
        carbs: store.getCarbs (),
        protein: store.getProtein (),
        fiber: store.getFiber (),
        sugar: store.getSugar (),
        sodium: store.getSodium (),
        fat: store.getFat (),
        saturatedFat: store.getSaturatedFat (),
        transFat: store.getTransFat (),
        cholesterol: store.getCholesterol (),
        isSaving: store.getIsSaving (),
        isFetching: store.getIsFetching (),
      };
    }) (
      withState ({
        isOpen: false,
      }) (props => {
        const renderImageAndTitle = (editable = false) => {
          return (
            <div className={props.className}>
              {/* Title and image start --> */}
              <div className="zrdn-columns zrdn-is-mobile">

                <div className="zrdn-column zrdn-is-three-quarters-tablet zrdn-is-two-thirds-mobile">
                  <div className="zrdn-field">
                    {editable
                      ? <label htmlFor="recipe-title" class="zrdn-label">
                          Title
                        </label>
                      : ''}
                    <div className="zrdn-control" id="title-container">
                      {editable
                        ? <input
                            id="recipe-title"
                            name="recipe-title"
                            className="zrdn-input"
                            type="text"
                            disabled={!editable}
                            value={props.title}
                            onChange={props.onTitleChange}
                            placeholder={i18n.__ (
                              'Recipe Title…',
                              'gutenberg-examples'
                            )}
                          />
                        : <h2>{props.title}</h2>}
                    </div>
                  </div>
                </div>
                <div className="zrdn-column">
                  <label className="zrdn-label">Image</label>
                  <div className="recipe-image">
                    {/* We show image upload control only when editable is false since we cannot show image upload control in 
                      in Modal window and editable=true only in modal window.
                      See: https://github.com/WordPress/gutenberg/issues/12830
                     */}
                    {editable
                      ? 'To add an image, save this recipe and click Upload Image from the main screen.'
                      : <MediaUploadCheck>
                          <MediaUpload
                            onSelect={props.onImageChange.bind (
                              null,
                              props.attributes.id
                            )}
                            allowedTypes="image"
                            render={({open}) => (
                              <Button
                                className={
                                  props.imageUrl
                                    ? 'image-button'
                                    : 'button button-large'
                                }
                                onClick={open}
                              >
                                {!props.imageUrl
                                  ? __ ('Upload Image', 'gutenberg-examples')
                                  : <div>
                                      {props.isFeaturedPostImage
                                        ? <span>
                                            Set from Featured Image.{' '}
                                          </span>
                                        : <div>
                                            <img
                                              src={props.imageUrl}
                                              alt={__ (
                                                'Upload Recipe Image',
                                                'gutenberg-examples'
                                              )}
                                            />
                                            <span>
                                              Click the image to change it.
                                            </span>
                                          </div>}
                                    </div>}
                              </Button>
                            )}
                          />
                        </MediaUploadCheck>}
                  </div>
                </div>
              </div>
              {/* Title and image end --> */}
            </div>
          );
        };

        const renderIngredients = (editable = false) => (
          <div id="ingredients-container" className="zrdn-field">
            <label className="zrdn-label" htmlFor="ingredients">
              Ingredients
            </label>
            {editable
              ? <p className="zrdn-help">
                  Put each ingredient on a separate line. There is no need to
                  use bullets for your ingredients.
                  <br />
                  You can also create labels, hyperlinks, bold/italic effects
                  and even add images!
                  <br />
                  <a
                    href="https://www.ziprecipes.net/docs/installing/"
                    target="_blank"
                  >
                    Learn how here
                  </a>
                </p>
              : ''}
            <div className="zrdn-control">
              {editable
                ? <textarea
                    className="zrdn-textarea clean-on-paste"
                    name="ingredients"
                    disabled={!editable}
                    onChange={props.onIngredientsChange}
                    onPaste={props.onIngredientsPaste}
                    id="ingredients"
                    value={props.ingredients.join ('\n')}
                  />
                : <div>
                    {props.ingredients.map (ing => {
                      return <div>{ing}</div>;
                    })}
                  </div>}
            </div>
          </div>
        );
        const renderInstructions = (editable = false) => (
          <div className="zrdn-field">
            <label className="zrdn-label" htmlFor="instructions">
              Instructions
            </label>
            {editable
              ? <p className="zrdn-help">
                  Press return after each instruction. There is no need to
                  number your instructions.
                  <br />
                  You can also create labels, hyperlinks, bold/italic effects
                  and even add images!
                  <br />
                  <a
                    href="https://www.ziprecipes.net/docs/installing/"
                    target="_blank"
                  >
                    Learn how here
                  </a>
                </p>
              : ''}
            <div className="zrdn-control">
              {editable
                ? <textarea
                    className="zrdn-textarea clean-on-paste"
                    disabled={!editable}
                    id="instructions"
                    onChange={props.onInstructionsChange}
                    name="instructions"
                    onPaste={props.onInstructionsPaste}
                    value={props.instructions.join ('\n')}
                  />
                : <div>
                    {props.instructions.map (inst => {
                      return <div>{inst}</div>;
                    })}
                  </div>}

            </div>
          </div>
        );
        const renderCategoryAndCuisine = (editable = false) => (
          <div className="zrdn-columns zrdn-is-mobile">
            <div className="zrdn-column">
              <div className="zrdn-field">
                <label htmlFor="category" className="zrdn-label">
                  Category
                </label>
                <div className="zrdn-control">
                  {editable
                    ? <input
                        className="zrdn-input zrdn-is-small"
                        id="category"
                        onChange={props.onCategoryChange}
                        disabled={!editable}
                        placeholder="e.g. appetizer, entree, etc."
                        type="text"
                        name="category"
                        value={props.category}
                      />
                    : props.category}
                </div>
              </div>
            </div>
            <div className="zrdn-column">
              <div className="zrdn-field">
                <label htmlFor="cuisine" className="zrdn-label">
                  Cuisine
                </label>
                <div className="zrdn-control">
                  {editable
                    ? <input
                        className="zrdn-input zrdn-is-small"
                        placeholder="e.g. French, Ethiopian, etc."
                        onChange={props.onCuisineChange}
                        disabled={!editable}
                        type="text"
                        id="cuisine"
                        name="cuisine"
                        value={props.cuisine}
                      />
                    : props.cuisine}
                </div>
              </div>
            </div>
          </div>
        );
        const renderSummary = (editable = false) => (
          <div className="zrdn-field">
            <label className="zrdn-label" htmlFor="summary">
              Description
            </label>
            <div className="zrdn-control">
              {editable
                ? <textarea
                    className="zrdn-textarea"
                    id="summary"
                    name="summary"
                    disabled={!editable}
                    onChange={props.onDescriptionChange}
                    data-caption="true"
                    value={props.description}
                  />
                : props.description}
            </div>
          </div>
        );
        const renderPrepAndCookTime = (editable = false) => (
          <div className="zrdn-columns zrdn-is-tablet">
            <div className="zrdn-column">
              <label htmlFor="prep_hours" className="zrdn-label">
                Prep Time
              </label>
              {editable
                ? <div className="zrdn-field zrdn-is-grouped">
                    <div>
                      <input
                        className="zrdn-input zrdn-is-small"
                        type="number"
                        min="0"
                        disabled={!editable}
                        id="prep_hours"
                        onChange={props.onPrepTimeHoursChange}
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
                        disabled={!editable}
                        id="prep_minutes"
                        onChange={props.onPrepTimeMinutesChange}
                        name="prep_time_minutes"
                        value={props.prepTimeMinutes}
                      />
                      minutes
                    </div>
                  </div>
                : <div className="zrdn-control">
                    {props.prepTimeHours}:{props.prepTimeMinutes}
                  </div>}
            </div>
            <div className="zrdn-column">
              <label htmlFor="cook_hours" className="zrdn-label">
                Cook Time
              </label>
              {editable
                ? <div className="zrdn-field zrdn-is-grouped">
                    <div>
                      <input
                        className="zrdn-input zrdn-is-small"
                        type="number"
                        min="0"
                        disabled={!editable}
                        onChange={props.onCookTimeHoursChange}
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
                        disabled={!editable}
                        id="cook_minutes"
                        onChange={props.onCookTimeMinutesChange}
                        name="cook_time_minutes"
                        value={props.cookTimeMinutes}
                      />
                      minutes
                    </div>
                  </div>
                : <div className="zrdn-control">
                    {props.cookTimeHours}:{props.cookTimeMinutes}
                  </div>}
            </div>
          </div>
        );
        const renderNotes = (editable = false) => (
          <div className="zrdn-field">
            <label className="zrdn-label" htmlFor="notes">
              Notes
            </label>
            <div className="zrdn-control">
              {editable
                ? <textarea
                    className="zrdn-textarea"
                    id="notes"
                    disabled={!editable}
                    name="notes"
                    onChange={props.onNotesChange}
                    value={props.notes}
                  />
                : props.notes}
            </div>
          </div>
        );
        const renderServings = (editable = false) => (
          <div class="zrdn-field zrdn-is-horizontal zrdn-is-mobile">
            <div class="zrdn-field-label">
              <label class="zrdn-label" for="servings">
                Yields
              </label>
            </div>
            <div class="zrdn-field-body">
              <div class="zrdn-field zrdn-is-narrow">
                <div class="zrdn-control">
                  {editable
                    ? <input
                        class="zrdn-input zrdn-is-small"
                        id="servings"
                        disabled={!editable}
                        type="text"
                        onChange={props.onServingsChange}
                        value={props.servings}
                        name="servings"
                      />
                    : props.servings}
                </div>
              </div>
            </div>
          </div>
        );
        const renderServingSize = (editable = false) => (
          <div class="zrdn-field zrdn-is-horizontal zrdn-is-mobile">
            <div class="zrdn-field-label">
              <label class="zrdn-label" for="serving_size">
                Serving Size
              </label>
            </div>
            <div class="zrdn-field-body">
              <div class="zrdn-field zrdn-is-narrow">
                <div class="zrdn-control">
                  {editable
                    ? <input
                        class="zrdn-input zrdn-is-small"
                        id="serving_size"
                        type="text"
                        onChange={props.onServingSizeChange}
                        value={props.servingSize}
                        name="serving_size"
                      />
                    : props.servingSize}
                </div>
              </div>
            </div>
          </div>
        );
        const renderCalories = (editable = false) => (
          <div class="zrdn-field zrdn-is-horizontal">
            <div class="zrdn-field-label">
              <label class="zrdn-label" for="calories">Calories</label>
            </div>
            <div class="zrdn-field-body">
              <div class="zrdn-field zrdn-is-narrow">
                <div class="zrdn-control">
                  {editable
                    ? <input
                        class="zrdn-input zrdn-is-small"
                        type="text"
                        disabled={!editable}
                        id="calories"
                        name="calories"
                        onChange={props.onCaloriesChange}
                        value={props.calories}
                      />
                    : props.calories}
                </div>
              </div>
            </div>
          </div>
        );
        const renderCarbs = (editable = false) => (
          <div class="zrdn-field zrdn-is-horizontal">
            <div class="zrdn-field-label">
              <label class="zrdn-label" for="carbs">Carbs</label>
            </div>
            <div class="zrdn-field-body">
              <div class="zrdn-field zrdn-is-narrow">
                <div class="zrdn-control">
                  {editable
                    ? <input
                        class="zrdn-input zrdn-is-small"
                        type="text"
                        id="carbs"
                        disabled={!editable}
                        name="carbs"
                        onChange={props.onCarbsChange}
                        value={props.carbs}
                      />
                    : props.carbs}
                </div>
              </div>
            </div>
          </div>
        );
        const renderProtein = (editable = false) => (
          <div class="zrdn-field zrdn-is-horizontal zrdn-is-mobile">
            <div class="zrdn-field-label">
              <label class="zrdn-label" for="protein">Protein</label>
            </div>
            <div class="zrdn-field-body">
              <div class="zrdn-field zrdn-is-narrow">
                <div class="zrdn-control">
                  {editable
                    ? <input
                        class="zrdn-input zrdn-is-small"
                        type="text"
                        id="protein"
                        name="protein"
                        disabled={!editable}
                        onChange={props.onProteinChange}
                        value={props.protein}
                      />
                    : props.protein}
                </div>
              </div>
            </div>
          </div>
        );
        const renderFiber = (editable = false) => (
          <div class="zrdn-field zrdn-is-horizontal">
            <div class="zrdn-field-label">
              <label class="zrdn-label" for="fiber">Fiber</label>
            </div>
            <div class="zrdn-field-body">
              <div class="zrdn-field zrdn-is-narrow">
                <div class="zrdn-control">
                  {editable
                    ? <input
                        class="zrdn-input zrdn-is-small"
                        type="text"
                        id="fiber"
                        disabled={!editable}
                        name="fiber"
                        onChange={props.onFiberChange}
                        value={props.fiber}
                      />
                    : props.fiber}
                </div>
              </div>
            </div>
          </div>
        );
        const renderSugar = (editable = false) => (
          <div class="zrdn-field zrdn-is-horizontal">
            <div class="zrdn-field-label">
              <label class="zrdn-label" for="sugar">Sugar</label>
            </div>
            <div class="zrdn-field-body">
              <div class="zrdn-field zrdn-is-narrow">
                <div class="zrdn-control">
                  {editable
                    ? <input
                        class="zrdn-input zrdn-is-small"
                        type="text"
                        id="sugar"
                        name="sugar"
                        onChange={props.onSugarChange}
                        value={props.sugar}
                      />
                    : props.sugar}
                </div>
              </div>
            </div>
          </div>
        );
        const renderSodium = (editable = false) => (
          <div class="zrdn-field zrdn-is-horizontal">
            <div class="zrdn-field-label">
              <label class="zrdn-label" for="sodium">Sodium</label>
            </div>
            <div class="zrdn-field-body">
              <div class="zrdn-field zrdn-is-narrow">
                <div class="zrdn-control">
                  {editable
                    ? <input
                        class="zrdn-input zrdn-is-small"
                        type="text"
                        id="sodium"
                        disabled={!editable}
                        name="sodium"
                        onChange={props.onSodiumChange}
                        value={props.sodium}
                      />
                    : props.sodium}
                </div>
              </div>
            </div>
          </div>
        );
        const renderFat = (editable = false) => (
          <div class="zrdn-field zrdn-is-horizontal">
            <div class="zrdn-field-label">
              <label class="zrdn-label" for="fat">Fat</label>
            </div>
            <div class="zrdn-field-body">
              <div class="zrdn-field zrdn-is-narrow">
                <div class="zrdn-control">
                  {editable
                    ? <input
                        class="zrdn-input zrdn-is-small"
                        type="text"
                        id="fat"
                        name="fat"
                        disabled={!editable}
                        onChange={props.onFatChange}
                        value={props.fat}
                      />
                    : props.fat}
                </div>
              </div>
            </div>
          </div>
        );
        const renderSaturatedFat = (editable = false) => (
          <div class="zrdn-field zrdn-is-horizontal">
            <div class="zrdn-field-label">
              <label class="zrdn-label" for="saturated_fat">
                Saturated Fat
              </label>
            </div>
            <div class="zrdn-field-body">
              <div class="zrdn-field zrdn-is-narrow">
                <div class="zrdn-control">
                  {editable
                    ? <input
                        class="zrdn-input zrdn-is-small"
                        type="text"
                        id="saturated_fat"
                        disabled={!editable}
                        name="saturated_fat"
                        onChange={props.onSaturatedFatChange}
                        value={props.saturatedFat}
                      />
                    : props.saturatedFat}
                </div>
              </div>
            </div>
          </div>
        );

        const renderTransFat = (editable = false) => (
          <div class="zrdn-field zrdn-is-horizontal">
            <div class="zrdn-field-label">
              <label class="zrdn-label" for="trans_fat">
                Trans. Fat
              </label>
            </div>
            <div class="zrdn-field-body">
              <div class="zrdn-field zrdn-is-narrow">
                <div class="zrdn-control">
                  {editable
                    ? <input
                        class="zrdn-input zrdn-is-small"
                        type="text"
                        disabled={!editable}
                        id="trans_fat"
                        name="trans_fat"
                        onChange={props.onTransFatChange}
                        value={props.transFat}
                      />
                    : props.transFat}
                </div>
              </div>
            </div>
          </div>
        );
        const renderCholesterol = (editable = false) => (
          <div class="zrdn-field zrdn-is-horizontal">
            <div class="zrdn-field-label">
              <label class="zrdn-label" for="cholesterol">
                Cholesterol
              </label>
            </div>
            <div class="zrdn-field-body">
              <div class="zrdn-field zrdn-is-narrow">
                <div class="zrdn-control">
                  {editable
                    ? <input
                        class="zrdn-input zrdn-is-small"
                        type="text"
                        id="cholesterol"
                        disabled={!editable}
                        name="cholesterol"
                        onChange={props.onCholesterolChange}
                        value={props.cholesterol}
                      />
                    : props.cholesterol}
                </div>
              </div>
            </div>
          </div>
        );

        return (
          <div>
            {props.attributes.id
              ? <Button
                  isPrimary
                  isLarge
                  isBusy={props.isFetching}
                  onClick={
                    props.isFetching
                      ? () => {}
                      : () => props.setState ({isOpen: true})
                  }
                >
                  {props.isFetching ? 'Loading recipe...' : 'Edit Recipe'}
                </Button>
              : <Button
                  isDefault
                  onClick={() => {
                    props.setState ({isOpen: true});
                    props.setInitialTitle ();
                  }}
                >
                  Create Recipe
                </Button>}
            {!props.isFetching && props.attributes.id
              ? <div>
                  {renderImageAndTitle ()}
                  {renderIngredients ()}
                  {renderInstructions ()}
                  {renderCategoryAndCuisine ()}
                  {renderSummary ()}
                  {renderPrepAndCookTime ()}
                  {renderNotes ()}
                  {renderServings ()}
                  {renderServingSize ()}
                  {renderCalories ()}
                  {renderCarbs ()}
                  {renderProtein ()}
                  {renderFiber ()}
                  {renderSugar ()}
                  {renderSodium ()}
                  {renderFat ()}
                  {renderSaturatedFat ()}
                  {renderTransFat ()}
                  {renderCholesterol ()}

                </div>
              : ''}

            {props.isOpen
              ? <Modal
                  style={{maxWidth: '780px', height: '100%'}}
                  title={
                    props.attributes.id
                      ? `Edit ${props.title}`
                      : 'Create Recipe'
                  }
                  shouldCloseOnClickOutside={false}
                  shouldCloseOnEsc={false}
                  isDismissable={false}
                  onRequestClose={() => props.setState ({isOpen: false})}
                >
                  {renderImageAndTitle (true)}
                  {renderIngredients (true)}
                  {renderInstructions (true)}
                  {renderCategoryAndCuisine (true)}
                  {renderSummary (true)}
                  {renderPrepAndCookTime (true)}
                  {renderNotes (true)}
                  {renderServings (true)}
                  {renderServingSize (true)}
                  {renderCalories (true)}
                  {renderCarbs (true)}
                  {renderProtein (true)}
                  {renderFiber (true)}
                  {renderSugar (true)}
                  {renderSodium (true)}
                  {renderFat (true)}
                  {renderSaturatedFat (true)}
                  {renderTransFat (true)}
                  {renderCholesterol (true)}

                  <div className="bottom-bar">
                    <Button
                      isDefault
                      onClick={props.onCancel.bind (null, props.setState)}
                    >
                      Cancel
                    </Button>

                    <Button
                      isPrimary
                      isLarge
                      isBusy={props.isSaving}
                      onClick={props.onSave.bind (
                        null,
                        props.setAttributes,
                        props.setState,
                        props.attributes.id
                      )}
                    >
                      {props.attributes.id ? 'Update Recipe' : 'Save Recipe'}
                    </Button>
                  </div>
                </Modal>
              : ''}
          </div>
        );
      })
    )
  ),
  save: function (props) {
    return null;
  },
});

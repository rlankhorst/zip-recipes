const Author = props => {
  return (
    <div className="zrdn-columns zrdn-is-mobile">
      <div className="zrdn-column">
        <div className="zrdn-field">
          <label htmlFor="author" class="zrdn-label">
            Author
          </label>
          <div className="zrdn-control" id="author-container">
            {props.editable
              ? props.authors.length
                  ? <select onChange={props.onChange}>
                      {props.authors.sort ().map (a => (
                        <option selected={a == props.selectedAuthor}>
                          {a}
                        </option>
                      ))}
                    </select>
                  : ''
              : props.selectedAuthor}
          </div>
          {props.editable
            ? <p class="zrdn-help">
                Authors can be added from Zip Recipes settings page.
              </p>
            : ''}
        </div>
      </div>
    </div>
  );
};

export {Author};

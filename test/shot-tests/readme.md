# Readme
- Run `node index.js --desc='example screenshot' --url=https://www.example.com`
- Screenshot will be saved to `./screenshots/{description slug}-screenshot.png`
- Then you can use `compare` command from ImageMagick to create a diff of the images

For example, you can use it to compare different version for the recipe card themes looks as follows:

- Switch to Zip Recipes development version not released yet
- Switch to Canada theme
- Run `node index.js --desc='canada dev' --url=http://localhost:8888/roasted-fennel/`
- Switch to latest stable version of Zip Recipes
- Switch to Canada theme
- Run `node index.js --desc='canada 4.36' --url=http://localhost:8888/roasted-fennel/`
- Run `compare ./screenshots/canada-4.36-screenshot.png ./screenshots/canada-dev-screenshot.png`
    - this command might require a third argument to save the diff
    
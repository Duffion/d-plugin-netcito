# Duffion WP Plugin - Custom Project - Netcito Chargify API Plugin

## Required Plugins

## Installation Instructions

## Development Instructions
Style / script development uses GULP for compiling.
### NPM Requirements
- NPM Ver 14+
- `npm install --global gulp-cli`

*Scripts*
`npm run` -- Runs default `gulp` command
`gulp dev` -- clear | build | watch
`npm run dev` -- Runs `gulp dev`
`gulp clear` -- clear built assets
`gulp sass` -- rebuild just sass
`gulp images` -- rebuild images
`gulp js` -- rebuild javascript
`gulp build` -- production build

### NOTE - IMPORTANT
If you are installing this via GIT repo and not the zip you will `NEED` to make sure to take your terminal to the plugin dir you cloned down then make sure to run `gulp build` or just keep a `gulp dev` active as this will also build and this will watch your `assets/src` directory for anything to compile.